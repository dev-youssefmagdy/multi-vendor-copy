<x-tenant::modal id="product-price-list-modal" title="Price List" size="xl">
    <div class="price-list-modal" data-price-list-root>
        <div class="price-list-save-message" data-price-list-message hidden></div>

        <p class="entity-subtitle price-list-legend">
            <strong>Sale Price</strong> = central catalog price &middot;
            <strong>Profit</strong> = your markup (% or fixed amount) &middot;
            <strong>Fixed Cost</strong> = shipping baked in &middot;
            <strong>Your Price</strong> = Sale + Profit + Fixed Cost (auto-calculated).
        </p>

        <h3 class="panel-title price-list-heading">Product Sell Prices</h3>
        <x-tenant::table :headers="['Country', 'Sale Price', 'Profit', 'Fixed Cost', 'Your Price']" data-price-list-product-table>
        </x-tenant::table>
        <p class="entity-subtitle" data-price-list-no-prices hidden>No pricing data loaded. Please close and re-open the modal.</p>

        <h3 class="panel-title price-list-heading" data-price-list-variants-heading hidden>Variant Sell Prices</h3>
        <div data-price-list-variants></div>

        <div class="price-list-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" data-price-list-save>Save Prices</button>
        </div>
    </div>
</x-tenant::modal>
