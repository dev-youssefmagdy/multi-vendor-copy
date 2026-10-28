import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/app.dart';
import 'package:application/features/tenant/tenant_models.dart';
import '../support/harness.dart';

Future<void> pumpApp(WidgetTester t, Harness h) async {
  await t.pumpWidget(UncontrolledProviderScope(
      container: h.container, child: const MobileApp()));
  await t.pumpAndSettle();
}

void main() {
  testWidgets('first launch shows store entry then reaches home', (t) async {
    final h = await Harness.create();
    await pumpApp(t, h);
    expect(find.text('Find your store'), findsOneWidget);
    await t.enterText(find.byKey(const Key('store_input')), 'shop-a.example.com');
    await t.tap(find.byKey(const Key('store_submit')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('home_marker')), findsOneWidget);
    expect(find.text('elora/v2'), findsOneWidget);
    expect(find.text('Shop A'), findsWidgets);
  });

  testWidgets('invalid tenant screen and change store', (t) async {
    final h = await Harness.create();
    await pumpApp(t, h);
    await t.enterText(find.byKey(const Key('store_input')), 'ghost.example.com');
    await t.tap(find.byKey(const Key('store_submit')));
    await t.pumpAndSettle();
    expect(find.text('Store not found'), findsOneWidget);
    await t.tap(find.byKey(const Key('change_store')));
    await t.pumpAndSettle();
    expect(find.text('Find your store'), findsOneWidget);
  });

  testWidgets('suspended tenant screen', (t) async {
    final h = await Harness.create();
    await pumpApp(t, h);
    await t.enterText(find.byKey(const Key('store_input')), 'sus.example.com');
    await t.tap(find.byKey(const Key('store_submit')));
    await t.pumpAndSettle();
    expect(find.text('Store unavailable'), findsOneWidget);
  });

  testWidgets('offline first launch w/ stored tenant shows offline, retry works',
      (t) async {
    final h = await Harness.create(initialPrefs: {
      'app:active_tenant': 'a1',
      'tenant:a1:tenant':
          '{"tenant_id":"a1","domain":"shop-a.example.com","name":"Shop A","status":"active"}',
    });
    h.repo.offline = true;
    await pumpApp(t, h);
    expect(find.text('You are offline'), findsOneWidget);
    h.repo.offline = false;
    await t.tap(find.byKey(const Key('retry')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('home_marker')), findsOneWidget);
  });

  testWidgets('offline with cache opens home from cache', (t) async {
    final h = await Harness.create(initialPrefs: {
      'app:active_tenant': 'a1',
      'tenant:a1:tenant':
          '{"tenant_id":"a1","domain":"shop-a.example.com","name":"Shop A","status":"active"}',
      'tenant:a1:bootstrap': '{"theme":{"slug":"souqify","variant_key":"v1"}}',
    });
    h.repo.offline = true;
    await pumpApp(t, h);
    expect(find.text('souqify/v1 (offline cache)'), findsOneWidget);
  });

  testWidgets('deep link launches into product screen', (t) async {
    final h = await Harness.create(
        initialLink: Uri.parse('https://shop-b.example.com/product/77'));
    await pumpApp(t, h);
    expect(find.text('Product 77'), findsWidgets);
  });

  testWidgets('maintenance and update-required screens', (t) async {
    final h = await Harness.create();
    h.repo.configs['a1'] = const TenantConfig(maintenance: true);
    await pumpApp(t, h);
    await t.enterText(find.byKey(const Key('store_input')), 'shop-a.example.com');
    await t.tap(find.byKey(const Key('store_submit')));
    await t.pumpAndSettle();
    expect(find.text('Under maintenance'), findsOneWidget);
    h.repo.configs['a1'] = const TenantConfig(updateRequired: true);
    await t.tap(find.byKey(const Key('retry')));
    await t.pumpAndSettle();
    expect(find.text('Update required'), findsOneWidget);
  });
}
