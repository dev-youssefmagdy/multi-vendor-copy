import 'package:flutter/material.dart';
import 'package:application/app/router.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/themes/shared/chrome.dart';
import '../support/mocks.dart';

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
        for (final r in ['/category/shoes', '/product/p3', '/cart', '/account']) {
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
}
