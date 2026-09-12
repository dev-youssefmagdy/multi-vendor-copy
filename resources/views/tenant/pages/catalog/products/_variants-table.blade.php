@if(count($variants))
    <x-tenant::table :headers="['Variant', 'Main Price', 'Weight', 'Vendor Sell Price', 'Status']" class="variants-table">
        @foreach($variants as $index => $variant)
            <tr>
                <td class="variants-table-variant-cell">
                    <input type="hidden" name="variants.{{ $index }}.id" value="{{ $variant['id'] }}">
                    <input type="hidden" name="variants.{{ $index }}.central_product_variant_id" value="{{ $variant['central_product_variant_id'] }}">
                    <input type="hidden" name="variants.{{ $index }}.real_price" value="{{ $variant['real_price'] }}">
                    <input type="hidden" name="variants.{{ $index }}.active" value="{{ $variant['active'] ? 1 : 0 }}">
                    <div class="entity-row">
                        @if(!empty($variant['image_url']))
                            <img src="{{ $variant['image_url'] }}" alt="{{ $variant['title'] }}" class="entity-thumb" loading="lazy" width="44" height="44">
                        @else
                            <div class="entity-thumb entity-thumb-empty">—</div>
                        @endif
                        <div class="entity-body">
                            <div class="entity-title">{{ $variant['title'] }}</div>
                            <div class="entity-subtitle">{{ $variant['sku'] ?: ($variant['options_label'] ?: 'Central variant') }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="entity-title">${{ number_format((float) $variant['real_price'], 2) }}</div>
                    <div class="entity-subtitle">Central price</div>
                </td>
                <td>
                    @if(!empty($variant['weight_grams']))
                        <div class="entity-title">{{ $variant['weight_grams'] }} g</div>
                    @else
                        <div class="entity-subtitle">—</div>
                    @endif
                </td>
                <td class="variants-table-price-cell">
                    <div class="input-prefix-group">
                        <span class="input-prefix">$</span>
                        <x-tenant::input type="number" step="0.01" min="0" name="variants.{{ $index }}.sell_price" :value="$variant['sell_price']" />
                    </div>
                </td>
                <td>
                    <x-tenant::status-badge :status="$variant['status']" />
                </td>
            </tr>
        @endforeach
    </x-tenant::table>
@else
    <x-tenant::empty-state title="No synced variants" copy="This central product does not currently expose any variants." />
@endif
