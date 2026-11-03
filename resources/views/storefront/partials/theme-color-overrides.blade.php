{{-- Tenant design overrides (Appearance > Colors & Fonts): changed color tokens as :root custom properties, and
     the storefront font for the current text direction (only that one family is requested from Google Fonts). --}}
@php
    $__lang = $currentLanguage ?? request()->attributes->get('storefrontCurrentLanguage');
    $__dir = (($__lang?->direction?->value ?? null) === 'rtl' || in_array($__lang?->code ?? app()->getLocale(), ['ar', 'he', 'fa', 'ur'], true)) ? 'rtl' : 'ltr';
    $__fontKey = app(\App\Repositories\Tenant\StorefrontRepository::class)->resolvedThemeFonts()[$__dir];
    $__fontUrl = \App\Support\ThemeDesign\ThemeFonts::googleStylesheetUrl($__dir, [$__fontKey]);
    $__fontStack = \App\Support\ThemeDesign\ThemeFonts::stack($__dir, $__fontKey);
@endphp
@if(!empty($storefrontColors))
    <style id="tenant-color-overrides">
        /* :root:root outranks any later :root declaration (e.g. footer stylesheets loaded after this block). */
        :root:root {
            @foreach ($storefrontColors as $property => $value)
                @if(str_starts_with($property, '--') && preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $value))
                    {{ $property }}: {{ $value }};
                @endif
            @endforeach
        }
    </style>
@endif
@if($__fontUrl)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $__fontUrl }}">
@endif
<style id="tenant-font-overrides">
    :root { --sf-font: {!! $__fontStack !!}; --font-sans: var(--sf-font); --font-family-base: var(--sf-font); --font-main: var(--sf-font); }
    /* Elements with an inline font-family (the store-name text logo) keep their own font. */
    html body, html body *:not(svg):not(svg *):not(pre):not(code):not(kbd):not(samp):not([style*="font-family"]):not([style*="font-family"] *) { font-family: var(--sf-font) !important; }
</style>
