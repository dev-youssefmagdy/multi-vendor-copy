# Mobile fonts

Goal: the app uses the same fonts as the storefront web themes, all bundled in
`application/assets/fonts` (no runtime network loading, no `google_fonts`).

## Findings (storefront)

- Body/headings/prices/buttons everywhere: `Outfit` (Google Fonts, wght 300-800).
  ecommet: `resources/css/ecommet.css` `--font-main: 'Outfit'`; elora: `layout/styles*.blade.php`
  + `public/elora-3/styles.css` `--font-family-base`; souqify: `layout/styles*.blade.php`, `scripts.blade.php`.
- souqify v1 (`layout/styles.blade.php`) also loads `Manrope` 400/600/700 and registers a Tailwind `font-manrope`
  utility (only an opt-in utility class; body stays Outfit).
- `Inter` 600: one badge in `souqify/pages/home-v5/sections/flash_sale.blade.php` (web-only detail, bundled for parity).
- 23-family catalog URL (`layout/app.blade.php` of ecommet/souqify, `elora/layout/styles.blade.php`,
  `layouts/tenant.blade.php`, invoice): used ONLY for the tenant-selectable **text logo** font
  (`StorefrontRepository::LOGO_FONTS`, settings `logo_font_ar` / `logo_font_en`). Mobile bootstrap already sends
  `branding.logo.font_family` (CSS stack) from `resolvedLogo()`, so no backend change was needed.
- Arabic/RTL: the storefront declares no Arabic body font (Outfit has no Arabic glyphs; browsers use the system font).
  The app bundles `Noto Sans Arabic` as the fallback family for ar/RTL glyphs.

## Theme x variant -> fonts

| Theme | Variants | Body / headings / price / buttons | Extra |
|---|---|---|---|
| ecommet | v1 | Outfit 300-800 | Arabic fallback Noto Sans Arabic |
| elora | v1-v6 | Outfit 300-800 | same |
| souqify | v1-v6 | Outfit 300-800 | v1 Manrope (utility only), v5 Inter 600 badge (web only) |

Text logo (any theme): one of the 23 catalog families, key from bootstrap `branding.logo.font_family`;
unknown key -> theme default (Outfit).

## Bundled files (7.6 MB total, TTF, from fonts.gstatic.com; OFL texts in `assets/fonts/licenses`)

Outfit 300/400/500/600/700/800, Manrope 400/600/700, Inter 400/600/700, and 400+700 (400 only for Bebas Neue,
Lalezar) of: Almarai, Amiri, Bebas Neue, Cairo, Cormorant Garamond, DM Sans, IBM Plex Sans Arabic, Lalezar,
Lemonada, Lora, Montserrat, Noto Sans Arabic, Nunito, Oswald, Playfair Display, Poppins, Raleway, Readex Pro,
Reem Kufi, Scheherazade New, Space Grotesk, Tajawal.

## Wiring

`lib/design/fonts.dart` (`AppFonts`), `ThemeTokens.fontFamily` -> `ThemeData.fontFamily` + `fontFamilyFallback`
(light and dark; all widgets inherit, none hardcode a family), `ThemeSkin.fontFamily` default Outfit,
`TenantConfig.logoText/logoFontFamily` used by the header text logo. Tests: `test/unit/fonts_test.dart`.
