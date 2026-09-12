@props([
    'variant' => 'primary',
    'icon' => null,
    'loadingText' => 'Saving…',
])

@php
    $variantClass = $variant === 'primary' ? 'btn-primary' : 'btn-secondary';
@endphp

<button type="submit" {{ $attributes->merge(['class' => "btn $variantClass t-submit"]) }} data-loading-text="{{ $loadingText }}">
    @if($icon)<span class="shrink-0">{!! $icon !!}</span>@endif
    <span class="t-submit-label">{{ $slot }}</span>
</button>
