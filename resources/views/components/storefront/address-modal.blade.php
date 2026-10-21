{{--
Shared "Add/Edit Address" modal, used by every storefront theme (checkout and
profile pages). Only fields that map to a real backend property are wired via
wire:model / wire-event; every other field in the new design (first/last name
split, WhatsApp toggle, map picker, national address, additional address) is
frontend-only and is not persisted — see the per-field "FOR DESIGN PURPOSE"
comments below.

Props:
$theme            – 'elora' | 'ecommet' | 'souqify', used to resolve the
                     theme's own country-select partial
$show             – bool, modal visibility
$title            – string, modal header title
$closeMethod      – wire:click target that closes the modal
$saveMethod       – wire:click target that saves the address
$savingLabel      – label on the save button while idle
$savingBusyLabel  – label on the save button while saving
$fullNameModel    – dot-notation wire:model path for the full name field
$fullNameValue    – current full name value (split into first/last for display)
$phoneEventModel  – wire-event target for the phone input
$phoneValue       – current phone value
$line1Model       – wire:model path for the street address field
$cityModel        – wire:model path for the city field
$cityValue        – current city value
$countryIdModel   – wire:model path for the country id field
$countryIdValue   – current country id value
$countries        – collection of countries for the country-select partial
$isDefaultModel   – wire:model path for "set as default" (null to hide it)
$labelModel       – wire:model path for an address label field (null to hide it)
--}}
@props([
    'theme',
    'show' => false,
    'title',
    'colorVar' => null,
    'closeMethod' => 'closeAddressModal',
    'saveMethod' => 'saveNewAddress',
    'savingLabel' => null,
    'savingBusyLabel' => null,
    'fullNameModel',
    'fullNameValue' => '',
    'phoneEventModel',
    'phoneValue' => '',
    'line1Model',
    'cityModel',
    'cityValue' => '',
    'countryIdModel',
    'countryIdValue' => null,
    'countries',
    'isDefaultModel' => null,
    'labelModel' => null,
])

@php
    $savingLabel ??= __('Save Address');
    $savingBusyLabel ??= __('Saving...');

    $nameParts = array_pad(explode(' ', trim($fullNameValue), 2), 2, '');
    [$firstNameInit, $lastNameInit] = $nameParts;

    // FOR DESIGN PURPOSE
    $dummyCities = ['Riyadh', 'Jeddah', 'Mecca', 'Medina', 'Dammam', 'Khobar', 'Taif', 'Abha'];
@endphp

