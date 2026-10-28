import 'package:flutter/material.dart';

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
  });

  final Color primary, secondary, background, surface, onSurface, error;
  final double radius;

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

  factory ThemeTokens.fromColors(Map<String, String> c, {double? radius}) {
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
    );
  }

  ThemeData toThemeData() {
    final scheme = ColorScheme.fromSeed(
      seedColor: primary,
      primary: primary,
      secondary: secondary,
      surface: surface,
      onSurface: onSurface,
      error: error,
    );
    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
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
