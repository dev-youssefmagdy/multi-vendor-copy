<div class="min-h-screen bg-gray-50 pt-16 pb-10 px-4">
    <div class="max-w-2xl mx-auto">

        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-rocket text-white text-xl"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900">{{ __('Set Up Your Store') }}</h1>
            <p class="text-gray-500 text-sm mt-1">{{ __('Just a few steps before you launch.') }}</p>
        </div>

        @php
            $stepLabels = [
                1 => __('Countries'),
                2 => __('Languages'),
                3 => __('Theme'),
                4 => __('Banners'),
                5 => __('Launch'),
            ];
        @endphp
        <div class="flex items-center justify-center gap-1 mb-8">
            @foreach($stepLabels as $n => $label)
                <div class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                            {{ $step === $n ? 'bg-primary text-white' : ($step > $n ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                            @if($step > $n)<i class="fas fa-check text-xs"></i>@else{{ $n }}@endif
                        </div>
                        <span class="text-xs mt-1 {{ $step === $n ? 'text-primary font-semibold' : 'text-gray-400' }} hidden sm:block">{{ $label }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="flex-1 h-px mx-2 {{ $step > $n ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">

            {{-- STEP 1: Countries --}}
            @if($step === 1)
                <h2 class="text-lg font-extrabold text-gray-900 mb-1">{{ __('Target Countries') }}</h2>
                <p class="text-sm text-gray-500 mb-5">{{ __('Select the countries you want to sell to. Free countries are pre-checked to get you started.') }}</p>

                <div class="space-y-2 max-h-72 overflow-y-auto border border-gray-200 rounded-xl p-3 mb-4">
                    @foreach($allCountries as $country)
                        <label class="flex items-center gap-3 p-2.5 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="checkbox"
                                value="{{ $country->id }}"
                                wire:model="countryIds"
                                class="w-4 h-4 rounded text-primary">
                            <span class="text-lg leading-none">{{ $country->flag_emoji }}</span>
                            <span class="text-sm text-gray-800 flex-1">{{ $country->translationValue('name') ?: $country->iso2 }}</span>
                            @if($country->is_free)
                                <span class="text-xs text-green-600 font-medium">{{ __('Free') }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>

                @error('countryIds') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

                <button wire:click="nextStep" type="button"
                    class="w-full btn-primary py-3.5 font-bold text-sm rounded-xl">
                    {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                </button>
            @endif

            {{-- STEP 2: Languages --}}
            @if($step === 2)
                <h2 class="text-lg font-extrabold text-gray-900 mb-1">{{ __('Store Languages') }}</h2>
                <p class="text-sm text-gray-500 mb-5">{{ __('Select the languages your store will support. You can add more later.') }}</p>

                <div class="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-xl p-3 mb-4">
                    @forelse($availableLanguages as $lang)
                        <label class="flex items-center gap-3 p-2.5 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                            <input type="checkbox"
                                value="{{ $lang->id }}"
                                wire:model="languageIds"
                                class="w-4 h-4 rounded text-primary">
                            <span class="text-sm text-gray-800 flex-1">
                                {{ $lang->native_name ?: $lang->name }}
                                @if($lang->is_default)
                                    <span class="text-xs text-gray-400 ms-1">({{ __('default') }})</span>
                                @endif
                            </span>
                            <span class="text-xs text-gray-400 uppercase">{{ $lang->code }}</span>
                        </label>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">{{ __('No languages available yet. Languages will be synced with your catalog.') }}</p>
                    @endforelse
                </div>

                <div class="flex gap-3">
                    <button wire:click="skipStep" type="button"
                        class="flex-1 py-3.5 font-bold text-sm rounded-xl border-2 border-gray-200 text-gray-500 hover:border-gray-300 transition-colors">
                        {{ __('Skip for now') }}
                    </button>
                    <button wire:click="nextStep" type="button"
                        class="flex-1 btn-primary py-3.5 font-bold text-sm rounded-xl">
                        {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                    </button>
                </div>
            @endif

            {{-- STEP 3: Theme --}}
            @if($step === 3)
                <h2 class="text-lg font-extrabold text-gray-900 mb-1">{{ __('Storefront Theme') }}</h2>
                <p class="text-sm text-gray-500 mb-5">{{ __('Pick the theme your storefront will use. You can change this later from the dashboard.') }}</p>

                @if($themes->isEmpty())
                    <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-500 mb-4">
                        <i class="fas fa-info-circle me-1.5 text-gray-400"></i>
                        {{ __('No themes are available yet. Your catalog sync will add themes automatically.') }}
                    </div>
                @else
                    <div class="space-y-2 mb-4">
                        @foreach($themes as $theme)
                            <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors
                                {{ (string) $activeThemeId === (string) $theme->id ? 'border-primary bg-primary/5' : 'border-gray-200 hover:bg-gray-50' }}">
                                <input type="radio" name="activeThemeId" value="{{ $theme->id }}"
                                    wire:model="activeThemeId"
                                    class="w-4 h-4 text-primary">
                                <span class="text-sm text-gray-800 font-medium">{{ $theme->name ?? $theme->slug }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="flex gap-3">
                    <button wire:click="skipStep" type="button"
                        class="flex-1 py-3.5 font-bold text-sm rounded-xl border-2 border-gray-200 text-gray-500 hover:border-gray-300 transition-colors">
                        {{ __('Skip for now') }}
                    </button>
                    <button wire:click="nextStep" type="button"
                        class="flex-1 btn-primary py-3.5 font-bold text-sm rounded-xl">
                        {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                    </button>
                </div>
            @endif

            {{-- STEP 4: Banners --}}
            @if($step === 4)
                <h2 class="text-lg font-extrabold text-gray-900 mb-1">{{ __('Homepage Banners') }}</h2>
                <p class="text-sm text-gray-500 mb-5">{{ __('Configure a banner for each country. You can upload images and set details later from the dashboard.') }}</p>

                <div class="space-y-4 mb-4">
                    @foreach($selectedCountries as $country)
                        @php $cid = (string) $country->id; @endphp
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-xl overflow-hidden">
                            <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-3 p-4 text-left hover:bg-gray-50 transition-colors">
                                <span class="text-lg">{{ $country->flag_emoji }}</span>
                                <span class="font-semibold text-sm text-gray-800 flex-1">
                                    {{ $country->translationValue('name') ?: $country->iso2 }}
                                </span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-90' : ''"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                            <div x-show="open" x-cloak class="px-4 pb-4 space-y-3 border-t border-gray-100">
                                <div class="pt-3">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Banner Title') }}</label>
                                    <input type="text" wire:model="banners.{{ $cid }}.title"
                                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2"
                                        placeholder="{{ __('e.g. Summer Collection 2025') }}">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Subtitle') }}</label>
                                    <input type="text" wire:model="banners.{{ $cid }}.subtitle"
                                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2"
                                        placeholder="{{ __('Short tagline') }}">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Button Text') }}</label>
                                        <input type="text" wire:model="banners.{{ $cid }}.button_text"
                                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2"
                                            placeholder="{{ __('Shop Now') }}">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('Button URL') }}</label>
                                        <input type="text" wire:model="banners.{{ $cid }}.url"
                                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2"
                                            placeholder="/products">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-3">
                    <button wire:click="skipStep" type="button"
                        class="flex-1 py-3.5 font-bold text-sm rounded-xl border-2 border-gray-200 text-gray-500 hover:border-gray-300 transition-colors">
                        {{ __('Skip for now') }}
                    </button>
                    <button wire:click="nextStep" type="button"
                        class="flex-1 btn-primary py-3.5 font-bold text-sm rounded-xl">
                        {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                    </button>
                </div>
            @endif

            {{-- STEP 5: Launch --}}
            @if($step === 5)
                <div class="text-center py-4">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <i class="fas fa-rocket text-green-600 text-2xl"></i>
                    </div>
                    <h2 class="text-xl font-extrabold text-gray-900 mb-2">{{ __("You're all set!") }}</h2>
                    <p class="text-gray-500 text-sm mb-6 leading-relaxed">
                        {{ __('Your store is configured and ready. Click the button below to launch your storefront and go to your dashboard.') }}
                    </p>
                    <button wire:click="launch" type="button"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                        class="w-full btn-primary py-4 font-bold text-base rounded-xl mb-3">
                        <span wire:loading.remove wire:target="launch">
                            <i class="fas fa-rocket me-2"></i> {{ __('Store Ready for Launch') }}
                        </span>
                        <span wire:loading wire:target="launch">
                            <i class="fas fa-spinner fa-spin me-2"></i> {{ __('Launching…') }}
                        </span>
                    </button>
                    <p class="text-xs text-gray-400">
                        {{ __('You can complete remaining setup steps from inside your dashboard.') }}
                    </p>
                </div>
            @endif

        </div>
    </div>
</div>
