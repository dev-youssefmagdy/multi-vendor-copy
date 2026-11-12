<x-tenant::modal id="product-price-list-modal" :title="__('Price List')" size="xl">
    <div class="price-list-modal" data-price-list-root>
        <div class="price-list-save-message" data-price-list-message hidden></div>

        <p class="entity-subtitle price-list-legend">
            <strong>{{ __('Sale Price') }}</strong> {{ __('= central catalog price ·') }}
            <strong>{{ __('Profit') }}</strong> {{ __('= your markup (% or fixed amount) ·') }}
            <strong>{{ __('Fixed Cost') }}</strong> {{ __('= shipping baked in ·') }}
            <strong>{{ __('Your Price') }}</strong> {{ __('= Sale + Profit + Fixed Cost (auto-calculated).') }}
        </p>

        <h3 class="panel-title price-list-heading">{{ __('Product Sell Prices') }}</h3>
        <x-tenant::table :headers="[__('Country'), __('Sale Price'), __('Profit'), __('Fixed Cost'), __('Your Price')]" data-price-list-product-table>
        </x-tenant::table>
        <p class="entity-subtitle" data-price-list-no-prices hidden>{{ __('No pricing data loaded. Please close and re-open the modal.') }}</p>

        <h3 class="panel-title price-list-heading" data-price-list-variants-heading hidden>{{ __('Variant Sell Prices') }}</h3>
        <div data-price-list-variants></div>

        <div class="price-list-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-modal-close>{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary btn-sm" data-price-list-save>{{ __('Save Prices') }}</button>
        </div>
    </div>
</x-tenant::modal>
