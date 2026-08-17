@php
    $symbol = $currentCurrency?->symbol ?? '$';
    $rate = (float) ($currentCurrency?->conversion_rate ?? 1.0);
@endphp

<div class="relative" id="cartIconWrapper" data-cart-dropdown-open="false">

    {{-- Cart trigger --}}
    <a id="cartDropdownToggle" href="{{ route('tenant.storefront.cart') }}"
        class="flex items-center gap-1 hover:text-yellow-light transition relative" aria-expanded="false"
        aria-controls="cart-dropdown-header">
        <svg class="w-[24px] h-[24px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span wire:key="cart-badge"
            class="cart-dropdown-count absolute -top-2 -right-1 bg-gray-darkest text-white text-[10px] w-[20px] h-[16px] flex items-center justify-center px-1.5 py-0.5 rounded-full border border-primary">
            {{ $cartCount }}
        </span>
        <span class="text-xs mt-2 hidden xl:block">{{ __('Cart') }}</span>
    </a>

    {{-- Dropdown panel --}}
    <div id="cart-dropdown-header"
        class="hidden lg:block absolute right-0 top-full mt-2 w-[360px] rounded-[18px] bg-white text-[#242424] border border-[#e5e5e5] shadow-[0_18px_40px_rgba(0,0,0,0.12)] z-[80] overflow-hidden opacity-0 invisible pointer-events-none translate-y-2 transition-all duration-200">

        @if ($cartCount === 0)
            <div class="bg-white px-6 py-10 text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#f4f4f4] text-[#242424]">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M3 3a1 1 0 000 2h1.18l1.273 6.365A2 2 0 007.414 13H14a2 2 0 001.94-1.515l1.007-4.028A1 1 0 0015.977 6H6.82l-.2-1H17a1 1 0 100-2H6.22l-.08-.4A2 2 0 004.18 1H3a1 1 0 00-1 1zm7 12a2 2 0 100 4 2 2 0 000-4zm-5 2a2 2 0 114 0 2 2 0 01-4 0zm10-2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                </div>
                <h4 class="text-[16px] font-bold text-[#242424] mb-2">{{ __('Your cart is empty') }}</h4>
                <p class="text-[13px] text-[#7a7a7a] mb-5">{{ __('Start adding products to see them here.') }}</p>
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="inline-flex items-center justify-center rounded-full bg-[#242424] px-5 py-3 text-[13px] font-bold text-white hover:bg-black transition">
                    {{ __('Continue Shopping') }}
                </a>
            </div>
        @else
            <div class="px-5 pt-4 pb-2">
                <h4 class="text-[14px] font-bold text-[#242424]">{{ __('Cart') }}
                    <span class="text-[#888] font-normal">({{ $cartCount }})</span>
                </h4>
            </div>

            <div class="max-h-[280px] overflow-y-auto px-5 flex flex-col gap-3 py-2">
                @foreach ($cartItems as $item)
                    @php
                        $img = $item['product']->centralProduct?->primary_image_url
                            ?? $item['product']->primary_image_url;
                        $name = $item['product']->translationValue('name') ?? $item['product']->slug;
                        $displayPrice = number_format($item['price'] * $rate, 2);
                    @endphp
                    <div class="flex items-center gap-3">
                        <div
                            class="w-[52px] h-[52px] rounded-lg bg-[#f8f8f8] overflow-hidden shrink-0 flex items-center justify-center">
                            @if ($img)
                                <img src="{{ $img }}" alt="{{ $name }}" class="w-full h-full object-contain mix-blend-multiply">
                            @else
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[12px] font-medium text-[#222] line-clamp-1">{{ $name }}</p>
                            <p class="text-[12px] text-[#888]">{{ $item['qty'] }} × {{ $symbol }}{{ $displayPrice }}</p>
                        </div>
                        <button wire:click="removeItem('{{ $item['key'] }}')"
                            class="text-[#bbb] hover:text-red-500 transition shrink-0 ml-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="px-5 py-4 border-t border-[#f0f0f0]">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[13px] text-[#666]">{{ __('Total') }}</span>
                    <span class="text-[15px] font-bold text-[#222]">
                        {{ $symbol }}{{ number_format($cartTotal * $rate, 2) }}
                    </span>
                </div>
                <a href="{{ route('tenant.storefront.cart') }}"
                    class="block w-full text-center rounded-full bg-[#242424] px-5 py-2.5 text-[13px] font-bold text-white hover:bg-black transition mb-2">
                    {{ __('View Cart') }}
                </a>
                <a href="{{ route('tenant.storefront.checkout') }}"
                    class="block w-full text-center rounded-full bg-primary px-5 py-2.5 text-[13px] font-bold text-white hover:opacity-90 transition">
                    {{ __('Checkout') }}
                </a>
            </div>
        @endif
    </div>
</div>