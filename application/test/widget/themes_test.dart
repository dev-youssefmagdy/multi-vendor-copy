import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:application/app/app.dart';
import 'package:application/core/storage/key_value_store.dart';
import 'package:application/features/deeplink/deep_link_service.dart';
import 'package:application/features/home/home_providers.dart';
import 'package:application/features/tenant/mock_tenant_repository.dart';
import 'package:application/features/theme/theme_registry.dart';
import 'package:application/themes/ecommet/ecommet_theme.dart';
import 'package:application/themes/elora/elora_theme.dart';
import 'package:application/themes/shared/chrome.dart';
import 'package:application/themes/souqify/souqify_theme.dart';
import 'package:application/themes/shared/skin.dart';
import '../support/harness.dart';
import '../support/home_fixtures.dart';

/// theme -> variant -> keys, from the matrix doc (section 2 table).
Map<String, Map<String, List<String>>> matrixFromDoc() {
  final f = File('../docs/mobile/01_SCREEN_THEME_MATRIX.md');
  if (!f.existsSync()) return {};
  final out = <String, Map<String, List<String>>>{};
  for (final line in f.readAsLinesSync()) {
    final c = line.split('|').map((e) => e.trim()).toList();
    if (c.length < 9 || (c[1] != 'ecommet' && c[1] != 'elora' && c[1] != 'souqify')) continue;
    out.putIfAbsent(c[1], () => {})[c[2]] = [c[8]]; // raw, resolved below
  }
  for (final t in out.values) {
    for (final v in t.keys.toList()) {
      var raw = t[v]!.first;
      final same = RegExp(r'same \d+ as (v\d)').firstMatch(raw);
      if (same != null) raw = t[same.group(1)]!.first;
      t[v] = [raw];
    }
  }
  // second pass for chained "same as" (v5 -> v4 -> own list)
  final res = <String, Map<String, List<String>>>{};
  out.forEach((th, vs) {
    res[th] = {
      for (final e in vs.entries)
        e.key: RegExp(r'[a-z_]+')
            .allMatches(e.value.first.split(' (').first)
            .map((m) => m.group(0)!)
            .toList()
    };
  });
  return res;
}

Widget host(String theme, String variant, Widget child, FakeHomeRepository repo,
    {Map<String, String> colors = const {}}) {
  return ProviderScope(
    overrides: [homeRepositoryProvider.overrideWithValue(repo)],
    child: MaterialApp(
      home: SkinScope(
        skin: ThemeSkin.resolve(theme, variant, colors),
        child: Scaffold(body: SingleChildScrollView(child: child)),
      ),
    ),
  );
}

