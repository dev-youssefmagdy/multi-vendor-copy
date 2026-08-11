{{--
    Delivery Popup Day - modal form partial.
    Rendered via modalContentView from DeliveryPopupPage.
    country_id = null means Global Default (fallback for all countries without specific data).
--}}

<x-paginated-searchable-select
    label="Country"
    search-model="countrySearch"
    :search-value="$countrySearch"
    next-action="countryPageNext"
    prev-action="countryPagePrev"
    :results="$this->countryResults"
    :current-page="$countryPage"
    :selected-value="$countryId"
    set-model="countryId"
    value-key="id"
    label-key="name"
    iso-key="iso2"
    emoji-key="flag_emoji"
    show-default
    default-value=""
    default-label="Global Default"
    default-icon="🌐"
    error-key="countryId"
    placeholder="Search by country name or ISO code..."
/>

<div class="form-grid form-grid-2">
    <div>
        <label class="field-label">Day Number</label>
        <x-input type="number" wire:model.defer="dayNumber" min="1" max="365"
            placeholder="e.g. 3" :error="$errors->has('dayNumber')" />
        @error('dayNumber')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="field-label">Percentage</label>
        <x-input type="number" wire:model.defer="percentage" min="0" max="100" step="0.01"
            placeholder="e.g. 25.00" :error="$errors->has('percentage')" />
        @error('percentage')<div class="field-error">{{ $message }}</div>@enderror
    </div>
</div>
