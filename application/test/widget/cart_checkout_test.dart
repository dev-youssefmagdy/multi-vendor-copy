import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/providers.dart';
import 'package:application/core/errors/app_exception.dart';
import 'package:application/features/shop/commerce_models.dart';
import 'package:application/features/shop/commerce_providers.dart';
import '../support/mocks.dart';

Future<void> addFromProduct(WidgetTester t, Shop s, String slug) async {
  await s.push(t, '/product/$slug');
  await t.tap(find.byKey(const Key('add_to_cart')));
  await t.pumpAndSettle();
  await t.pump(const Duration(seconds: 5)); // let the "Added to cart" snackbar go
  await t.pumpAndSettle();
}

Future<void> toReview(WidgetTester t) async {
  for (var i = 0; i < 2; i++) {
    await t.tap(find.byKey(const Key('step_next')));
    await t.pumpAndSettle();
  }
}

void main() {
  testWidgets('guest: browse -> product -> cart (device cart), checkout asks to sign in', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await s.go(t, '/cart');
    expect(find.text('Product 3'), findsOneWidget);
    expect(find.byKey(const Key('grand_total')), findsNothing, reason: 'no final total for guests');
    expect(s.cart.lines, isEmpty, reason: 'guest cart never hits the server');
    await t.tap(find.byKey(const Key('inc_p3:101')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('qty_p3:101')), findsOneWidget);
    expect(t.widget<Text>(find.byKey(const Key('qty_p3:101'))).data, '2');
    await t.tap(find.byKey(const Key('go_checkout')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('login_submit')), findsOneWidget);
  });

  testWidgets('guest cart is merged to the server cart on login', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await addFromProduct(t, s, 'p3');
    await s.push(t, '/login?from=${Uri.encodeComponent('/cart')}');
    await t.enterText(find.byKey(const Key('login_email')), 'sam@example.com');
    await t.enterText(find.byKey(const Key('login_password')), 'secret123');
    await t.tap(find.byKey(const Key('login_submit')));
    await t.pumpAndSettle();
    expect(s.cart.merges, hasLength(1));
    expect(s.cart.merges.single.single['qty'], 2);
    expect(s.cart.merges.single.single.containsKey('price'), isFalse, reason: 'never send prices');
    expect(find.byKey(const Key('grand_total')), findsOneWidget);
    expect(s.c.read(guestCartStoreProvider).read(), isEmpty);
  });

  testWidgets('signed in: cart -> checkout quote -> COD order placed', (t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    expect(s.cart.lines, isNotEmpty);
    await s.go(t, '/cart');
    expect(find.byKey(const Key('grand_total')), findsOneWidget);
    await t.tap(find.byKey(const Key('go_checkout')));
    await t.pumpAndSettle();
    // quote requested for the default address
    expect(s.checkout.quoteCalls.last, {'address_id': 3, 'country_id': 1});
    expect(find.byKey(const Key('step_next')), findsOneWidget);
    await toReview(t);
    await t.tap(find.byKey(const Key('place_order')));
    await t.pumpAndSettle();
    final body = s.checkout.placedBodies.single;
    expect(body['payment_method'], 'cod');
    expect(body['expected_total'], 16.0);
    expect((body['shipping'] as Map)['address_id'], 3);
    expect(s.checkout.idemKeys.single, isNotEmpty);
    expect(find.byKey(const Key('order_placed_banner')), findsOneWidget);
  });

  testWidgets('price_changed: shows new total, creates nothing, second confirm uses new expected_total', (t) async {
    final s = await Shop.create(signedIn: true);
    s.checkout.priceChangeTo = 25;
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await s.go(t, '/checkout');
    final firstKey = s.checkout.idemKeys;
    await toReview(t);
    await t.tap(find.byKey(const Key('place_order')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('price_changed_dialog')), findsOneWidget);
    expect(find.textContaining('25.00'), findsWidgets);
    expect(find.byKey(const Key('order_placed_banner')), findsNothing);
    await t.tap(find.byKey(const Key('price_changed_ok')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('place_order')), findsOneWidget);
    await t.tap(find.byKey(const Key('place_order')));
    await t.pumpAndSettle();
    expect(s.checkout.placedBodies.map((b) => b['expected_total']), [16.0, 25]);
    expect(s.checkout.idemKeys.toSet(), hasLength(2), reason: 'new quote => new idempotency key');
    expect(firstKey, isNotEmpty);
    expect(find.byKey(const Key('order_placed_banner')), findsOneWidget);
  });

  testWidgets('network failure keeps the idempotency key for a safe retry', (t) async {
    final s = await Shop.create(signedIn: true);
    s.checkout.placeError = const NetworkException();
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await s.go(t, '/checkout');
    await toReview(t);
    await t.tap(find.byKey(const Key('place_order')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('checkout_error')), findsOneWidget);
    s.checkout.placeError = null;
    await t.tap(find.byKey(const Key('place_order')));
    await t.pumpAndSettle();
    expect(s.checkout.idemKeys.toSet(), hasLength(1));
    expect(find.byKey(const Key('order_placed_banner')), findsOneWidget);
  });

  testWidgets('shipping unavailable blocks placing the order', (t) async {
    final s = await Shop.create(signedIn: true);
    s.checkout.shippingAvailable = false;
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await s.go(t, '/checkout');
    await toReview(t);
    expect(find.byKey(const Key('shipping_unavailable')), findsOneWidget);
    final btn = t.widget<FilledButton>(find.descendant(
        of: find.byKey(const Key('place_order')), matching: find.byType(FilledButton)));
    expect(btn.onPressed, isNull);
    expect(s.checkout.placeCalls, 0);
  });

  testWidgets('coupon apply / invalid coupon error / remove', (t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await s.go(t, '/cart');
    await t.tap(find.byKey(const Key('coupon_open')));
    await t.pumpAndSettle();
    await t.enterText(find.byKey(const Key('coupon_field')), 'BAD');
    await t.tap(find.byKey(const Key('coupon_apply')));
    await t.pumpAndSettle();
    expect(find.textContaining('invalid or has expired'), findsOneWidget);
    await t.pump(const Duration(seconds: 5));
    await t.enterText(find.byKey(const Key('coupon_field')), 'SAVE');
    await t.tap(find.byKey(const Key('coupon_apply')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('coupon_applied')), findsOneWidget);
    await t.tap(find.byKey(const Key('coupon_remove')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('coupon_open')), findsOneWidget);
  });

  testWidgets('empty cart and cart load error states', (t) async {
    final s = await Shop.create(signedIn: true);
    s.cart.failOnce = const NetworkException();
    await s.launch(t);
    await s.go(t, '/cart');
    expect(find.byKey(const Key('offline_view')), findsOneWidget);
    await t.tap(find.byKey(const Key('retry')));
    await t.pumpAndSettle();
    expect(find.text('Your cart is empty'), findsOneWidget);
  });

  testWidgets('tenant switch resets cart + checkout state (sessionEpoch)', (t) async {
    final s = await Shop.create(signedIn: true);
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await s.go(t, '/checkout');
    expect(s.c.read(checkoutProvider).quote, isNotNull);
    expect(s.c.read(cartProvider).value!.items, isNotEmpty);
    // the other store has its own (empty) server cart
    s.cart.lines.clear();
    s.c.read(sessionEpochProvider.notifier).bump();
    await t.pumpAndSettle();
    expect(s.c.read(checkoutProvider).placed, isNull);
    expect(s.c.read(checkoutProvider).quote!.items, isEmpty, reason: 'fresh quote of the new store, not the old one');
    expect((await s.c.read(cartProvider.future)).items, isEmpty);
  });

  testWidgets('guest cart is namespaced per tenant', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    final keys = s.c.read(sharedPreferencesProvider).getKeys().where((k) => k.contains('guest_cart'));
    expect(keys, ['tenant:a1:guest_cart']);
  });

  testWidgets('payment WebView: opens the hosted URL; gateway return link only navigates to the status check', (t) async {
    final s = await Shop.create(signedIn: true);
    s.checkout.requiresPayment = true;
    s.checkout.statuses = [const PaymentStatus('ord-1', false, 'pending')];
    await s.launch(t);
    await addFromProduct(t, s, 'p3');
    await s.go(t, '/checkout');
    await t.tap(find.byKey(const Key('step_next')));
    await t.pumpAndSettle();
    await t.tap(find.byKey(const Key('gateway_card')));
    await t.pumpAndSettle();
    await t.tap(find.byKey(const Key('gateway_stripe')));
    await t.pumpAndSettle();
    await t.tap(find.byKey(const Key('step_next')));
    await t.pumpAndSettle();
    await t.tap(find.byKey(const Key('place_order')));
    await t.pumpAndSettle();
    expect(s.checkout.placedBodies.single['payment_method'], 'stripe');
    expect(s.checkout.placedBodies.single['payment_gateway_id'], 7);
    expect(find.byKey(const Key('web_surface')), findsOneWidget);
    expect(s.webCalls, ['https://pay.example.com/hosted']);
    // simulated redirect with status=success in the URL must NOT show success
    await t.tap(find.byKey(const Key('web_return')));
    await t.pump();
    await t.pump(const Duration(milliseconds: 50));
    expect(find.byKey(const Key('payment_paid')), findsNothing);
    await t.pumpAndSettle(const Duration(milliseconds: 100));
    expect(find.byKey(const Key('payment_pending')), findsOneWidget);
  });
}
