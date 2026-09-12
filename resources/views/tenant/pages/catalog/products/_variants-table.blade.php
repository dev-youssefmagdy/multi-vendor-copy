@if(count($variants))
    <x-tenant::table :headers="['Variant', 'Main Price', 'Weight', 'Vendor Sell Price', 'Status']">
        @foreach($variants as $index => $variant)
            <tr>
                <td>
                    <input type="hidden" name="variants.{{ $index }}.id" value="{{ $variant['id'] }}">
                    <input type="hidden" name="variants.{{ $index }}.central_product_variant_id" value="{{ $variant['central_product_variant_id'] }}">
                    <input type="hidden" name="variants.{{ $index }}.real_price" value="{{ $variant['real_price'] }}">
                    <input type="hidden" name="variants.{{ $index }}.active" value="{{ $variant['active'] ? 1 : 0 }}">
                    <div class="entity-row">
                        @if(!empty($variant['image_url']))
                            <img src="{{ $variant['image_url'] }}" alt="{{ $variant['title'] }}" class="entity-thumb">
                        @else
                            <div class="entity-thumb entity-thumb-empty">—</div>
                        @endif
                        <div>
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
                <td>
                    <x-tenant::input type="number" step="0.01" name="variants.{{ $index }}.sell_price" :value="$variant['sell_price']" />
                </td>
                <td>{{ $variant['status'] }}</td>
            </tr>
        @endforeach
    </x-tenant::table>
@else
    <x-tenant::empty-state title="No synced variants" copy="This central product does not currently expose any variants." />
@endif
