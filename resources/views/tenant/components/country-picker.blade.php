@props([
    'triggerClass' => '',
    'label' => 'Select your country',
])

{{--
    "Select your country" dropdown: any trigger button (the slot is its inner
    markup; put data-country-label on the text to replace) opens a searchable
    list of countries with flags. The pick is remembered in the browser and
    shared by every picker in the panel (components/country-picker.js), and
    fires `tenant:country-change` on document with { code, name }.

        <x-tenant::country-picker trigger-class="db-country-select">
            <span data-country-label>Select your country</span> <svg …chevron… />
        </x-tenant::country-picker>
--}}

@php
    // FOR DESIGN PURPOSE
    // Markets shown in the picker (codes match x-tenant::flag artwork).
    // BACKEND TODO: load the tenant's markets and persist the chosen one.
    $countries = [
        'sa' => 'Saudi Arabia', 'ae' => 'UAE', 'qa' => 'Qatar', 'eg' => 'Egypt', 'ma' => 'Morocco',
        'iq' => 'Iraq', 'fr' => 'France', 'gb' => 'United Kingdom', 'us' => 'USA',
    ];
    $listId = 'country-list-'.\Illuminate\Support\Str::random(6);
@endphp

<div class="t-country-picker" data-country-picker>
    <button type="button" {{ $attributes->merge(['class' => $triggerClass]) }}
        data-country-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $listId }}" aria-label="{{ $label }}">
        {{ $slot }}
    </button>

    <div class="t-country-panel" data-country-panel hidden>
        <label class="t-country-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8.75"/><path d="M17.5 17.5l4.25 4.25"/></svg>
            <input type="search" placeholder="Search country" aria-label="Search country" data-country-search>
        </label>
        <ul class="t-country-list" id="{{ $listId }}" role="listbox" aria-label="{{ $label }}">
            @foreach($countries as $code => $name)
                <li role="option" tabindex="-1" aria-selected="false" data-code="{{ $code }}" data-name="{{ $name }}">
                    <x-tenant::flag :code="$code" class="t-country-flag" />
                    <span>{{ $name }}</span>
                    <svg class="t-country-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                </li>
            @endforeach
            <li class="t-country-empty" data-country-empty hidden>No countries found.</li>
        </ul>
    </div>
</div>
