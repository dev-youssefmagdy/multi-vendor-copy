@section('pageData', 'cart')
@php
$currency = $currentCurrency ?? null;
$symbol = $currency?->symbol ?? '$';
$rate = (float) ($currency?->conversion_rate ?? 1.0);

$itemCount = count($cartItems);

$displayTotal = number_format($cartTotal * $rate, 2);
$displayDiscount = number_format($cartDiscount * $rate, 2);
$displayFinal = number_format($cartFinalTotal * $rate, 2);
@endphp

<main class="flex-grow bg-white pt-3 lg:pt-6 pb-14 w-full" data-cart-rate="{{ $rate }}">
@unless (in_array($storefrontThemeVariant?->key, ['v2', 'v3', 'v4', 'v5', 'v6'], true))
    <div class="-mt-3 sm:mt-0">@include('themes.elora.partials.mobile-home-header', ['tight' => true])</div>
@endunless
    <div class="max-w-[1280px] mx-auto px-4 lg:px-8">
        <h1 class="text-[24px] font-bold text-[#242424] mb-8 lg:hidden">{{ __('My Cart') }}</h1>

        {{-- ── Breadcrumb ──────────────────────────────────────────────────── --}}
        <div class="hidden lg:flex items-center gap-2 text-[13px] text-[#707070] mb-8">
            <a href="{{ route('tenant.home') }}" class="hover:text-primary transition">{{ __('Home') }}</a>
            <svg class="w-[10px] h-[10px] text-[#707070] rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-[#242424] font-medium">{{ __('Cart') }}</span>
        </div>

        {{-- ── Empty Cart ───────────────────────────────────────────────────── --}}
        @if ($itemCount === 0)
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <img loading="lazy" src="{{ asset('elora/assets/images/empty-cart.svg') }}" alt="{{ __('Visa') }}" class="" />
            <h2 class="text-[22px] font-bold text-[#242424] mb-2">{{ __('Your cart is empty') }}</h2>
            <p class="text-[14px] text-[#707070] mb-8">{{ __('You don\'t have any Products at the moment.') }}</p>
            <a href="{{ route('tenant.home') }}"
                class="bg-[#242424] text-white px-10 py-3 rounded-full text-[14px] font-bold hover:bg-black transition">
                {{ __('Continue Shopping') }}
            </a>
        </div>

        {{-- ── Cart with items ─────────────────────────────────────────────── --}}
        @else
        <div>

            {{-- Free Shipping Banner --}}
            @if ($shippingThreshold > 0)
            <div class="mb-4 lg:mb-10 block p-1 lg:p-0 shipping-modal-trigger cursor-pointer">
                <x-storefront.free-shipping-progress theme="elora" :threshold="$shippingThreshold" :weight="$cartWeight" />
            </div>
            @endif
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-[72px] items-start">
                {{-- ──────────────────── LEFT COLUMN ───────────────────────── --}}
                <div class="w-full lg:w-[795px] flex flex-col gap-6">

                    {{-- Login Banner (desktop only) --}}
                    @guest('storefront')
                    <div class="hidden lg:flex items-center p-3 bg-[#FFFBFB] border border-[#CCCCCC] rounded-lg gap-2">
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="#171717" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-[#171717] text-sm font-normal font-['Outfit'] tracking-[0.5px]">{{ __('Log in to complete your shopping faster') }}</span>
                    </div>
                    @endguest

                    <div class="flex flex-col gap-6">
                        {{-- Select All Header --}}
                        <div class="hidden sm:flex items-center justify-between pb-4 border-b border-[#C0C0C0]">
                            <label class="flex items-center gap-4 cursor-pointer">
                                <span class="relative inline-flex w-5 h-5 shrink-0">
                                    <input type="checkbox" id="cartSelectAll" checked
                                        class="peer w-5 h-5 rounded-[4px] appearance-none bg-white border border-black checked:bg-[#171717] checked:border-[#171717] cursor-pointer">
                                    <svg class="pointer-events-none absolute inset-0 hidden peer-checked:block text-white w-5 h-5 p-[3px]" viewBox="0 0 12 12" fill="none">
                                        <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span class="text-[#171717] text-sm font-medium font-['Outfit'] tracking-[0.5px]"
                                    id="cartSelectionLabel">{{ __('Select all (:count)', ['count' => $itemCount]) }}</span>
                            </label>
                        </div>

                        {{-- ── Cart Line Items ──────────────────────────────── --}}
                        <div class="flex flex-col gap-4 shopping-table">
                            @foreach ($cartItems as $item)
                            @php
                            $product = $item['product'];
                            $variant = $item['variant'];
                            $qty = $item['qty'];
                            $price = $item['price'];
                            $subtotal = $item['subtotal'];
                            $key = $item['key'];

                            $img = $variant?->thumbnail_url
                            ?? $product->centralProduct->primary_image_url
                            ?? $product->primary_image_url
                            ?? null;

                            $productName = $product->translationValue('name') ?? $product->slug;
                            $productUrl = route('tenant.storefront.product', $product->slug);
                            $variantTitle = $variant?->display_label ?? null;

                            $displaySubtotal = number_format($subtotal * $rate, 2);
                            $originalPrice = number_format($price * $rate, 2);

                            $productDescription = $product->translationValue('description') ?? null;
                            $hasDiscount = $item['has_discount'] ?? false;
                            $isFlashSale = $item['is_flash_sale'] ?? false;
                            $effectiveDiscountPct = $isFlashSale
                            ? (int) round($item['flash_sale_percentage'] ?? 0)
                            : ($hasDiscount ? (int) round($item['discount_percentage'] ?? 0) : 0);
                            $deliveryFrom = \Carbon\Carbon::now()->addDays(7)->translatedFormat('j M');
                            $deliveryTo = \Carbon\Carbon::now()->addDays(10)->translatedFormat('j M');
                            @endphp

                            <!-- product card -->
                            <div class="cart-line-item flex items-center gap-4" data-subtotal="{{ $subtotal }}">
                                {{-- Checkbox --}}
                                <div class="hidden sm:flex items-center shrink-0">
                                    <span class="relative inline-flex w-6 h-6">
                                        <input type="checkbox"
                                            class="cart-line-checkbox peer w-6 h-6 rounded-[4px] appearance-none bg-white border border-black checked:bg-[#171717] checked:border-[#171717] cursor-pointer"
                                            checked>
                                        <svg class="pointer-events-none absolute inset-0 hidden peer-checked:block text-white w-6 h-6 p-[4px]" viewBox="0 0 12 12" fill="none">
                                            <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                                {{-- Image + Info wrapper --}}
                                <div class="flex gap-4 flex-1 min-w-0">
                                    {{-- Product Image --}}
                                    <a href="{{ $productUrl }}" class="shrink-0">
                                        @if ($img)
                                        <img loading="lazy" src="{{ $img }}" alt="{{ $productName }}"
                                            class="w-32 h-28 lg:w-[171px] lg:h-[162px] object-cover rounded-lg">
                                        @else
                                        <div class="w-32 h-28 lg:w-[171px] lg:h-[162px] bg-[#f9f9f9] rounded-lg flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        @endif
                                    </a>
                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0 lg:w-[560px] pt-2 pb-6 border-b border-[#C4C4C4] flex flex-col justify-between">
                                        {{-- Top section --}}
                                        <div class="flex flex-col gap-2">
                                            {{-- Name + Weight + Trash --}}
                                            <div class="flex justify-between items-start gap-4">
                                                <div class="flex-1 min-w-0 flex flex-col gap-2">
                                                    <div class="flex justify-between items-center gap-2">
                                                        <a href="{{ $productUrl }}" class="text-[#171717] text-xs sm:text-base font-normal font-['Outfit'] line-clamp-1 flex-1 min-w-0">{{ $productName }}</a>
                                                    </div>
                                                    @if($variantTitle)
                                                    <div class="flex gap-6">
                                                        <span class="text-[#171717] text-sm font-normal font-['Outfit'] tracking-[0.5px]">{{ __('Variant') }}: <span class="text-[#ADADAD]">{{ $variantTitle }}</span></span>
                                                    </div>
                                                    @endif
                                                </div>
                                                {{-- Trash icon --}}
                                                <button onclick="storefrontConfirmRemoveItem(this, {{ \Illuminate\Support\Js::from($key) }})"
                                                    class="item-remove shrink-0 hover:opacity-80 transition" style="color:#dc2626"
                                                    type="button" aria-label="{{ __('Remove item') }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                            @if($item['is_out_of_stock'] ?? false)
                                            <div class="text-red-600 text-xs font-semibold flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ __('This item is currently out of stock') }}
                                            </div>
                                            @endif
                                            {{-- Delivery --}}
                                            <p class="text-[var(--color-primary)] text-sm font-normal font-['Outfit'] tracking-[0.5px]">
                                                {{ __('Estimated delivery') }}: {{ $deliveryFrom }} - {{ $deliveryTo }}
                                            </p>
                                        </div>
                                        {{-- Bottom: Price + Qty --}}
                                        <div class="flex justify-between items-center flex-wrap gap-2 mt-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[#171717] text-xs sm:text-base font-normal font-['Outfit']">{{ $symbol }}{{ $displaySubtotal }}</span>
                                                @if($effectiveDiscountPct > 0)
                                                <span class="text-[#ADADAD] text-[10px] sm:text-sm font-normal font-['Outfit'] line-through tracking-[0.5px]">{{ $symbol }}{{ $originalPrice }}</span>
                                                @endif
                                            </div>
                                            {{-- Qty control (+/-) --}}
                                            <div class="flex items-center gap-4 px-2 py-2 border border-[#E0E0E0] rounded-3xl"
                                                x-data="{ qty: {{ $qty }} }"
                                                wire:key="qty-ctrl-{{ $key }}">
                                                <button type="button"
                                                    @click="if(qty > 1){ qty--; $wire.updateQty('{{ $key }}', qty) } else { $wire.updateQty('{{ $key }}', 0) }"
                                                    class="w-[18px] h-[18px] flex items-center justify-center hover:opacity-70 transition"
                                                    aria-label="{{ __('Decrease quantity') }}">
                                                    <svg width="18" height="18" fill="none" viewBox="0 0 18 18">
                                                        <line x1="3.75" y1="9" x2="14.25" y2="9" stroke="#C9C5C5" stroke-width="1.5" stroke-linecap="round"/>
                                                    </svg>
                                                </button>
                                                <div class="w-[18px] h-[18px] bg-[#FFD3C0] rounded-full flex items-center justify-center">
                                                    <span x-text="qty" class="text-[var(--color-primary)] text-xs font-medium font-['Outfit'] tracking-[0.5px] leading-none"></span>
                                                </div>
                                                <button type="button"
                                                    @click="qty++; $wire.updateQty('{{ $key }}', qty)"
                                                    class="w-[18px] h-[18px] flex items-center justify-center hover:opacity-70 transition"
                                                    aria-label="{{ __('Increase quantity') }}">
                                                    <svg width="18" height="18" fill="none" viewBox="0 0 18 18">
                                                        <line x1="9" y1="3.75" x2="9" y2="14.25" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round"/>
                                                        <line x1="3.75" y1="9" x2="14.25" y2="9" stroke="var(--color-primary)" stroke-width="1.5" stroke-linecap="round"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Mobile Coupon (desktop has it in the order summary) --}}
                    <div class="lg:hidden mt-6">
                        @if ($appliedCoupon)
                            <div class="flex items-center justify-between bg-[#f0fdf4] border border-[#bbf7d0] rounded-lg px-3 py-2 h-12">
                                <span class="text-xs text-[#166534] font-medium">{{ __('":code" applied', ['code' => $appliedCoupon->code]) }}</span>
                                <button wire:click="removeCoupon" type="button"
                                    class="text-xs text-[#dc2626] font-semibold hover:underline ml-3 shrink-0">{{ __('Remove') }}</button>
                            </div>
                        @else
                            <div class="flex items-center w-full border border-[#CACACA] rounded-lg overflow-hidden h-12 bg-white">
                                <input wire:model="couponCode" type="text" placeholder="{{ __('Enter the discount code') }}"
                                    class="flex-1 min-w-0 px-3 text-sm bg-transparent outline-none text-[#171717] placeholder-[#ADADAD] h-full"
                                    wire:keydown.enter="applyCoupon">
                                <button wire:click="applyCoupon" type="button"
                                    class="bg-[var(--color-primary)] text-white px-5 h-full text-sm font-medium shrink-0">{{ __('Apply') }}</button>
                            </div>
                            @error('coupon')
                                <p class="text-xs text-[#dc2626] mt-1">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- Mobile CTA --}}
                    <div
                        data-mobile-buybar class="lg:hidden fixed bottom-0 left-0 right-0 z-30 px-4 flex gap-4 items-start justify-center bg-white py-3">
                        {{-- Grand Total --}}
                        <div class="">
                            <p class="text-[14px] mb-2 text-[#8F8F8F]">{{ __('Total') }}</p>
                            <p class="text-[20px] font-bold text-[#000000]">
                                {{ $symbol }}<span data-cart-total>{{ $displayFinal }}</span>
                            </p>
                        </div>
                        <a href="{{ route('tenant.storefront.checkout') }}"
                            class="w-full bg-[var(--color-primary)] text-white py-4 rounded-full font-bold text-[15px] shadow-sm transition active:scale-[0.98] uppercase tracking-wider flex items-center justify-center">
                            {{ __('Checkout') }}
                        </a>
                        @if ($cartDiscount > 0)
                        <p class="absolute -top-7 left-0 bg-[#FFB00A] rounded-se-2xl text-sm px-2 py-1">
                            {{ __('You save') }}
                            <span>{{ $symbol }}{{ $displayDiscount }}</span>
                        </p>
                        @endif
                    </div>

                    {{-- Mobile Trust Badges --}}
                    @if ($relatedProducts->isNotEmpty())
                    <div class="sm:hidden bg-[#f562233d] p-2 rounded-lg border border-[var(--color-primary)] mt-8">
                        <h3 class="font-bold">{{ __('People buy with') }} :</h3>
                        <div class="flex items-center justify-between mt-3">
                            @foreach ($relatedProducts->take(2) as $relatedProduct)
                            <div class="w-[45%]">
                                @include($this->pageView('_product-card'), ['product' => $relatedProduct, 'badge' =>
                                null])
                            </div>
                            @if (!$loop->last)
                            <span class="text-[16px] font-bold text-[#000000]">+</span>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- ──────────── RIGHT: ORDER SUMMARY ─────────────────────── --}}
                <div class="hidden lg:flex lg:w-[405px] flex-col gap-8 shrink-0">
                    {{-- Order Summary Card --}}
                    <div id="cartSummary">
                        <div class="w-full p-4 bg-white border border-[#E8E8E8] rounded-2xl flex flex-col gap-4 sticky top-4">
                            <div class="flex flex-col gap-2">
                                <div class="pb-4 flex flex-col gap-4 border-b border-[#C0C0C0]">
                                    <h2 class="text-[#171717] text-xl font-medium font-['Outfit']">{{ __('Order Summary') }}</h2>
                                    {{-- Subtotal row --}}
                                    <div class="flex justify-between items-start">
                                        <span class="text-[#171717] text-base font-normal font-['Outfit']">{{ __('Subtotal:') }}</span>
                                        <span class="text-[#171717] text-base font-normal font-['Outfit']">
                                            {{ $symbol }}<span data-cart-item-total>{{ $displayTotal }}</span>
                                        </span>
                                    </div>
                                    {{-- Shipping fee row --}}
                                    <div class="flex justify-between items-center">
                                        <span class="text-[#171717] text-base font-normal font-['Outfit']">{{ __('Shipping fee:') }}</span>
                                        <span class="text-[#171717] text-base font-normal font-['Outfit']">{{ __('Free') }}</span>
                                    </div>
                                    {{-- Discount Row --}}
                                    @if ($appliedCoupon && $cartDiscount > 0)
                                    <div class="flex justify-between items-center">
                                        <span class="text-base font-normal text-[#171717] flex items-center gap-2">
                                            {{ __('Coupon') }}
                                            <span class="text-xs font-bold bg-[#f0f0f0] text-[#555] px-2 py-0.5 rounded uppercase tracking-wide">{{ $appliedCoupon->code }}</span>
                                        </span>
                                        <span class="text-base font-bold text-[#ED181A]">-{{ $symbol }}{{ $displayDiscount }}</span>
                                    </div>
                                    @endif
                                </div>

                                {{-- Coupon Input --}}
                                <div>
                                    @if ($appliedCoupon)
                                    <div class="flex items-center justify-between bg-[#f0fdf4] border border-[#bbf7d0] rounded-lg px-3 py-2 h-12">
                                        <span class="text-xs text-[#166534] font-medium">{{ __('":code" applied', ['code' => $appliedCoupon->code]) }}</span>
                                        <button wire:click="removeCoupon" type="button"
                                            class="text-xs text-[#dc2626] font-semibold hover:underline ml-3 shrink-0">{{ __('Remove') }}</button>
                                    </div>
                                    @else
                                    <div class="relative w-full h-12 border border-[#CACACA] rounded-lg overflow-hidden">
                                        <input wire:model="couponCode" type="text" placeholder="{{ __('Enter the discount code') }}"
                                            class="absolute left-0 top-0 h-full w-[calc(100%-92px)] px-3 text-base font-normal font-['Outfit'] bg-transparent outline-none text-[#171717] placeholder-[#ADADAD]"
                                            wire:keydown.enter="applyCoupon">
                                        <button wire:click="applyCoupon" type="button"
                                            class="absolute right-0 top-0 w-[92px] h-12 bg-[#EB7441] rounded-r-lg flex items-center justify-center text-white text-xl font-medium font-['Outfit'] hover:bg-[#d4632f] transition">
                                            {{ __('Apply') }}
                                        </button>
                                    </div>
                                    @error('coupon')
                                    <p class="text-xs text-[#dc2626] mt-1">{{ $message }}</p>
                                    @enderror
                                    @endif
                                </div>
                            </div>

                            {{-- Total + Checkout --}}
                            <div class="flex flex-col gap-6">
                                <div class="flex flex-col gap-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-[#171717] text-base font-medium font-['Outfit']">{{ __('Total') }}</span>
                                        <span class="text-[#171717] text-base font-medium font-['Outfit']">{{ $symbol }}<span data-cart-total>{{ $displayFinal }}</span></span>
                                    </div>
                                    <p class="text-[#707070] text-base font-normal font-['Outfit']">{{ __('Please refer to your final actual payment amount. Tax & levies excluded.') }}</p>
                                </div>
                                <a href="{{ route('tenant.storefront.checkout') }}"
                                    class="w-full h-12 px-8 py-2 bg-[#171717] rounded-[32px] flex items-center justify-center text-white text-base font-medium font-['Outfit'] hover:bg-black transition">
                                    {{ $symbol }}<span data-cart-checkout-total>{{ $displayFinal }}</span>&nbsp;{{ __('to checkout') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Methods Card --}}
                    <div class="w-full p-4 bg-white border border-[#D4D4D4] rounded-2xl flex flex-col gap-4">
                        <h3 class="text-[#242424] text-xl font-medium font-['Outfit']">{{ __('1. Payment methods') }}</h3>
                        <div class="p-2">
                            <div class="flex justify-center items-center gap-3 flex-wrap">
                                <!-- apple pay svg -->
                                <svg width="74" height="31" viewBox="0 0 74 31" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <rect width="74" height="31" fill="url(#pattern0_3_3168)"/>
                                    <defs>
                                        <pattern id="pattern0_3_3168" patternContentUnits="objectBoundingBox" width="1" height="1">
                                            <use xlink:href="#image0_3_3168" transform="matrix(0.00529915 0 0 0.0124913 -0.0961538 -0.318182)"/>
                                        </pattern>
                                        <image id="image0_3_3168" width="225" height="131" preserveAspectRatio="none" xlink:href="data:image/jpeg;base64,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"/>
                                    </defs>
                                </svg>
                                <!-- mastercard -->
                                <svg width="70" height="29" viewBox="0 0 70 29" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <rect width="70" height="29" fill="url(#pattern0_3_3169)"/>
                                    <defs>
                                        <pattern id="pattern0_3_3169" patternContentUnits="objectBoundingBox" width="1" height="1">
                                            <use xlink:href="#image0_3_3169" transform="matrix(0.00829238 0 0 0.0203443 -0.113636 -0.277778)"/>
                                        </pattern>
                                        <image id="image0_3_3169" width="148" height="148" preserveAspectRatio="none" xlink:href="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIAJQAlAMBEQACEQEDEQH/xAAbAAACAgMBAAAAAAAAAAAAAAABAgADBAUGB//EAEkQAAEDAwEEAwgOBwkBAAAAAAEAAgMEBREGEiExQRNRkwciVIGhorHRFBUjMkJSU2Fxc3SRsuEzNUNEkuPwJSY3RVVjcsHCJP/EABsBAAMBAQEBAQAAAAAAAAAAAAECAwAEBQYH/8QALREAAwACAQMCAwgDAQAAAAAAAAECAxESBBMhMVEUQVIFFSJCYXGhsTKR0SP/2gAMAwEAAhEDEQA/APQl+ZnvERMKUyCBMgilOgiFOhkIVSQilVQSsqqCIVWRkVuCqg7KnBVQxW4KiGTKXBVQyZU4JhkypzUyGTK3NRGTEITBJsrG2dwvitHjEW0EUpkggKKCInQRSnQwhVEEUqkhEcFZBEIVZCIQqoJW4KqDsrcFRBTKnNVENsqc1OMmVOCZDJiFqIyYhaiHZNlY2zs18bo8kC2goCISqSZrXBgDnvIzssaXHHXgclWMN35QruV6il0ng9R2ZVPh6Xt/s3dkQvf4PUdmU3Zr9P8AYe7Ipe/wefsynWJ+6G7sil7/AAefsyqLG/dB7silz/kKjsyqrG/dG7sgzIeFNP2ZVpxM3dkUiU/u1R2ZVpw0bvSI5svg1R2ZVFhoPfgrc2Xwefs1RYqCs8lT9v5Cb+BOsVDrLJS94aCXxzNaOJMZwm7dDLKgYDhlu8HmOa3oVTWhC1EbYpaiHYNhYx1q+P0eWQraMKjoZGRaWD2DDNgbc7BK89ZcM+TOF7XZ1KSPPd7psF0uNHaqR1VXzshhBDcuPvnHgAOZPUlnpqt6lD80l5L3DeQovE0VTK3DchwHTKyVlI2wKsoJAumEBjg7l1ShGA71UBTImQyZiSKiRWWY7sjgnUlORrujEdTLGwYbhrwOrOQR5ufGpZVpj436oJapltibKIdk2Vg7OnXymjzNgW4h2TmENDJmVaf1VRfZ4/whfXR0+4T90eHeXVNHj/dot15F6o66WvY6hlnbFRQAn3F2y3LiMY99nrXXixqJ1oHPkdDqO9aj0do9ntnWQ196qqswwzho2WtIznGAMjHlXO+lx3k9PBZZaS9TSatfr/SljZcKnUMdQJJGslDIW7UDiCdxLeG7H3LRgwVWuHoM6yTO9mx11eL9BXaZobNcfYs1xi90c6NpDnd7gnIPWeClhwYtU7nei2TJe5Uv1NFPV68h1dT6bfqGL2VPEZGyCFmwAGudv7zPwSqrF0zh2p8Ccs3Phy8m40fe77BqO92/UNxFY23U+2dhjWgncd2ADwS3ixuE4WtlcV2rat+hj2Kr1xqilmvlBeKehpxI72NRmIOa4N5E48WfQrOccPi0JPdyJ2mCzanvtR3OL7c6quf7YUtTsRybDAYwNjdjGOZWqZ7iWvAJuu2235LLRe9WU+n5NSXueCWgjoSaeFuA6WQ4DXvwP+/EmcRvSNF5FPNswQ/XMmnH6iF6iAkhMwpOhb3se/BG7Gcb8KnGd60bebhz2dNoWvqrppShra+YzVEnSbbyAM4kcBw+YJuJ1YMjrGnRsnjNbJ9VH6Xrl6haaOvE/LIWrnL7Bsoh2DZWDs6FfNcTzNkWch2DmEvEdMybSf7Lovs8f4QvvsGNPFP7I+Wy3rJX7s5bum6YuWp6G3xWmWnjmpagyl07iBw3YwDzVaxN+g2LKl6mBcNIX/UWlJaDU91p5bpHU9PSVETAGMw3GDstbuO/keSTsvRZZU34NNftJ6/1HaY6G73O2GOBzXNY0kGZwGNpxDeQJ+/hzU+0150V57Rv9QaXr7je9M1sD6cRWtoE4c8gn3vvd2/geOFLtaTXuXT20xa7TVdN3SaLUbXwCigpzG5u0ekyWPbuGMcXDmpdvWNwdKTrKrK7dpypp9Y3y61JgdRXCPo2MDiXYwAcjGOR61nP4UvYtjwvuVT9Gaa36X1jY21FssV2ohapnuLHzNy+EHjgYO/yfQmri/LRP4fNH4ZfgvtOirpR6Eu9ilnpX1VZP0kbw92zjvffHGQe9PWg2uaYqwVONy/U6KDT/SaIh0/cHgO9hiCR8RyA4DiOvejK/FsPb/8APizjRpfWsdnfYRdaD2uDCxkmDtub8T3uQPR1q6gjwyceO/B0+j7TUWPTdJbqx0bpoS/aMZJbve4jeQOtUUePJbEnM6ZnEf8A2y/VR+l64esWqR24H6jELkOgBaiHYMLG2b1eHxPL5EKHEZMXglcjpi0tS6jZ0UjHvhB9zcwZLR1EeghfRdB9q45xrHmemjyOr+z7q3eLzsvNyh+JP2LvUvS+8+k+v+zkX2f1P0/0KblB8SfsXIfenR/X/D/4VnoepX5f6/6IblB8WfsXLfeXR/X/AAy89H1C/KKbhCeDJ+yclf2h0f1/wy89NnXrJW+ujP7OfsnJfjekf5v4Z0RiyL1Rjuq25/Rz9kUr6vpvlR3RtLyiNq2j9nP2RUn1OB+lDt/oWCtj+JN2RS97H7kqTfyA6tj+Tm7Iqk58S9WRqK9ip1U0/s5uyK6J6nD9Qnbv2KnVI+DDMTyGxjyncnfV4UvU3bv2Eijc3afJgyPO/HAY4AfN+a8zNl7tcjrxRwkfCmV2AhE2wYRQdm5Xl8DyNkKDgZMUpHJRMQqbkomKkcFpYpScSiFQ0UQN3NEOgJ0HRFWQaAqoBFVAaQFVAAU6ADmn2AXCZGAQiYVExETbNuubgeIqAUrgZUKUjgrLFKlUFZoQqTkvLFKm5LSKk0VQq2hwJgkToxFRAZFVCgVEKwKiAROhRSnRgImAeCYAETG2TPGfPciFK8YyoU71KoKzQjlKoOiaEKjUF5YhUXJ0SxCpuSyYEuiiAtocGUyDomU6AwqiEZFVCsiohQJ0KKnRgJkAXKYxFjG3XdwPleYClcDqxSpuC00KVCoLxRW5QqDqihCuepOiWIVKpOiWIVNouhUNDomVtBJlOkYmU6QrDlUQjJlUQoCVRIUBKdAFJToAEwCLANzhevwPj+QClcDqhSpVBaKKXyDb6NjHySYyWMbkgfPyHjXLmqMf+b0dWNOvQVwm8En831rhrqen+o65m18itwm8En831qLz4fqOiW/YQibwSfzfWpPLi+ovN6EPT+CT+b60nPG/zIrOVC4n8En831oJz7lVmkmzUeCT+b606nfzD34JsVHgk/m+tUWJg+IgmxUD90n831p1hoHxEAPTDjSzeb606xMHeliGSQfu03m+tUWNm57+QjqjA76CZo69nPo3p1jYrf6Dte17Q5pDmuGQQcgo8QBym0Bkym0KDKOjG8PBe7xPidioORlQDxCk5LxRfaWAUEMnwpmCV56y4Z/L6AvlermsmWmz2sLUwhKq6W+mr4KCoq4o6uoHuMLnd9J9C5PhradJeEXWRehkOapPEWTNdRXW33Geop6GshnmpnbM7GOyYzkjB8YP3I109wk6WthjJNPSZkuCVSVQMKkphIF0whdDhdcoRkdwVAHH6yrtT26toprJSw1VA57WTRhhdJknyDHPlzV8ShrySyVkl/hXg1nt3qI6/ktb7eRaQTiXojgM2ch+3wO/d5OSoonjsacuTvcdeDqX7kqk7tmGzDKmaNu5uGvx1E5z6M+MrNCfN6LMo6FZMo6FJlHRjfZXvaPhNgKGiksHMKbReGZNq/VdF9nj/CF4mTp922emsukjgNY/4t6S/wCDv/SKw6xufcdXt7Nrd9TV9H3RbPYIm05oqyLbkc5hLwe/4HOPgjkuf4SeDb9S6zPkjn+5aP726zB8Kb+ORbqsfLHH6D4K1bPSHBec8J2zSK3LLGVQuVaYDokkzIo3ySO2WMaXOPUBxXRM/InXjyzzKfXerLlTVl005ZoDaKYn3Wdpc9wHE4Dhw44A3Lq7WNaVPycLzZK25XgqtOp+6Bereyvt1vtktO8uaHbJByDg7i9P28cvRoy5qW0XPuPdJ52q3fR/T0yiRufUHZ05ldTQmpaBMYwZAOAdjf5U3A7Jt8fPqYzziuk+qZ6XpakZPbYcraMyZR0KTK3Ex0K9w+C2AoMomTmEhaGX2r9VUX2eP8IXL21s6HbXg5DVFnr6nug2C8Qw5t9CxxqJi4ARjfvPNJWPyWjIuLXzNFcq+lu3dmsb7ZPHUsp6ciR8Tg5oOy/dnxj71NynWkXmmp2zIvmltS2bVVVqDR0kEgrP09LM4DfuzuO4jIznORlC8O/A85PmhTdu6j/ods+7+Yovp59jonNfyK3Xbunc7Hbfu/mKfw2Mssub5IrN37pY/wAlto/r6xHsQVVdS/yiTXLuk1EEkL7LbSyRpY76CMfKI9uECn1LWnJqbJae6FbLLNY6OkgjpKjaaXyPbtRh252Dnd9xRfBvZCceeZ468HoekLENN6ep7b0glkZl8rxwLyd+Pm5eJDXJ7LxHCdGykKvMDbKHKqkPI105xXyD/aZ6XqeSfJTG97JtJeI7BlNoQOVtG2dGvWPgiErDpgQaKyxKeofSAx9G6WEE7OwRtM35xgnePoUXOi/+XoWOuERaQYKjB3EGP80P3HnHXyNJYbPYNOySyWm1ywSy7nyFpc7HUCTuHzBJMzJ06y16m3Nzi+SqOzR5SVnFZW+6Q/JT9n+anVwdMYsnsUvukPyc/Z/mpu8Z2Y8WT2Md9ziz+jm/gSOsZ348dpegG3SAcWTdmovRVxf0ji704+DN2ZS6JViyewHXenPBs3ZlPOiLw5PYqdc4D8Cbs1dXKJPDk9it9xjIOzFM4/O0N9Kp3JB279jEBc5zpH423nJxwHUFJ+XstM8VobKOjbJlNoUmVtA2dOvRPgwFEdAQKJgKBaWIUjOiWVuU2jphlblNo6oZS9SaOzHRjyBTaO7HRjPG9K5O6a8FZS8SmwI8QAym4iMmUUiTZMqiRJkym4kmyZTcSbYco6FBlbRjql2nwRCsOmBYohSgWQpStF5ZW5K0dMMrcptHTDKXqbR1wyl6m0duOjHeEvE64opcENF1QhR0HkAo6FbBlFIm2DKdSSZMp0iTJlHQjCjoUmVtGOsXSfBkKwUxViqAVissQpWXliOSs6IKykaOqSpyQ6YZS9IzsgoeErOqGUuCBdMrKw4hRQGBOkIwJibBlYkyJkIyZTaARbQD/9k="/>
                                    </defs>
                                </svg>
                                <!-- visa svg  -->
                                <svg width="56" height="37" viewBox="0 0 56 37" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <rect width="56" height="37" fill="url(#pattern0_3_3170)"/>
                                    <defs>
                                        <pattern id="pattern0_3_3170" patternContentUnits="objectBoundingBox" width="1" height="1">
                                            <use xlink:href="#image0_3_3170" transform="matrix(0.00833333 0 0 0.0126126 0 -0.0045045)"/>
                                        </pattern>
                                        <image id="image0_3_3170" width="120" height="80" preserveAspectRatio="none" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAABQCAYAAADSm7GJAAAQAElEQVR4AeycCZxfRZXvv1X3v/WeTsieQAIJWUgkEVD2sIoCsuMD5TFGB8b96aijM/B0XAKyKDMwCkYQAgKORtxACcsjQATZ15B979BpsnTS+3+79/3O/XcnnRACSeNnQts3dW7dqjrn1FnqnKp7/w2e0lU+a9asI26//fbZ999/f3snZFXnBAVBURD1wf17vQ3kx7tuvfXWKebWKIoSsYNvu+220wcPHjz3/PPPP2fatGmZTkipTgoCgRfQB9P2dhuEH9M1dOjQF+TTi51zBa+HQ4YMGfJzOS+ZyWQKgqKAPsi8F23g5besfIl8eoMi+TAvL39ZHYlMJpNXWLtOiFT3lfeeBYoS2Zyck0+rvfdf8UrN56jTBro71Z67QMN95T1kAdt2zXfZQYMGnWWNcglvkFadEHRFsR77ynvMAua7QDKnBAll59IhS42+0jssYA62oLXaHO2t0TtU69PCLGCONbBng5062BC6gyH2Nvh70cf1RXAvd3Wfg/sc3Mst0MvV64vgPgf3cgv0cvX6IrjPwb3cAr1cvb4I/vtycC/X9u9Qvb4I7uVO73Nwn4N7uQV6uXp9Edzn4O0tYH8qYLB97ztvGW13eOeUb4UZasBA1f9A6a5L9+c9FaU7D3veUz5ddLsVwTahmdLA/sZnpyCk4g4Qqa2CQdfEb1kbkkAFo4vnUCPmKSJrW7+6Yn4RXrVAHTFO97oLX3WoflWlYs8CFdGyFYy38SgIq6DBN4H6bdzwNBzTqettSxeu1Ua7M7Axg7dltpsIu3BwKFYGqlRscmuZgm9SXIP5twDDtbHYwMIRq+1KRBgbyoZi0C/RVoeazP4KsKA6BnV21dZvRtKQqInB+myuGEcDXXVez20aVLVtnq45JInxMRAKRp9VX7vmMmgV0ZYcNHaAtbPqbxFihwhUxfMKXUWIcWtb3aWX6W29IsFobA4Ds0lOA3kNxG1xMduq2q5oym1ya+Sdt7Xotfh34WBx26GsrdvMzJl/5saZj/HjmX/htrsWMHPWfG6atViwnJtuW81Ns9Zw021rBfWdsJZH/hKRlTKyFabwDmzf1JTOmAFm/+51ZsY8jZ/xFf/bljNz1sv89g/PYXimcEEc7vjlYm69u4GZd9TH8/7sF+u45Y41kvN5nnrmDTCnGjK6VEsczKAmUzslB/7xoVZ++OPFfOFrc/n81+dx7id+w4XTf8/HP3UP//SVuXzhX+byk58v5a8vQKu8pRLLII47LXkp2yEkc+CadTD79/X8bNYSfjprhXRYwc13rOQmPd8w80Wef7GFDiliCzPOUJLR6p0y3o3O3XJw5BwrVq/jvjlP8sMbfsO3rvhvvnP1HP796of596vm8u2r56p+VG2rS2B9n/36f/OJf5wTO7kgQ5txu2Bnsuak6Lxn4WuXa44rH+Lfvv8w//r9h7jsiofEfy4/uO4BHn5sAYVCKXpfnp/nyv94gO/98PF4/NtXdcpz1QNcff2DzHt6bezM9py5UzSSwR7N8K8tgetu2sRHzn+QS/95Nlf/5GnuuX8df3xoHS8tCQSOFxY57n24gdn3ruWaG5/hkv9zFx8682ZenE/s4C5ddqy9d/gkPPEMnHHBrfzz5Q9w+ZXz+NaVj8Vw+RWP8p1r5nH5jHu56zdPUxSDIiX5QslooGaPym45eMSwGmZ8fzr3zP4Gc/70Iy775lc44vAp1NRUKY0kqKweTln1/mSqx2+Dqoly7AjmPlnPt2e8ikVNtAuRtehRZuHu2QtIVIynvN9EqgZMpnqf91HVfyLlNQeQKh/IxRdfRBDIGDJKc0uCtnwtyYox9Bs0hf5D3k9l/0kky0fJwP2ZcshUIgfeJ+JEajIUPfxMmebCi2/m5lmPsqIuIfxxZKoElQeQyOyHSw3DJ4aQLtsXnxopnQ6krHI8TW3VbGiEtizsUhfpuWETXPbtu2hsHkSq4iAq+k2RjSbFUF49STwnUFE7gUXLGnFaDE5yOdF1gR57VMTurehtyEAWlFmcwGvWpKBMcOBIuPSiGn55ywncOfMiPvXxg8j41QSuVdmw0Amh6lCUSSqqR/OHPz3HS6+gNjs1TAhxRLz4Kjzy2CtaDBXCDcSjxM+TE/8mjj92MlMmgxJKzOepZ+eT0x6QVT5sb2+no6ND0Z0jKnZQXVHgsKlgiMmEx/b2thzM+NEqpePH4sWXj/rpQCfrCk0YdEGkFJHP58nlspSlk2RSCbJqFwpZxowezOSJ4Oi6zFYGXW2wxXr3bzZSv6Ec7xUEoXRRp488Bs45iVXQQg1o3Fxg8TLRRoJ3sWwv0dswdrG5QxIuJCVIS7uM4FAZ+/uXHcItN03XWCPe6VTiiuJmLlMlhYpk5LBKtjSFUkp9Oymmm/Tn6eeUUinXbPan2tsQncuSZBPHHnEggbpNeEtj9Q2tKNzkpIhisSjnFgjlyTDqYMz+AynLIJlA/ifv4Bbte3f9+klyDNNeb84tk0wJ8QjlsDw2j3dtJIJ20qlcDNmOjXJ0E0lNnAgiRgyvjXmKHW++vPj5eLH+ac7L5Aq1OF8p+RyRnIzsYRBpk3VSOpEso2FTlpWrEd2bufWkx2y0R/SSK6YzBjINST0cIkd/7pIzad2yBo/CJMawm5fgns1bWlm4ZDXSyzq3QqRRa5hzjeq+OU8LJ2Vd20GAImdUGeecUSknQKhRW0bPvaSN1CVJJksQKHdbGs4r8g47bAIpOUWocj48rwxyw091HihUasElNLMGlb+duDkK4pvX4mkjxQbSrgEfroV8PWGugY7W9WSzJScfPGk0mVLQG+tu4MWT2LlzHmxh0ZINRK5G42nNpzFzrmYwuYmfwUv25lbHkhWbsAUr5HetyC0952VMTNeUg8MPTTOgXxGv6EFRvo27lJNiq9dsjJU352wbQxEHtmhefBmWr2pRwzh2YhifokVWO6eecii2TRiy4b+2ANZvLJApq9GicFhKtSiGkFTaK4KHyIBqCTmRht/du5zWXC2JdC2hNmKLqEgORqsr0AJK08CnP34EV373fG696ePc+pOLmPHtC7lmxj/wxUuOY+IYKZlfphRdrbQLpnvXAu2UVjNDTvP98f7naW6FyKXIh6HkiwjDgtBC1V0WcEJIKuuU8dKLy4Sj4XexmHy7yc5IPE6qSTTVbAXFAgePh4Mn7CMF2uRfaYkurVSnx5qaASxdUY9sqc5txVKVvR5k8zD7d0tpba2SkdJaJAi8AO2xrfSvSXPGaftrG1CEFAoyDKxeA5sak+KZIl8syJiiSTg5L6/U6hlQWx3jofnt1DzvKR3eMoOEKx106BK25EzEcwwfmOGn113A1z9XwxknwgeVkY6cAueeCheeCd/44j7c8eMTufOWS5hyUKUsUGIdRl3OKunU1g4LF8NTz62jZsAgQpcjDIvKIAVCn6c93xK3QwnltOi1FKmuHszCJa/T0lbiKXGxyzjvEbhQ84axjMbnXQEnLhnl68kThhIVtHSlgLri4pwjCj3r17dTv66khA1EwilqAdgqWbceHtPhKkjUCiGw4RicYj4sbGHa0ZOpKcdQcVFelLBkWQfO1RIpSqLO/OY9hFGWIYNqGD0qSXxJuLXKtq/XbxG3NJFU94aIBvSM4NijPsDhh6DUXALLFEkZKu0K6suR0fPwfeDoQ/szqL+LKaVVzL7rFukhlYJ7fr+ONzYERJEtvpx6Q4r5VsozWYYOzki+DvVZkbCR8FyapuY8dXWS3boFEaHuXaDHPSjivguq3RwyZoGDKe/Ta0bwZsGKSoUNGztYKUObIYy9Ra+sjVlr7tx61qxthqBcXVoplK5Ip+GBSvsnHzeaigrkCvUHXlEIry1YhUtUiTyJc04DKHpl0LCd/fYbxPDhYL2amhUr28U3oPtliyKSMLb41tat07jord0dKX6WdnKw8doKUUlH77zoIgFYJtq4GR565GVC14+QZExt8zvXzrGHj4rBRwpVjUTSJhJ9qEXe1p7Td4YWLUDJoLFtRXNva+zW055T7mwaB2bjyRNrSCWLoCjzMhadl/cpWlpDVqzo0Fip0zmn1Qyt6rr/oeeJfBmh21GsDsaPreWID4CmIKcvAskgjaXCZSvrcUFGxvU4Z6NQDPNKuW1MHD8Cb10C1yXH1odQzAR0XZ6//PUVvvz1x/VaQ7yHFkQT4SVrJ8Sooe5doMedlIfnNrF85UbSmf6Y82IULQ57uzjz1JEcfWR/vMvG3fFNznUukNwJvSq9jn3o0dTxEGjuzqc9qXpGvcOMZjunviGD4f1TxlHIKxoxY3ilKlvhAR3ZJMtWric0ROE69E/B+uxLOuG+XE9ZeY2cowEV5xz2uhMpGk85+WAs/ZvDCgoTM4Cl+rX1G0DpObRp0CWaKCoQJDoYO3aguBObyGlo9OgytXOCAsrx2OXieIFQWIWomoefaOSiS+/jtrs3o1drGR0ijWEgRxhNdzCtnDhaJsqLbT6C+/78nLJLAp8IOlG1HyoL1VR7prwPRo9Ah6oWnFa/0cUgzIKMsnDxWrLio+a7Ut5VB0vP2G56W2GCoicstED8Pkx8RXodCF2G5avWUSia4ZB5zflw7wOv0diSltJ6aY2xrb+og0kr++/bjxOnDY0J9JorgweigwWL1ouPV9sLL4ypnHNyRYFU0KETNNsuByOHwbSjJhC4zRI1pzGjMYj0bLKklWL3YUV9gmuvv5ePnncHf9EnU/tRYGs0i3uMrJtzTnwCza9aWSdy8MTT8NwrdVTVDJRMUtLwJG22bT1HHDaGQQNg2BAYOLBCtAVCLUahlEqUYNnyN2hTNitJVOruyf1ddXCxGMlAJXEOmjCSyvKClMhhihsUdXwurxxAXf0mGhR4ZlrDblivL1ePvKQVX4vzJQfbmJNhXLEx/nI1Qs6x6JUNCXT6sV+Jnn9xqRzssCgvKoRtDq804pT+9tu3mlH7stUdpmjg4F++fBhlrk5Otj1Qsyh1okMUVkuY0DllmzKl6IE6K1Tyj1+axWe/+lictmMnx5Y3bggvUuQ7RbkYi1bK8rv7FrKpKU2qrBqLUJPL65tAVXlWbwC2uKC2Fg6etD/53BYCX5IhkvypTIZ161upex3xNYaleexpT6HnHLrPLAsXSyHGpPFQUWbCF/FbZ/FkyqpoaGhmgxyc1wK398Xf37uEdRvypFL9ZLRtDL3LUl3RymkfHkRCPMyMXllP5yt9MoQlKzfIEOrQHTm2ZJZQzisw7sChJDVkNF0cnRqjlB6vu+ZzpFy9eDbiXSueAnR3MgmKUZXcMkBpegRzn6jnHy69gyeeCdneyRIqQukYLUWYvwieeGoRFVXD9NWrKF0i6e4Vya3sOzTJkYcJNwdan4wbOxQXtlKiRLhFnM4f6zfmWVuvtkZCQU+L330GNm0XdKOWouZIs7NF2uCB6JAzjLaWRlCHhuNILoaOjRtbWVOXi/cai8QH5i4jXyjHJ3TAwiMUnHN0dGzmmKNHM3ECapcAqPp3sAAAD+RJREFUXcZr0xaoW9tEKplRFEQkE+AIiQRJfXGZOG4EafWhy3WCRXBKGp94LPzXDz/NmOHtJFwjznVo3y8Kyzgb6NGK9tyQpJzanxWvV/NPX5rJE8+GassBnWiRTGFr2vT4y9P1bNhcJKcPKIViFDvNu4hQr4zHT5tAv2rRaRqZgwNGD5Ds7SRT6lP2UGyASxD5WuY9uVJaoDMMPb6kbk94SLtu5PIJgT4Telm0ogwOmTpGCkSxoqGUMOyCbma0JcvXIX14/Emt/IVbSCSrcd48YiKFpPQYhk2ceNxB2FnFWXfnXGbbZStglV6pAlnIUmFg3pNZwrBA4IuMP1AnaOFLFN1LxVgoqEmqecJR8NMbzuUTH5tK2q8h4dcr8pu1vBRiGi8VUWhfDKMyfYkaQC4czPeu/AUNG9FMwpAggRiag5va4c8PviTnS3GXlM6Rto4CRDnJ085JJ04UfzQP2lZs8aMtrEhHW5MYdZWEbFDF8uUb0G8mFGUrTdE1uEe13yOqXRAFCelUBFV84NDReH2YzxbyMYVzTkqHStMVLF5WJ2OYUTbpFamcZKqyRCjTKalRKDQxcfxw7Vv9Yl7eOOhmCktv/bzWypZmpUFnM4WKwkjRWyAKczJokQNGpXBCFolRbgfWl5Dn7TR7+VdGcfP1F3HCUbX6mPG6nNCGt7CMKYSEgVGgNFzGqvqI+x5cLylB6hBpDiURpW94dWGjZMio36n2hFFRUbiFqQePZuwBwgfMPrZg7XwwbHAVHdk28fIxTaiM4X05S5fXs2kTOE+Pr3eBRacMrlQ71SaY1aP2gwEDKvQFR8u7NCyDRHEqrqvvYIu6H5n3KolUf1yQijEiy1XaD3PZBk7/yOGKKBBLGYytl63s5ave0E+Qg9XnxVMrSt50MpVTxAwf1p9hOpTJ9hq3EtptOzDFbWmkFIFHHua5/trjuOk/p7Pf0CIJv1Hz5rrhmwSaR7FfiPrx12eWynloNrCMJL/wh3sX6BNkheQs6VEiDvHif+JJ00gobXRIjFCT5sQuIbQx4ydJt0DbQwnb7l7v95tlmIY3iGmtryfge0JcopXUpQckLdJQUSDh0KtAfzjwgGHxIcOiUl0qIc6V8fp6z00/h8YtoZDT6i+JYv6Nwiz9K/OcerL2ZY24TlCFPcv6zJ+/rBT1ThbUgHM2IkfTFs9p3FwcieKvcWJ3xA9bb5GevMgsotIi0JdK7vjZRznhyH2UNVrAPGcgvLjok2IYpfWhpoGNijC9FMSOnq8fPOY9uQAX1Er9QAtOnLUlea89lbT21DVcPmM1l31/Gd/47jK++n+X8p1rN2uLyZPKVEmnMM42NkegnN+m3zXX1Gn1S6bQOnsAYtED6q2kbxbDbGun2PdPOUCHjEbiUyrgnCyqPaq5Lclddz9O5DJASQznHGaxfK6Jk0+czH4jiReLEOLidLeZVq9GBu4gChUGnbQakpHyarUyedIoDNf6uiDUiNF2tbfVkXCjeB6dzRg6AL71r0fhXL4bislnHFXLyYiXDUa62c+79z+wnA2NOZLJChylBYddTvguoR8dliqtz+fXf3g1hj/8eQF3/+pxXnhlNc6bDoaM5BdnLxrxX6ozSmQT0LPLuPWMw47UTh2CiAin+gOHVFKZyUlkMz6qQynicGR0iLAVrtTnINKKj7RnOV/UB/mIj503maT6LcLEcWsJ9bRwcTObGvUeq4VSyFvUqtOKPqr4qEUHrEprEclCYTyj5lBPJDB6VW9dNKeEY8iQQSCZ2OHy2olHjxrGAGUn42evNI/MW4TX3mmozjmcK4G1US6ISBJRAb6mExS1viqm8YryYrePHU6Zpry8khdeXqaziexSYrKTu2myCzDZBX4nlO9KVxA4nDiNHwuTx+mdVMaPU6blNfWHRS2BUIaXE8wRMejNs5DdyLFHjuN9B8E255opkepoUcDSpQ20NOUhjiaIP3TYUVYLZPjQGv1WaxNAZPz1WFef5We3P8XytZBVOyfQ+RaJQEF7QgyyVaET/vzgZv3itSHmG8r4kZwqEjAdXDvOZZHYmCr2B3WvLlhPWUU/TA66rkimjbThmoz6RSlSao90Gi+Bfs2ytjbkVFLbkzGKtZMAqr2ifsmyelq1hrUyujjuUS0p9oiuG5GxMOjW1flovQOqYP+RVWzZtM4sHjvNSepQznAuwjknbFMMjWXVbuD448Zi77VG7zTavdiH+MVL39BJvJ+6ZUC9NBtOpJOX048ME8YOp6ZCQxHi59DPsCxclON7P/h/nHfRb/nS11/jzl9HvLQQ3miUw4sCTb9uM/z1Zbjy+g3c+auX9SElIydqERqrWM5Q/HI6MG7kuGmHijl6dbJfjV5TXSvd0jjhbgU5OJCjnM+wM/DamrzSc04KBd7HtLagnWwTBAmdruHFV8Q2Ak2v8agTiqoNuto7r30EMfA3vJxDCQomjB1EeZnHHCpd4tqE9moYOOfU5/A+x6RxtRx1RAoPOPvCpBVNDCVjW6AuWrIOFBXGwzmHHUyMT1LvPhMOHIn9KU1KDOzwhK5VdW0E6TE0dwzh9/ct4muX3crF0+/kk5f+idPPmc3Jp/+S08++g0995i5+fvvjLFudlSzlMd9An9BKnxML2l3b9X27iqOPSCPhePYFfbl6cgGVVYPkFo9zDruc5A5cMx3Ny8k2LyLXtGB7aF5AtkXQtIQUTSSUEbwWvNHGoMgPowzLlm0U37hnj29+jynfAaExV6bm8A+O1VclOaiYp5jLE+pnl0KhQFFgdUG5MV8sEBbamXbMOIbq7cdMpUWoWRReusdFHavWwJrVm0FGINIM2mcipwEhOBl21Mh95AgwerOOWPPiy8spKtLtx47KmiFU146hNT+IF+Z3sKyunLqGav3QsQ8dhYGQ6E8QVECQxPwVbytK0T7qwLstfOnSUxkutLx2iD/NeZ7NTUrXeEqXZHWO1pb1jByS4+afnMsvb/kYd9x4wXYw68YLMbj7lgv4wj+eICdv0lxZsQjxkSrxs1S+cJGUjdvWtyNornjh77rukmxH6p22d7fT7G4C2z48eFA1iSAkUJQFMl4QJAh8kiB+dtjXp6R+ATrl+Al6sZB9Acf2V6Sm/SlMvlgm1ZJqQSRjxA/q8XLEmDHVsYOtL9QtpllSRypdQV4Lqz1b1KfEJLmognTFCIpuQAyhH0B8CHJlRC4hxl57qnbqqJVMopUov4qvfP5M/apVpYiD19fpI82cF6islrc1D5o/Bi04p336g4cO5eRpcMwH4SR9Gu0OHzrGcfKxjqMPhzNOdVRksqR1ovQ+oTlDcvru2dbh9aNDY4mr0wQ6K+i+2+Vv6uCiDg8KUsrS6PfhseTaN2tfK1KUmFEk02vTcXJHpM+L6LfjY488KP5b4wTEznW6G5iQBohkybJGiq6S8gp92nQBzkDKe0Xv0KG1TBwnWgcxPvDaQli1ZgNO84RhSCKRJAgCtZz2tiL2mRM5xcD6vfK6F47hBjpUZdvq6V+5mWu+ez6XXNSPMr3V2OHskXl5NjZlKCr+ItFLBAycegJaOWnaBFIOtJ6xLLYjSPW4f5iy1bixQyjkc4RFEZhsyTItyGoa1rewYjWyGSgBsf3l1TRQtYvy9hi7IH67oUBaBQmwffPQqfvS3ryQfOsKcq3LY8i2WG3tFZBfwUnHTUTosXO2F8zLQVJe5emnHqG95XW2bFhC25bFgiXaz8SnfTmjhutgVJRUwtMdEWHXvsMUrYkNJMK1mmctYcdaRUwrFWU5MkG70mIzSZpIus3kWtbQtHE+xY6lDKjayIXnHMLtN3+Csz9Su1W2SHPc9YtZ5LJNkmWlYGkM2ebFdLQs0pmjguOODTAn6piBc7wJ4jGIndyvKkfLFvFoWqp5VxNm62jevJzFrz1L3eoNwtrz4vec9J1RmnI2yfsPKufCMyfwsTP244KP7qta8NERnHvaSM47bT8+M/0wTjkukLG35+so/bNer9vUSf248OyJnH3aCM4Xn/NP25dzTh3O2aeM5IxTDiSlFWLbglAJdJt8IPz2rou57Scf5/PTJ3HBWaN1kIsoC9bQtnkBbVp0rrCStK+jMrWW909KcPF5B/KjGafzwG/P5drvTmLsSEiLb+BR9CsjrGpm6kG1nHPaAZx1ynDO+vBgzjllcFyf/eF9+LevfkSLhdjBDl122wGsaU42nueddYhS9iD+15n7c+6HR4jXUOl4AGd+ZIwWVbMswJvsIq7vqEjkd4S3R0iWhZUVMSVsH77umrO45nvHc80VR/PDGUdz7Ywj+dEVh/IfPziKb3750NLrTbeZXKyadYR2ix32rW+ewHVXHcN//uBwbrjmCK6/+kiuv+pYwYmcdfpglDSIyZwqpfSUNKzKgP3XF1+8ZAIzLp/Knbecwy9nTeeP93yG++75LPfOvlTPn+Lee7QQfno637nsA1p0/RhUI8eKPomukgh6gNH7VvGjq87VT44f0jfsk/iva7vgQ3o+jROPqdrm3Jhi5zeJSEL8jz9mALfeeJ5s8UGuveIIwVFaYEdxy43/W4fO0W/jXDHgrcFGdj77u9RrBk8m0EFFIEtZJGQ0awyB+jrBHGGfNg1/29RC3NbAVrztaWl1p40ugVIsij5IemJjGY4d7mIQrc1tYPwr5GijHVAJEw6AyWPhYMGkMaBP5gwbADoLUlshvinixeLFwwmMrz0bpNPEEZrWQLmHctWxPnrO6Dnee0UTvB0I13BMtpQeMjuA9ccpXnxL8zs8QSfYcxd09W2rnd4wDDx/w8vSs9MMJpw9m/O6Q0Jzm8O29qlteKresmzFNePsCKJSF3EEAza3PVuf062L1uY0J8Qg+eJa41bbmEEARloCjcWGpnSJBOMVy6+u7rXRGXgs5A2EsJMiliXeGusum/HtDjaXUHZRdo2x69FdsN3rh8yCfwMhja3B27F2ijMDenjZXAZ7yqb3OnhPLbITuvdy117nYFut24NXKjNANe+5a3tdiHWwPnp8eXEwULWL8vYYuyDuG9r7LdDn4L3fRz2SsM/BPTLf3k/c5+C930c9krDPwT0y395P3Ofgvd9HPZKwz8E9Mt/eT9wTB+/92vVJqO9pfUbo1Rboi+Be7V76IriX+7fPwX0O7u0W6OX67bgHv/Uv1L3cEL1Vve4ONucaFKWs1ZHqvvLesoD5zMD8F0vum5ub77b/gWcURWEUxVCIoriOOi9U90EUddlgr6pjL267mXPzasYObmhouNu3tLRc9+STTzblcrlEqMs5Fwl8J6hy6NYHbu+0g5zZvVhGLnR0dCQeffTRJrnzOj99+vRn6urqvvj444/T1NQUWDQLQgF9kN9rbaCAxCCbzWIgpyIIBWk5l3Xr1n3RfOuVfhOf/vSnb5eTp86ZM2e2BsNOKHTWqO6DRx99L9jA/0pXfX391E9+8pO3m2//PwAAAP//5kp81wAAAAZJREFUAwDKryKlfLhuFgAAAABJRU5ErkJggg=="/>
                                    </defs>
                                </svg>
                                <svg width="56" height="37" viewBox="0 0 56 37" fill="none" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <rect width="56" height="37" fill="url(#pattern0_3_3171)"/>
                                    <defs>
                                        <pattern id="pattern0_3_3171" patternContentUnits="objectBoundingBox" width="1" height="1">
                                            <use xlink:href="#image0_3_3171" transform="matrix(0.00833333 0 0 0.0126126 0 -0.0045045)"/>
                                        </pattern>
                                        <image id="image0_3_3171" width="120" height="80" preserveAspectRatio="none" xlink:href="data:image/png;base64,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"/>
                                    </defs>
                                </svg>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ──────────── Product Sliders: Recommended + Shoppers Also Viewed ──── --}}
            <div class="flex flex-col gap-8 mt-6 px-1 lg:px-0">

                {{-- Section 1: Recommended products --}}
                @if ($recommended->isNotEmpty())
                <div class="flex flex-col gap-8">
                    <h2 class="text-2xl font-medium text-[#171717]">{{ __('Recommended products') }}:</h2>
                    <div class="relative overflow-hidden">
                        <div class="swiper product-slid-swiper w-full select-none">
                            <div class="swiper-wrapper">
                                @foreach ($recommended as $recProduct)
                                <div class="swiper-slide">
                                    @include('themes.elora.pages._product-card', ['product' => $recProduct])
                                </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- Right gradient + next arrow overlay --}}
                        {{--<div class="pointer-events-none absolute right-0 top-0 h-full w-28 bg-gradient-to-r from-transparent to-zinc-100 z-10"></div>
                            <button type="button" data-swiper-target="product-slid-swiper"
                                class="elora-swiper-next-btn absolute right-11 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white rounded-3xl flex items-center justify-center pointer-events-auto"
                                style="box-shadow:4px 4px 10px rgba(88,88,88,0.33)">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="#242424" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 18l6-6-6-6" />
                                </svg>
                            </button>
                        </div>--}}
                </div>
                @endif

                {{-- Section 2: Shoppers also viewed --}}
                @if ($hotDeals->isNotEmpty() || $recommended->isNotEmpty())
                <div class="flex flex-col gap-8">
                    <h2 class="text-2xl font-medium text-[#171717]">{{ __('Shoppers also viewed') }}:</h2>
                    <div class="relative overflow-hidden">
                        <div class="swiper shoppers-slid-swiper w-full select-none">
                            <div class="swiper-wrapper">
                                @foreach ($hotDeals->isNotEmpty() ? $hotDeals : $recommended as $recProduct)
                                <div class="swiper-slide">
                                    @include('themes.elora.pages._product-card', ['product' => $recProduct])
                                </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- Right gradient + next arrow overlay --}}
                        {{--<div class="pointer-events-none absolute right-0 top-0 h-full w-28 bg-gradient-to-r from-transparent to-zinc-100 z-10"></div>
                        <button type="button" data-swiper-target="shoppers-slid-swiper"
                            class="elora-swiper-next-btn absolute right-11 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-white rounded-3xl flex items-center justify-center pointer-events-auto"
                            style="box-shadow:4px 4px 10px rgba(88,88,88,0.33)">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="#242424" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 18l6-6-6-6" />
                            </svg>
                        </button>
                    </div>--}}
                </div>
                @endif

            </div>
    </div>
    @endif
    </div>
</main>

@push('scripts')
    @include('storefront.partials.confirm-remove-cart-item')
@vite('resources/js/elora/cart.js')
@endpush

@push('scripts')
    @include('storefront.partials.mobile-buybar-offset')
@endpush
