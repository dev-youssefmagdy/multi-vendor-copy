@if($centralProduct)
    <div>
        @if(!empty($centralProduct['image_url']))
            <img src="{{ $centralProduct['image_url'] }}" alt="{{ $centralProduct['name'] }}" class="entity-thumb-lg">
        @else
            <div class="entity-thumb-lg entity-thumb-empty">No image</div>
        @endif
    </div>
    <div class="page-stack">
        <div>
            <div class="entity-title">{{ $centralProduct['name'] }}</div>
            <div class="entity-subtitle">SKU {{ $centralProduct['sku'] }} &middot; {{ $centralProduct['status'] }}</div>
        </div>
        <div class="form-grid form-grid-2">
            <div><label class="field-label">Base Price</label><div class="field-control-static">${{ number_format((float) $centralProduct['base_price'], 2) }}</div></div>
            <div><label class="field-label">Current Central Price</label><div class="field-control-static">${{ number_format((float) $centralProduct['current_price'], 2) }}</div></div>
            <div><label class="field-label">Factory</label><div class="field-control-static">{{ $centralProduct['factory'] ?: '—' }}</div></div>
            <div><label class="field-label">Stock</label><div class="field-control-static">{{ number_format((int) $centralProduct['stock']) }}</div></div>
            <div><label class="field-label">Delivery Scope</label><div class="field-control-static">{{ $centralProduct['delivery_scope'] ?: '—' }}</div></div>
            <div><label class="field-label">Categories</label><div class="field-control-static">{{ implode(', ', $centralProduct['categories'] ?: ['Unassigned']) }}</div></div>
        </div>
        @if(!empty($centralProduct['summary']))
            <div><label class="field-label">Summary</label><div class="field-control-static field-control-multiline">{{ $centralProduct['summary'] }}</div></div>
        @endif
    </div>
@endif
