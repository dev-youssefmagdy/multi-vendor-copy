@props(['variant' => 'primary', 'type' => 'button', 'icon' => null])

@php
    $baseClass = 'btn';
    $variantClass = $variant === 'primary' ? 'btn-primary' : 'btn-secondary';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "$baseClass $variantClass"]) }}>
    @if($icon)
        <span class="shrink-0">{!! $icon !!}</span>
    @endif
    {{ $slot }}
</button>
