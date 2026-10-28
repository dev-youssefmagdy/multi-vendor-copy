import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/providers.dart';
import 'package:application/features/shop/commerce_providers.dart';
import '../support/mocks.dart';

void main() {
  testWidgets('preferences: picking Arabic switches to RTL + localized chrome, currency symbol follows', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.push(t, '/account/preferences');
    await t.tap(find.byKey(const Key('currency_EUR')));
    await t.pumpAndSettle();
    expect(s.c.read(apiSessionProvider).currency, 'EUR');
    await t.tap(find.byKey(const Key('country_2')));
    await t.pumpAndSettle();
    expect(s.c.read(apiSessionProvider).countryId, 2);
    await t.tap(find.byKey(const Key('locale_ar')));
    await t.pumpAndSettle();
    expect(s.c.read(apiSessionProvider).locale, 'ar');
    final dir = Directionality.of(t.element(find.byKey(const Key('locale_ar'))));
    expect(dir, TextDirection.rtl);
    expect(find.text('التفضيلات'), findsOneWidget);
    expect(s.c.read(prefsProvider).locale, 'ar');
  });

  testWidgets('preferences are stored per tenant', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.c.read(prefsProvider.notifier).set(currency: 'EUR');
    final keys = s.c.read(sharedPreferencesProvider).getKeys().where((k) => k.contains('pref_'));
    expect(keys, everyElement(startsWith('tenant:a1:')));
  });

  testWidgets('account hub lists CMS pages; page opens', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await s.go(t, '/account');
    await t.tap(find.byKey(const Key('page_about')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('cms_body')), findsOneWidget);
  });

  testWidgets('bottom nav tabs navigate (Categories, Cart, Account)', (t) async {
    final s = await Shop.create();
    await s.launch(t);
    await t.tap(find.byKey(const Key('nav_categories')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('cat_shoes')), findsOneWidget);
    await t.tap(find.byKey(const Key('nav_cart')));
    await t.pumpAndSettle();
    expect(find.text('Your cart is empty'), findsOneWidget);
    await t.tap(find.byKey(const Key('nav_account')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('account_sign_in')), findsOneWidget);
  });
}
