<x-tenant::modal id="theme-countries-modal" title="Theme Countries" size="lg">
    <div class="page-stack">
        <p class="theme-country-summary-line">
            Enable or disable countries for <strong data-countries-theme-name></strong>.
            You can only toggle countries allowed by the central template.
        </p>

        <x-tenant::form id="theme-countries-form" action="" method="PUT" success="close-modal emit:tenant:setup-progress:refresh reload-page">
            <div class="t-field">
                <label class="field-label" for="theme-countries-search">Search</label>
                <input type="text" id="theme-countries-search" class="theme-country-input" data-countries-search placeholder="Search by country name or ISO code...">
            </div>

            <x-tenant::checkbox-group name="country_ids" select-all wrapper-class="theme-country-list-wrapper" columns="1" />

            <div class="theme-modal-actions">
                <button type="button" class="theme-pill-btn" data-modal-close>Cancel</button>
                <x-tenant::submit variant="primary">Save</x-tenant::submit>
            </div>
        </x-tenant::form>
    </div>
</x-tenant::modal>
