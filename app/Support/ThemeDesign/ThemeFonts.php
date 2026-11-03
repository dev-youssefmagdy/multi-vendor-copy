<?php

namespace App\Support\ThemeDesign;

/**
 * Fonts a tenant can pick for the storefront, one list per text direction. Only the family needed for the
 * current page direction is loaded from Google Fonts (see googleStylesheetUrl()), so a larger list costs nothing.
 */
final class ThemeFonts
{
    public const DEFAULT_LTR = 'outfit';

    public const DEFAULT_RTL = 'cairo';

    /** key => [label, css family, Google Fonts "family=" spec, style note] */
    public const LTR = [
        'outfit' => ['Outfit', "'Outfit'", 'Outfit:wght@300;400;500;600;700;800', 'Geometric, friendly'],
        'inter' => ['Inter', "'Inter'", 'Inter:wght@300;400;500;600;700;800', 'Neutral, very readable UI font'],
        'poppins' => ['Poppins', "'Poppins'", 'Poppins:wght@300;400;500;600;700;800', 'Geometric, rounded'],
        'montserrat' => ['Montserrat', "'Montserrat'", 'Montserrat:wght@300;400;500;600;700;800', 'Urban, wide letters'],
        'dm_sans' => ['DM Sans', "'DM Sans'", 'DM+Sans:wght@300;400;500;600;700', 'Clean, low contrast'],
        'nunito' => ['Nunito', "'Nunito'", 'Nunito:wght@300;400;500;600;700;800', 'Soft, rounded terminals'],
        'raleway' => ['Raleway', "'Raleway'", 'Raleway:wght@300;400;500;600;700;800', 'Elegant, thin strokes'],
        'manrope' => ['Manrope', "'Manrope'", 'Manrope:wght@300;400;500;600;700;800', 'Modern, technical'],
        'plus_jakarta' => ['Plus Jakarta Sans', "'Plus Jakarta Sans'", 'Plus+Jakarta+Sans:wght@300;400;500;600;700;800', 'Contemporary, premium'],
        'work_sans' => ['Work Sans', "'Work Sans'", 'Work+Sans:wght@300;400;500;600;700;800', 'Grotesque, practical'],
        'open_sans' => ['Open Sans', "'Open Sans'", 'Open+Sans:wght@300;400;500;600;700;800', 'Classic humanist'],
        'roboto' => ['Roboto', "'Roboto'", 'Roboto:wght@300;400;500;700;900', 'Familiar, neutral'],
        'lato' => ['Lato', "'Lato'", 'Lato:wght@300;400;700;900', 'Warm, professional'],
        'space_grotesk' => ['Space Grotesk', "'Space Grotesk'", 'Space+Grotesk:wght@300;400;500;600;700', 'Quirky, techy'],
        'oswald' => ['Oswald', "'Oswald'", 'Oswald:wght@300;400;500;600;700', 'Condensed, strong headings'],
        'lora' => ['Lora', "'Lora'", 'Lora:wght@400;500;600;700', 'Serif, editorial'],
        'playfair' => ['Playfair Display', "'Playfair Display'", 'Playfair+Display:wght@400;500;600;700;800', 'Serif, luxury'],
        'cormorant' => ['Cormorant Garamond', "'Cormorant Garamond'", 'Cormorant+Garamond:wght@300;400;500;600;700', 'Serif, fashion'],
    ];

    public const RTL = [
        'cairo' => ['Cairo', "'Cairo'", 'Cairo:wght@300;400;500;600;700;800', 'Modern, balanced'],
        'tajawal' => ['Tajawal', "'Tajawal'", 'Tajawal:wght@300;400;500;700;800', 'Light, clean'],
        'almarai' => ['Almarai', "'Almarai'", 'Almarai:wght@300;400;700;800', 'Rounded, friendly'],
        'noto_sans_ar' => ['Noto Sans Arabic', "'Noto Sans Arabic'", 'Noto+Sans+Arabic:wght@300;400;500;600;700;800', 'Neutral, very readable'],
        'noto_kufi' => ['Noto Kufi Arabic', "'Noto Kufi Arabic'", 'Noto+Kufi+Arabic:wght@300;400;500;600;700;800', 'Kufi style, geometric'],
        'ibm_plex_ar' => ['IBM Plex Sans Arabic', "'IBM Plex Sans Arabic'", 'IBM+Plex+Sans+Arabic:wght@300;400;500;600;700', 'Technical, professional'],
        'readex_pro' => ['Readex Pro', "'Readex Pro'", 'Readex+Pro:wght@300;400;500;600;700', 'Highly legible, modern'],
        'rubik' => ['Rubik', "'Rubik'", 'Rubik:wght@300;400;500;600;700;800', 'Slightly rounded, Latin + Arabic'],
        'vazirmatn' => ['Vazirmatn', "'Vazirmatn'", 'Vazirmatn:wght@300;400;500;600;700;800', 'Clean, Persian/Arabic'],
        'mada' => ['Mada', "'Mada'", 'Mada:wght@300;400;500;600;700;900', 'Smooth, geometric'],
        'changa' => ['Changa', "'Changa'", 'Changa:wght@300;400;500;600;700;800', 'Playful, bold'],
        'el_messiri' => ['El Messiri', "'El Messiri'", 'El+Messiri:wght@400;500;600;700', 'Decorative, elegant'],
        'lemonada' => ['Lemonada', "'Lemonada'", 'Lemonada:wght@300;400;500;600;700', 'Casual, rounded'],
        'reem_kufi' => ['Reem Kufi', "'Reem Kufi'", 'Reem+Kufi:wght@400;500;600;700', 'Kufi, display'],
        'lalezar' => ['Lalezar', "'Lalezar'", 'Lalezar', 'Heavy display, headlines'],
        'amiri' => ['Amiri', "'Amiri'", 'Amiri:wght@400;700', 'Naskh serif, classic'],
        'scheherazade' => ['Scheherazade New', "'Scheherazade New'", 'Scheherazade+New:wght@400;500;600;700', 'Traditional naskh'],
        'harmattan' => ['Harmattan', "'Harmattan'", 'Harmattan:wght@400;500;600;700', 'Compact, readable'],
    ];

    /** @return array<string, array{0: string, 1: string, 2: string, 3: string}> */
    public static function options(string $direction): array
    {
        return $direction === 'rtl' ? self::RTL : self::LTR;
    }

    public static function isValid(string $direction, ?string $key): bool
    {
        return $key !== null && isset(self::options($direction)[$key]);
    }

    /** CSS font-family stack for a font key, with a safe system fallback. */
    public static function stack(string $direction, ?string $key): string
    {
        $default = $direction === 'rtl' ? self::DEFAULT_RTL : self::DEFAULT_LTR;
        $font = self::options($direction)[$key] ?? self::options($direction)[$default];

        return $font[1] . ', ' . ($direction === 'rtl' ? "'Segoe UI', Tahoma, sans-serif" : 'system-ui, -apple-system, sans-serif');
    }

    /** One Google Fonts stylesheet URL for the given font keys (all families in a single request). */
    public static function googleStylesheetUrl(string $direction, array $keys): ?string
    {
        $specs = [];
        foreach (array_unique($keys) as $key) {
            if ($font = self::options($direction)[$key] ?? null) {
                $specs[] = 'family=' . $font[2];
            }
        }

        return $specs ? 'https://fonts.googleapis.com/css2?' . implode('&', $specs) . '&display=swap' : null;
    }
}
