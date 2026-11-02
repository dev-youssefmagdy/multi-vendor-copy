@if(count($variants))
    {{-- PANELS-UPDATES-VARIANT-MARGIN: weight is no longer shown; each variant has an editable profit margin (% over
         the central price) that stays in sync with its sell price, plus a bulk "apply to all variants" control. --}}
    <div class="variants-bulk" data-variants-bulk style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 14px">
        <label for="variants-bulk-margin" style="font-weight:600">Profit margin for all variants</label>
        {{-- plain input: it has no field name, which x-tenant::input does not support --}}
        <div style="display:flex;align-items:center;gap:6px;max-width:170px">
            <input type="number" step="0.01" min="0" id="variants-bulk-margin" class="field-control" placeholder="e.g. 30" data-bulk-margin-input>
            <span aria-hidden="true">%</span>
        </div>
        <button type="button" class="btn btn-secondary" data-bulk-margin-apply>Apply to all variants</button>
    </div>
    <x-tenant::table :headers="['Variant', 'Main Price', 'Profit Margin', 'Sell Price', 'Active']" class="variants-table">
        @foreach($variants as $index => $variant)
            <tr>
                <td class="variants-table-variant-cell">
                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] }}">
                    <input type="hidden" name="variants[{{ $index }}][central_product_variant_id]" value="{{ $variant['central_product_variant_id'] }}">
                    <input type="hidden" name="variants[{{ $index }}][real_price]" value="{{ $variant['real_price'] }}">
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
                <td class="variants-table-margin-cell">
                    <x-tenant::input type="number" step="0.01" min="0" name="variants.{{ $index }}.profit_margin" suffix="%" :value="$variant['profit_margin'] ?? '0.00'" data-margin-input data-real-price="{{ $variant['real_price'] }}" />
                </td>
                <td class="variants-table-price-cell">
                    <div class="input-prefix-group">
                        <span class="input-prefix">$</span>
                        <x-tenant::input type="number" step="0.01" min="0" name="variants.{{ $index }}.sell_price" :value="$variant['sell_price']" data-sell-input data-real-price="{{ $variant['real_price'] }}" />
                    </div>
                </td>
                <td class="variants-table-active-cell">
                    {{-- PANELS-UPDATES-VARIANT-ACTIVE: the vendor can switch single variants off (hidden from the storefront,
                         kept through catalog syncs); central status is shown underneath when central itself disabled it. --}}
                    <x-tenant::switch name="variants.{{ $index }}.active" :checked="$variant['active']" label="" aria-label="{{ __('Active') }}" />
                    @if(strtolower((string) $variant['status']) !== 'active')
                        <x-tenant::status-badge :status="$variant['status']" />
                    @endif
                </td>
            </tr>
        @endforeach
    </x-tenant::table>
@else
    <x-tenant::empty-state title="No synced variants" copy="This central product does not currently expose any variants." />
@endif
