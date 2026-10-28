import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:application/design/fonts.dart';
import 'package:application/features/theme/theme_tokens.dart';
import 'package:application/themes/shared/skin.dart';

void main() {
  final pubspec = File('pubspec.yaml').readAsStringSync();
  final declared = RegExp(r'- family: (.+)')
      .allMatches(pubspec)
      .map((m) => m.group(1)!.trim())
      .toSet();

  test('every bundled family is declared in pubspec with existing files', () {
    expect(declared, containsAll(AppFonts.bundled));
    for (final m in RegExp(r'asset: (assets/fonts/.+\.ttf)').allMatches(pubspec)) {
      expect(File(m.group(1)!).existsSync(), isTrue, reason: m.group(1));
    }
  });

  test('every theme x variant resolves a bundled family + Arabic fallback', () {
    final variants = {
      'ecommet': ['v1'],
      'elora': ['v1', 'v2', 'v3', 'v4', 'v5', 'v6'],
      'souqify': ['v1', 'v2', 'v3', 'v4', 'v5', 'v6'],
    };
    variants.forEach((t, vs) {
      for (final v in vs) {
        expect(AppFonts.bundled, contains(AppFonts.forTheme(t, v)));
        expect(ThemeSkin.resolve(t, v, const {}).fontFamily, AppFonts.base);
        final light = ThemeTokens.defaults.toThemeData();
        final dark = ThemeTokens.defaults.toDarkThemeData();
        for (final td in [light, dark]) {
          expect(td.textTheme.bodyMedium!.fontFamily, AppFonts.base);
          expect(td.textTheme.bodyMedium!.fontFamilyFallback, AppFonts.fallback);
        }
      }
    });
    expect(AppFonts.bundled, contains(AppFonts.arabic));
  });

  test('logo font key: known resolves, unknown -> theme default', () {
    expect(AppFonts.resolve("'Cairo', sans-serif"), 'Cairo');
    expect(AppFonts.resolve("'Scheherazade New', serif"), 'Scheherazade New');
    expect(AppFonts.resolve("'Comic Sans', cursive"), AppFonts.base);
    expect(AppFonts.resolve(null), AppFonts.base);
  });

  test('no google_fonts dependency and no runtime font fetch', () {
    expect(pubspec.contains('google_fonts'), isFalse);
    expect(File('pubspec.lock').readAsStringSync().contains('google_fonts'), isFalse);
  });
}
