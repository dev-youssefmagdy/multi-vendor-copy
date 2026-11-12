<x-tenant::modal id="theme-country-variants-modal" :title="__('Country Variant Overrides')" size="lg">
    <div class="page-stack">
        <p class="theme-country-summary-line">
            {{ __('Choose a specific home page variant for each country on') }} <strong data-cv-theme-name></strong>{{ __('. Leave a country on') }} <em>{{ __('Default') }}</em> {{ __('to use the globally active variant.') }}
        </p>

        <div class="t-field">
            <label class="field-label" for="theme-cv-search">{{ __('Search') }}</label>
            <input type="text" id="theme-cv-search" class="theme-country-input" data-cv-search placeholder="{{ __('Search by country name or ISO code…') }}">
        </div>

        <div class="theme-country-list-wrapper" data-cv-list style="max-height:380px;overflow-y:auto;">
            {{-- rows injected by JS --}}
        </div>

        <div class="theme-modal-actions">
            <button type="button" class="theme-pill-btn" data-modal-close>{{ __('Cancel') }}</button>
            <button type="button" class="theme-pill-btn is-primary" data-cv-save>{{ __('Save') }}</button>
        </div>
    </div>
</x-tenant::modal>
