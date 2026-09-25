@php
    // First item's product (image + name) represents the order, like the design.
    $item = $order->items->first();
    $product = $item?->product ?? $item?->variant?->product;
    $name = $product?->translationValue('name') ?? $product?->slug ?? ('Order '.\Illuminate\Support\Str::limit((string) $order->uuid, 8, ''));
    $image = $product?->primary_image_url;
    $more = max(0, (int) $order->items_count - 1);
@endphp
<div class="od-order" title="{{ $order->uuid }}">
    @if($image)
        <img src="{{ $image }}" alt="" class="od-thumb" loading="lazy">
    @else
        <span class="od-thumb od-thumb-empty" aria-hidden="true">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
    @endif
    <span class="od-order-name">{{ $name }}@if($more) <small>+{{ $more }}</small>@endif</span>
</div>
