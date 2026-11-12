<x-tenant::modal id="product-price-finder-modal" :title="__('AI Price Finder')" size="lg">
    <div class="price-finder-modal" data-price-finder-root>
        <div class="price-finder-error" data-price-finder-error hidden></div>

        <div class="price-finder-options">
            <label class="price-finder-checkbox">
                <input type="checkbox" data-price-finder-use-image>
                {{ __('Include product image in search') }}
            </label>
            <select class="field-control" data-price-finder-variant hidden>
                <option value="">{{ __('Main product image') }}</option>
            </select>
        </div>

        <div class="price-finder-fetch-row">
            <p class="entity-subtitle" data-price-finder-hint>{{ __('Click Fetch to discover current market prices for this product from the web.') }}</p>
            <button type="button" class="btn btn-primary btn-sm" data-price-finder-fetch>{{ __('Fetch Prices') }}</button>
        </div>

        <div class="price-finder-loading" data-price-finder-loading hidden>
            <span class="t-spinner"></span>
            <p class="entity-subtitle">{{ __('Searching the web for pricing data…') }}</p>
        </div>

        <div data-price-finder-results hidden>
            <div class="price-finder-stats" data-price-finder-stats></div>

            <x-tenant::table :headers="[__('Source'), __('Price'), __('Title')]" data-price-finder-hits-table>
            </x-tenant::table>

            <p class="entity-subtitle" data-price-finder-query></p>
        </div>

        <p class="entity-subtitle" data-price-finder-empty>{{ __('No price data yet. Click Fetch Prices to discover market pricing.') }}</p>
    </div>
</x-tenant::modal>
