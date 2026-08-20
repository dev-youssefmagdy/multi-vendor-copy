@section('pageData','checkout')

<main class="flex-grow bg-white pb-[160px] sm:pb-20">
    <div class="max-w-[1280px] mx-auto px-5 sm:px-8 mt-3 md:mt-6">
        <h1 class="text-[24px] font-bold text-[#242424] mb-8 lg:hidden">{{ __('Check out') }}</h1>
        {{-- ── Breadcrumb ──────────────────────────────────────────────────── --}}
        <div class="hidden md:flex items-center gap-2 text-[11px] font-[500] text-[#999] mb-8">
            <a href="{{ route('tenant.home') }}"  class="hover:text-black transition">{{ __('Home') }}</a>
            <svg class="w-[5px] h-[5px] text-[#707070]" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('tenant.storefront.cart') }}"
                class="hover:text-black transition">{{ __('Cart') }}</a>
            <svg class="w-[5px] h-[5px] text-[#707070]" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-[#242424] font-bold">{{ __('Checkout') }}</span>
        </div>

        {{-- ── Flash error ─────────────────────────────────────────────────── --}}
        @if (session('error') || $errors->has('payment'))
        <div class="mb-4 rounded-[10px] border border-[#fecaca] bg-[#fef2f2] px-4 py-3 text-[13px] text-[#dc2626]">
            {{ session('error') ?? $errors->first('payment') }}
        </div>
        @endif

        {{-- ══════════════════════════════════ MAIN FORM ═════════════════════════════════════ --}}
        <form wire:submit.prevent="placeOrder" id="checkout-form">
            <div class="flex flex-col-reverse md:flex-row gap-10 md:gap-[40px] lg:gap-[130px] items-start">

                {{-- ══════════════════════ LEFT COLUMN ══════════════════════ --}}
                <div class="flex-1 w-full max-w-full">

                    {{-- ───────────────── SHIPPING ADDRESS ────────────────── --}}
                    <section class="mb-10">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[20px] font-[500] text-[#101828]">{{ __('Shipping address') }}</h2>
                            <button type="button" wire:click="openAddressModal('shipping', true)"
                                class="text-[13px] text-main hover:underline flex items-center gap-1">
                                {{ __('Add new address') }}
                                <svg width="7" height="12" viewBox="0 0 7 12" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 11L6 6L1 1" stroke="#FF824C" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>

                        @if ($hasAddresses)
                        {{-- Saved address cards --}}
                        <div class="flex flex-col gap-2.5">
                            @foreach ($savedAddresses as $addr)
                            <label wire:key="ship-addr-{{ $addr->id }}"
                                class="flex items-start gap-3 rounded-[8px] border px-4 py-3 cursor-pointer transition
                                               {{ $data['shipping']['address_id'] === $addr->id ? 'border-[#D4D4D4] bg-[#FDFDFD]' : 'border-[#dcdcdc] bg-[#fafafa] hover:border-[#868686]' }}">
                                <input type="radio" wire:click="selectShippingAddress({{ $addr->id }})"
                                    name="shipping_address_sel" value="{{ $addr->id }}"
                                    @checked($data['shipping']['address_id']===$addr->id) class="mt-0.5 accent-black
                                shrink-0">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-[13px] font-semibold text-[#242424] flex gap-12 items-center">
                                            {{ $addr->full_name }} @if ($addr->phone)
                                            <span
                                                class="text-[12px] text-[#8F8F8F] font-normal">{{ $addr->phone }}</span>
                                            @endif
                                        </h4>
                                        <div class="flex items-center gap-2">
                                            @if ($addr->is_default)
                                            <span
                                                class="text-[10px] bg-[#242424] text-white px-2 py-0.5 rounded-full">{{ __('Default') }}</span>
                                            @endif
                                            <button type="button"
                                                wire:click.stop="openAddressModal('shipping', false, {{ $addr->id }})"
                                                class="text-[11px] text-main hover:underline shrink-0">{{ __('Edit') }}</button>
                                        </div>
                                    </div>
                                    <p class="text-[12px] text-[#8F8F8F] font-normal my-3">{{ $addr->email }}</p>
                                    <p class="text-[12px] text-[#8F8F8F] leading-snug">{{ $addr->oneliner }}</p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @else
                        {{-- appear please add new address --}}
                        <div class="flex flex-col gap-2.5">
                            <p class="text-[13px] text-[#555]">{{ __('No saved addresses found. Please add a new address.') }}</p>
                        </div>

                        @endif

                        {{-- Validation errors when saved addresses exist --}}
                        @if ($hasAddresses)
                        @error('data.shipping.name')<p class="text-[11px] text-[#dc2626] mt-2">{{ $message }}</p>
                        @enderror
                        @error('data.shipping.address')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>
                        @enderror
                        @endif
                    </section>

                    {{-- ──────────────── BILLING ADDRESS TOGGLE ────────────── --}}
                    <section class="mb-10">
                        <label class="flex items-center gap-2.5 cursor-pointer w-max">
                            <input wire:model.live="data.billing.same_as_shipping" type="checkbox"
                                class="w-3.5 h-3.5 appearance-none border border-[#ccc] rounded-[2px] checked:bg-black checked:border-black cursor-pointer relative
                                       before:content-[''] before:absolute before:hidden checked:before:block
                                       before:w-[4px] before:h-[8px] before:border-r-[1.5px] before:border-b-[1.5px]
                                       before:border-white before:transform before:rotate-45 before:left-[4px] before:top-[1px]">
                            <span
                                class="text-[11px] text-[#888] font-medium tracking-wide">{{ __('Billing address is same as shipping') }}</span>
                        </label>

                        @if (!$data['billing']['same_as_shipping'])
                        <div class="mt-5">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-[18px] font-[500] text-[#101828]">{{ __('Billing address') }}</h3>
                                <button type="button" wire:click="openAddressModal('billing', true)"
                                    class="text-[13px] font-semibold text-main hover:underline flex items-center gap-1">
                                    {{ __('Add new address') }}
                                    <svg width="7" height="12" viewBox="0 0 7 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 11L6 6L1 1" stroke="#FF824C" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </div>

                            @if ($hasAddresses)
                            <div class="flex flex-col gap-2.5">
                                @foreach ($savedAddresses as $addr)
                                <label wire:key="bill-addr-{{ $addr->id }}"
                                    class="flex items-start gap-3 rounded-[8px] border px-4 py-3 cursor-pointer transition {{ $data['billing']['address_id'] === $addr->id ? 'border-[#D4D4D4] bg-[#FDFDFD]' : 'border-[#dcdcdc] bg-[#fafafa] hover:border-[#868686]' }}">
                                    <input type="radio" wire:click="selectBillingAddress({{ $addr->id }})"
                                        name="billing_address_sel" value="{{ $addr->id }}"
                                        @checked($data['billing']['address_id']===$addr->id) class="mt-0.5 accent-black
                                    shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <h4
                                                class="text-[13px] font-semibold text-[#242424] flex gap-12 items-center">
                                                {{ $addr->full_name }} @if ($addr->phone)
                                                <span
                                                    class="text-[12px] text-[#8F8F8F] font-normal">{{ $addr->phone }}</span>
                                                @endif
                                            </h4>
                                            <div class="flex items-center gap-2">
                                                @if ($addr->is_default)
                                                <span
                                                    class="text-[10px] bg-[#242424] text-white px-2 py-0.5 rounded-full">{{ __('Default') }}</span>
                                                @endif
                                                <button type="button"
                                                    wire:click.stop="openAddressModal('billing', false, {{ $addr->id }})"
                                                    class="text-[11px] text-main hover:underline shrink-0">{{ __('Edit') }}</button>
                                            </div>
                                        </div>
                                        <p class="text-[12px] text-[#8F8F8F] font-normal my-3">{{ $addr->email }}</p>
                                        <p class="text-[12px] text-[#8F8F8F] leading-snug">{{ $addr->oneliner }}</p>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @else
                            <div class="flex flex-col gap-3">
                                <div>
                                    <input wire:model="data.billing.name" type="text"
                                        placeholder="{{ __('Full name') }}"
                                        class="w-full border @error('data.billing.name') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition">
                                    @error('data.billing.name')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}
                                    </p>@enderror
                                </div>
                                <div>
                                    <input wire:model="data.billing.email" type="email"
                                        placeholder="{{ __('Email address') }}"
                                        class="w-full border @error('data.billing.email') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition">
                                    @error('data.billing.email')<p class="text-[11px] text-[#dc2626] mt-1">
                                        {{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <input wire-event="data.billing.phone" value="{{ $data['billing']['phone'] ?? '' }}" type="tel" data-phone-input
                                        placeholder="{{ __('Phone number') }}"
                                        class="w-full border @error('data.billing.phone') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition">
                                    @error('data.billing.phone')<p class="text-[11px] text-[#dc2626] mt-1">
                                        {{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <textarea wire:model="data.billing.address" rows="2"
                                        placeholder="{{ __('Street address, city, country') }}"
                                        class="w-full border @error('data.billing.address') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition resize-none"></textarea>
                                    @error('data.billing.address')<p class="text-[11px] text-[#dc2626] mt-1">
                                        {{ $message }}</p>@enderror
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                    </section>

                    {{-- ──────────────────── PAYMENT ───────────────────────── --}}
                    <section>
                        <h2 class="text-[20px] font-[500] text-[#242424] mb-4">{{ __('Payment details') }}</h2>

                        <div class="flex flex-col gap-2.5">

                            {{-- Payment gateways --}}
                            @foreach ($gateways as $gateway)
                            <label wire:key="gw-{{ $gateway['id'] }}"
                                class="flex items-center gap-3 rounded-[6px] border px-4 py-3 cursor-pointer hover:border-[#242424] transition bg-white
                                           {{ $data['payment']['method'] === $gateway['code'] ? 'border-[#242424]' : 'border-[#dcdcdc]' }}">
                                <input type="radio"
                                    wire:click="selectPayment('{{ $gateway['code'] }}', {{ $gateway['id'] }})"
                                    name="payment_choice" value="{{ $gateway['code'] }}"
                                    @checked($data['payment']['method']===$gateway['code']) class="accent-black">
                                <div class="flex items-center gap-2.5 flex-1">
                                    <svg class="w-4 h-4 text-[#555]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <span class="text-[14px] font-medium text-[#242424]">{{ $gateway['name'] }}</span>
                                    @if(isset($gateway['logo_url']) && $gateway['logo_url'])
                                        <img src="{{ $gateway['logo_url'] }}" alt="{{ $gateway['name'] }}"
                                             class="h-6 object-contain ml-auto">
                                    @endif
                                </div>
                            </label>
                            @endforeach

                        </div>

                        @error('data.payment.method')
                        <p class="text-[11px] text-[#dc2626] mt-2">{{ $message }}</p>
                        @enderror
                        <p id="payment-method-error" class="text-[11px] text-[#dc2626] mt-2 hidden"></p>

                        {{-- ── Inline card panel (Stripe / AuthorizeNet / 2Checkout) ─── --}}
                        @if ($activeInlineGateway)
                        <div id="inline-card-form" class="mt-4 border border-[#dcdcdc] rounded-[8px] p-4 bg-[#fafafe]"
                            data-gateway="{{ $activeInlineGateway['code'] }}"
                            @if($activeInlineGateway['code']==='stripe' )
                            data-stripe-key="{{ $activeInlineGateway['creds']['key'] ?? '' }}"
                            @elseif($activeInlineGateway['code']==='authorize_net' )
                            data-auth-login="{{ $activeInlineGateway['creds']['login_id'] ?? '' }}"
                            data-auth-client="{{ $activeInlineGateway['creds']['client_key'] ?? $activeInlineGateway['creds']['transaction_key'] ?? '' }}"
                            data-sandbox="{{ $activeInlineGateway['mode'] === 'test' ? '1' : '0' }}"
                            @elseif($activeInlineGateway['code']==='2checkout' )
                            data-2co-seller="{{ $activeInlineGateway['creds']['seller_id'] ?? '' }}" @endif>
                            <h4 class="text-[13px] font-semibold text-[#333] mb-3 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#16a34a] shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                {{ __('Secure card details') }}
                            </h4>

                            @if ($activeInlineGateway['code'] === 'stripe')
                            {{-- Stripe Elements mounts into this div --}}
                            <div id="stripe-card-element"
                                class="block w-full border border-[#dcdcdc] rounded-[6px] px-4 py-3 bg-white min-h-[42px]">
                            </div>
                            <p id="stripe-card-errors" class="text-[11px] text-[#dc2626] mt-1 hidden"></p>
                            <input type="hidden" id="stripe-token-input" wire:model="data.payment.stripe_token">
                            @else
                            {{-- Manual card fields for AuthorizeNet / 2Checkout --}}
                            <div class="flex flex-col gap-3">
                                <input type="text" id="card-number" inputmode="numeric" autocomplete="cc-number"
                                    placeholder="{{ __('Card number') }}"
                                    class="w-full border border-[#dcdcdc] rounded-[6px] px-4 py-2.5 text-[13px] text-[#333] placeholder-[#aaa] outline-none focus:border-[#242424] transition bg-white tracking-widest">
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="text" id="card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                        placeholder="{{ __('MM / YY') }}" maxlength="7"
                                        class="w-full border border-[#dcdcdc] rounded-[6px] px-4 py-2.5 text-[13px] text-[#333] placeholder-[#aaa] outline-none focus:border-[#242424] transition bg-white">
                                    <input type="text" id="card-cvc" inputmode="numeric" autocomplete="cc-csc"
                                        placeholder="{{ __('CVC') }}" maxlength="4"
                                        class="w-full border border-[#dcdcdc] rounded-[6px] px-4 py-2.5 text-[13px] text-[#333] placeholder-[#aaa] outline-none focus:border-[#242424] transition bg-white">
                                </div>
                                @if ($activeInlineGateway['code'] === 'authorize_net')
                                <input type="hidden" id="auth-net-descriptor" wire:model="data.payment.authnet_desc">
                                <input type="hidden" id="auth-net-value" wire:model="data.payment.authnet_value">
                                @elseif ($activeInlineGateway['code'] === '2checkout')
                                <input type="hidden" id="twoco-token-input" wire:model="data.payment.twoco_token">
                                @endif
                                <p id="card-errors" class="text-[11px] text-[#dc2626] hidden"></p>
                            </div>
                            @endif
                        </div>
                        @endif


                    </section>


                    {{-- Mobile pay button --}}
                    <div
                        class="sm:hidden fixed bottom-0 left-0 right-0 z-30 px-4 flex gap-4 items-start justify-center bg-white py-2 h-[152px]">
                        @if (!$hasAddresses)
                            <p class="text-red-600 text-xs font-medium text-center w-full mb-1">
                                {{ __('Please add a shipping address before placing your order.') }}
                            </p>
                        @endif
                        <button type="submit" id="pay-btn" @disabled(!$shippingAvailable || !$hasAddresses) wire:loading.attr="disabled"
                            wire:loading.class="opacity-60 cursor-not-allowed"
                            class="w-full bg-main text-white py-4 rounded-full font-bold text-[15px] shadow-sm transition active:scale-[0.98] uppercase tracking-wider flex items-center justify-center {{ !$shippingAvailable || !$hasAddresses ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <span wire:loading.remove>{{ __('Pay') }} {{ $formattedFinalTotal }}</span>
                            <span wire:loading class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                </svg>
                                {{ __('Processing...') }}
                            </span>
                        </button>
                        @if($cartDiscount > 0)
                        <p class="absolute -top-7 left-0 bg-[#FFB00A] rounded-se-2xl text-sm px-2 py-1">
                            {{ __('You save') }}
                            <span>{{ $formattedDiscount }}</span>
                        </p>
                        @endif
                    </div>

                </div>
                {{-- end LEFT COLUMN --}}

                {{-- ══════════════════════ RIGHT COLUMN ══════════════════════ --}}
                <div class="flex-1 w-full max-w-full pt-1 flex flex-col gap-4">

                    {{-- Grand total header --}}
                    <div class="hidden md:flex flex-col gap-1">
                        <div class="text-[14px] text-[#8F8F8F] leading-5">{{ __('Total') }}</div>
                        <div class="text-[24px] font-medium text-[#FF4D00] font-['Outfit'] leading-none">{{ $formattedFinalTotal }}</div>
                    </div>

                    @if($shippingThreshold > 0 && $cartShipping == 0)
                    {{-- Free shipping progress (only show when shipping is actually free) --}}
                    <div class="p-3 bg-[#ff4d0016] rounded-2xl border border-[#ff4d00]">
                        <p class="text-center text-[14px] text-[#FF4D00] font-medium mb-2">
                            @if ($remainingForFreeShipping <= 0)
                                {{ __("You've reached free shipping!") }}
                            @else
                                {{ __('Add :weight more to qualify for free shipping', ['weight' => $remainingForFreeShipping >= 1000 ? number_format($remainingForFreeShipping / 1000, 2) . __('kg') : number_format($remainingForFreeShipping) . __('g')]) }}
                            @endif
                        </p>
                        <div class="h-2 bg-[#D9D9D9] rounded-full overflow-hidden">
                            <div class="h-full bg-[#FF4D00] rounded-full transition-all duration-500" style="width: {{ $shippingPct }}%"></div>
                        </div>
                    </div>
                    @endif

                    {{-- Order items --}}
                    <div class="flex flex-col gap-4">
                        @foreach ($cartItems as $item)
                        @php
                        $coVariant    = $item['display_variant'] ?? null;
                        $coIsFlash    = $item['is_flash_sale'] ?? false;
                        $coHasDiscount= $item['has_discount'] ?? false;
                        $coDiscountPct= $coIsFlash
                            ? (int) round($item['flash_sale_percentage'] ?? 0)
                            : ($coHasDiscount ? (int) round($item['discount_percentage'] ?? 0) : 0);
                        $coDeliveryFrom = \Carbon\Carbon::now()->addDays(7)->translatedFormat('j M');
                        $coDeliveryTo   = \Carbon\Carbon::now()->addDays(10)->translatedFormat('j M');
                        @endphp

                        <div class="flex items-start gap-4">
                            {{-- Product image --}}
                            @if ($item['display_image'])
                            <img loading="lazy" src="{{ $item['display_image'] }}" alt="{{ $item['display_name'] }}"
                                class="w-[123px] h-[116px] object-cover rounded-lg flex-shrink-0">
                            @else
                            <div class="w-[123px] h-[116px] bg-[#f9f9f9] rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            @endif

                            {{-- Info --}}
                            <div class="flex-1 pt-2 flex flex-col justify-between h-[116px]">
                                {{-- Top: name + delivery --}}
                                <div class="flex flex-col gap-2">
                                    <p class="text-[#171717] text-xs font-normal font-['Outfit'] tracking-[0.5px] line-clamp-1">
                                        {{ $item['display_name'] }}@if($coVariant) <span class="text-[#ADADAD]"> – {{ $coVariant }}</span>@endif
                                    </p>
                                    @if($item['is_out_of_stock'] ?? false)
                                    <span class="text-red-500 text-xs font-medium block mt-0.5">
                                        {{ __('Out of stock') }}
                                    </span>
                                    @endif
                                    <p class="text-[#FF4D00] text-sm font-normal font-['Outfit'] tracking-[0.5px]">
                                        {{ __('Estimated delivery') }}: {{ $coDeliveryFrom }} - {{ $coDeliveryTo }}
                                    </p>
                                </div>
                                {{-- Bottom: price + qty pill --}}
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[#171717] text-base font-normal font-['Outfit']">{{ $item['formatted_subtotal'] }}</span>
                                        @if($coDiscountPct > 0 && isset($item['formatted_original_price']))
                                        <span class="text-[#ADADAD] text-sm font-normal font-['Outfit'] line-through tracking-[0.5px]">{{ $item['formatted_original_price'] }}</span>
                                        @endif
                                    </div>
                                    {{-- Qty pill: trash|minus · qty · plus --}}
                                    <div class="flex items-center gap-4 p-2 rounded-3xl border border-[#E0E0E0]">
                                        @if($item['qty'] <= 1)
                                        <button type="button"
                                            wire:click="removeFromCart('{{ $item['key'] }}')"
                                            class="w-5 h-5 flex items-center justify-center hover:opacity-70 transition"
                                            aria-label="{{ __('Remove item') }}">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2.5 4.58H17.5M7.08 2H12.92" stroke="#C9C5C5" stroke-width="1.2" stroke-linecap="round"/>
                                                <path d="M4.29 7.62L4.29 15.25C4.29 16.08 4.96 16.75 5.79 16.75H14.21C15.04 16.75 15.71 16.08 15.71 15.25V7.62M8.61 10.42V13.75M11.39 10.42V13.75" stroke="#C9C5C5" stroke-width="1.2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        @else
                                        <button type="button"
                                            wire:click="updateQty('{{ $item['key'] }}', {{ $item['qty'] - 1 }})"
                                            class="w-[18px] h-[18px] flex items-center justify-center hover:opacity-70 transition"
                                            aria-label="{{ __('Decrease quantity') }}">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 18 18">
                                                <line x1="3.75" y1="9" x2="14.25" y2="9" stroke="#C9C5C5" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                        @endif
                                        <span class="text-[#FF4D00] text-base font-semibold font-['Outfit'] leading-none min-w-[12px] text-center">{{ $item['qty'] }}</span>
                                        <button type="button"
                                            wire:click="updateQty('{{ $item['key'] }}', {{ $item['qty'] + 1 }})"
                                            class="w-[18px] h-[18px] flex items-center justify-center hover:opacity-70 transition"
                                            aria-label="{{ __('Increase quantity') }}">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 18 18">
                                                <line x1="9" y1="3.75" x2="9" y2="14.25" stroke="#FF4D00" stroke-width="1.5" stroke-linecap="round"/>
                                                <line x1="3.75" y1="9" x2="14.25" y2="9" stroke="#FF4D00" stroke-width="1.5" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Subtotal + Shipping --}}
                    <div class="pt-4 border-t border-gray-200 flex flex-col gap-3">
                        <div class="flex justify-between items-start">
                            <span class="text-[#171717] text-base font-normal">{{ __('Subtotal') }}</span>
                            <span class="text-[#171717] text-base font-normal">{{ $formattedTotal }}</span>
                        </div>
                        @if ($cartDiscount > 0)
                        <div class="flex justify-between items-center">
                            <span class="text-[#8F8F8F] text-sm font-normal">{{ __('Discount') }}</span>
                            <span class="text-[#16a34a] text-sm font-medium">- {{ $formattedDiscount }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[#8F8F8F] text-sm font-normal leading-5">{{ __('Shipping') }}</span>
                                <span class="text-[#8F8F8F] text-xs font-normal leading-4">
                                    @if ($shippingZoneLabel || $shippingRateLabel)
                                    {{ collect([$shippingZoneLabel, $shippingRateLabel])->filter()->implode(' / ') }}
                                    @else
                                    {{ __('Ground shipping (3-5 business days)') }}
                                    @endif
                                </span>
                            </div>
                            <span class="text-[#ADADAD] text-sm font-normal leading-5">
                                @if ($cartShipping > 0){{ $formattedShipping }}@else{{ __('Free') }}@endif
                            </span>
                        </div>
                        @if (!$shippingAvailable && $shippingMessage)
                        <div class="text-[11px] text-[#dc2626]">{{ $shippingMessage }}</div>
                        @endif
                    </div>

                    {{-- Total due --}}
                    <div class="flex justify-between items-center border-t border-[#C4C4C4] py-3">
                        <span class="text-[#171717] text-base font-bold">{{ __('Total due') }}</span>
                        <span class="text-[#171717] text-2xl font-bold">{{ $formattedFinalTotal }}</span>
                    </div>

                    {{-- Pay button (desktop) --}}
                    @if (!$hasAddresses)
                        <p class="hidden sm:block text-red-600 text-sm font-medium text-center mb-2">
                            {{ __('Please add a shipping address before placing your order.') }}
                        </p>
                    @endif
                    <button type="submit" id="pay-btn" @disabled(!$shippingAvailable || !$hasAddresses)
                        wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                        class="hidden sm:flex w-full bg-[#FF4D00] text-white items-center justify-center rounded-[32px] h-16 px-8 py-2 gap-2 text-3xl font-medium font-['Outfit'] hover:bg-orange-700 transition {{ !$shippingAvailable || !$hasAddresses ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span wire:loading.remove>{{ __('Pay') }} {{ $formattedFinalTotal }}</span>
                        <span wire:loading class="flex items-center justify-center gap-2 text-base">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                            </svg>
                            {{ __('Processing...') }}
                        </span>
                    </button>

                    {{-- Powered by footer --}}
                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-sm text-[#8F8F8F] flex items-center gap-1 flex-wrap leading-5">
                            <span>{{ __('Powered by') }}</span>
                            <span class="font-bold text-[#364153]">ELORA</span>
                            <span>|</span>
                            <a href="{{ route('tenant.home') }}" class="underline hover:text-[#555] transition">{{ __('Terms') }}</a>
                            <a href="{{ route('tenant.home') }}" class="underline hover:text-[#555] transition">{{ __('Privacy') }}</a>
                        </p>
                    </div>

                    {{-- Trust badges 2×2 --}}
                    <div class="flex flex-wrap gap-[18px]">

                        {{-- Card 1: Payment methods --}}
                        <div class="w-full sm:w-[228px] py-2 bg-[#FDFDFD] border border-[#EEEEEE] rounded-lg flex flex-col">
                            <div class="px-4 py-2 flex items-center justify-center">
                                <span class="text-black text-base font-normal font-['Outfit'] text-center">{{ __('Payment methods:') }}</span>
                            </div>
                            <div class="px-4 py-2 flex items-center justify-center gap-4">
                                <!-- mastercard -->
                                <img src="{{ asset('elora/assets/images/pay-mastercard.svg') }}" alt="Mastercard" width="37" height="25">
                                <!-- visa -->
                                <img src="{{ asset('elora/assets/images/pay-visa.svg') }}" alt="Visa" width="56" height="24">
                                <!-- apple pay -->
                                <img src="{{ asset('elora/assets/images/pay-applepay.svg') }}" alt="Apple Pay" width="54" height="22">
                            </div>
                        </div>

                        {{-- Card 2: Privacy & Secure --}}
                        <div class="w-full sm:w-[228px] px-4 py-2 bg-[#FDFDFD] border border-[#EEEEEE] rounded-lg flex flex-col gap-4">
                            <div class="flex items-start gap-4">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
                                    <path d="M3 4.65V12C3 19.35 12 22.5 12 22.5C12 22.5 21 19.35 21 12V4.65L12 1.5L3 4.65Z" stroke="#2AAF2F" stroke-width="2" stroke-linecap="square"/>
                                    <path d="M8.172 11.172L10.999 14L16.656 8.343" stroke="#2AAF2F" stroke-width="2" stroke-linecap="square"/>
                                </svg>
                                <span class="text-[#2AAF2F] text-lg font-medium font-['Outfit']">{{ __('Privacy & Secure:') }}</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" class="flex-shrink-0"><rect width="10" height="6" stroke="#2AAF2F" stroke-width="1.5"/></svg>
                                    <span class="text-[#171717] text-sm font-normal font-['Outfit'] tracking-[0.5px]">{{ __('Secure payment') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" class="flex-shrink-0"><rect width="10" height="6" stroke="#2AAF2F" stroke-width="1.5"/></svg>
                                    <span class="text-[#171717] text-sm font-normal font-['Outfit'] tracking-[0.5px]">{{ __('Privacy protection') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Card 3: Free Shipping --}}
                        <div class="w-full sm:w-[228px] px-4 py-2 bg-[#FDFDFD] border border-[#EEEEEE] rounded-lg flex flex-col gap-4">
                            <div class="flex items-start gap-4">
                                <svg width="24" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0 mt-0.5">
                                    <path d="M18.25 14.25C18.25 15.493 17.243 16.5 16 16.5C14.757 16.5 13.75 15.493 13.75 14.25C13.75 13.007 14.757 12 16 12C17.243 12 18.25 13.007 18.25 14.25ZM8.25 14.25C8.25 15.493 7.243 16.5 6 16.5C4.757 16.5 3.75 15.493 3.75 14.25C3.75 13.007 4.757 12 6 12C7.243 12 8.25 13.007 8.25 14.25Z" stroke="#2AAF2F" stroke-width="1.5"/>
                                    <path d="M13.25 14.25H8.25M18.25 14.25H19.013C19.453 14.25 19.75 13.953 19.75 13.513V9.75C19.75 7.679 18.071 6 16 5.75M0.75 0.75H10.75C12.235 0.75 13.75 1.264 13.75 2.75V12.25M0.75 9.5V11.75C0.75 13.545 2.205 14.25 4.25 14.25M0.75 3.75H7.75M0.75 6.75H5.75" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="text-[#2AAF2F] text-lg font-medium font-['Outfit']">{{ __('Free returns:') }}</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-2">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" class="flex-shrink-0"><rect width="10" height="6" stroke="#2AAF2F" stroke-width="1.5"/></svg>
                                    <span class="text-[#171717] text-sm font-normal font-['Outfit'] tracking-[0.5px]">{{ __('Refund for lost packages') }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" class="flex-shrink-0"><rect width="10" height="6" stroke="#2AAF2F" stroke-width="1.5"/></svg>
                                    <span class="text-[#171717] text-sm font-normal font-['Outfit'] tracking-[0.5px]">{{ __('Coupon for late delivery') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Card 4: Easy Returns --}}
                        <div class="w-full sm:w-[228px] px-4 py-2 bg-[#FDFDFD] border border-[#EEEEEE] rounded-lg flex flex-col gap-4">
                            <div class="flex items-start gap-4">
                                <svg width="24" height="24" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
                                    <path d="M0.75 5.75V11.75C0.75 15.521 0.75 17.407 1.92 18.578C3.093 19.75 4.979 19.75 8.75 19.75H12.25M0.75 5.75L2.118 3.565C2.979 2.189 3.411 1.501 4.089 1.125C4.767 0.75 5.579 0.75 7.203 0.75H13.353C15.013 0.75 15.842 0.75 16.53 1.139C17.218 1.529 17.644 2.24 18.498 3.663L19.75 5.75M0.75 5.75H19.75M19.75 12.25V5.75M10.25 5.75V0.75M14.25 12.75C14.25 12.75 11.75 14.591 11.75 15.25C11.75 15.909 14.25 17.75 14.25 17.75M12.25 15.25H17.5C18.657 15.25 19.75 16.343 19.75 17.5C19.75 18.657 18.657 19.75 17.5 19.75H16.75M8.25 8.75H12.25" stroke="#2AAF2F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="text-[#2AAF2F] text-lg font-medium font-['Outfit']">{{ __('Free returns:') }}</span>
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex items-start gap-2">
                                    <div class="flex-shrink-0 pt-1">
                                        <svg width="10" height="6" viewBox="0 0 10 6" fill="none"><rect width="10" height="6" stroke="#2AAF2F" stroke-width="1.5"/></svg>
                                    </div>
                                    <span class="text-[#171717] text-sm font-normal font-['Outfit'] tracking-[0.5px] leading-[18px]">{{ __('Easy and free return in 15 days.') }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                {{-- end RIGHT COLUMN --}}

            </div>
        </form>

    </div>
    <!-- add address modal -->
    @if ($data['modal']['show'])
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-[#0c0c0e]/60 backdrop-blur-[2px] z-[1040]" wire:click="closeAddressModal"></div>

    {{-- Dialog --}}
    <div class="fixed inset-0 z-[1050] flex items-center justify-center p-4 sm:p-8 overflow-y-auto">
        <div class="relative w-full max-w-[620px] my-auto bg-white rounded-[20px] shadow-[0_24px_60px_-12px_rgba(16,24,40,0.25)] ring-1 ring-black/[0.06]" wire:click.stop>

            {{-- Header --}}
            <div class="flex items-start gap-4 px-7 sm:px-8 pt-7 pb-5 border-b border-[#eeeeee]">
                <div class="shrink-0 w-11 h-11 rounded-[12px] bg-main/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-main" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0 pt-1">
                    <h5 class="text-[18px] font-[700] text-[#101828] leading-tight">
                        @if ($data['modal']['address_id'])
                            {{ $data['modal']['context'] === 'shipping' ? __('Edit Shipping Address') : __('Edit Billing Address') }}
                        @else
                            {{ $data['modal']['context'] === 'shipping' ? __('Add Shipping Address') : __('Add Billing Address') }}
                        @endif
                    </h5>
                    <p class="text-[13px] text-[#8a8a8a] mt-1">{{ __('Fill in the details below to save this address.') }}</p>
                </div>
                <button type="button" wire:click="closeAddressModal"
                    class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-[#999] hover:text-[#242424] hover:bg-[#f5f5f5] transition">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-7 sm:px-8 py-6 space-y-7 max-h-[65vh] overflow-y-auto">

                {{-- Contact section --}}
                <div class="space-y-4">
                    <p class="text-[11px] font-bold tracking-wider uppercase text-[#a3a3a3]">{{ __('Contact') }}</p>
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Full name') }} <span
                                class="text-[#dc2626]">*</span></label>
                        <input wire:model="data.modal.full_name" type="text" placeholder="{{ __('Full name') }}"
                            class="w-full border @error('data.modal.full_name') border-[#dc2626] @else border-[#e0e0e0] @enderror rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-main focus:ring-4 focus:ring-main/10 transition bg-white">
                        @error('data.modal.full_name')<p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div wire:ignore>
                            <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Phone') }}</label>
                            <input wire-event="data.modal.phone" value="{{ $data['modal']['phone'] ?? '' }}" type="tel" data-phone-input placeholder="{{ __('Phone number') }}"
                                class="w-full border @error('data.modal.phone') border-[#dc2626] @else border-[#e0e0e0] @enderror rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-main focus:ring-4 focus:ring-main/10 transition bg-white">
                            @error('data.modal.phone')<p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Email address') }}</label>
                            <input wire:model="data.modal.email" type="email" placeholder="{{ __('Email address') }}"
                                class="w-full border @error('data.modal.email') border-[#dc2626] @else border-[#e0e0e0] @enderror rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-main focus:ring-4 focus:ring-main/10 transition bg-white">
                            @error('data.modal.email')<p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="h-px bg-[#f0f0f0]"></div>

                {{-- Address section --}}
                <div class="space-y-4">
                    <p class="text-[11px] font-bold tracking-wider uppercase text-[#a3a3a3]">{{ __('Address') }}</p>
                    <div>
                        <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Street Address') }} <span
                                class="text-[#dc2626]">*</span></label>
                        <input wire:model="data.modal.line1" type="text"
                            placeholder="{{ __('Street, building, apartment') }}"
                            class="w-full border @error('data.modal.line1') border-[#dc2626] @else border-[#e0e0e0] @enderror rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-main focus:ring-4 focus:ring-main/10 transition bg-white">
                        @error('data.modal.line1')<p class="text-[11px] text-[#dc2626] mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('City') }}</label>
                            <input wire:model="data.modal.city" type="text" placeholder="{{ __('City') }}"
                                class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-main focus:ring-4 focus:ring-main/10 transition bg-white">
                        </div>
                        <div>
                            <label
                                class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('State / Region') }}</label>
                            <input wire:model="data.modal.state" type="text" placeholder="{{ __('State') }}"
                                class="w-full border border-[#e0e0e0] rounded-[10px] px-4 py-3 text-[13.5px] outline-none focus:border-main focus:ring-4 focus:ring-main/10 transition bg-white">
                        </div>
                        <div>
                            <label class="block text-[12.5px] font-medium text-[#4a4a4a] mb-1.5">{{ __('Country') }} *</label>
                            @include('themes.elora.sections.country-select', [
                                'wireModel' => 'data.modal.country_id',
                                'countries' => $countries,
                                'currentId' => $data['modal']['country_id'],
                            ])
                            @error('data.modal.country_id') <p class="text-red-500 text-[11px] mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <label class="flex items-center justify-between gap-3 cursor-pointer rounded-[12px] border border-[#ececec] px-4 py-3.5 hover:border-main/40 hover:bg-main/[0.03] transition">
                    <span class="text-[13px] text-[#333] font-medium">{{ __('Set as default address') }}</span>
                    <span class="relative inline-flex shrink-0">
                        <input wire:model="data.modal.is_default" type="checkbox" class="peer sr-only">
                        <span class="w-9 h-5 rounded-full bg-[#dcdcdc] peer-checked:bg-main transition-colors"></span>
                        <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>

            </div>

            {{-- Footer --}}
            <div class="px-7 sm:px-8 py-5 border-t border-[#eeeeee] flex flex-col-reverse sm:flex-row justify-end gap-3 bg-[#fafafa] rounded-b-[20px]">
                <button type="button" wire:click="closeAddressModal"
                    class="px-5 py-2.5 bg-white border border-[#e0e0e0] text-[#555] rounded-[10px] text-[13px] font-[600] hover:bg-[#f5f5f5] hover:border-[#d4d4d4] transition">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="saveNewAddress" wire:loading.attr="disabled"
                    wire:target="saveNewAddress"
                    class="px-6 py-2.5 bg-main text-white rounded-[10px] text-[13px] font-[600] shadow-sm hover:bg-orange-600 transition disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveNewAddress">{{ $data['modal']['address_id'] ? __('Update Address') : __('Save Address') }}</span>
                    <span wire:loading wire:target="saveNewAddress">{{ __('Saving...') }}</span>
                </button>
            </div>

        </div>
    </div>
    @endif

    {{-- ── External payment SDK scripts (loaded only when relevant gateways are active) ── --}}
    @if ($hasStripe)
    <script src="https://js.stripe.com/v3/"></script>
    @endif
    @if ($hasAuthorizeNet)
    <script
        src="{{ $authNetSandbox ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js' }}"
        charset="utf-8"></script>
    @endif
    @if ($has2Checkout)
    <script src="https://2pay-js.2checkout.com/v1/2pay.js"></script>
    @endif

    @script
    <script>
    let _stripe = null;
    let _stripeCard = null;

    function initPaymentForms() {
        const container = document.getElementById('inline-card-form');
        if (!container || container.dataset.initialized) return;

        const gateway = container.dataset.gateway;

        if (gateway === 'stripe') {
            const stripeKey = container.dataset.stripeKey;
            if (!stripeKey || typeof Stripe === 'undefined') return;

            _stripe = Stripe(stripeKey);
            const elms = _stripe.elements();
            _stripeCard = elms.create('card', {
                style: {
                    base: {
                        fontSize: '13px',
                        color: '#333333',
                        fontFamily: 'inherit',
                        '::placeholder': {
                            color: '#aaaaaa'
                        },
                    },
                    invalid: {
                        color: '#dc2626'
                    },
                },
                hidePostalCode: true,
            });
            _stripeCard.mount('#stripe-card-element');
            _stripeCard.on('change', (e) => {
                const errEl = document.getElementById('stripe-card-errors');
                if (errEl) {
                    errEl.textContent = e.error?.message ?? '';
                    errEl.classList.toggle('hidden', !e.error);
                }
            });
            container.dataset.initialized = '1';
        } else {
            // AuthorizeNet / 2Checkout use plain inputs — set card formatting
            setupCardFormatting();
            container.dataset.initialized = '1';
        }
    }

    function setupCardFormatting() {
        const numInput = document.getElementById('card-number');
        if (numInput && !numInput.dataset.fmt) {
            numInput.dataset.fmt = '1';
            numInput.addEventListener('input', () => {
                let v = numInput.value.replace(/\D/g, '').slice(0, 16);
                numInput.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
            });
        }
        const expInput = document.getElementById('card-expiry');
        if (expInput && !expInput.dataset.fmt) {
            expInput.dataset.fmt = '1';
            expInput.addEventListener('input', (evt) => {
                let v = expInput.value.replace(/\D/g, '').slice(0, 4);
                if (v.length > 2) v = v.slice(0, 2) + ' / ' + v.slice(2);
                expInput.value = v;
            });
        }
    }


    initPaymentForms();

    Livewire.on('paymentMethodChanged', () => {
        initPaymentForms();
        const errEl = document.getElementById('payment-method-error');
        if (errEl) errEl.classList.add('hidden');
    });


    document.addEventListener('livewire:updated', () => {
        // Small tick so the new DOM nodes are in place
        setTimeout(initPaymentForms, 30);
    });

    // Intercept form submit when an inline-card gateway is active
    document.addEventListener('submit', async (e) => {
        if (e.target.id !== 'checkout-form') return;

        // ── Validate payment selection ──────────────────────────────────────
        const paymentSelected = document.querySelector('input[name="payment_choice"]:checked');
        if (!paymentSelected) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const errEl = document.getElementById('payment-method-error');
            if (errEl) { errEl.textContent = '{{ __('Please select a payment method.') }}'; errEl.classList.remove('hidden'); }
            return;
        }

        const inlineForm = document.getElementById('inline-card-form');
        if (!inlineForm) return; // No inline-card gateway → Livewire handles normally

        e.preventDefault();
        e.stopImmediatePropagation();

        const btn = document.getElementById('pay-btn');
        const gateway = inlineForm.dataset.gateway;
        if (btn) btn.disabled = true;

        // ── Stripe ───────────────────────────────────────────────
        if (gateway === 'stripe') {
            if (!_stripe || !_stripeCard) {
                alert('{{ __('Stripe.js is still loading. Please try again in a moment.') }}');
                if (btn) btn.disabled = false;
                return;
            }
            const {
                token,
                error
            } = await _stripe.createToken(_stripeCard);
            if (error) {
                const errEl = document.getElementById('stripe-card-errors');
                if (errEl) {
                    errEl.textContent = error.message;
                    errEl.classList.remove('hidden');
                }
                if (btn) btn.disabled = false;
                return;
            }
            await $wire.set('data.payment.stripe_token', token.id);
            $wire.call('placeOrder');
        }

        // ── Authorize.Net Accept.js ──────────────────────────────
        else if (gateway === 'authorize_net') {
            if (typeof Accept === 'undefined') {
                alert('{{ __('Accept.js is still loading. Please try again.') }}');
                if (btn) btn.disabled = false;
                return;
            }
            const rawExp = document.getElementById('card-expiry')?.value.replace(/\s/g, '') ?? '';
            const [mon, yr] = rawExp.split('/');
            const secureData = {
                authData: {
                    apiLoginID: inlineForm.dataset.authLogin,
                    clientKey: inlineForm.dataset.authClient
                },
                cardData: {
                    cardNumber: document.getElementById('card-number')?.value.replace(/\s/g, '') ?? '',
                    month: (mon ?? '').trim(),
                    year: '20' + (yr ?? '').trim(),
                    cardCode: document.getElementById('card-cvc')?.value ?? '',
                },
            };
            Accept.dispatchData(secureData, async (response) => {
                if (response.messages.resultCode === 'Error') {
                    const errEl = document.getElementById('card-errors');
                    if (errEl) {
                        errEl.textContent = response.messages.message?. [0]?.text ??
                            '{{ __('Card error') }}';
                        errEl.classList.remove('hidden');
                    }
                    if (btn) btn.disabled = false;
                    return;
                }
                await $wire.set('data.payment.authnet_desc', response.opaqueData
                    .dataDescriptor);
                await $wire.set('data.payment.authnet_value', response.opaqueData.dataValue);
                $wire.call('placeOrder');
            });
        }

        // ── 2Checkout 2Pay.js ────────────────────────────────────
        else if (gateway === '2checkout') {
            if (typeof TCO === 'undefined') {
                alert('{{ __('2Pay.js is still loading. Please try again.') }}');
                if (btn) btn.disabled = false;
                return;
            }
            const rawExp = document.getElementById('card-expiry')?.value.replace(/\s/g, '') ?? '';
            const [mon, yr] = rawExp.split('/');
            TCO.requestToken({
                sellerId: inlineForm.dataset['2coSeller'],
                publishableKey: inlineForm.dataset['2coSeller'],
                ccNo: document.getElementById('card-number')?.value.replace(/\s/g, '') ?? '',
                cvv: document.getElementById('card-cvc')?.value ?? '',
                expMonth: (mon ?? '').trim(),
                expYear: '20' + (yr ?? '').trim(),
            }, async (data) => {
                if (data.errorCode > 0) {
                    const errEl = document.getElementById('card-errors');
                    if (errEl) {
                        errEl.textContent = data.errorMsg ?? '{{ __('Card error') }}';
                        errEl.classList.remove('hidden');
                    }
                    if (btn) btn.disabled = false;
                    return;
                }
                await $wire.set('data.payment.twoco_token', data.token.token);
                $wire.call('placeOrder');
            });
        }
    }, {
        capture: true
    });
    </script>
    @endscript

</main>

@push('scripts')
    <script>
        document.addEventListener('storefront-open-address-modal-changed', function (event) {
            window.bootPhoneInputs();
        });
    </script>
@endpush
