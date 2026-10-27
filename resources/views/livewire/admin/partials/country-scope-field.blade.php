{{-- Searchable country multi-select with an exclusive "All countries" option.
     Requires the ManagesCountryScope trait on the Livewire component. --}}
<div class="span-2" wire:key="country-scope-{{ $countryScopeVersion }}">
    <x-multi-select
        label="Countries"
        model="countryIds"
        :options="$countryOptions"
        :selected="$countryIds"
        exclusive="all"
        placeholder="Search countries…"
        hint="Choose “All countries” or pick the countries this applies to."
        error-key="countryIds"
    />
    @error('countryIds.*')<div class="field-error">{{ $message }}</div>@enderror
</div>
