import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart' show CupertinoPageTransitionsBuilder;
import '../../design/fonts.dart';
import '../../design/tokens.dart';

/// Design tokens derived from bootstrap colors with safe defaults.
class ThemeTokens {
  const ThemeTokens({
    required this.primary,
    required this.secondary,
    required this.background,
    required this.surface,
    required this.onSurface,
    required this.error,
    this.radius = 12,
    this.fontFamily = AppFonts.base,
  });

  final Color primary, secondary, background, surface, onSurface, error;
  final double radius;

  /// Bundled body font (all text inherits it) + Arabic fallback.
  final String fontFamily;

  static const defaults = ThemeTokens(
    primary: Color(0xFF1F6FEB),
    secondary: Color(0xFF6C757D),
    background: Color(0xFFFFFFFF),
    surface: Color(0xFFF6F7F9),
    onSurface: Color(0xFF1A1A1A),
    error: Color(0xFFD32F2F),
  );

  /// Parses `#RGB`, `#RRGGBB`, `#AARRGGBB` (with or without `#`); null if bad.
  static Color? parseColor(String? v) {
    if (v == null) return null;
    var h = v.trim().replaceFirst('#', '');
    if (h.length == 3) h = h.split('').map((c) => '$c$c').join();
    if (h.length == 6) h = 'FF$h';
    if (h.length != 8) return null;
    final n = int.tryParse(h, radix: 16);
    return n == null ? null : Color(n);
  }

  factory ThemeTokens.fromColors(Map<String, String> c, {double? radius, String? fontFamily}) {
    Color pick(List<String> keys, Color fallback) {
      for (final k in keys) {
        final p = parseColor(c[k]);
        if (p != null) return p;
      }
      return fallback;
    }

    const d = defaults;
    return ThemeTokens(
      primary: pick(['primary', 'primary_color'], d.primary),
      secondary: pick(['secondary', 'secondary_color'], d.secondary),
      background: pick(['background', 'background_color'], d.background),
      surface: pick(['surface', 'surface_color'], d.surface),
      onSurface: pick(['text', 'on_surface', 'text_color'], d.onSurface),
      error: pick(['error', 'danger'], d.error),
      radius: radius ?? d.radius,
      fontFamily: fontFamily ?? d.fontFamily,
    );
  }

  /// Dark counterpart: neutral dark surfaces, brand colors lifted to AA.
  ThemeData toDarkThemeData() {
    const bg = Color(0xFF121212);
    final dark = ThemeTokens(
      primary: Contrast.ensureAA(primary, bg),
      secondary: Contrast.ensureAA(secondary, bg),
      background: bg,
      surface: const Color(0xFF1E1E1E),
      onSurface: const Color(0xFFEDEDED),
      error: Contrast.ensureAA(error, bg),
      radius: radius,
      fontFamily: fontFamily,
    );
    return dark.toThemeData(brightness: Brightness.dark);
  }

  ThemeData toThemeData({Brightness brightness = Brightness.light}) {
    final scheme = ColorScheme.fromSeed(
      brightness: brightness,
      seedColor: primary,
      primary: primary,
      secondary: secondary,
      surface: surface,
      onSurface: onSurface,
      error: error,
    );
    return ThemeData(
      useMaterial3: true,
      fontFamily: fontFamily,
      fontFamilyFallback: AppFonts.fallback,
      colorScheme: scheme,
      materialTapTargetSize: MaterialTapTargetSize.padded,
      pageTransitionsTheme: const PageTransitionsTheme(builders: {
        TargetPlatform.android: PredictiveBackPageTransitionsBuilder(),
        TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
      }),
      snackBarTheme: SnackBarThemeData(
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(radius))),
      bottomSheetTheme: const BottomSheetThemeData(
          showDragHandle: true,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20)))),
      scaffoldBackgroundColor: background,
      cardTheme: CardThemeData(
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(radius))),
      filledButtonTheme: FilledButtonThemeData(
          style: FilledButton.styleFrom(
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(radius)))),
      inputDecorationTheme: InputDecorationTheme(
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(radius))),
    );
  }
}
