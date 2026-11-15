<x-tenant::modal id="product-price-list-modal" :title="__('Price List')" size="xl">
    <div class="price-list-modal" data-price-list-root>
        <div class="price-list-save-message" data-price-list-message hidden></div>

        {{-- Own (vendor-created) products: one vendor-set price for every country, so the list is read-only. --}}
        <div data-price-list-own hidden>
            <p class="entity-subtitle price-list-legend">{{ __('This is your own product: it sells at the price you set, the same in every country (only the currency conversion differs). No catalog cost, profit rows or fixed shipping apply.') }}</p>
            <x-tenant::table :headers="[__('Price'), __('Amount')]" data-price-list-own-table>
            </x-tenant::table>
            <h3 class="panel-title price-list-heading" data-price-list-own-variants-heading hidden>{{ __('Variant Sell Prices') }}</h3>
            <div data-price-list-own-variants-wrap hidden>
                <x-tenant::table :headers="[__('Variant'), __('Your Price')]" data-price-list-own-variants>
                </x-tenant::table>
            </div>
            <div class="price-list-actions">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-close>{{ __('Close') }}</button>
                <a href="#" class="btn btn-primary btn-sm" data-price-list-own-edit>{{ __('Edit prices') }}</a>
            </div>
        </div>

        <div data-price-list-catalog>
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
    </div>
</x-tenant::modal>
