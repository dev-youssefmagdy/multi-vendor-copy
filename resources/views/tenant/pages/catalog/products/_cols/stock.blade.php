@php
    $stockQty = $product->variants->isNotEmpty() ? $product->variants->sum('stock') : ($product->stock ?? 0);
    $stockStatus = $product->stockStatus();
@endphp
<div class="entity-title">{{ number_format((int) $stockQty) }}</div>
@if($stockStatus === 'out_of_stock')
    <div class="entity-subtitle products-stock-out">Out of stock</div>
@elseif($stockStatus === 'partial')
    <div class="entity-subtitle products-stock-partial">Partial</div>
@endif
