@props(['storeName' => null])

@php
    $logo = app(\App\Repositories\Tenant\StorefrontRepository::class)->resolvedLogo();
    $alt = $storeName ?? ($logo['text'] ?? '');
    $borderRadius = $logo['shape'] === 'rounded' ? '999px' : '8px';
    $textStyle = 'font-family:' . $logo['font_family'] . ';color:' . $logo['color']
        . ';background-color:' . $logo['bg_color'] . ';border-radius:' . $borderRadius
        . ';font-size:1.35rem;font-weight:700;line-height:1;white-space:nowrap;display:inline-flex;align-items:center;padding:0.35em 0.85em';
@endphp

@if ($logo['mode'] === 'image')
    <img loading="lazy" src="{{ $logo['image_url'] }}" alt="{{ $alt }}" {{ $attributes }} />
@else
    <span {{ $attributes->merge(['style' => $textStyle]) }}
        dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">{{ $logo['text'] }}</span>
@endif
