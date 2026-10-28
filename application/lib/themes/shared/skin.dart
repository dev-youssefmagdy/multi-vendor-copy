import 'package:flutter/material.dart';
import '../../features/theme/theme_tokens.dart';

enum CardStyle { base, flat, shadow, compact, square, overlay }

enum HeaderStyle { ecommet, eloraBase, eloraV2, eloraV3, eloraV4, eloraV5, eloraV6 }

typedef OpenRoute = void Function(BuildContext context, String route);

/// Resolved visual parameters for one theme x variant. Colors come from the
/// bootstrap tokens and fall back to the variant defaults read from the blade
/// / CSS (`HomeVariant.colors`, `resources/css/<theme>-<variant>.css`).
class ThemeSkin {
  const ThemeSkin({
    required this.themeSlug,
    required this.variantKey,
    required this.primary,
    required this.secondary,
    required this.background,
    required this.text,
    required this.subtitle,
    required this.success,
    required this.error,
    this.radius = 8,
    this.cardRadius = 8,
    this.heroHeight = 200,
    this.cardImageHeight = 160,
    this.cardWidth = 160,
    this.cardStyle = CardStyle.base,
    this.headerStyle = HeaderStyle.eloraBase,
    this.imageFit = BoxFit.cover,
    this.heroTextAlign = CrossAxisAlignment.start,
    this.heroDotsBelow = false,
    this.pillButtons = true,
    this.fontFamily,
  });

  final String themeSlug, variantKey;
  final Color primary, secondary, background, text, subtitle, success, error;
  final double radius, cardRadius, heroHeight, cardImageHeight, cardWidth;
  final CardStyle cardStyle;
  final HeaderStyle headerStyle;
  final BoxFit imageFit;
  final CrossAxisAlignment heroTextAlign;
  final bool heroDotsBelow, pillButtons;
  final String? fontFamily;

  Color get border => const Color(0xFFE7E7E7);
  Color get muted => const Color(0xFFF5F5F5);

  /// Variant defaults (blade/CSS) overlaid with bootstrap colors.
  factory ThemeSkin.resolve(
      String themeSlug, String variantKey, Map<String, String> colors) {
    final base = _defaults(themeSlug, variantKey);
    Color pick(List<String> keys, Color fb) {
      for (final k in keys) {
        final c = ThemeTokens.parseColor(colors[k]);
        if (c != null) return c;
      }
      return fb;
    }

    return base.copyWith(
      primary: pick(['primary', 'color_primary'], base.primary),
      secondary: pick(['secondary', 'color_secondary'], base.secondary),
      background: pick(['bg_main', 'background'], base.background),
      text: pick(['text_primary', 'text', 'black'], base.text),
      subtitle: pick(['text_subtitle', 'subtitle'], base.subtitle),
      success: pick(['success'], base.success),
      error: pick(['error', 'danger'], base.error),
    );
  }

  ThemeSkin copyWith(
          {Color? primary,
          Color? secondary,
          Color? background,
          Color? text,
          Color? subtitle,
          Color? success,
          Color? error}) =>
      ThemeSkin(
        themeSlug: themeSlug,
        variantKey: variantKey,
        primary: primary ?? this.primary,
        secondary: secondary ?? this.secondary,
        background: background ?? this.background,
        text: text ?? this.text,
        subtitle: subtitle ?? this.subtitle,
        success: success ?? this.success,
        error: error ?? this.error,
        radius: radius,
        cardRadius: cardRadius,
        heroHeight: heroHeight,
        cardImageHeight: cardImageHeight,
        cardWidth: cardWidth,
        cardStyle: cardStyle,
        headerStyle: headerStyle,
        imageFit: imageFit,
        heroTextAlign: heroTextAlign,
        heroDotsBelow: heroDotsBelow,
        pillButtons: pillButtons,
        fontFamily: fontFamily,
      );

  static const _white = Color(0xFFFFFFFF);
  static const _ok = Color(0xFF2AAF2F);
  static const _err = Color(0xFFDE1709);
  static const _paletteA = [
    Color(0xFFFF4D00), // primary
    Color(0xFFFF522C), // secondary
    Color(0xFFFDFDFD), // bg-main
    Color(0xFF121212), // text-primary
    Color(0xFFADADAD), // text-subtitle
  ];

