import 'package:flutter/material.dart';
import 'package:application/app/router.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/themes/shared/chrome.dart';
import '../support/mocks.dart';
import 'cart_checkout_test.dart' show addFromProduct;

const _authedRoutes = [
  '/checkout', '/orders', '/returns', '/favorites', '/account/addresses',
  '/account/profile', '/account/preferences', '/orders/ord-2', '/orders/ord-2/tracking',
  '/orders/ord-2/return', '/returns/1',
];

void main() {
  test('bottom nav keys parse from settings with safe fallback', () {
    expect(AppBottomNav.parseKeys(['home', 'offers', 'cart', 'bogus']), ['home', 'offers', 'cart']);
    expect(AppBottomNav.parseKeys(null), ['home', 'categories', 'cart', 'favorites', 'account']);
  });

  for (final w in [320.0, 375.0, 430.0]) {
    for (final scale in [1.0, 2.0]) {
      testWidgets('home/listing/product/cart have no overflow at ${w.toInt()}dp, text x$scale', (t) async {
        t.view.physicalSize = Size(w, 800);
        t.view.devicePixelRatio = 1;
        t.platformDispatcher.textScaleFactorTestValue = scale;
        addTearDown(() {
          t.view.reset();
          t.platformDispatcher.clearTextScaleFactorTestValue();
        });
        final s = await Shop.create();
        await s.launch(t);
        expect(t.takeException(), isNull, reason: 'home');
        for (final r in const [
          '/category/shoes', '/category/all', '/product/p3', '/cart', '/account', '/search',
          '/login', '/register', '/forgot-password', '/list/offers', '/page/about',
        ]) {
          await s.push(t, r);
          expect(t.takeException(), isNull, reason: r);
          s.c.read(routerProvider).pop();
          await t.pumpAndSettle();
        }
      });
    }
  }

  testWidgets('listing: list/grid toggle and sticky buy bar', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/category/shoes');
    await t.tap(find.byKey(const Key('toggle_layout')));
    await t.pumpAndSettle();
    expect(find.byType(Scrollable), findsWidgets);
    await s.push(t, '/product/p3');
    expect(find.byKey(const Key('add_to_cart')), findsOneWidget);
    expect(find.byKey(const Key('buy_now')), findsOneWidget);
  });

  for (final w in [320.0, 430.0]) {
    for (final dark in [false, true]) {
      testWidgets('signed-in screens: no overflow at ${w.toInt()}dp x2.0 text dark=$dark', (t) async {
        t.view.physicalSize = Size(w, 800);
        t.view.devicePixelRatio = 1;
        t.platformDispatcher.textScaleFactorTestValue = 2.0;
        if (dark) t.platformDispatcher.platformBrightnessTestValue = Brightness.dark;
        addTearDown(() {
          t.view.reset();
          t.platformDispatcher.clearTextScaleFactorTestValue();
          t.platformDispatcher.clearPlatformBrightnessTestValue();
        });
        final s = await Shop.create(signedIn: true);
        await s.launch(t);
        await addFromProduct(t, s, 'p3');
        for (final r in _authedRoutes) {
          await s.push(t, r);
          expect(t.takeException(), isNull, reason: r);
          s.c.read(routerProvider).pop();
          await t.pumpAndSettle();
        }
      });
    }
  }
}
