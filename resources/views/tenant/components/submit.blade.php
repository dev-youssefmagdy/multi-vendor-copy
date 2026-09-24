@props([
    'variant' => 'primary',
    'icon' => null,
    'loadingText' => 'Saving…',
    'size' => null,
])

@php
    $variantClass = $variant === 'primary' ? 'btn-primary' : 'btn-secondary';
    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'md' => 'btn-md',
        'lg' => 'btn-lg',
        default => '',
    };
@endphp

<button type="submit" {{ $attributes->merge(['class' => trim("btn $variantClass $sizeClass t-submit")]) }} data-loading-text="{{ $loadingText }}">
    @if($icon)<span class="shrink-0">{!! $icon !!}</span>@endif
    <span class="t-submit-label">{{ $slot }}</span>
</button>
