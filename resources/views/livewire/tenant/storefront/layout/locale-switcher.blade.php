{{-- Locale Switcher: inline trigger + modal overlay --}}
<div>
    @unless($hideTrigger)
    {{-- ── Desktop trigger ── --}}
    <button type="button"
        wire:click="openModal('currency')"
        class="hidden lg:flex items-center gap-2 text-charcoal hover:text-main transition-colors focus:outline-none">
        @if ($currentLanguage?->image_url)
            <img src="{{ $currentLanguage->image_url }}" alt="{{ $currentLanguage->name }}"
                class="w-[28px] h-auto rounded-sm object-cover flex-shrink-0">
        @endif
        <div class="flex flex-col items-start leading-none">
            <span class="text-[11px] text-[#888]">{{ strtoupper($currentLanguage?->code ?? 'EN') }}/</span>
            <div class="flex items-center gap-0.5 mt-0.5">
                <span class="text-[12px] font-semibold text-charcoal">{{ $currentCurrency?->code ?? 'USD' }}</span>
                <svg class="w-3 h-3 text-charcoal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
    </button>

    {{-- ── Mobile trigger ── --}}
    <button type="button"
        wire:click="openModal('currency')"
        class="lg:hidden lang-currency-trigger flex flex-col items-center justify-center leading-[1] text-gray-darkest cursor-pointer">
        <div class="flex items-end gap-[1px]">
            <span class="text-[11px] font-bold">{{ strtoupper($currentLanguage?->code ?? 'En') }}/</span>
        </div>
        <div class="flex items-center gap-[2px]">
            <span class="text-[11px] font-bold">{{ $currentCurrency?->code ?? 'USD' }}</span>
            <svg class="w-2 h-2 text-gray-darkest" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>
    @endunless

    {{-- ── Modal overlay (teleported to <body> so it isn't hidden by ancestors like
         .navbar, which gets display:none on mobile viewports) ── --}}
    @if ($open)
        <template x-teleport="body">
        <div class="fixed inset-0 z-[200] flex items-center justify-center p-4"
            wire:click.self="close">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-[16px] font-bold text-[#222]">{{ __('Currency & Country') }}</h3>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-gray-100">
                    <button wire:click="$set('tab', 'currency')"
                        class="flex-1 py-3 text-[13px] font-semibold transition {{ $tab === 'currency' ? 'text-primary border-b-2 border-primary' : 'text-[#888] hover:text-[#333]' }}">
                        {{ __('Currency') }}
                    </button>
                    <button wire:click="$set('tab', 'language')"
                        class="flex-1 py-3 text-[13px] font-semibold transition {{ $tab === 'language' ? 'text-primary border-b-2 border-primary' : 'text-[#888] hover:text-[#333]' }}">
                        {{ __('Language') }}
                    </button>
                </div>

                {{-- Currency list --}}
                @if ($tab === 'currency')
                    <div class="px-4 py-4 max-h-80 overflow-y-auto grid grid-cols-2 gap-2">
                        @forelse ($currencies as $currency)
                            <button wire:click="switchCurrency('{{ $currency->code }}')"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border transition text-left
                                    {{ ($currentCurrency?->code === $currency->code) ? 'border-primary bg-primary/5 text-primary' : 'border-[#eee] hover:border-gray-300 text-[#333]' }}">
                                <span class="text-[16px] font-bold">{{ $currency->symbol }}</span>
                                <div class="flex flex-col leading-snug">
                                    <span class="text-[13px] font-semibold">{{ $currency->code }}</span>
                                    <span class="text-[11px] text-[#999]">{{ $currency->name }}</span>
                                </div>
                                @if ($currentCurrency?->code === $currency->code)
                                    <svg class="w-4 h-4 ml-auto text-primary shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @empty
                            <p class="col-span-2 text-center text-[13px] text-[#aaa] py-8">{{ __('No currencies configured.') }}</p>
                        @endforelse
                    </div>
                @endif

                {{-- Language list --}}
                @if ($tab === 'language')
                    <div class="px-4 py-4 max-h-80 overflow-y-auto grid grid-cols-2 gap-2">
                        @forelse ($languages as $language)
                            <button wire:click="switchLanguage('{{ $language->code }}')"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border transition text-left
                                    {{ ($currentLanguage?->code === $language->code) ? 'border-primary bg-primary/5 text-primary' : 'border-[#eee] hover:border-gray-300 text-[#333]' }}">
                                @if ($language->image_url)
                                    <img src="{{ $language->image_url }}" alt="{{ $language->name }}"
                                        class="w-7 h-5 object-cover rounded-sm shrink-0">
                                @else
                                    <span class="w-7 h-5 bg-gray-200 rounded-sm flex items-center justify-center text-[9px] font-bold shrink-0">
                                        {{ strtoupper($language->code) }}
                                    </span>
                                @endif
                                <div class="flex flex-col leading-snug">
                                    <span class="text-[13px] font-semibold">{{ $language->native_name ?? $language->name }}</span>
                                    <span class="text-[11px] text-[#999]">{{ strtoupper($language->code) }}</span>
                                </div>
                                @if ($currentLanguage?->code === $language->code)
                                    <svg class="w-4 h-4 ml-auto text-primary shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </button>
                        @empty
                            <p class="col-span-2 text-center text-[13px] text-[#aaa] py-8">{{ __('No languages configured.') }}</p>
                        @endforelse
                    </div>
                @endif

            </div>
        </div>
        </template>
    @endif
</div>
