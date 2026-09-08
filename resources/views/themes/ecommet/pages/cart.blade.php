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
    <div class="max-w-[1280px] mx-auto px-4 lg:px-8">

        {{-- ── Breadcrumb ──────────────────────────────────────────────────── --}}
        <div class="hidden lg:flex items-center gap-2 text-[13px] text-[#707070] mb-8">
            <a href="{{ route('tenant.home') }}" class="hover:text-primary transition">{{ __('Home') }}</a>
            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" alt="{{ __('arrow') }}"
                class="w-[10px] h-[10px]">
            <span class="text-[#242424] font-medium">{{ __('Cart') }}</span>
        </div>

        {{-- ── Empty Cart ───────────────────────────────────────────────────── --}}
        @if ($itemCount === 0)
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <svg class="w-20 h-20 text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h2 class="text-[22px] font-bold text-[#242424] mb-2">{{ __('Your cart is empty') }}</h2>
                <p class="text-[14px] text-[#707070] mb-8">{{ __('Looks like you have not added anything yet.') }}</p>
                <a href="{{ route('tenant.home') }}"
                    class="bg-[#242424] text-white px-10 py-3 rounded-full text-[14px] font-bold hover:bg-black transition">
                    {{ __('Continue Shopping') }}
                </a>
            </div>

            {{-- ── Cart with items ─────────────────────────────────────────────── --}}
        @else
            <div class="flex flex-col lg:flex-row gap-8 items-start">

                {{-- ──────────────────── LEFT COLUMN ───────────────────────── --}}
                <div class="w-full lg:flex-1 flex flex-col gap-0">

                    {{-- Free Shipping Banner --}}
                    @if($shippingThreshold > 0)
                    <div class="mb-4 lg:mb-10 block p-1 lg:p-0">
                        <div class="bg-[#ffe8e6] w-full text-[#222] rounded-md px-4 py-3 shipping-modal-trigger cursor-pointer">
                            <div class="flex items-center gap-2 text-[12px] lg:text-[13px] font-medium mb-2">
                                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/group-cart.png') }}" alt="{{ __('Shipping') }}"
                                    class="w-[18px] h-[18px] lg:w-[20px] lg:h-[20px]">
                                @if ($cartWeight >= $shippingThreshold)
                                    <span>{{ __("You've reached free shipping!") }}</span>
                                @else
                                    <span>{{ __('Add :weight more to unlock free shipping', ['weight' => number_format($shippingThreshold - $cartWeight) . __('g')]) }}</span>
                                @endif
                            </div>
                            <div class="h-2 bg-white/70 rounded-full overflow-hidden">
                                <div class="h-full bg-[#242424] rounded-full transition-all duration-500" style="width: {{ min(100, (int) round($cartWeight / $shippingThreshold * 100)) }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Select All & Filter --}}
                    <div class="flex flex-col gap-0 mb-8">
                        <div class="flex items-center justify-between pb-4 border-b border-[#eaeaea]">
                            <label class="flex items-center gap-2 lg:gap-3 cursor-pointer">
                                <input type="checkbox" id="cartSelectAll" checked
                                    class="w-[18px] h-[18px] lg:w-[20px] lg:h-[20px] rounded-[3px] appearance-none bg-white border border-[#ccc] checked:bg-[#242424] checked:border-[#242424] cursor-pointer flex items-center justify-center relative before:content-[''] before:absolute before:hidden checked:before:block before:w-[4px] lg:before:w-[5px] before:h-[9px] lg:before:h-[11px] before:border-r-2 before:border-b-2 before:border-white before:transform before:rotate-45 before:-mt-0.5">
                                <span class="text-[14px] font-[500] text-[#242424]"
                                    id="cartSelectionLabel">{{ __('Select all (:count)', ['count' => $itemCount]) }}</span>
                            </label>
                            <div class="flex items-center gap-1.5 lg:gap-2">
                                <button type="button" data-cart-filter="all"
                                    class="cart-filter-btn border border-[#808080] text-[#808080] font-[400] text-[12px] px-4 py-1.5 rounded-full hover:bg-gray-50 transition min-w-[70px]">
                                    {{ __('All (:count)', ['count' => $itemCount]) }}
                                </button>
                                <button type="button" data-cart-filter="selected"
                                    class="cart-filter-btn cart-filter-active border border-[#242424] bg-white text-[#242424] font-[400] text-[12px] px-4 py-1.5 rounded-full min-w-[100px]">
                                    {{ __('Selected') }} (<span id="selectedItemsCount">{{ $itemCount }}</span>)
                                </button>
                            </div>
                        </div>

                        {{-- ── Cart Line Items ──────────────────────────────── --}}
                        <div class="flex flex-col gap-0 shopping-table">
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
                                @endphp

                                <div class="cart-line-item flex gap-3 lg:gap-5 pt-6 relative items-center border-b border-[#eaeaea] pb-6"
                                    data-cart-line="{{ $key }}" data-subtotal="{{ $subtotal }}">

                                    {{-- Checkbox --}}
                                    <div class="flex items-center shrink-0">
                                        <input type="checkbox"
                                            class="cart-line-checkbox w-[18px] h-[18px] lg:w-[20px] lg:h-[20px] rounded-[3px] appearance-none bg-white border border-[#ccc] checked:bg-[#242424] checked:border-[#242424] cursor-pointer flex items-center justify-center relative before:content-[''] before:absolute before:hidden checked:before:block before:w-[4px] lg:before:w-[5px] before:h-[9px] lg:before:h-[11px] before:border-r-2 before:border-b-2 before:border-white before:transform before:rotate-45 before:-mt-0.5"
                                            checked>
                                    </div>

                                    {{-- Product Image --}}
                                    <a href="{{ $productUrl }}"
                                        class="w-[95px] h-[95px] lg:w-[130px] lg:h-[130px] bg-[#f9f9f9] rounded flex-shrink-0 relative flex items-center justify-center overflow-hidden">
                                        @if ($img)
                                            <img loading="lazy" src="{{ $img }}" alt="{{ $productName }}" class="w-full h-full object-contain p-2">
                                        @else
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </a>

                                    {{-- Product Details --}}
                                    <div
                                        class="flex-1 flex flex-col justify-between py-1 h-full min-h-[95px] lg:min-h-[130px] min-w-0">
                                        <div class="flex justify-between items-start relative">
                                            <div class="pr-6 lg:pr-8">
                                                <a href="{{ $productUrl }}"
                                                    class="text-[#242424] text-[12px] lg:text-[13.5px] font-[400] leading-[1.3] lg:leading-[1.4] tracking-tight line-clamp-2 block hover:text-black transition">
                                                    {{ $productName }}
                                                </a>
                                                @if ($variantTitle)
                                                    <div
                                                        class="text-[#808080] text-[12px] lg:text-[13px] mt-1 lg:mt-1.5 font-[400] truncate max-w-[150px] lg:max-w-none">
                                                        {{ $variantTitle }}
                                                    </div>
                                                @endif
                                                @if($item['is_out_of_stock'] ?? false)
                                                    <div class="text-red-600 text-[11px] lg:text-xs font-semibold mt-1 lg:mt-1.5 flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                        </svg>
                                                        {{ __('This item is currently out of stock') }}
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Remove Button --}}
                                            <button wire:click="removeFromCart('{{ $key }}')"
                                                class="item-remove text-[#999] hover:text-[#333] transition absolute top-0 right-0"
                                                type="button" aria-label="{{ __('Remove item') }}">
                                                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/trash.png') }}" alt="{{ __('Trash') }}"
                                                    class="w-[18px] h-[18px] lg:w-[20px] lg:h-[20px]">
                                            </button>
                                        </div>

                                        <div class="flex items-end justify-between mt-2 lg:mt-4 gap-3">
                                            {{-- Subtotal --}}
                                            <span class="text-[14px] lg:text-[16px] font-[400] text-[#242424] leading-none">
                                                {{ $symbol }}&nbsp;{{ $displaySubtotal }}
                                            </span>

                                            {{-- Qty Dropdown --}}
                                            <div class="relative qty-dropdown-container">
                                                <input type="hidden" class="cart-qty-input" value="{{ $qty }}">
                                                <button type="button"
                                                    class="qty-trigger flex items-center justify-between border border-[#707070] rounded-md px-2 lg:px-3 py-1 bg-white h-[28px] lg:h-[30px] min-w-[55px] lg:min-w-[90px] cursor-pointer hover:border-[#333] transition">
                                                    <span
                                                        class="qty-value text-[11px] lg:text-[12px] font-medium text-[#242424]">
                                                        <span class="lg:inline hidden">{{ __('Qty') }} </span>
                                                        <span class="current-qty">{{ $qty }}</span>
                                                    </span>
                                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-down-black.png') }}"
                                                        alt="{{ __('arrow') }}"
                                                        class="qty-chevron w-[10px] h-[10px] lg:w-[14px] lg:h-[14px] object-contain transition-transform duration-200">
                                                </button>
                                                <div
                                                    class="qty-menu hidden absolute top-full left-0 right-0 mt-1 bg-white border border-[#eaeaea] rounded-lg shadow-xl z-50 overflow-hidden min-w-[65px]">
                                                    @for ($n = 1; $n <= 10; $n++)
                                                        <div class="qty-item px-3 py-2 text-[12px] {{ $n === $qty ? 'bg-[#f6f6f6] font-bold' : 'font-medium hover:bg-gray-50' }} text-[#242424] cursor-pointer {{ $n < 10 ? 'border-b border-[#f0f0f0]' : '' }} flex items-center justify-between"
                                                            data-value="{{ $n }}" wire:click="updateQty('{{ $key }}', {{ $n }})">
                                                            <span>{{ $n }}</span>
                                                            @if ($n === $qty)
                                                                <img loading="lazy" src="{{ asset('ecommet/assets/images/home/trueIcon.png') }}"
                                                                    alt="{{ __('Selected') }}" class="w-3 h-3">
                                                            @endif
                                                        </div>
                                                    @endfor
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Mobile CTA --}}
                        <div class="lg:hidden mt-8 mb-4">
                            <a href="{{ route('tenant.storefront.checkout') }}"
                                class="w-full bg-[#232323] text-white py-4 rounded-full font-bold text-[15px] shadow-sm transition active:scale-[0.98] uppercase tracking-wider flex items-center justify-center">
                                {{ __('Checkout') }}
                            </a>
                        </div>

                        {{-- Mobile Trust Badges --}}
                        <div class="lg:hidden mt-10 -mx-5 bg-[#f6f6f6]">
                            <div class="h-[12px]"></div>
                            <div class="bg-white py-6 px-4">
                                <div class="flex items-start justify-between w-full gap-1">
                                    <div class="flex flex-col items-center gap-1.5 text-center w-1/4">
                                        <div class="h-[24px] flex items-center justify-center">
                                            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/marketeq_secure.png') }}"
                                                class="w-[21px] h-[21px] object-contain"
                                                style="filter: brightness(0) saturate(100%) invert(42%) sepia(76%) saturate(518%) hue-rotate(76deg);">
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-[#2AAF2F] leading-[1.25]">{{ __('Safe Payment') }}</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-1.5 text-center w-1/4">
                                        <div class="h-[24px] flex items-center justify-center">
                                            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/secureCart.png') }}"
                                                class="w-[21px] h-[21px] object-contain"
                                                style="filter: brightness(0) saturate(100%) invert(42%) sepia(76%) saturate(518%) hue-rotate(76deg);">
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-[#2AAF2F] leading-[1.25]">{{ __('Secure Privacy') }}</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-1.5 text-center w-1/4">
                                        <div class="h-[24px] flex items-center justify-center">
                                            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/group-product-details.png') }}"
                                                class="w-[21px] h-[21px] object-contain"
                                                style="filter: brightness(0) saturate(100%) invert(42%) sepia(76%) saturate(518%) hue-rotate(76deg);">
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-[#2AAF2F] leading-[1.25]">{{ __('Delivery Guarantee') }}</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-1.5 text-center w-1/4">
                                        <div class="h-[24px] flex items-center justify-center">
                                            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/marketeq_secure.png') }}"
                                                class="w-[21px] h-[21px] object-contain"
                                                style="filter: brightness(0) saturate(100%) invert(42%) sepia(76%) saturate(518%) hue-rotate(76deg);">
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-[#2AAF2F] leading-[1.25]">{{ __('Purchase Protection') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="h-[12px]"></div>
                        </div>
                    </div>

                    {{-- ── Recommended Products ─────────────────────────────── --}}
                    @if ($relatedProducts->isNotEmpty())
                        <div class="lg:mt-6 p-1 lg:p-0">
                            <h2 class="text-[18px] font-bold text-[#333] mb-5">{{ __('You may also like') }}</h2>
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4">
                                @foreach ($relatedProducts as $recProduct)
                                    @include('themes.ecommet.pages._product-card', ['product' => $recProduct])
                                @endforeach
                            </div>
                            <div class="flex justify-center mt-10">
                                <a href="{{ route('tenant.storefront.best-selling') }}"
                                    class="bg-[#242424] text-white px-10 py-3 rounded-full text-[14px] font-bold hover:bg-black transition flex items-center gap-2">
                                    {{ __('See More') }}
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-down-seemore.png') }}"
                                        alt="{{ __('Arrow right') }}" class="w-[18px] h-[18px] lg:w-[24px] lg:h-[24px]">
                                </a>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- ──────────── RIGHT: ORDER SUMMARY ─────────────────────── --}}
                <div class="hidden lg:block w-full lg:w-[350px] xl:w-[380px] shrink-0">
                    <div id="cartSummary">
                        <div
                            class="bg-white rounded-xl shadow-[0_0_10px_rgba(0,0,0,0.03)] border border-[#eaeaea] p-7 sticky top-4">
                            <h2 class="text-[20px] font-[500] text-[#242424] mb-5">{{ __('Order Summary') }}</h2>

                            {{-- Item Total --}}
                            <div class="flex justify-between items-end mb-3.5">
                                <span class="text-[16px] font-medium text-[#000000]">{{ __('Item total:') }}</span>
                                <span class="text-[16px] font-bold text-[#000000]">
                                    {{ $symbol }}<span data-cart-item-total>{{ $displayTotal }}</span>
                                </span>
                            </div>

                            {{-- Discount Row --}}
                            @if ($appliedCoupon && $cartDiscount > 0)
                                <div class="flex justify-between items-end mb-3.5">
                                    <span class="text-[14px] font-medium text-[#000000] flex items-center gap-2">
                                        {{ __('Coupon') }}
                                        <span
                                            class="text-[11px] font-bold bg-[#f0f0f0] text-[#555] px-2 py-0.5 rounded uppercase tracking-wide">
                                            {{ $appliedCoupon->code }}
                                        </span>
                                    </span>
                                    <span class="text-[16px] font-bold text-[#ED181A]">
                                        -{{ $symbol }}{{ $displayDiscount }}
                                    </span>
                                </div>
                            @endif

                            {{-- Coupon Input or Applied --}}
                            <div class="mb-5">
                                @if ($appliedCoupon)
                                    <div
                                        class="flex items-center justify-between bg-[#f0fdf4] border border-[#bbf7d0] rounded px-3 py-2">
                                        <span class="text-[12px] text-[#166534] font-medium">
                                            {{ __('":code" applied', ['code' => $appliedCoupon->code]) }}
                                        </span>
                                        <button wire:click="removeCoupon" type="button"
                                            class="text-[11px] text-[#dc2626] font-semibold hover:underline ml-3 shrink-0">
                                            {{ __('Remove') }}
                                        </button>
                                    </div>
                                @else
                                    <div
                                        class="flex items-center gap-0 w-full relative border border-[#bbb] rounded overflow-hidden h-[38px]">
                                        <input wire:model="couponCode" type="text" placeholder="{{ __('Enter discount code') }}"
                                            class="flex-1 px-3 py-2 text-[12px] outline-none min-w-0 bg-transparent h-full placeholder-[#999]"
                                            wire:keydown.enter="applyCoupon">
                                        <button wire:click="applyCoupon" type="button"
                                            class="bg-[#787878] text-white px-6 w-[90px] text-[13px] font-bold hover:bg-[#555] transition border-l border-[#bbb] h-full flex items-center justify-center leading-none">
                                            {{ __('Apply') }}
                                        </button>
                                    </div>
                                    @error('coupon')
                                        <p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p>
                                    @enderror
                                @endif
                            </div>

                            {{-- Grand Total --}}
                            <div class="flex justify-between items-end mb-4 pt-4 border-t border-[#eaeaea]">
                                <span class="text-[20px] font-bold text-[#000000]">{{ __('Total') }}</span>
                                <span class="text-[20px] font-bold text-[#000000]">
                                    {{ $symbol }}<span data-cart-total>{{ $displayFinal }}</span>
                                </span>
                            </div>

                            <p class="text-[14px] font-[400] text-[#707070] mb-5 leading-[1.6]">
                                {{ __('Please refer to your final actual payment amount. Tax & levies excluded.') }}
                            </p>

                            {{-- Checkout Button --}}
                            <a href="{{ route('tenant.storefront.checkout') }}"
                                class="w-full bg-[#242424] text-white py-4 rounded-full text-[16px] font-bold hover:bg-black transition mb-8 tracking-wide flex items-center justify-center">
                                {{ __('Checkout') }}
                            </a>

                            {{-- Trust: Safe Payment --}}
                            <div class="flex flex-col border-t border-[#eaeaea] pt-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/marketeq_secure.png') }}" alt="{{ __('Secure') }}"
                                        class="w-[20px] h-[20px]">
                                    <h3 class="text-[14px] font-bold text-[#242424]">{{ __('Safe Payment Options') }}</h3>
                                </div>
                                <p class="text-[12px] font-[400] text-[#808080] leading-relaxed mb-5">
                                    {{ __('We follow PCI DSS standards, use strong encryption, and perform regular security reviews to protect your payment information.') }}
                                </p>
                                <h4 class="text-[14px] font-bold text-[#242424] mb-3">{{ __('Payment methods') }}</h4>
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/visa.png') }}" alt="{{ __('Visa') }}"
                                        class="w-[45px] h-[30px] object-contain">
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/mastercard.png') }}" alt="{{ __('Mastercard') }}"
                                        class="w-[45px] h-[30px] object-contain">
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/pay.png') }}" alt="{{ __('Apple Pay') }}"
                                        class="w-[45px] h-[30px] object-contain">
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/fawry.png') }}" alt="{{ __('Fawry') }}"
                                        class="w-[45px] h-[30px] object-contain">
                                </div>
                            </div>

                            {{-- Trust: Purchase Protection --}}
                            <div class="pt-4 border-t border-[#eaeaea]">
                                <div class="flex items-center gap-2 mb-3">
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/marketeq_secure.png') }}"
                                        alt="{{ __('Protection') }}" class="w-[22px] h-[22px]">
                                    <h3 class="text-[14px] font-bold text-[#333]">{{ __('Purchase Protection') }}</h3>
                                </div>
                                <p class="text-[13px] text-[#808080] leading-relaxed mb-2">
                                    {{ __('Shop confidently knowing that if something goes wrong, we have always got your back.') }}
                                </p>
                            </div>

                            {{-- Trust: Delivery Guarantee --}}
                            <div class="border-t border-[#eaeaea] pt-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <img loading="lazy" src="{{ asset('ecommet/assets/images/home/group-product-details.png') }}"
                                        alt="{{ __('Delivery') }}" class="w-[20px] h-[20px]">
                                    <h3 class="text-[14px] font-bold text-[#333]">{{ __('Delivery Guarantee') }}</h3>
                                </div>
                                <ul class="text-[12px] text-[#808080] grid grid-cols-2 gap-y-3 gap-x-2">
                                    <li class="flex items-start gap-1.5">
                                        <img loading="lazy" src="{{ asset('ecommet/assets/images/home/trueIcon.png') }}" alt="{{ __('Check') }}"
                                            class="w-[12px] h-[12px] mt-0.5">
                                        <span>{{ __('Credit for delay') }}</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <img loading="lazy" src="{{ asset('ecommet/assets/images/home/trueIcon.png') }}" alt="{{ __('Check') }}"
                                            class="w-[12px] h-[12px] mt-0.5">
                                        <span>{{ __('Return if damaged') }}</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <img loading="lazy" src="{{ asset('ecommet/assets/images/home/trueIcon.png') }}" alt="{{ __('Check') }}"
                                            class="w-[12px] h-[12px] mt-0.5">
                                        <span>{{ __('15-day no update refund') }}</span>
                                    </li>
                                    <li class="flex items-start gap-1.5">
                                        <img loading="lazy" src="{{ asset('ecommet/assets/images/home/trueIcon.png') }}" alt="{{ __('Check') }}"
                                            class="w-[12px] h-[12px] mt-0.5">
                                        <span>{{ __('45-day no delivery refund') }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @endif
    </div>
</main>

@push('scripts')
@vite('resources/js/ecommet/cart.js')
@endpush
