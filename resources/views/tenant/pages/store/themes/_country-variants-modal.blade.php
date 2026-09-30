<x-tenant::modal id="theme-country-variants-modal" title="Country Variant Overrides" size="lg">
    <div class="page-stack">
        <p class="theme-country-summary-line">
            Choose a specific home page variant for each country on <strong data-cv-theme-name></strong>.
            Leave a country on <em>Default</em> to use the globally active variant.
        </p>

        <div class="t-field">
            <label class="field-label" for="theme-cv-search">Search</label>
            <input type="text" id="theme-cv-search" class="theme-country-input" data-cv-search placeholder="Search by country name or ISO code…">
        </div>

        <div class="theme-country-list-wrapper" data-cv-list style="max-height:380px;overflow-y:auto;">
            {{-- rows injected by JS --}}
        </div>

        <div class="theme-modal-actions">
            <button type="button" class="theme-pill-btn" data-modal-close>Cancel</button>
            <button type="button" class="theme-pill-btn is-primary" data-cv-save>Save</button>
        </div>
    </div>
</x-tenant::modal>
