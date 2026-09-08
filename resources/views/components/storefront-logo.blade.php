@props([
    'storeName' => null,
    // Theme demo branding, supplied by each header/footer partial that knows
    // which theme + edition it belongs to. Only used when the vendor hasn't
    // touched any logo appearance setting (see resolvedLogo()'s is_default) —
    // a vendor's own upload or text customization always wins.
    'brandLogo' => null,
    'brandText' => null,
    'brandColor' => null,
])

@php
    $logo = app(\App\Repositories\Tenant\StorefrontRepository::class)->resolvedLogo();
    $alt = $storeName ?? ($logo['text'] ?? '');
    $borderRadius = $logo['shape'] === 'rounded' ? '999px' : '8px';
    $textStyle = 'font-family:' . $logo['font_family'] . ';color:' . $logo['color']
        . ';background-color:' . $logo['bg_color'] . ';border-radius:' . $borderRadius
        . ';font-size:1.35rem;font-weight:700;line-height:1;white-space:nowrap;display:inline-flex;align-items:center;padding:0.35em 0.85em';
    $brandTextStyle = 'font-family:Outfit,sans-serif;color:' . $brandColor
        . ';font-weight:700;line-height:1;white-space:nowrap';
@endphp

@if ($logo['mode'] === 'image')
    <img loading="lazy" src="{{ $logo['image_url'] }}" alt="{{ $alt }}" {{ $attributes }} />
@elseif ($logo['is_default'] && $brandLogo)
    <img loading="lazy" src="{{ $brandLogo }}" alt="{{ $alt }}" {{ $attributes }} />
@elseif ($logo['is_default'] && $brandText)
    <span {{ $attributes->merge(['style' => $brandTextStyle]) }}>{{ $brandText }}</span>
@elseif ($logo['is_default'])
    {{-- Theme has no branding of its own (e.g. the base Ecommet theme) — fall
         back to the platform's own logo rather than a plain text placeholder. --}}
    <img loading="lazy" src="{{ asset('central-website/assets/image/central-footer-logo.png') }}" alt="{{ $alt }}" {{ $attributes }} />
@else
    <span {{ $attributes->merge(['style' => $textStyle]) }}
        dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">{{ $logo['text'] }}</span>
@endif
