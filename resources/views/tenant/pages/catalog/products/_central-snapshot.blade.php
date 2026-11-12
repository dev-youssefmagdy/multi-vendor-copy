@if($centralProduct)
    <div>
        @if(!empty($centralProduct['image_url']))
            <img src="{{ $centralProduct['image_url'] }}" alt="{{ $centralProduct['name'] }}" class="entity-thumb-lg">
        @else
            <div class="entity-thumb-lg entity-thumb-empty">{{ __('No image') }}</div>
        @endif
    </div>
    <div class="page-stack">
        <div>
            <div class="entity-title">{{ $centralProduct['name'] }}</div>
            <div class="entity-subtitle">{{ __('SKU :sku · :status', ['sku' => $centralProduct['sku'], 'status' => $centralProduct['status']]) }}</div>
        </div>
        <div class="form-grid form-grid-2">
            <div><label class="field-label">{{ __('Base Price') }}</label><div class="field-control-static">${{ number_format((float) $centralProduct['base_price'], 2) }}</div></div>

            <div><label class="field-label">{{ __('Stock') }}</label><div class="field-control-static">{{ number_format((int) $centralProduct['stock']) }}</div></div>
            <div><label class="field-label">{{ __('Delivery Scope') }}</label><div class="field-control-static">{{ $centralProduct['delivery_scope'] ?: '—' }}</div></div>
            <div><label class="field-label">{{ __('Categories') }}</label><div class="field-control-static">{{ implode(', ', $centralProduct['categories'] ?: [__('Unassigned')]) }}</div></div>
        </div>
        @if(!empty($centralProduct['summary']))
            <div><label class="field-label">{{ __('Summary') }}</label><div class="field-control-static field-control-multiline">{{ $centralProduct['summary'] }}</div></div>
        @endif
    </div>
@endif
