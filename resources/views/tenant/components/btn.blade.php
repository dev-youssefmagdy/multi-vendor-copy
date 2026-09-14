@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'icon' => null,
    'type' => 'button',
    'loading' => false,
])

@php
    $variantClass = match ($variant) {
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'ghost' => 'btn-ghost',
        'link' => 'btn-link',
        default => 'btn-primary',
    };
    $sizeClass = $size === 'sm' ? 'btn-sm' : '';
    $classes = trim("btn $variantClass $sizeClass".($loading ? ' is-loading' : ''));
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<span class="shrink-0">{!! $icon !!}</span>@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<span class="shrink-0">{!! $icon !!}</span>@endif
        {{ $slot }}
    </button>
@endif
