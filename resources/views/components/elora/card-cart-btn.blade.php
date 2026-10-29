{{-- Cart button for Elora variant cards. Same behaviour as the default Elora card:
     out of stock -> disabled; several variants -> variant picker modal; otherwise add directly.
     Expects $p from Product::storefrontCartData() merged into the card array. --}}
@props(['p' => []])

@if (!empty($p['isOutOfStock']))
    <button type="button" disabled aria-disabled="true" aria-label="{{ __('Add to cart') }}"
        onclick="event.preventDefault(); event.stopPropagation()"
        {{ $attributes->merge(['class' => 'cursor-not-allowed']) }}>{{ $slot }}</button>
@elseif (!empty($p['hasMultipleVariants']))
    <button type="button" aria-label="{{ __('Add to cart') }}"
        onclick="event.preventDefault(); event.stopPropagation(); openVariantModal({{ $p['id'] ?? 'null' }}, {{ \Illuminate\Support\Js::from($p['nameJs'] ?? ($p['name'] ?? '')) }}, {{ \Illuminate\Support\Js::from($p['variantModalData'] ?? []) }})"
        {{ $attributes }}>{{ $slot }}</button>
@else
    <button type="button" aria-label="{{ __('Add to cart') }}"
        wire:click.prevent="addToCart({{ $p['id'] ?? 'null' }})"
        onclick="event.preventDefault(); event.stopPropagation()"
        {{ $attributes }}>{{ $slot }}</button>
@endif
