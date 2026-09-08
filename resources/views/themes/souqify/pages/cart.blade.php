@php
    $currency = $currentCurrency ?? null;
    $symbol = data_get($currency, 'symbol', '$');
    $rate = (float) data_get($currency, 'conversion_rate', 1.0);
    $shippingPct = $shippingThreshold > 0
        ? min(100, (int) round($cartWeight / $shippingThreshold * 100))
        : 0;
    $weightLabel = fn (int $g) => $g >= 1000 ? number_format($g / 1000, 2) . __('kg') : $g . __('g');
@endphp

<div>
    <!-- =========== BREADCRUMB =========== -->
    <div class="hidden lg:block max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-2">
        <nav class="flex items-center gap-2 text-sm text-neutral-500">
            <a href="{{ route('tenant.home') }}" class="hover:text-blue-700 transition">{{ __('Home') }}</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-blue-700 font-medium">{{ __('My Cart') }}</span>
        </nav>
    </div>

    <!-- =========== FREE SHIPPING PROGRESS BAR =========== -->
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center gap-4 mb-8 lg:hidden">
            <button class="w-12 h-12 flex justify-center items-center rounded-full border hover:bg-gray-200"
                aria-label="{{ __('Back') }}" onclick="window.history.back()">
                <svg width=" 8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.66797 0.666992L0.667968 6.66699L6.66797 12.667" stroke="#0A0A0A" stroke-width="1.33333"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <h1 class="text-[24px] font-bold text-[#242424]">{{ __('My Cart') }}</h1>
        </div>
        @if($shippingThreshold > 0)
        <div class="relative h-12 bg-zinc-300 rounded-full overflow-hidden">
            <div class="absolute inset-y-0 left-0 bg-orange-600 rounded-full transition-all"
                style="width: {{ $shippingPct }}%"></div>
            <div class="relative z-10 h-full flex items-center justify-between px-5 sm:px-7 text-base font-medium">
                <span class="text-white">{{ $cartWeight }} {{ __('g') }}</span>
                <div class="flex items-center gap-2 text-white">
                    {{-- <span class="hidden sm:inline">( {{ $cartWeight }} g )</span> --}}
                    @if ($cartWeight >= $shippingThreshold)
                        <span class="">{{ __('Free Shipping!') }}</span>
                    @else
                        <span class="hidden sm:inline">{{ number_format($shippingThreshold - $cartWeight) }}{{ __('g') }}
                            {{ __('to free shipping') }}</span>
                    @endif
                    <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                </div>
                <span class="text-neutral-500">{{ number_format($shippingThreshold, 0) }} {{ __('g') }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- =========== CART CONTENT =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Cart items -->
            <div class="lg:col-span-2 flex flex-col gap-4">
                @forelse($cartItems as $item)
                    @php
                        $itemProduct = $item['product'];
                        $itemVariant = $item['variant'];
                        $itemName = $itemProduct->translationValue('name') ?? $itemProduct->slug;
                        $itemMeta = $itemVariant?->display_label ?? '';
                        $itemImg = $itemVariant?->thumbnail_url
                            ?? $itemProduct->centralProduct?->primary_image_url
                            ?? $itemProduct->primary_image_url
                            ?? null;
                        $itemWeight = (int) ($itemProduct->centralProduct?->weight_grams ?? $itemProduct?->weight_grams ?? 0);
                        $itemPrice = number_format($item['subtotal'] * $rate, 2);
                    @endphp
                    <div class="bg-white rounded-2xl p-4 sm:p-6 flex gap-3 sm:gap-6 items-stretch">
                        <div class="w-24 h-24 sm:w-40 sm:h-40 product-img-bg rounded-lg overflow-hidden shrink-0">
                            @if ($itemImg)
                                <img loading="lazy" src="{{ $itemImg }}" alt="{{ $itemName }}" class="w-full h-full object-cover" />
                            @else
                                <div class="w-full h-full flex items-center justify-center text-4xl text-neutral-300">🛍️</div>
                            @endif
                        </div>
                        <div class="flex-1 flex flex-col justify-between min-w-0">
                            <div class="flex justify-between items-start gap-3">
                                <div class="min-w-0">
                                    <h3 class="text-base sm:text-xl font-medium text-neutral-800 truncate">{{ $itemName }}
                                    </h3>
                                    @if ($itemMeta)
                                        <p class="text-xs sm:text-sm text-neutral-400 mt-1">{{ $itemMeta }}</p>
                                    @endif
                                    @if ($itemWeight > 0)
                                        <p class="text-base sm:text-xl font-medium text-slate-900 mt-2 sm:mt-3">
                                            {{ $itemWeight }}g
                                        </p>
                                    @endif
                                    @if($item['is_out_of_stock'] ?? false)
                                        <div class="text-red-600 text-xs font-semibold mt-1 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ __('This item is currently out of stock') }}
                                        </div>
                                    @endif
                                </div>
                                <p class="text-lg sm:text-2xl font-medium text-blue-700 shrink-0">
                                    {{ $symbol }}{{ $itemPrice }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between gap-3 mt-3 sm:mt-0">
                                <div
                                    class="bg-violet-50 rounded-full px-3 sm:px-4 py-1.5 sm:py-2 flex items-center gap-3 sm:gap-4">
                                    <button wire:click="updateQty('{{ $item['key'] }}', {{ max(1, $item['qty'] - 1) }})"
                                        class="w-6 h-6 flex items-center justify-center text-stone-400 hover:text-blue-700 transition"
                                        aria-label="{{ __('Decrease') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 12H4" />
                                        </svg>
                                    </button>
                                    <span
                                        class="text-base sm:text-lg font-medium text-neutral-800 min-w-[16px] text-center">{{ $item['qty'] }}</span>
                                    <button wire:click="updateQty('{{ $item['key'] }}', {{ $item['qty'] + 1 }})"
                                        class="w-6 h-6 flex items-center justify-center text-blue-700 hover:text-blue-800 transition"
                                        aria-label="{{ __('Increase') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                                <button wire:click="removeFromCart('{{ $item['key'] }}')"
                                    class="flex items-center gap-1.5 text-neutral-400 hover:text-red-600 transition text-xs sm:text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                                    </svg>
                                    <span class="hidden lg:inline">{{ __('Remove') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl p-12 text-center">
                        <svg class="w-16 h-16 mx-auto text-neutral-300 mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <h3 class="text-xl font-medium mb-2">{{ __('Your cart is empty') }}</h3>
                        <p class="text-neutral-500 mb-6">{{ __("Looks like you haven't added anything yet.") }}</p>
                        <a href="{{ route('tenant.storefront.category') }}"
                            class="inline-block px-6 py-3 bg-blue-700 hover:bg-blue-800 text-white rounded-lg font-medium transition">
                            {{ __('Continue Shopping') }}
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- Order summary -->
            <aside class="lg:col-span-1">
                <div
                    class="bg-white rounded-2xl border border-stone-300 p-6 lg:p-8 lg:sticky lg:top-28 flex flex-col gap-6">
                    <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Order Summary') }}</h2>

                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500 text-base sm:text-lg">{{ __('Subtotal') }}</span>
                            <span
                                class="text-neutral-700 text-base sm:text-lg">{{ $symbol }}{{ number_format($cartFinalTotal * $rate, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500 text-base sm:text-lg">{{ __('Shipping Estimate') }}</span>
                            <span class="text-neutral-700 text-base sm:text-lg">
                                @if (count($cartItems) === 0)
                                    —
                                @elseif ($shippingCost == 0)
                                    {{ __('Free') }}
                                @else
                                    {{ $symbol }}{{ number_format($shippingCost * $rate, 2) }}
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500 text-base sm:text-lg">{{ __('Tax') }}</span>
                            <span
                                class="text-neutral-700 text-base sm:text-lg">{{ $symbol }}{{ number_format($cartTax * $rate, 2) }}</span>
                        </div>
                        @if ($appliedCoupon && $cartDiscount > 0)
                            <div class="flex justify-between items-center text-emerald-600">
                                <span class="text-base sm:text-lg">{{ __('Discount') }} ({{ $appliedCoupon->code }})</span>
                                <span
                                    class="text-base sm:text-lg">−{{ $symbol }}{{ number_format($cartDiscount * $rate, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center pt-4 border-t border-stone-300">
                            <span class="text-slate-900 text-lg sm:text-xl">{{ __('Total') }}</span>
                            <span
                                class="text-blue-700 text-2xl sm:text-3xl font-medium">{{ $symbol }}{{ number_format($orderTotal * $rate, 2) }}</span>
                        </div>
                    </div>

                    <!-- Promo code -->
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-gray-700">{{ __('Have a promo code?') }}</label>
                        @if ($appliedCoupon)
                            <div
                                class="flex items-center justify-between gap-2 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3">
                                <span class="text-emerald-700 text-sm font-medium">{{ $appliedCoupon->code }}</span>
                                <button wire:click="removeCoupon"
                                    class="text-red-500 hover:text-red-700 text-xs font-medium transition">{{ __('Remove') }}</button>
                            </div>
                        @else
                            <div class="flex gap-2">
                                <input type="text" wire:model="couponCode" placeholder="{{ __('Enter code') }}"
                                    class="flex-1 h-12 px-4 bg-gray-100 rounded-lg text-sm placeholder:text-gray-400 outline-none focus:ring-2 focus:ring-blue-300" />
                                <button wire:click="applyCoupon"
                                    class="h-12 px-5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-medium transition">{{ __('Apply') }}</button>
                            </div>
                            @error('coupon')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>

                    <!-- Checkout button -->
                    <div
                        class="fixed lg:relative bottom-0 left-0 lg:bottom-auto lg:left-auto w-screen lg:w-auto z-50 lg:z-0 bg-white lg:bg-auto py-4 px-2 lg:p-0 flex flex-col gap-3">
                        <a href="{{ route('tenant.storefront.checkout') }}"
                            class="h-14 bg-blue-700 hover:bg-blue-800 text-white rounded-lg font-medium text-base flex items-center justify-center gap-2 transition">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M1.46 3.15024H16.674C18.052 3.15024 19.047 4.42024 18.669 5.69824L17.015 11.2982C16.76 12.1582 15.946 12.7502 15.02 12.7502H5.862C4.935 12.7502 4.12 12.1572 3.866 11.2982L1.46 3.15024ZM1.46 3.15024L0.75 0.750244M14.25 18.7502C14.6478 18.7502 15.0294 18.5922 15.3107 18.3109C15.592 18.0296 15.75 17.6481 15.75 17.2502C15.75 16.8524 15.592 16.4709 15.3107 16.1896C15.0294 15.9083 14.6478 15.7502 14.25 15.7502C13.8522 15.7502 13.4706 15.9083 13.1893 16.1896C12.908 16.4709 12.75 16.8524 12.75 17.2502C12.75 17.6481 12.908 18.0296 13.1893 18.3109C13.4706 18.5922 13.8522 18.7502 14.25 18.7502ZM6.25 18.7502C6.64782 18.7502 7.02936 18.5922 7.31066 18.3109C7.59196 18.0296 7.75 17.6481 7.75 17.2502C7.75 16.8524 7.59196 16.4709 7.31066 16.1896C7.02936 15.9083 6.64782 15.7502 6.25 15.7502C5.85218 15.7502 5.47064 15.9083 5.18934 16.1896C4.90804 16.4709 4.75 16.8524 4.75 17.2502C4.75 17.6481 4.90804 18.0296 5.18934 18.3109C5.47064 18.5922 5.85218 18.7502 6.25 18.7502Z"
                                    stroke="#FDFDFD" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>

                            {{ __('Proceed to Checkout') }}
                        </a>
                        <p class="text-center text-xs text-gray-600">{{ __('Secure SSL Checkout • Free Returns') }}</p>
                    </div>

                    <!-- Trust badges -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-zinc-100 rounded-2xl p-4 flex flex-col gap-1">
                            <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                            <p class="text-sm font-semibold text-neutral-800">{{ __('Free Delivery') }}</p>
                            <p class="text-xs text-neutral-500">{{ __('when you add more :weight to cart', ['weight' => $weightLabel($shippingThreshold)]) }}</p>
                        </div>
                        {{-- <div class="bg-zinc-100 rounded-2xl p-4 flex flex-col gap-1">
                            <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <p class="text-sm font-semibold text-neutral-800">{{ __('2-Year Warranty') }}</p>
                            <p class="text-xs text-neutral-500">{{ __('Full coverage protection') }}</p>
                        </div> --}}
                    </div>

                    <!-- Payment methods -->
                    <div class="flex items-center justify-center gap-3 flex-wrap">
                        <img loading="lazy" src="{{ asset('souqify/assets/images/pay-visa.svg') }}" alt="">
                        <img loading="lazy" src="{{ asset('souqify/assets/images/pay-tabby.svg') }}" alt="">
                        <img loading="lazy" src="{{ asset('souqify/assets/images/pay-tamara.svg') }}" alt="">
                    </div>
                </div>
            </aside>
        </div>
    </section>
    <!-- =========== ADD ITEMS FOR FREE SHIPPING (orange section) =========== -->
    <section class="bg-orange-600 py-12 lg:py-16 relative overflow-hidden">
        @if ($freeShippingProducts->isNotEmpty())
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white mb-8 lg:mb-10">
                    @if ($shippingThreshold > 0)
                        {{ __('Add some Items to') }} "<span class="text-slate-900">{{ __('Get Free Shipping') }}</span>"
                    @elseif ($flashSaleDiscount > 0)
                        {{ __('Add some Items and') }} "<span class="text-slate-900">{{ __('Save :percent% Now', ['percent' => $flashSaleDiscount]) }}</span>"
                    @else
                        {{ __('You Might Also') }} "<span class="text-slate-900">{{ __('Like') }}</span>"
                    @endif
                </h2>
                <div class="flex overflow-x-auto gap-4 sm:gap-5 no-scrollbar scroll-snap-x scroll-smooth pb-2">
                    @foreach ($freeShippingProducts->take(4) as $product)
                        <div class="flex-shrink-0 w-60 sm:w-64 scroll-snap-start">
                            @include('themes.souqify.pages._showcase-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
            </div>
            @if ($flashSaleDiscount > 0)
                <!-- Decorative discount text -->
                <div
                    class="hidden lg:block absolute right-0 top-1/2 -translate-y-1/2 text-transparent font-mono font-bold text-[160px] xl:text-[200px] leading-none pointer-events-none select-none -rotate-90 origin-center [-webkit-text-stroke:3px_white]">
                    {{ $flashSaleDiscount }}%</div>
            @endif
        @endif
    </section>

    <!-- =========== COMPLETE THE COLLECTION =========== -->
    @if ($collection->isNotEmpty())
        <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Complete the Collection') }}</h2>
                <div class="flex items-center gap-2">
                    <button onclick="scrollCarousel('collection', -1)"
                        class="w-10 h-10 rounded-full bg-white border border-neutral-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition"
                        aria-label="{{ __('Previous') }}">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button onclick="scrollCarousel('collection', 1)"
                        class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center hover:bg-blue-800 transition"
                        aria-label="{{ __('Next') }}">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
            <div id="collection" class="flex overflow-x-auto gap-4 sm:gap-5 no-scrollbar scroll-snap-x scroll-smooth pb-2">
                @foreach ($collection as $cp)
                    @php
                        $cpVariant = $cp->variants->firstWhere('active', true) ?? $cp->variants->first();
                        $cpPricing = $cp->storefrontPricing($cpVariant);
                        $cpPrice = number_format((float) $cpPricing['current_price'] * $rate, 2);
                        $cpImg = $cp->centralProduct?->primary_image_url ?? $cp->primary_image_url ?? null;
                        $cpName = $cp->translationValue('name') ?? $cp->slug;
                        $cpUrl = route('tenant.storefront.product', $cp->slug);
                    @endphp
                    <a href="{{ $cpUrl }}" class="flex-shrink-0 w-56 sm:w-64 group scroll-snap-start">
                        <div class="aspect-square rounded-xl overflow-hidden product-img-bg mb-3">
                            @if ($cpImg)
                                <img loading="lazy" src="{{ $cpImg }}" alt="{{ $cpName }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                            @else
                                <div class="w-full h-full flex items-center justify-center text-4xl text-neutral-300">🛍️</div>
                            @endif
                        </div>
                        <h3 class="font-medium text-zinc-900 mb-1 line-clamp-1">{{ $cpName }}</h3>
                        <p class="text-blue-700 font-medium">{{ $symbol }}{{ $cpPrice }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =========== RECOMMENDED FOR YOU =========== -->
    @if ($recommended->isNotEmpty())
        <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Recommended For You') }}</h2>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <button onclick="scrollCarousel('recommended', -1)"
                            class="w-10 h-10 rounded-full bg-white border border-neutral-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition"
                            aria-label="{{ __('Previous') }}">
                            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button onclick="scrollCarousel('recommended', 1)"
                            class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center hover:bg-blue-800 transition"
                            aria-label="{{ __('Next') }}">
                            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                    <a href="{{ route('tenant.storefront.category') }}"
                        class="text-blue-700 font-medium text-sm hover:underline flex items-center gap-1">
                        {{ __('view all products') }}
                        <svg class="w-3 h-3 rtl:rotate-180" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" />
                        </svg>
                    </a>
                </div>
            </div>
            <div id="recommended" class="flex overflow-x-auto gap-4 no-scrollbar scroll-snap-x scroll-smooth pb-2">
                @foreach ($recommended as $product)
                    <div class="flex-shrink-0 w-60 sm:w-64 scroll-snap-start">
                        @include('themes.souqify.pages._product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =========== HOT DEALS =========== -->
    @if ($hotDeals->isNotEmpty())
        <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Hot Deals') }}</h2>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <button onclick="scrollCarousel('hot-deals', -1)"
                            class="w-10 h-10 rounded-full bg-white border border-neutral-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition"
                            aria-label="{{ __('Previous') }}">
                            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button onclick="scrollCarousel('hot-deals', 1)"
                            class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center hover:bg-blue-800 transition"
                            aria-label="{{ __('Next') }}">
                            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                    <a href="{{ route('tenant.storefront.category') }}"
                        class="text-blue-700 font-medium text-sm hover:underline flex items-center gap-1">
                        {{ __('view all products') }}
                        <svg class="w-3 h-3 rtl:rotate-180" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" />
                        </svg>
                    </a>
                </div>
            </div>
            <div id="hot-deals" class="flex overflow-x-auto gap-4 no-scrollbar scroll-snap-x scroll-smooth pb-2">
                @foreach ($hotDeals as $product)
                    <div class="flex-shrink-0 w-60 sm:w-64 scroll-snap-start">
                        @include('themes.souqify.pages._showcase-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- =========== EXPLORE PRODUCTS =========== -->
    @if ($explore->isNotEmpty())
        <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl sm:text-3xl font-medium text-zinc-900">{{ __('Explore Products') }}</h2>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <button onclick="scrollCarousel('explore', -1)"
                            class="w-10 h-10 rounded-full bg-white border border-neutral-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition"
                            aria-label="{{ __('Previous') }}">
                            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button onclick="scrollCarousel('explore', 1)"
                            class="w-10 h-10 rounded-full bg-blue-700 text-white flex items-center justify-center hover:bg-blue-800 transition"
                            aria-label="{{ __('Next') }}">
                            <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                    <a href="{{ route('tenant.storefront.category') }}"
                        class="text-blue-700 font-medium text-sm hover:underline flex items-center gap-1">
                        {{ __('view all products') }}
                        <svg class="w-3 h-3 rtl:rotate-180" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" />
                        </svg>
                    </a>
                </div>
            </div>
            <div id="explore" class="flex overflow-x-auto gap-4 no-scrollbar scroll-snap-x scroll-smooth pb-2">
                @foreach ($explore as $product)
                    <div class="flex-shrink-0 w-60 sm:w-64 scroll-snap-start">
                        @include('themes.souqify.pages._showcase-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <script>
        function scrollCarousel(id, dir) {
            var el = document.getElementById(id);
            if (!el) return;
            var rtl = document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
            el.scrollBy({
                left: (rtl ? -dir : dir) * 300,
                behavior: 'smooth'
            });
        }
    </script>
</div>
