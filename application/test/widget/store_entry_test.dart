import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/app/app.dart';
import 'package:application/features/startup/store_entry_screen.dart';
import 'package:application/app/providers.dart';
import 'package:application/features/startup/screens.dart';
import 'package:application/features/startup/tenant_controller.dart';
import 'package:application/features/startup/tenant_state.dart';
import '../support/harness.dart';

Future<void> pumpApp(WidgetTester t, Harness h) async {
  await t.pumpWidget(UncontrolledProviderScope(
      container: h.container, child: const MobileApp()));
  await t.pumpAndSettle();
}

const _stored = {
  'app:active_tenant': 'a1',
  'tenant:a1:tenant':
      '{"tenant_id":"a1","domain":"shop-a.example.com","name":"Shop A","status":"active"}',
};

class _BootingShopA extends TenantController {
  @override
  TenantState build() =>
      const TenantState(phase: StartupPhase.booting, brandingTenant: shopA);
}

void main() {
  test('normalisation helpers', () {
    expect(previewStoreInput('https://Shop-A.example.com/products/1'), 'shop-a.example.com');
    expect(previewStoreInput('shop-a.example.com'), 'shop-a.example.com');
    expect(previewStoreInput('shopa'), 'shopa');
    expect(previewStoreInput('!!'), isNull);
    expect(clipboardStoreCandidate('hello'), isNull);
    expect(clipboardStoreCandidate('buy milk please.'), isNull);
    expect(clipboardStoreCandidate('https://shop-a.example.com/p/1'), 'shop-a.example.com');
  });

  testWidgets('empty storage shows entry screen with live preview', (t) async {
    final h = await Harness.create();
    await pumpApp(t, h);
    expect(find.byKey(const Key('store_input')), findsOneWidget);
    await t.enterText(find.byKey(const Key('store_input')),
        'https://Shop-A.example.com/products/1');
    await t.pump();
    expect(find.text('Opens: shop-a.example.com'), findsOneWidget);
  });

  testWidgets('deep link arriving while entry is shown opens the store', (t) async {
    final h = await Harness.create();
    await pumpApp(t, h);
    expect(find.byKey(const Key('store_input')), findsOneWidget);
    h.links.emit(Uri.parse('https://shop-b.example.com/product/p9'));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('store_input')), findsNothing);
    expect(find.byKey(const Key('home_marker')), findsNothing);
  });

  testWidgets('invalid input shows an error and stays on entry', (t) async {
    final h = await Harness.create();
    await pumpApp(t, h);
    await t.enterText(find.byKey(const Key('store_input')), '!!');
    await t.tap(find.byKey(const Key('store_submit')));
    await t.pumpAndSettle();
    expect(find.text('Invalid store address'), findsOneWidget);
    expect(find.byKey(const Key('store_input')), findsOneWidget);
  });

  testWidgets('clipboard store URL is suggested and only used on confirm', (t) async {
    final h = await Harness.create(extraOverrides: [
      clipboardReaderProvider
          .overrideWithValue(() async => 'https://shop-a.example.com/products/1')
    ]);
    await pumpApp(t, h);
    expect(find.byKey(const Key('clipboard_suggestion')), findsOneWidget);
    expect(find.byKey(const Key('home_marker')), findsNothing);
    await t.tap(find.byKey(const Key('clipboard_use')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('home_marker')), findsOneWidget);
  });

  testWidgets('non-store clipboard is not suggested; dismiss hides', (t) async {
    final h = await Harness.create(
        extraOverrides: [clipboardReaderProvider.overrideWithValue(() async => 'hello')]);
    await pumpApp(t, h);
    expect(find.byKey(const Key('clipboard_suggestion')), findsNothing);
  });

  testWidgets('dismissing clipboard suggestion hides it', (t) async {
    final h2 = await Harness.create(extraOverrides: [
      clipboardReaderProvider.overrideWithValue(() async => 'shop-a.example.com')
    ]);
    await pumpApp(t, h2);
    await t.tap(find.byKey(const Key('clipboard_dismiss')));
    await t.pump();
    expect(find.byKey(const Key('clipboard_suggestion')), findsNothing);
  });

  testWidgets('paste button fills field; help sheet opens', (t) async {
    final h = await Harness.create(extraOverrides: [
      clipboardReaderProvider.overrideWithValue(() async => 'hello')
    ]);
    await pumpApp(t, h);
    await t.tap(find.byKey(const Key('store_paste')));
    await t.pump();
    expect(find.text('Store code: hello'), findsOneWidget);
    await t.tap(find.byKey(const Key('store_help')));
    await t.pumpAndSettle();
    expect(find.byKey(const Key('store_help_sheet')), findsOneWidget);
  });

  testWidgets('recent stores listed with fallback avatar', (t) async {
    final h = await Harness.create(initialPrefs: {
      'app:recent_stores': [
        '{"tenant_id":"a1","domain":"shop-a.example.com","name":"Shop A","status":"active","logo":"https://x.invalid/l.png"}'
      ],
    });
    await pumpApp(t, h);
    expect(find.byKey(const Key('recent_a1')), findsOneWidget);
    expect(find.text('S'), findsOneWidget);
  });

  testWidgets('splash shows cached store name and primary colour', (t) async {
    final h = await Harness.create(initialPrefs: {
      ..._stored,
      'tenant:a1:bootstrap':
          '{"tenant":{"name":"Shop A"},"branding":{"app_name":"Shop A App"},"theme":{"slug":"elora","variant_key":"v2","colors":{"primary":"#ff0000"}}}',
    }, extraOverrides: [
      tenantControllerProvider.overrideWith(_BootingShopA.new)
    ]);
    await t.pumpWidget(UncontrolledProviderScope(
        container: h.container,
        child: const MaterialApp(home: SplashScreen())));
    await t.pump(const Duration(milliseconds: 400));
    expect(find.byKey(const Key('splash_name')), findsOneWidget);
    expect(find.text('Shop A App'), findsOneWidget);
  });

  testWidgets('first launch splash is neutral', (t) async {
    final h = await Harness.create();
    await t.pumpWidget(UncontrolledProviderScope(
        container: h.container, child: const MobileApp()));
    expect(find.byKey(const Key('splash_neutral_mark')), findsOneWidget);
    await t.pumpAndSettle();
  });
}
