@props([
    'label' => null,
    'value' => null,
    'caption' => null,
    'dot' => 'dot-cyan',
    'glow' => null,
    'href' => null,
    'icon' => null,
    'trend' => null,
    'delay' => 0,
])

{{--
    KPI card (design: Orders page cards) — label, big value, caption, and an
    illustration on the right. `icon` may be SVG markup, an image path under
    public/, or false for none; when omitted the illustration is picked from
    the label. `trend` is a signed percentage (12, -4.5) or a string like "+12%".
--}}

@php
    $tag = $href ? 'a' : 'div';

    // Design illustrations, matched by keywords in the label (first match wins).
    $illustrations = [
        'processing' => ['tenant-panel/orders/processing.png', 72],
        'pending' => ['tenant-panel/requests/pending.png', 72],
        'collected|revenue|balance|sales|spend|value|payout|profit|earning|amount' => ['tenant-panel/orders/collected.png', 42],
        'order' => ['tenant-panel/orders/orders.png', 72],
        'buyer' => ['tenant-panel/customers/buyers.png', 49],
        'customer|subscriber|admin|user|member' => ['tenant-panel/customers/customers.png', 48],
        'total|request' => ['tenant-panel/requests/total.png', 30],
    ];

    $image = null;
    $svg = null;
    if (is_string($icon) && str_starts_with(ltrim($icon), '<')) {
        $svg = $icon;
    } elseif (is_string($icon) && $icon !== '') {
        $image = [$icon, 44];
    } elseif ($icon !== false) {
        foreach ($illustrations as $pattern => $illustration) {
            if (preg_match('/'.$pattern.'/i', (string) $label)) {
                $image = $illustration;
                break;
            }
        }
    }
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'ds-kpi fu d'.min($delay, 6)]) }}>
    <div class="ds-kpi-body">
        <div class="ds-kpi-head">
            <span class="ds-kpi-label">{{ $label }}</span>
            @if($trend !== null && $trend !== '')
                <x-tenant::trend :value="$trend" />
            @endif
        </div>
        <strong class="ds-kpi-value">{{ $value }}</strong>
        @if($caption)<p class="ds-kpi-caption">{{ rtrim($caption, '.') }}</p>@endif
    </div>
    @if($svg)
        <span class="ds-kpi-icon" aria-hidden="true">{!! $svg !!}</span>
    @elseif($image)
        <img src="{{ asset($image[0]) }}" alt="" class="ds-kpi-icon" style="--ds-kpi-icon: {{ $image[1] }}px">
    @endif
</{{ $tag }}>
