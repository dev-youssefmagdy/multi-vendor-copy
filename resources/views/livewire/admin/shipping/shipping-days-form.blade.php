{{--
    Shipping Days Rule - modal form partial.
    Rendered via modalContentView from ShippingDaysPage.
--}}

<x-paginated-searchable-select
    label="Country"
    search-model="countrySearch"
    :search-value="$countrySearch"
    next-action="countryPageNext"
    prev-action="countryPagePrev"
    :results="$this->countryResults"
    :current-page="$countryPage"
    :selected-value="$countryCode"
    set-model="countryCode"
    value-key="iso2"
    label-key="name"
    iso-key="iso2"
    emoji-key="flag_emoji"
    show-default
    default-value=""
    default-label="Global Default"
    default-icon="🌐"
    error-key="countryCode"
    placeholder="Search by country name or ISO code…"
/>

<div class="form-grid form-grid-2">
    <div>
        <label class="field-label">Min Days</label>
        <x-input type="number" wire:model.defer="minDays" :error="$errors->has('minDays')" />
        @error('minDays')<div class="field-error">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="field-label">Max Days</label>
        <x-input type="number" wire:model.defer="maxDays" :error="$errors->has('maxDays')" />
        @error('maxDays')<div class="field-error">{{ $message }}</div>@enderror
    </div>
</div>
