{{--
Elora address add/edit modal (shared by checkout and profile).

Props:
$f            – Livewire property paths: full_name, phone, line1, city, state, country, country_id, is_default
                (+ optional label, email)
$title        – modal heading
$saveAction   – Livewire method called on save
$saveLabel    – save button text
$phone        – current phone value (the phone input is wire:ignore'd)
$countryId    – currently selected country id
$isDefault    – current "default address" state
$countries    – country collection (id, name, iso2, flag_emoji)
$cityRequired – optional, shows the required marker on City
--}}
@php
    $addrInput = 'w-full h-12 border border-[#D4D4D4] rounded-[4px] px-4 py-2 text-[16px] leading-5 font-[400] text-[#121212] placeholder-[#8F8F8F] outline-none bg-transparent focus:border-[#FF4D00] transition';
    $addrLabel = 'flex gap-1 text-[16px] leading-5 font-[400] text-[#121212]';
    $addrError = fn ($field) => $errors->has($field) ? 'border-[#DE1709]' : '';
@endphp
    
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-[#0c0c0e]/60 backdrop-blur-[2px] z-[1040]" wire:click="closeAddressModal"></div>

    {{-- Dialog --}}
    <div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-8 overflow-y-auto font-['Outfit']">
        <div class="relative w-full max-w-[934px] my-auto bg-[#FDFDFD] rounded-[28px] px-5 py-7 sm:px-[68px] flex flex-col items-end gap-[18px]" wire:click.stop>

            <button type="button" wire:click="closeAddressModal"
                class="absolute top-4 right-4 sm:top-5 sm:right-6 w-9 h-9 rounded-full flex items-center justify-center text-[#8F8F8F] hover:text-[#121212] transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Title --}}
            <h5 class="self-stretch text-[32px] leading-10 font-[500] text-[#121212]" style="font-size:32px;line-height:40px;font-weight:500;color:#121212">{{ $title }}</h5>

            {{-- Contact --}}
            <div class="self-stretch flex flex-col gap-4">
                <div class="grid grid-cols-1 {{ !empty($f['label']) ? 'sm:grid-cols-2' : '' }} gap-4 sm:gap-[31px]">
                    <div class="flex flex-col gap-3">
                        <label class="{{ $addrLabel }}">{{ __('Full name') }} <span class="text-[#DE1709]">*</span></label>
                        <input wire:model="{{ $f['full_name'] }}" type="text" placeholder="{{ __('Full name') }}"
                            class="{{ $addrInput }} {{ $addrError($f['full_name']) }}">
                        @error($f['full_name'])<p class="text-[12px] text-[#DE1709]">{{ $message }}</p>@enderror
                    </div>
                    @if (!empty($f['label']))
                    <div class="flex flex-col gap-3">
                        <label class="{{ $addrLabel }}">{{ __('Label') }}</label>
                        <input wire:model="{{ $f['label'] }}" type="text" placeholder="{{ __('e.g. Home, Work') }}" class="{{ $addrInput }}">
                    </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 {{ !empty($f['email']) ? 'sm:grid-cols-2' : '' }} gap-4 sm:gap-[31px]">
                    <div class="flex flex-col gap-3" wire:ignore>
                        <label class="{{ $addrLabel }}">{{ __('Phone') }}</label>
                        <input wire-event="{{ $f['phone'] }}" value="{{ $phone ?? '' }}" type="tel" data-phone-input placeholder="{{ __('Phone number') }}"
                            class="{{ $addrInput }} {{ $addrError($f['phone']) }}">
                        @error($f['phone'])<p class="text-[12px] text-[#DE1709]">{{ $message }}</p>@enderror
                    </div>
                    @if (!empty($f['email']))
                    <div class="flex flex-col gap-3">
                        <label class="{{ $addrLabel }}">{{ __('Email address') }}</label>
                        <input wire:model="{{ $f['email'] }}" type="email" placeholder="{{ __('Email address') }}"
                            class="{{ $addrInput }} {{ $addrError($f['email']) }}">
                        @error($f['email'])<p class="text-[12px] text-[#DE1709]">{{ $message }}</p>@enderror
                    </div>
                    @endif
                </div>
            </div>

            {{-- Select address on map (current location) --}}
            @php
                $countryIsoMap = $countries->filter(fn ($c) => !empty($c->iso2))->mapWithKeys(fn ($c) => [strtoupper($c->iso2) => $c->id])->toJson();
            @endphp
            <div class="self-stretch flex flex-col items-center gap-3"
                x-data="{
                    loading: false,
                    error: '',
                    notice: '',
                    isoMap: {{ $countryIsoMap }},
                    locate() {
                        this.error = '';
                        this.notice = '';
                        if (!window.isSecureContext) { this.fillFromIp(); return; }
                        if (!navigator.geolocation) { this.error = @js(__('Your browser does not support location.')); return; }
                        this.loading = true;
                        navigator.geolocation.getCurrentPosition(
                            pos => this.fill(pos.coords.latitude, pos.coords.longitude),
                            err => {
                                this.loading = false;
                                this.error = err.code === 1
                                    ? @js(__('Location access is blocked. Click the lock icon in the address bar, allow Location, then try again.'))
                                    : @js(__('Could not get your current location.'));
                            },
                            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                        );
                    },
                    async fillFromIp() {
                        // Browsers block precise geolocation on non-HTTPS pages, so fall back to an approximate IP lookup.
                        this.loading = true;
                        try {
                            const res = await fetch('https://ipwho.is/');
                            const a = await res.json();
                            if (!a.success) throw new Error('ip lookup failed');
                            const countryId = this.isoMap[(a.country_code || '').toUpperCase()] ?? null;
                            const wrap = document.getElementById('addr-country-wrap');
                            wrap && wrap.querySelector('[x-data]').dispatchEvent(new CustomEvent('country-select-set', { detail: { id: countryId } }));
                            $wire.set(@js($f['city']), a.city || '', false);
                            $wire.set(@js($f['state']), a.region || '', false);
                            $wire.set(@js($f['country']), a.country || '', false);
                            $wire.set(@js($f['country_id']), countryId, true);
                            this.notice = @js(__('Approximate location detected. Please complete the street address (exact location needs a secure HTTPS connection).'));
                        } catch (e) {
                            this.error = @js(__('Could not detect your location. Please enter the address manually.'));
                        } finally {
                            this.loading = false;
                        }
                    },
                    async fill(lat, lon) {
                        try {
                            const lang = document.documentElement.lang || 'en';
                            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=${lat}&lon=${lon}&accept-language=${encodeURIComponent(lang)}`, { headers: { 'Accept': 'application/json' } });
                            if (!res.ok) throw new Error('geocode failed');
                            const data = await res.json();
                            const a = data.address || {};
                            const street = [[a.house_number, a.road].filter(Boolean).join(' '), a.neighbourhood || a.suburb || a.quarter]
                                .filter(Boolean).join(', ') || (data.display_name || '').split(',').slice(0, 2).join(',').trim();
                            const city = a.city || a.town || a.village || a.municipality || a.county || '';
                            const state = a.state || a.region || a.state_district || '';
                            const countryId = this.isoMap[(a.country_code || '').toUpperCase()] ?? null;

                            const wrap = document.getElementById('addr-country-wrap');
                            wrap && wrap.querySelector('[x-data]').dispatchEvent(new CustomEvent('country-select-set', { detail: { id: countryId } }));

                            $wire.set(@js($f['line1']), street, false);
                            $wire.set(@js($f['city']), city, false);
                            $wire.set(@js($f['state']), state, false);
                            $wire.set(@js($f['country']), a.country || '', false);
                            $wire.set(@js($f['country_id']), countryId, true);
                        } catch (e) {
                            this.error = @js(__('Could not detect your address. Please enter it manually.'));
                        } finally {
                            this.loading = false;
                        }
                    }
                }">
                <button type="button" x-on:click="locate()" x-bind:disabled="loading"
                    style="background:#FF4D00;color:#fff;height:54px;border-radius:32px;padding:8px 32px;font-size:20px;line-height:25px;font-weight:500"
                    class="w-full sm:w-[376px] h-[54px] px-8 py-2 bg-[#FF4D00] text-white rounded-[32px] flex items-center justify-center gap-2 text-[20px] leading-[25px] font-[500] hover:bg-orange-600 transition disabled:opacity-70">
                    <svg class="w-6 h-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="#FDFDFD" stroke-width="1.8" stroke-linecap="round">
                        <circle cx="12" cy="12" r="6.5" />
                        <circle cx="12" cy="12" r="2.5" fill="#FDFDFD" />
                        <path d="M12 1.5v3M12 19.5v3M1.5 12h3M19.5 12h3" />
                    </svg>
                    <span x-show="!loading">{{ __('Select address on map') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Detecting location...') }}</span>
                    <svg class="w-4 h-4 shrink-0 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="#FDFDFD" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7" /></svg>
                </button>
                <p x-show="notice" x-cloak x-text="notice" class="text-[12px] text-[#8F8F8F] self-stretch text-center"></p>
                <p x-show="error" x-cloak x-text="error" class="text-[12px] text-[#DE1709] self-stretch text-center"></p>

                <div class="self-stretch flex items-center gap-5 h-[25px]">
                    <span class="flex-1 h-px bg-[#D4D4D4]"></span>
                    <span class="text-[20px] leading-[25px] font-[500] text-[#8F8F8F] text-center">{{ __('Or Enter address Manually') }}</span>
                    <span class="flex-1 h-px bg-[#D4D4D4]"></span>
                </div>
            </div>

            {{-- Address --}}
            <div class="self-stretch flex flex-col gap-4">
                <div class="flex flex-col gap-3">
                    <label class="{{ $addrLabel }}">{{ __('Street Address') }} <span class="text-[#DE1709]">*</span></label>
                    <input wire:model="{{ $f['line1'] }}" type="text" placeholder="{{ __('Street, building, apartment') }}"
                        class="{{ $addrInput }} {{ $addrError($f['line1']) }}">
                    @error($f['line1'])<p class="text-[12px] text-[#DE1709]">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-[31px]">
                    <div class="flex flex-col gap-3">
                        <label class="{{ $addrLabel }}">{{ __('City') }}@if (!empty($cityRequired)) <span class="text-[#DE1709]">*</span>@endif</label>
                        <input wire:model="{{ $f['city'] }}" type="text" placeholder="{{ __('City') }}" class="{{ $addrInput }} {{ $addrError($f['city']) }}">
                        @error($f['city'])<p class="text-[12px] text-[#DE1709]">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col gap-3">
                        <label class="{{ $addrLabel }}">{{ __('State / Region') }}</label>
                        <input wire:model="{{ $f['state'] }}" type="text" placeholder="{{ __('State') }}" class="{{ $addrInput }}">
                    </div>
                </div>

                <div class="flex flex-col gap-3" id="addr-country-wrap">
                    <label class="{{ $addrLabel }}">{{ __('Country') }} <span class="text-[#DE1709]">*</span></label>
                    @include('themes.elora.sections.country-select', [
                        'wireModel' => $f['country_id'],
                        'countries' => $countries,
                        'currentId' => $countryId,
                        'triggerClass' => 'h-12 border border-[#D4D4D4] rounded-[4px] px-4 py-2 text-[16px] leading-5 text-[#121212] bg-transparent',
                    ])
                    @error($f['country_id'])<p class="text-[12px] text-[#DE1709]">{{ $message }}</p>@enderror
                </div>
            </div>

            <label class="self-stretch flex items-center justify-between gap-3 cursor-pointer">
                <span class="text-[16px] leading-5 text-[#121212]">{{ __('Set as default address') }}</span>
                <span class="relative inline-flex shrink-0">
                    <input wire:model.live="{{ $f['is_default'] }}" type="checkbox" class="sr-only">
                    <span class="block w-9 h-5 rounded-full transition-colors duration-200"
                        style="background-color: {{ !empty($isDefault) ? '#FF4D00' : '#D4D4D4' }}"></span>
                    <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform duration-200"
                        style="transform: translateX({{ !empty($isDefault) ? '16px' : '0px' }})"></span>
                </span>
            </label>

            {{-- Save --}}
            <button type="button" wire:click="{{ $saveAction }}" wire:loading.attr="disabled" wire:target="{{ $saveAction }}"
                style="background:#FF4D00;color:#fff;height:54px;border-radius:32px;padding:8px 32px;font-size:20px;line-height:25px;font-weight:500"
                class="h-[54px] px-8 py-2 bg-[#FF4D00] text-white rounded-[32px] text-[20px] leading-[25px] font-[500] flex items-center justify-center gap-2 hover:bg-orange-600 transition disabled:opacity-60">
                <span wire:loading.remove wire:target="{{ $saveAction }}">{{ $saveLabel }}</span>
                <span wire:loading wire:target="{{ $saveAction }}">{{ __('Saving...') }}</span>
            </button>

        </div>
    </div>
