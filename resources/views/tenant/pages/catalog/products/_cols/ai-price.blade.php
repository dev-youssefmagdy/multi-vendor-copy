@if(!empty($product->ai_price_data))
    <div class="entity-title products-ai-price">${{ number_format((float) ($product->ai_price_data['average_price'] ?? 0), 2) }}</div>
    <div class="entity-subtitle">{{ __('avg · :samplesize sources', ['samplesize' => $product->ai_price_data['sample_size'] ?? 0]) }}</div>
@else
    <span class="entity-subtitle">&mdash;</span>
@endif