@if ($show)
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-[#0c0c0e]/60 backdrop-blur-[2px] z-[1040]" wire:click="{{ $closeMethod }}"></div>

    {{-- Dialog --}}
    <div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-8 overflow-y-auto">
        <div class="relative w-full max-w-[560px] my-auto bg-white rounded-[20px] shadow-[0_24px_60px_-12px_rgba(16,24,40,0.25)] ring-1 ring-black/[0.06]"
            @if ($colorVar) style="--color-primary: var({{ $colorVar }});" @endif
            x-data="{
                firstName: @js($firstNameInit),
                lastName: @js($lastNameInit),
                whatsappSame: 'yes',
                syncFullName() {
                    $wire.set('{{ $fullNameModel }}', (this.firstName + ' ' + this.lastName).trim());
                }
            }"
            wire:click.stop>

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4 px-6 sm:px-7 pt-6 pb-5 border-b border-[#eeeeee]">
                <h5 class="text-[19px] font-[700] text-[#101828] leading-tight">{{ $title }}</h5>
                <button type="button" wire:click="{{ $closeMethod }}"
                    class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-[#999] hover:text-[#242424] hover:bg-[#f5f5f5] transition">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 sm:px-7 py-6 space-y-5 max-h-[72vh] overflow-y-auto">

                {{-- Country / Region --}}
                <div>
                    <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Country / Region') }}
                        <span class="text-[#dc2626]">*</span></label>
                    @include('themes.' . $theme . '.sections.country-select', [
                        'wireModel' => $countryIdModel,
                        'countries' => $countries,
                        'currentId' => $countryIdValue,
                    ])
                    @error($countryIdModel) <p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p> @enderror
                </div>

                {{-- First / Last name (FOR DESIGN PURPOSE: kept in sync into the real full-name field via Alpine) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('First name') }}
                            <span class="text-[#dc2626]">*</span></label>
                        <input type="text" x-model="firstName" x-on:input="syncFullName()"
                            placeholder="{{ __('First name') }}"
                            class="w-full border @error($fullNameModel) border-[#dc2626] @else border-[#e0e0e0] @enderror rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                    </div>
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Last name') }}
                            <span class="text-[#dc2626]">*</span></label>
                        <input type="text" x-model="lastName" x-on:input="syncFullName()"
                            placeholder="{{ __('Last name') }}"
                            class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                    </div>
                    @error($fullNameModel) <p class="text-[11px] text-[#dc2626] mt-1.5 sm:col-span-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Phone --}}
                <div>
                    <div wire:ignore>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Phone Number') }}
                            <span class="text-[#dc2626]">*</span></label>
                        <input wire-event="{{ $phoneEventModel }}" value="{{ $phoneValue }}" type="tel"
                            data-phone-input placeholder="{{ __('9-digit number starting with 5') }}"
                            class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                    </div>
                    <p class="text-[11.5px] text-[#9a9a9a] mt-1.5">{{ __('Need correct phone number for delivery') }}</p>
                </div>

                {{-- Is WhatsApp number same with this? (FOR DESIGN PURPOSE: UI only, not persisted) --}}
                <div>
                    <p class="text-[12.5px] font-medium text-[#4a4a4a] mb-2">
                        {{ __('Is WhatsApp number same with this?') }} <span class="text-[#dc2626]">*</span>
                    </p>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="yes" x-model="whatsappSame"
                                class="w-4 h-4 accent-[var(--color-primary,#FF6A1A)]">
                            <span class="text-[13px] text-[#333]">{{ __('Yes') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="no" x-model="whatsappSame"
                                class="w-4 h-4 accent-[var(--color-primary,#FF6A1A)]">
                            <span class="text-[13px] text-[#333]">{{ __('No') }}</span>
                        </label>
                    </div>
                    <div x-show="whatsappSame === 'no'" x-transition class="mt-3">
                        <input type="text" placeholder="{{ __('WhatsApp number') }}"
                            class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                    </div>
                </div>

                {{-- Map picker (FOR DESIGN PURPOSE: visual only, no geocoding wired up) --}}
                <button type="button" title="{{ __('Coming soon') }}"
                    class="w-full flex items-center justify-center gap-2 bg-[var(--color-primary,#FF6A1A)] text-white rounded-[10px] py-3.5 text-[13.5px] font-[700] shadow-sm hover:opacity-90 transition cursor-default">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('Select address on map') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                {{-- Divider --}}
                <div class="flex items-center gap-3">
                    <div class="h-px bg-[#f0f0f0] flex-1"></div>
                    <span class="text-[11px] font-medium text-[#a3a3a3] whitespace-nowrap">{{ __('Or Enter address Manually') }}</span>
                    <div class="h-px bg-[#f0f0f0] flex-1"></div>
                </div>

                <div class="border border-dashed border-[var(--color-primary,#FF6A1A)]/40 rounded-[14px] p-4 space-y-4">
                    {{-- City --}}
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('City') }}
                            <span class="text-[#dc2626]">*</span></label>
                        <div class="relative">
                            <select wire:model="{{ $cityModel }}"
                                class="w-full appearance-none border @error($cityModel) border-[#dc2626] @else border-[#e0e0e0] @enderror rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                                <option value="">{{ __('select') }}</option>
                                {{-- FOR DESIGN PURPOSE --}}
                                @foreach ($dummyCities as $cityOption)
                                    <option value="{{ $cityOption }}" @selected($cityValue === $cityOption)>{{ $cityOption }}</option>
                                @endforeach
                                @if ($cityValue && !in_array($cityValue, $dummyCities, true))
                                    <option value="{{ $cityValue }}" selected>{{ $cityValue }}</option>
                                @endif
                            </select>
                            <svg class="w-4 h-4 text-[#999] absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        @error($cityModel) <p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    {{-- National address (FOR DESIGN PURPOSE: UI only, not persisted) --}}
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('National address') }}</label>
                        <input type="text" placeholder="{{ __('e.g. ABCD1234') }}"
                            class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                    </div>

                    {{-- Street --}}
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">
                            {{ __('Street name / Street number / Landmark') }} <span class="text-[#dc2626]">*</span>
                        </label>
                        <input wire:model="{{ $line1Model }}" type="text" placeholder="{{ __('Such as Olaya St, Mobily CS') }}"
                            class="w-full border @error($line1Model) border-[#dc2626] @else border-[#e0e0e0] @enderror rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                        @error($line1Model) <p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p> @enderror
                    </div>

                    {{-- Additional / Detailed address (FOR DESIGN PURPOSE: UI only, not persisted) --}}
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">
                            {{ __('Additional / Detailed address') }}
                            <span class="text-[11px] font-normal text-[#a3a3a3]">({{ __('optional') }})</span>
                        </label>
                        <input type="text" placeholder="{{ __('Building no, Floor no, Flat no or Landmark') }}"
                            class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                    </div>
                </div>

                @if ($labelModel)
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Label') }}
                            <span class="text-[11px] font-normal text-[#a3a3a3]">({{ __('optional') }})</span></label>
                        <input wire:model="{{ $labelModel }}" type="text" placeholder="{{ __('e.g. Home, Work') }}"
                            class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-[var(--color-primary,#FF6A1A)] focus:ring-4 focus:ring-[var(--color-primary,#FF6A1A)]/10 transition bg-white">
                    </div>
                @endif

                @if ($isDefaultModel)
                    <label class="flex items-center justify-between gap-3 cursor-pointer rounded-[12px] border border-[#ececec] px-4 py-3.5 hover:border-[var(--color-primary,#FF6A1A)]/40 hover:bg-[var(--color-primary,#FF6A1A)]/[0.03] transition">
                        <span class="text-[13px] text-[#333] font-medium">{{ __('Set as default address') }}</span>
                        <span class="relative inline-flex shrink-0">
                            <input wire:model.live="{{ $isDefaultModel }}" type="checkbox" class="peer sr-only">
                            <span class="w-9 h-5 rounded-full bg-[#dcdcdc] peer-checked:bg-[var(--color-primary,#FF6A1A)] transition-colors"></span>
                            <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                @endif

            </div>

            {{-- Footer --}}
            <div class="px-6 sm:px-7 py-5 border-t border-[#eeeeee] bg-[#fafafa] rounded-b-[20px]">
                <button type="button" wire:click="{{ $saveMethod }}" wire:loading.attr="disabled"
                    wire:target="{{ $saveMethod }}"
                    class="w-full bg-[var(--color-primary,#FF6A1A)] text-white rounded-[10px] py-3.5 text-[13.5px] font-[700] shadow-sm hover:opacity-90 transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="{{ $saveMethod }}">{{ $savingLabel }}</span>
                    <span wire:loading wire:target="{{ $saveMethod }}">{{ $savingBusyLabel }}</span>
                </button>
            </div>

        </div>
    </div>
@endif
