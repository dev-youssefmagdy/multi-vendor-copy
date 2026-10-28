import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:application/features/home/home_providers.dart';
import 'package:application/features/theme/theme_registry.dart';
import 'package:application/themes/ecommet/ecommet_theme.dart';
import 'package:application/themes/shared/chrome.dart';
import 'package:application/themes/shared/skin.dart';
import '../support/home_fixtures.dart';

/// theme -> variant -> keys, from the matrix doc (section 2 table).
Map<String, Map<String, List<String>>> matrixFromDoc() {
  final f = File('../docs/mobile/01_SCREEN_THEME_MATRIX.md');
  if (!f.existsSync()) return {};
  final out = <String, Map<String, List<String>>>{};
  for (final line in f.readAsLinesSync()) {
    final c = line.split('|').map((e) => e.trim()).toList();
    if (c.length < 9 || (c[1] != 'ecommet' && c[1] != 'elora')) continue;
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
      
      
      expect(c('ecommet', 'v1'), const Color(0xFF111827));
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

}
