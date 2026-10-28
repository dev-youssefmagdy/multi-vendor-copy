/// Bundled fonts (assets/fonts, declared in pubspec.yaml). The app never loads
/// fonts over the network; every family here mirrors what the storefront web
/// themes load from Google Fonts. See docs/mobile/FONTS.md.
class AppFonts {
  AppFonts._();

  /// Body/heading/price/button font of ecommet v1, elora v1-v6, souqify v1-v6.
  static const String base = 'Outfit';

  /// Arabic/RTL glyph fallback (Outfit/Latin fonts have no Arabic coverage).
  static const String arabic = 'Noto Sans Arabic';

  /// Fallback stack applied after the primary family on every TextStyle.
  static const List<String> fallback = [arabic];

  /// Every bundled family (must match pubspec.yaml `fonts:`).
  static const Set<String> bundled = {
    'Outfit', 'Manrope', 'Inter', 'Almarai', 'Amiri', 'Bebas Neue', 'Cairo',
    'Cormorant Garamond', 'DM Sans', 'IBM Plex Sans Arabic', 'Lalezar',
    'Lemonada', 'Lora', 'Montserrat', 'Noto Sans Arabic', 'Nunito', 'Oswald',
    'Playfair Display', 'Poppins', 'Raleway', 'Readex Pro', 'Reem Kufi',
    'Scheherazade New', 'Space Grotesk', 'Tajawal',
  };

  /// Theme x variant -> primary family (all storefront variants use Outfit).
  static String forTheme(String themeSlug, String variantKey) => base;

  /// Parses a CSS stack such as `'Cairo', sans-serif` (storefront
  /// `LOGO_FONTS`, sent as `branding.logo.font_family`) to a bundled family.
  /// Unknown / missing -> [fallbackFamily] (theme default).
  static String resolve(String? cssStack, {String fallbackFamily = base}) {
    if (cssStack == null) return fallbackFamily;
    final m = RegExp(r'''^\s*['"]?([^'",]+)['"]?''').firstMatch(cssStack);
    final name = m?.group(1)?.trim();
    return name != null && bundled.contains(name) ? name : fallbackFamily;
  }
}