void main() {
  final registry = ThemeRegistry();
  final variants = <String, Map<String, List<String>>>{
    'ecommet': ecommetVariants,
    'elora': eloraVariants,
    'souqify': souqifyVariants,
  };

  group('coverage', () {
    test('registry has a builder for every ecommet/elora variant+section', () {
      variants.forEach((theme, vs) {
        final def = ThemeRegistry.builtIn[theme]!;
        for (final e in vs.entries) {
          for (final k in e.value) {
            expect(def.sectionBuilders[k], isNotNull, reason: '$theme ${e.key} $k');
          }
        }
      });
      expect(souqifyVariants.keys, ['v1', 'v2', 'v3', 'v4', 'v5', 'v6']);
      expect(eloraVariants.keys, ['v1', 'v2', 'v3', 'v4', 'v5', 'v6']);
    });

    test('matches the matrix doc (rendered section order per variant)', () {
      final doc = matrixFromDoc();
      expect(doc, isNotEmpty);
      variants.forEach((theme, vs) {
        expect(doc[theme]!.keys.toSet(), vs.keys.toSet(), reason: theme);
        for (final v in vs.keys) {
          expect(vs[v], doc[theme]![v], reason: '$theme $v');
          for (final k in doc[theme]![v]!) {
            expect(registry.resolve(null).definition.sectionBuilders.containsKey(k), isFalse);
            expect(ThemeRegistry.builtIn[theme]!.sectionBuilders.containsKey(k), isTrue,
                reason: '$theme $v $k');
          }
        }
      });
    });

    test('variant default colors', () {
      Color c(String t, String v) => ThemeSkin.resolve(t, v, const {}).primary;
      expect(c('elora', 'v2'), const Color(0xFFFF4D00));
      expect(c('elora', 'v5'), const Color(0xFF132092));
      expect(c('ecommet', 'v1'), const Color(0xFF111827));
      expect(c('souqify', 'v2'), const Color(0xFF8B03BD));
      expect(c('souqify', 'v6'), const Color(0xFFFF1A90));
      // bootstrap tokens win over defaults
      expect(ThemeSkin.resolve('elora', 'v2', {'primary': '#C0392B'}).primary,
          const Color(0xFFC0392B));
    });
  });

  group('render every variant with fixture data', () {
    variants.forEach((theme, vs) {
      vs.forEach((variant, keys) {
        testWidgets('$theme $variant', (t) async {
          t.view.physicalSize = const Size(390, 4000);
          t.view.devicePixelRatio = 1;
          addTearDown(t.view.reset);
          final repo = FakeHomeRepository();
          final def = ThemeRegistry.builtIn[theme]!;
          await t.pumpWidget(host(
            theme,
            variant,
            Builder(builder: (context) {
              return Column(children: [
                const HeaderBar(storeName: 'Acme'),
                for (final k in keys)
                  KeyedSubtree(
                    key: ValueKey('s_$k'),
                    child: def.sectionBuilders[k]!(context, {...payloadFor(k), 'key': k}),
                  ),
                const FooterInfo(text: 'About', copyright: 'c'),
                const AppBottomNav(),
              ]);
            }),
            repo,
          ));
          await t.pump(const Duration(milliseconds: 50));
          expect(t.takeException(), isNull);
          for (final k in keys) {
            expect(find.byKey(ValueKey('s_$k')), findsOneWidget);
          }
          // product-bearing sections rendered cards
          expect(find.byKey(const Key('product_p1')), findsWidgets);
          expect(find.text('Acme'), findsOneWidget);
          await t.pumpWidget(const SizedBox()); // dispose timers
        });
      });
    });
  });

  group('widgets', () {
    testWidgets('tabbed products loads a tab', (t) async {
      t.view.physicalSize = const Size(390, 2000);
      t.view.devicePixelRatio = 1;
      addTearDown(t.view.reset);
      final repo = FakeHomeRepository();
      final def = ThemeRegistry.builtIn['elora']!;
      await t.pumpWidget(host('elora', 'v2',
          Builder(builder: (c) => def.sectionBuilders['tabbed_products']!(c, {...payloadFor('tabbed_products'), 'key': 'tabbed_products'})),
          repo));
      await t.pump();
      await t.ensureVisible(find.byKey(const Key('tab_cat-newin')));
      await t.tap(find.byKey(const Key('tab_cat-newin')));
      await t.pump();
      await t.pump();
      expect(find.byKey(const Key('product_p1')), findsOneWidget);
    });

    testWidgets('ecommet best_selling see-more loads more', (t) async {
      t.view.physicalSize = const Size(390, 4000);
      t.view.devicePixelRatio = 1;
      addTearDown(t.view.reset);
      final repo = FakeHomeRepository();
      final def = ThemeRegistry.builtIn['ecommet']!;
      await t.pumpWidget(host('ecommet', 'v1',
          Builder(builder: (c) => def.sectionBuilders['best_selling']!(c, {...payloadFor('best_selling'), 'key': 'best_selling'})),
          repo));
      await t.pump();
      expect(find.byKey(const Key('product_p10')), findsOneWidget);
      expect(find.byKey(const Key('product_p11')), findsNothing);
      await t.tap(find.text('See More'));
      await t.pump();
      await t.pump();
      expect(find.byKey(const Key('product_p11')), findsOneWidget);
    });
  });

  group('HomeScreen', () {
    Future<ProviderContainer> boot(
        WidgetTester t, List<String> sections, Map<String, List<String>> allowed) async {
      SharedPreferences.setMockInitialValues({});
      final prefs = await SharedPreferences.getInstance();
      final repo = MockTenantRepository()
        ..add(shopA,
            code: 'shopa',
            config: bootstrapFor('elora', 'v2', sections, allowed: allowed));
      final c = ProviderContainer(overrides: [
        ...Harness.overrides(prefs, InMemorySecureStore(), repo, FakeDeepLinkSource(null)),
        homeRepositoryProvider.overrideWithValue(FakeHomeRepository()),
      ]);
      addTearDown(c.dispose);
      t.view.physicalSize = const Size(390, 6000);
      t.view.devicePixelRatio = 1;
      addTearDown(t.view.reset);
      await t.pumpWidget(UncontrolledProviderScope(container: c, child: const MobileApp()));
      await t.pumpAndSettle();
      await t.enterText(find.byKey(const Key('store_input')), 'shop-a.example.com');
      await t.tap(find.byKey(const Key('store_submit')));
      await t.pump();
      await t.pump(const Duration(milliseconds: 100));
      await t.pump(const Duration(milliseconds: 100));
      return c;
    }

    testWidgets('renders sections in server order, ignores unknown/disallowed', (t) async {
      await boot(t, [
        'trending_now',
        'hero_banner',
        'totally_unknown',
        'new_arrivals',
        'hot_deals', // no elora builder
        'best_seller', // present in sections but not allowed for v2 below
      ], {
        'v2': ['trending_now', 'hero_banner', 'new_arrivals', 'hot_deals']
      });
      expect(find.byKey(const Key('home_marker')), findsOneWidget);
      expect(find.text('elora/v2'), findsOneWidget);
      final a = t.getTopLeft(find.byKey(const ValueKey('section_trending_now'))).dy;
      final b = t.getTopLeft(find.byKey(const ValueKey('section_hero_banner'))).dy;
      final c = t.getTopLeft(find.byKey(const ValueKey('section_new_arrivals'))).dy;
      expect(a < b && b < c, isTrue);
      expect(find.byKey(const ValueKey('section_totally_unknown')), findsNothing);
      expect(find.byKey(const ValueKey('section_hot_deals')), findsNothing);
      expect(find.byKey(const ValueKey('section_best_seller')), findsNothing);
      expect(find.text('Trending Now'), findsOneWidget);
      await t.pumpWidget(const SizedBox());
    });
  });
}