  static ThemeSkin _defaults(String theme, String v) {
    if (theme == 'ecommet') {
      return const ThemeSkin(
        themeSlug: 'ecommet',
        variantKey: 'v1',
        primary: Color(0xFF111827),
        secondary: Color(0xFF4B5563),
        background: _white,
        text: Color(0xFF232323),
        subtitle: Color(0xFF7F7F7F),
        success: Color(0xFF2AAF2E),
        error: _err,
        radius: 8,
        cardRadius: 12,
        heroHeight: 279,
        cardImageHeight: 180,
        cardStyle: CardStyle.flat,
        headerStyle: HeaderStyle.ecommet,
        imageFit: BoxFit.contain,
      );
    }
    // elora
    ThemeSkin a({
      required HeaderStyle header,
      CardStyle card = CardStyle.base,
      double cardRadius = 8,
      double hero = 200,
      double imgH = 160,
      double cardW = 160,
      BoxFit fit = BoxFit.cover,
      bool dots = false,
      CrossAxisAlignment align = CrossAxisAlignment.start,
      List<Color>? pal,
    }) {
      final p = pal ?? _paletteA;
      return ThemeSkin(
        themeSlug: 'elora',
        variantKey: v,
        primary: p[0],
        secondary: p[1],
        background: p[2],
        text: p[3],
        subtitle: p[4],
        success: _ok,
        error: _err,
        radius: 8,
        cardRadius: cardRadius,
        heroHeight: hero,
        cardImageHeight: imgH,
        cardWidth: cardW,
        cardStyle: card,
        headerStyle: header,
        imageFit: fit,
        heroDotsBelow: dots,
        heroTextAlign: align,
      );
    }

    switch (v) {
      case 'v2':
        return a(header: HeaderStyle.eloraV2, card: CardStyle.flat, hero: 238, imgH: 183);
      case 'v3':
        return a(header: HeaderStyle.eloraV3, card: CardStyle.shadow, hero: 200, imgH: 170);
      case 'v4':
        return a(
            header: HeaderStyle.eloraV4,
            card: CardStyle.compact,
            cardRadius: 6,
            hero: 200,
            imgH: 110,
            cardW: 130,
            dots: true);
      case 'v5':
        return a(
            header: HeaderStyle.eloraV5,
            card: CardStyle.square,
            hero: 254,
            fit: BoxFit.contain,
            pal: const [
              Color(0xFF132092),
              Color(0xFFFF522C),
              Color(0xFFFDFDFD),
              Color(0xFF121212),
              Color(0xFFADADAD)
            ]);
      case 'v6':
        return a(
            header: HeaderStyle.eloraV6,
            card: CardStyle.overlay,
            cardRadius: 0,
            hero: 254,
            imgH: 155,
            cardW: 177,
            align: CrossAxisAlignment.center);
      default: // v1: "Default" - gray palette, base blade card (h-48, contain)
        return a(
            header: HeaderStyle.eloraBase,
            imgH: 190,
            fit: BoxFit.contain,
            hero: 190,
            pal: const [
              Color(0xFF111827),
              Color(0xFF4B5563),
              Color(0xFFFDFDFD),
              Color(0xFF121212),
              Color(0xFFADADAD)
            ]);
    }
  }
}

/// Provides the active [ThemeSkin] plus the navigation hook to descendants.
class SkinScope extends InheritedWidget {
  const SkinScope(
      {super.key, required this.skin, this.onOpen, required super.child});
  final ThemeSkin skin;
  final OpenRoute? onOpen;

  static SkinScope _of(BuildContext c) {
    final s = c.dependOnInheritedWidgetOfExactType<SkinScope>();
    assert(s != null, 'SkinScope missing');
    return s!;
  }

  static ThemeSkin of(BuildContext c) => _of(c).skin;
  static void open(BuildContext c, String? route) {
    if (route == null || route.isEmpty) return;
    _of(c).onOpen?.call(c, route);
  }

  @override
  bool updateShouldNotify(SkinScope old) => old.skin != skin;
}
