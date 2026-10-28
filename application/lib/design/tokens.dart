import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Mobile design tokens (4/8 grid). Variant identity (colors, radii, fonts)
/// stays in ThemeSkin; this file holds the shared mobile-native scale.
class Space {
  const Space._();
  static const double x1 = 4, x2 = 8, x3 = 12, x4 = 16, x5 = 20, x6 = 24, x8 = 32, x10 = 40, x12 = 48;
  static const double gutter = x4;
}

/// Minimum interactive size (Material/Apple guidance: 48dp).
const double kMinTouch = 48;

class TypeScale {
  const TypeScale._();
  static const double caption = 12, body = 14, bodyL = 16, title = 18, headline = 22, display = 28;
  static const FontWeight regular = FontWeight.w400, medium = FontWeight.w600, bold = FontWeight.w800;
}

class Elevation {
  const Elevation._();
  static const double none = 0, card = 1, bar = 3, sheet = 8;
  static List<BoxShadow> shadow(double level) => level <= 0
      ? const []
      : [BoxShadow(color: Colors.black.withValues(alpha: 0.06 + level * 0.01), blurRadius: level * 3, offset: Offset(0, level))];
}

class Motion {
  const Motion._();
  static const fast = Duration(milliseconds: 120);
  static const base = Duration(milliseconds: 220);
  static const slow = Duration(milliseconds: 360);
  static const fade = Duration(milliseconds: 260);
  static const curve = Curves.easeOutCubic;
}

class Haptics {
  const Haptics._();
  static void tap() => _safe(HapticFeedback.selectionClick);
  static void success() => _safe(HapticFeedback.lightImpact);
  static void warn() => _safe(HapticFeedback.mediumImpact);
  static void _safe(Future<void> Function() f) {
    try {
      f();
    } catch (_) {}
  }
}

/// Breakpoints for responsive grids.
class Breakpoints {
  const Breakpoints._();
  static int gridColumns(double width, {int phone = 2}) {
    if (width >= 1000) return 5;
    if (width >= 720) return 4;
    if (width >= 540) return 3;
    return phone;
  }
}

/// Contrast helpers (WCAG AA = 4.5:1 for normal text).
class Contrast {
  const Contrast._();
  static double ratio(Color a, Color b) {
    final la = a.computeLuminance(), lb = b.computeLuminance();
    final hi = la > lb ? la : lb, lo = la > lb ? lb : la;
    return (hi + 0.05) / (lo + 0.05);
  }

  /// Black/white, whichever reads better on [bg].
  static Color on(Color bg) => bg.computeLuminance() > 0.5 ? const Color(0xFF111111) : Colors.white;

  /// Returns [fg] if it meets AA on [bg], otherwise a darkened/lightened variant.
  static Color ensureAA(Color fg, Color bg, {double min = 4.5}) {
    if (ratio(fg, bg) >= min) return fg;
    final dark = bg.computeLuminance() > 0.5;
    var c = fg;
    for (var i = 0; i < 20 && ratio(c, bg) < min; i++) {
      c = Color.lerp(c, dark ? Colors.black : Colors.white, 0.15)!;
    }
    return c;
  }
}
