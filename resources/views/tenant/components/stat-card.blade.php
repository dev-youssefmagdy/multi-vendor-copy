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

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card '.($glow ? 'card-glow-'.$glow : '').' fu d'.min($delay, 6)]) }}>
    <div class="stat-head">
        <div>
            <div class="eyebrow">{{ $label }}</div>
            <div class="D stat-value">{{ $value }}</div>
        </div>
        <div class="mini-stat-dot {{ $dot }}">{!! $icon !!}</div>
    </div>
    @if($caption)<p class="panel-copy">{{ $caption }}</p>@endif
    @if($trend)<p class="t-stat-trend">{{ $trend }}</p>@endif
</{{ $tag }}>
