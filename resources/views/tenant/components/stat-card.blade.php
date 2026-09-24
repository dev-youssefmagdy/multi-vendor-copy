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

{{-- KPI card. `trend` is a signed percentage (12, -4.5) or a string like "+12%". --}}

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card t-kpi fu d'.min($delay, 6)]) }}>
    <div class="t-kpi-head">
        <span class="t-kpi-label">{{ $label }}</span>
        @if($trend !== null && $trend !== '')
            <x-tenant::trend :value="$trend" />
        @elseif($icon)
            <span class="t-kpi-icon">{!! $icon !!}</span>
        @endif
    </div>
    <div class="t-kpi-value">{{ $value }}</div>
    @if($caption)<p class="t-kpi-caption">{{ $caption }}</p>@endif
</{{ $tag }}>
