import 'package:flutter/widgets.dart';
import '../tenant/tenant_models.dart';
import 'theme_tokens.dart';

typedef SectionBuilder = Widget Function(
    BuildContext context, Map<String, dynamic> section);

/// Skeleton for a built-in app theme. P5 fills in layouts / chrome styles.
class ThemeDefinition {
  const ThemeDefinition({
    required this.slug,
    this.displayName,
    this.radius = 12,
    this.sectionBuilders = const {},
  });
  final String slug;
  final String? displayName;
  final double radius;

  /// section type -> widget builder (no remote code; P5 populates).
  final Map<String, SectionBuilder> sectionBuilders;
}

class ResolvedTheme {
  const ResolvedTheme(this.definition, this.variantKey, this.tokens);
  final ThemeDefinition definition;
  final String variantKey;
  final ThemeTokens tokens;
}

class ThemeRegistry {
  ThemeRegistry([Map<String, ThemeDefinition>? themes])
      : _themes = themes ?? builtIn;

  static const fallback = ThemeDefinition(slug: 'default');

  static const Map<String, ThemeDefinition> builtIn = {
    'ecommet': ThemeDefinition(slug: 'ecommet', displayName: 'Ecommet', radius: 8),
    'elora': ThemeDefinition(slug: 'elora', displayName: 'Elora', radius: 14),
    'souqify': ThemeDefinition(slug: 'souqify', displayName: 'Souqify', radius: 10),
  };

  final Map<String, ThemeDefinition> _themes;

  /// Unknown slugs (including custom themes) fall back to safe defaults.
  ResolvedTheme resolve(TenantConfig? config) {
    if (config == null) {
      return const ResolvedTheme(fallback, 'default', ThemeTokens.defaults);
    }
    final def = _themes[config.themeSlug] ?? fallback;
    return ResolvedTheme(
      def,
      config.variantKey,
      ThemeTokens.fromColors(config.colors, radius: def.radius),
    );
  }
}
