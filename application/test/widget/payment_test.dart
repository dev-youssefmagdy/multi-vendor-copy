import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/features/checkout/payment_screens.dart';
import 'package:application/features/shop/commerce_models.dart';
import '../support/mocks.dart';

PaymentStatus st(bool paid, String s) => PaymentStatus('ord-1', paid, s);

void main() {
  Future<Shop> boot(WidgetTester t, List<PaymentStatus> seq) async {
    final s = await Shop.create(signedIn: true);
    s.checkout.statuses = seq;
    await s.launch(t);
    return s;
  }

  testWidgets('pending then paid: only the server status shows success', (t) async {
    final s = await boot(t, [st(false, 'pending'), st(true, 'processing')]);
    await s.push(t, '/payment/result?order=ord-1&status=success');
    // deep-link hint says success but the first server answer is "not paid":
    // success only appears after the 2nd poll returned paid.
    expect(find.byKey(const Key('payment_paid')), findsOneWidget);
    expect(s.checkout.statusCalls, 2);
  });

  testWidgets('never paid => stays pending (no fake success), can re-check', (t) async {
    final s = await boot(t, [st(false, 'pending')]);
    await s.push(t, '/payment/result?order=ord-1&status=success');
    await t.pumpAndSettle(const Duration(milliseconds: 50));
    expect(find.byKey(const Key('payment_pending')), findsOneWidget);
    expect(find.byKey(const Key('payment_paid')), findsNothing);
    expect(s.checkout.statusCalls, 3, reason: 'maxAttempts');
    s.checkout.statuses = [st(true, 'processing')];
    await t.tap(find.byKey(const Key('payment_recheck')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('payment_paid')), findsOneWidget);
  });

  testWidgets('cancelled redirect or closed order => failed with retry', (t) async {
    final s = await boot(t, [st(false, 'pending')]);
    await s.push(t, '/payment/result?order=ord-1&status=cancelled');
    await t.pumpAndSettle();
    expect(find.byKey(const Key('payment_failed')), findsOneWidget);
    expect(find.byKey(const Key('payment_retry')), findsOneWidget);
    expect(find.byKey(const Key('payment_paid')), findsNothing);
  });

  testWidgets('server says cancelled => failed even without a hint', (t) async {
    final s = await boot(t, [st(false, 'cancelled')]);
    await s.push(t, '/payment/result?order=ord-1');
    await t.pumpAndSettle();
    expect(find.byKey(const Key('payment_failed')), findsOneWidget);
  });

  testWidgets('isPaymentReturnLink only matches the app scheme', (t) async {
    expect(isPaymentReturnLink('storeapp://payment/result?status=success'), isTrue);
    expect(isPaymentReturnLink('https://shop.example.com/payment/result'), isFalse);
    expect(isPaymentReturnLink('https://pay.example.com/checkout'), isFalse);
    expect(isPaymentReturnLink('storeapp://orders/1'), isFalse);
  });
}
