@php
    $symbol = $currentCurrency?->symbol ?? '$';
    $rate = (float) ($currentCurrency?->conversion_rate ?? 1.0);
    $selectedPayment = $data['payment']['method'];
@endphp
<div>
    <!-- mobile screen header -->
    <div class="flex items-center gap-4 px-4 sm:px-6 lg:px-8 py-4 shadow-md lg:hidden">
        <button class="w-12 h-12 flex justify-center items-center rounded-full border hover:bg-gray-200"
            aria-label="{{ __('Back') }}" onclick="window.history.back()">
            <svg width=" 8" height="14" viewBox="0 0 8 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6.66797 0.666992L0.667968 6.66699L6.66797 12.667" stroke="#0A0A0A" stroke-width="1.33333"
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <h1 class="text-[24px] font-bold text-[#242424]">{{ __('Checkout') }}</h1>
    </div>

    <!-- =========== BREADCRUMB =========== -->
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-2">
        <nav class="flex items-center gap-2 text-sm text-neutral-500">
            <a href="{{ route('tenant.home') }}" class="hover:text-blue-700 transition">{{ __('Home') }}</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a href="{{ route('tenant.storefront.cart') }}" class="hover:text-blue-700 transition">{{ __('My Cart') }}</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-blue-700 font-medium">{{ __('Checkout') }}</span>
        </nav>
    </div>
    @if (session('error') || $errors->has('payment'))
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-600">
                {{ session('error') ?? $errors->first('payment') }}
            </div>
        </div>
    @endif
    <form wire:submit.prevent="placeOrder" id="checkout-form">
        <!-- =========== CHECKOUT CONTENT =========== -->
        <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

                <!-- ========== LEFT COLUMN: Forms ========== -->
                <div class="lg:col-span-3 bg-white rounded-3xl p-5 sm:p-8 lg:p-10 flex flex-col gap-8">

                    <!-- Shipping information -->
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h2 class="text-lg sm:text-xl font-medium text-neutral-900">{{ __('Shipping information') }}
                            </h2>
                            <button type="button" wire:click="openAddressModal('shipping')"
                                class="flex items-center gap-1 text-blue-700/80 text-sm hover:text-blue-700 transition">
                                {{ $data['shipping']['address_id'] ? __('Edit Address') : __('Add Address') }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        @if($hasAddresses)
                            <div class="flex flex-col gap-2">
                                @foreach($savedAddresses as $addr)
                                    <button type="button" wire:click="selectShippingAddress({{ $addr->id }})"
                                        class="border-2 rounded-xl p-4 flex items-start gap-4 text-left w-full transition
                                                                                                {{ $data['shipping']['address_id'] == $addr->id ? 'border-blue-700 bg-blue-50' : 'border-neutral-200 hover:border-neutral-300 bg-white' }}">
                                        <span
                                            class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition
                                                                                                {{ $data['shipping']['address_id'] == $addr->id ? 'border-blue-700' : 'border-stone-300' }}">
                                            @if($data['shipping']['address_id'] == $addr->id)
                                                <span class="w-2.5 h-2.5 rounded-full bg-blue-700"></span>
                                            @endif
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <span class="font-semibold text-slate-900 text-sm">{{ $addr->full_name }}</span>
                                                @if($addr->phone)
                                                    <span class="text-neutral-500 text-sm">{{ $addr->phone }}</span>
                                                @endif
                                                @if($addr->is_default)
                                                    <span
                                                        class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">{{ __('Default') }}</span>
                                                @endif
                                            </div>
                                            <p class="text-neutral-500 text-xs mt-1">
                                                {{ implode(', ', array_filter([$addr->line1, $addr->city, $addr->state, $addr->country])) }}
                                            </p>
                                        </div>
                                    </button>
                                @endforeach
                                <button type="button" wire:click="openAddressModal('shipping',true)"
                                    class="border-2 border-dashed border-neutral-300 rounded-xl p-4 text-sm text-neutral-500 hover:border-blue-400 hover:text-blue-600 transition flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ __('Add new address') }}
                                </button>
                            </div>
                        @else
                            <div class="flex flex-col gap-2.5">
                                <p class="text-[13px] text-[#555]">{{ __('No saved addresses found. Please add a new address.') }}</p>
                            </div>
                        @endif
                        <div
                            class="rounded-2xl px-4 py-4 flex items-start gap-3 {{ !$shippingAvailable && $shippingMessage ? 'border border-red-200 bg-red-50' : 'border border-blue-100 bg-blue-50/60' }}">
                            <svg class="w-5 h-5 shrink-0 mt-0.5 {{ !$shippingAvailable && $shippingMessage ? 'text-red-500' : 'text-blue-700' }}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if(!$shippingAvailable && $shippingMessage)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1" />
                                @endif
                            </svg>
                            <div class="flex-1 min-w-0">
                                @if(!$shippingAvailable && $shippingMessage)
                                    <p class="text-sm font-medium text-red-700">{{ $shippingMessage }}</p>
                                    <p class="text-xs text-red-600 mt-1">{{ __('Shipping fees are calculated automatically from the selected shipping address.') }}</p>
                                @else
                                    <div class="flex items-center justify-between gap-3 flex-wrap">
                                        <p class="text-sm font-semibold text-slate-900">{{ __('Shipping is calculated by address') }}</p>
                                        <span class="text-sm font-semibold text-blue-700">{{ $formattedShipping }}</span>
                                    </div>
                                    <p class="text-xs text-neutral-500 mt-1">
                                        {{ collect([$shippingZoneLabel, $shippingRateLabel])->filter()->implode(' / ') ?: __('Shipping fees update automatically after you change the delivery address.') }}
                                    </p>
                                    @if(!empty($shippingEstimate['total_days']))
                                        <p class="text-xs text-neutral-500 mt-1">
                                            {{ __('Estimated delivery in :days business day(s)', ['days' => $shippingEstimate['total_days']]) }}
                                            @if(!empty($shippingEstimate['estimated_arrival']))
                                                {{ __('by :date', ['date' => $shippingEstimate['estimated_arrival']->format('d M')]) }}
                                            @endif
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Coupon / Promo code -->
                    <div class="flex flex-col gap-4">
                        <h2 class="text-lg sm:text-xl font-medium text-zinc-900">{{ __('Coupon / Promo Code') }}</h2>

                        @if($appliedCoupon)
                            <div
                                class="bg-green-50 border border-green-200 rounded-xl px-4 py-4 flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-green-800 font-semibold text-sm">{{ $appliedCoupon->code }}</p>
                                    <p class="text-green-600 text-xs mt-0.5">{{ __('Savings') }}: -{{ $formattedDiscount }}</p>
                                </div>
                                <button type="button" wire:click="removeCoupon()"
                                    class="text-red-500 text-sm hover:underline shrink-0 transition">
                                    {{ __('Remove') }}
                                </button>
                            </div>
                        @else
                            <div class="flex gap-3">
                                <input type="text" wire:model="data.coupon.code" wire:keydown.enter="applyCoupon()"
                                    placeholder="{{ __('Enter coupon or promo code') }}"
                                    class="flex-1 h-12 px-4 rounded-xl border border-neutral-300 outline-none focus:ring-2 focus:ring-blue-300 text-sm min-w-10" />
                                <button type="button" wire:click="applyCoupon()" wire:loading.attr="disabled"
                                    wire:target="applyCoupon"
                                    class="px-5 h-12 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-sm font-medium transition disabled:opacity-70">
                                    <span wire:loading wire:target="applyCoupon"
                                        class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                    <span wire:loading.remove wire:target="applyCoupon">{{ __('Apply') }}</span>
                                </button>
                            </div>
                            @error('data.coupon.code')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <!-- Billing details -->
                    <div class="flex flex-col gap-4">
                        <label class="inline-flex items-center gap-3 cursor-pointer w-max">
                            <input type="checkbox" wire:model.live="data.billing.same_as_shipping"
                                class="w-4 h-4 rounded border-neutral-300 text-blue-700 focus:ring-blue-300">
                            <span class="text-sm text-neutral-600 font-medium">{{ __('Billing address is same as shipping') }}</span>
                        </label>

                        @if(!$data['billing']['same_as_shipping'])
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <h2 class="text-lg sm:text-xl font-medium text-neutral-900">{{ __('Billing address') }}</h2>
                                <button type="button" wire:click="openAddressModal('billing')"
                                    class="flex items-center gap-1 text-blue-700/80 text-sm hover:text-blue-700 transition">
                                    {{ $data['billing']['address_id'] ? __('Edit Billing Address') : __('Add Billing Address') }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>

                            @if($hasAddresses)
                                <div class="flex flex-col gap-2">
                                    @foreach($savedAddresses as $addr)
                                        <button type="button" wire:click="selectBillingAddress({{ $addr->id }})"
                                            class="border rounded-xl p-4 flex items-start gap-4 text-left w-full transition
                                                                                                                                {{ $data['billing']['address_id'] == $addr->id ? 'border-blue-700 bg-blue-50' : 'border-neutral-200 hover:border-neutral-300 bg-white' }}">
                                            <span
                                                class="mt-0.5 w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition
                                                                                                                                {{ $data['billing']['address_id'] == $addr->id ? 'border-blue-700' : 'border-stone-300' }}">
                                                @if($data['billing']['address_id'] == $addr->id)
                                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-700"></span>
                                                @endif
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-3 flex-wrap">
                                                    <span class="font-semibold text-slate-900 text-sm">{{ $addr->full_name }}</span>
                                                    @if($addr->phone)
                                                        <span class="text-neutral-500 text-sm">{{ $addr->phone }}</span>
                                                    @endif
                                                    @if($addr->is_default)
                                                        <span
                                                            class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-medium">{{ __('Default') }}</span>
                                                    @endif
                                                </div>
                                                <p class="text-neutral-500 text-xs mt-1">
                                                    {{ implode(', ', array_filter([$addr->line1, $addr->city, $addr->state])) }}
                                                </p>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2 flex flex-col gap-1">
                                        <label class="text-sm font-medium text-slate-700">{{ __('Full Name') }}</label>
                                        <input type="text" wire:model="data.billing.name" placeholder="{{ __('Full Name') }}"
                                            class="h-11 px-3 rounded-lg border border-neutral-300 outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                                        @error('data.billing.name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                                        <input type="email" wire:model="data.billing.email" placeholder="{{ __('Email') }}"
                                            class="h-11 px-3 rounded-lg border border-neutral-300 outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                                        @error('data.billing.email') <p class="text-red-500 text-xs">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-medium text-slate-700">{{ __('Phone') }}</label>
                                        <input type="tel" data-phone-input wire:model="data.billing.phone" placeholder="{{ __('Phone') }}"
                                            class="h-11 px-3 rounded-lg border border-neutral-300 outline-none focus:ring-2 focus:ring-blue-300 text-sm">
                                        @error('data.billing.phone') <p class="text-red-500 text-xs">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2 flex flex-col gap-1">
                                        <label class="text-sm font-medium text-slate-700">{{ __('Address') }}</label>
                                        <textarea wire:model="data.billing.address" rows="3"
                                            placeholder="{{ __('Street address, city, country') }}"
                                            class="px-3 py-3 rounded-lg border border-neutral-300 outline-none focus:ring-2 focus:ring-blue-300 text-sm resize-none"></textarea>
                                        @error('data.billing.address') <p class="text-red-500 text-xs">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Payment details -->
                    <div class="flex flex-col gap-5">
                        <h2 class="text-lg sm:text-xl font-medium text-slate-900">{{ __('Payment details') }}</h2>

                        <div class="flex flex-col gap-3">
                            @foreach($gateways as $gw)
                                <label wire:key="gw-{{ $gw['id'] }}"
                                    class="rounded-2xl border px-4 py-4 cursor-pointer transition bg-white hover:border-blue-300 {{ $selectedPayment === $gw['code'] ? 'border-blue-700 ring-2 ring-blue-100' : 'border-neutral-200' }}">
                                    <input type="radio" class="sr-only"
                                        wire:click="selectPayment('{{ $gw['code'] }}', {{ $gw['id'] }})"
                                        name="payment_choice" value="{{ $gw['code'] }}"
                                        @checked($selectedPayment === $gw['code'])>
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition {{ $selectedPayment === $gw['code'] ? 'border-blue-700 bg-blue-700' : 'border-stone-300' }}">
                                            @if($selectedPayment === $gw['code'])
                                                <span class="w-2 h-2 rounded-full bg-white"></span>
                                            @endif
                                        </span>
                                        <div
                                            class="w-10 h-7 rounded-lg border border-neutral-300 flex items-center justify-center bg-white shrink-0">
                                            <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                        </div>
                                        <span class="font-medium text-slate-900">{{ $gw['name'] }}</span>
                                        @if(isset($gw['logo_url']) && $gw['logo_url'])
                                            <img src="{{ $gw['logo_url'] }}" alt="{{ $gw['name'] }}"
                                                 class="h-6 object-contain ml-auto">
                                        @endif
                                    </div>
                                </label>
                            @endforeach

                            {{--<label
                                class="rounded-2xl border px-4 py-4 cursor-pointer transition bg-white hover:border-blue-300 {{ $selectedPayment === 'cod' ? 'border-blue-700 ring-2 ring-blue-100' : 'border-neutral-200' }}">
                                <input type="radio" class="sr-only" wire:click="selectPayment('cod')"
                                    name="payment_choice" value="cod" @checked($selectedPayment==='cod' )>
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition {{ $selectedPayment === 'cod' ? 'border-blue-700 bg-blue-700' : 'border-stone-300' }}">
                                        @if($selectedPayment === 'cod')
                                        <span class="w-2 h-2 rounded-full bg-white"></span>
                                        @endif
                                    </span>
                                    <div
                                        class="w-10 h-7 rounded-lg border border-neutral-300 flex items-center justify-center bg-white shrink-0">
                                        <svg class="w-4 h-4 text-slate-900" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <span class="font-medium text-slate-900">{{ __('Cash on Delivery') }}</span>
                                </div>
                            </label>--}}
                        </div>

                        @error('data.payment.method')
                            <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                        @enderror
                        <p id="payment-method-error" class="text-xs text-red-600 mt-2 hidden"></p>

                        @if($activeInlineGateway)
                            <div id="inline-card-form" class="rounded-2xl border border-neutral-200 bg-slate-50 p-5"
                                data-gateway="{{ $activeInlineGateway['code'] }}"
                                @if($activeInlineGateway['code'] === 'stripe')
                                    data-stripe-key="{{ $activeInlineGateway['creds']['key'] ?? '' }}"
                                @elseif($activeInlineGateway['code'] === 'authorize_net')
                                    data-auth-login="{{ $activeInlineGateway['creds']['login_id'] ?? '' }}"
                                    data-auth-client="{{ $activeInlineGateway['creds']['client_key'] ?? $activeInlineGateway['creds']['transaction_key'] ?? '' }}"
                                    data-sandbox="{{ $activeInlineGateway['mode'] === 'test' ? '1' : '0' }}"
                                @elseif($activeInlineGateway['code'] === '2checkout')
                                data-2co-seller="{{ $activeInlineGateway['creds']['seller_id'] ?? '' }}" @endif>
                                <h4 class="text-sm font-semibold text-slate-900 mb-3 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ __('Secure card details') }}
                                </h4>

                                @if($activeInlineGateway['code'] === 'stripe')
                                    <div id="stripe-card-element"
                                        class="block w-full min-h-12 border border-neutral-300 rounded-xl px-4 py-3 bg-white">
                                    </div>
                                    <p id="stripe-card-errors" class="text-xs text-red-600 mt-2 hidden"></p>
                                    <input type="hidden" id="stripe-token-input" wire:model="data.payment.stripe_token">
                                @else
                                    <div class="flex flex-col gap-3">
                                        <input type="text" id="card-number" inputmode="numeric" autocomplete="cc-number"
                                            placeholder="{{ __('Card number') }}"
                                            class="w-full border border-neutral-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-neutral-400 outline-none focus:ring-2 focus:ring-blue-300 transition bg-white tracking-widest">
                                        <div class="grid grid-cols-2 gap-3">
                                            <input type="text" id="card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                                placeholder="{{ __('MM / YY') }}" maxlength="7"
                                                class="w-full border border-neutral-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-neutral-400 outline-none focus:ring-2 focus:ring-blue-300 transition bg-white">
                                            <input type="text" id="card-cvc" inputmode="numeric" autocomplete="cc-csc"
                                                placeholder="{{ __('CVC') }}" maxlength="4"
                                                class="w-full border border-neutral-300 rounded-xl px-4 py-3 text-sm text-slate-900 placeholder-neutral-400 outline-none focus:ring-2 focus:ring-blue-300 transition bg-white">
                                        </div>
                                        @if($activeInlineGateway['code'] === 'authorize_net')
                                            <input type="hidden" id="auth-net-descriptor" wire:model="data.payment.authnet_desc">
                                            <input type="hidden" id="auth-net-value" wire:model="data.payment.authnet_value">
                                        @elseif($activeInlineGateway['code'] === '2checkout')
                                            <input type="hidden" id="twoco-token-input" wire:model="data.payment.twoco_token">
                                        @endif
                                        <p id="card-errors" class="text-xs text-red-600 hidden"></p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>



                <!-- ========== RIGHT COLUMN: Order Summary ========== -->
                <aside class="lg:col-span-2">
                    <div class="bg-white rounded-3xl p-5 sm:p-6 flex flex-col gap-5 lg:sticky lg:top-28">
                        <h2 class="text-2xl sm:text-3xl font-medium text-slate-900">{{ __('Order Summary') }}</h2>

                        @if($shippingThreshold > 0 && $cartShipping == 0)
                        <!-- Free shipping progress (only show when shipping is actually free) -->
                        <div class="relative h-10 bg-zinc-300 rounded-full overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-orange-600 rounded-full transition-all"
                                style="width: {{ $shippingPct }}%"></div>
                            <div class="relative z-10 h-full flex items-center justify-between px-4 text-sm font-medium">
                                <span class="text-white">{{ $cartWeight }} {{ __('g') }}</span>
                                @if ($cartWeight >= $shippingThreshold)
                                    <span class="text-white">{{ __('Free Shipping!') }}</span>
                                @else
                                    <span class="hidden sm:inline text-white">{{ number_format($remainingForFreeShipping) }}{{ __('g') }}
                                        {{ __('to free shipping') }}</span>
                                @endif
                                <span class="text-neutral-500">{{ number_format($shippingThreshold, 0) }} {{ __('g') }}</span>
                            </div>
                        </div>
                        @endif

                        <!-- Items -->
                        <div class="flex flex-col divide-y divide-stone-200/60">
                            @forelse($cartItems as $item)
                                <div class="py-4 first:pt-0 flex items-start gap-3">
                                    @if($item['display_image'])
                                        <img loading="lazy" src="{{ $item['display_image'] }}" alt="{{ $item['display_name'] }}"
                                            class="w-32 h-32 object-cover rounded-xl shrink-0 border border-neutral-200">
                                    @else
                                        <div
                                            class="w-16 h-16 bg-neutral-100 rounded-xl shrink-0 flex items-center justify-center border border-neutral-200">
                                            <svg class="w-6 h-6 text-neutral-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0 flex flex-col gap-2">
                                        <p class="font-medium text-slate-900 text-sm leading-snug line-clamp-2">
                                            {{ $item['display_name'] }}
                                        </p>
                                        @if($item['display_variant'])
                                            <p class="text-neutral-400 text-xs mt-0.5">{{ $item['display_variant'] }}</p>
                                        @endif
                                        @if($item['is_out_of_stock'] ?? false)
                                            <span class="text-red-500 text-xs font-medium block mt-0.5">
                                                {{ __('Out of stock') }}
                                            </span>
                                        @endif
                                        @if(!empty($shippingEstimate['estimated_arrival']))
                                            <p class="text-neutral-400 text-xs mt-0.5">
                                                {{ __('Delivery by :date', ['date' => $shippingEstimate['estimated_arrival']->format('d M')]) }}
                                            </p>
                                        @endif
                                        <p class="text-neutral-400 text-xs mt-1">{{ __('Qty') }}: {{ $item['qty'] }}</p>
                                        <div class="">
                                            <p class="text-slate-900 font-semibold text-sm">
                                                {{ $item['formatted_subtotal'] }}
                                            </p>
                                            @if($item['has_discount'])
                                                <p class="text-neutral-400 text-xs line-through">
                                                    {{ $symbol }}
                                                    {{ number_format($item['original_price'] * $item['qty'] * $rate, 2) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-8 text-center text-neutral-400 text-sm">{{ __('Your cart is empty') }}</div>
                            @endforelse
                        </div>

                        <!-- Subtotals -->
                        <div class="border-t border-stone-300/60 pt-4 flex flex-col gap-3">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-900 font-medium">{{ __('Subtotal') }}</span>
                                <span class="text-slate-900 font-medium">{{ $formattedTotal }}</span>
                            </div>
                            @if($appliedCoupon && $cartDiscount > 0)
                                <div class="flex justify-between items-center">
                                    <span class="text-green-600 text-sm">{{ __('Coupon') }} ({{ $appliedCoupon->code }})</span>
                                    <span class="text-green-600 text-sm font-medium">-{{ $formattedDiscount }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between items-start gap-3">
                                <div>
                                    <p class="text-neutral-500 text-sm">{{ __('Shipping') }}</p>
                                    @if($shippingRateLabel)
                                        <p class="text-neutral-400 text-xs">{{ $shippingRateLabel }}</p>
                                    @elseif(!$shippingAvailable && $shippingMessage)
                                        <p class="text-red-400 text-xs">{{ $shippingMessage }}</p>
                                    @endif
                                </div>
                                <span class="text-neutral-700 text-sm">{{ $formattedShipping }}</span>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="border-t border-stone-300 pt-4 flex justify-between items-center">
                            <span class="text-slate-900 font-medium">{{ __('Total due') }}</span>
                            <span class="text-slate-900 text-2xl font-bold">{{ $formattedFinalTotal }}</span>
                        </div>

                        <!-- Pay button -->
                        <div
                            class="fixed lg:relative bottom-0 left-0 lg:bottom-auto lg:left-auto w-screen lg:w-auto z-50 lg:z-0 bg-white lg:bg-auto py-4 px-2 lg:p-0">
                            @if (!$hasAddresses)
                                <p class="text-red-600 text-sm font-medium text-center mb-3">
                                    {{ __('Please add a shipping address before placing your order.') }}
                                </p>
                            @endif
                            <button type="submit" id="pay-btn" @disabled(!$shippingAvailable || !$hasAddresses)
                                wire:loading.attr="disabled" wire:target="placeOrder"
                                class="h-14 sm:h-16 w-full bg-blue-700 hover:bg-blue-800 text-white rounded-full text-xl sm:text-2xl font-medium transition flex items-center justify-center gap-2 disabled:opacity-70 {{ !$shippingAvailable || !$hasAddresses ? 'opacity-60 cursor-not-allowed' : '' }}">
                                <span wire:loading wire:target="placeOrder"
                                    class="inline-block w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                {{ __('Pay') }} {{ $formattedFinalTotal }}
                            </button>
                        </div>

                        <!-- Powered by -->
                        <!-- <div
                            class="border-t border-gray-200 pt-3 text-sm text-neutral-400 flex items-center gap-1.5 flex-wrap">
                            <span>{{ __('Powered by') }}</span>
                            <span class="font-bold text-gray-700">{{ __('Souqify') }}</span>
                            <span>|</span>
                            <a href="#" class="underline hover:text-blue-700 transition">{{ __('Terms') }}</a>
                            <a href="#" class="underline hover:text-blue-700 transition">{{ __('Privacy') }}</a>
                        </div> -->

                        <!-- Trust badges -->
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <div class="bg-zinc-100 rounded-2xl p-4 flex flex-col gap-1">
                                <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                                <p class="text-sm font-semibold text-neutral-800">{{ __('Free Delivery') }}</p>
                                <p class="text-xs text-neutral-500">{{ __('Orders over $100') }}</p>
                            </div>
                            {{-- <div class="bg-zinc-100 rounded-2xl p-4 flex flex-col gap-1">
                                <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <p class="text-sm font-semibold text-neutral-800">{{ __('2-Year Warranty') }}</p>
                                <p class="text-xs text-neutral-500">{{ __('Full coverage protection') }}</p>
                            </div> --}}
                        </div>

                        <!-- Payment chips -->
                        <div class="flex items-center justify-center gap-3 flex-wrap">
                            <img loading="lazy" src="{{ asset('souqify/assets/images/pay-visa.svg') }}" alt="">
                            <img loading="lazy" src="{{ asset('souqify/assets/images/pay-tabby.svg') }}" alt="">
                            <img loading="lazy" src="{{ asset('souqify/assets/images/pay-tamara.svg') }}" alt="">
                        </div>
                    </div>
                </aside>
            </div>
        </section>

    </form>


    <!-- =========== ADDRESS MODAL =========== -->
    @if($data['modal']['show'])
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            wire:click.self="closeAddressModal()">
            <div class="bg-white rounded-3xl w-full max-w-lg mx-4 flex flex-col max-h-[90vh] overflow-hidden shadow-2xl ring-1 ring-black/5"
                wire:click.stop>
                <div class="flex items-start gap-3.5 px-6 sm:px-8 pt-6 sm:pt-7 pb-5">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h3 class="text-lg font-semibold text-slate-900 leading-tight">
                            @if($data['modal']['address_id'])
                                {{ $data['modal']['context'] === 'billing' ? __('Edit Billing Address') : __('Edit Shipping Address') }}
                            @else
                                {{ $data['modal']['context'] === 'billing' ? __('Add Billing Address') : __('Add Shipping Address') }}
                            @endif
                        </h3>
                        <p class="text-[12.5px] text-slate-400 mt-0.5">{{ __('Fill in the details below to save this address.') }}</p>
                    </div>
                    <button type="button" wire:click="closeAddressModal()" aria-label="{{ __('Close') }}"
                        class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 sm:px-8 pb-6 sm:pb-7 space-y-6 overflow-y-auto">

                    {{-- Contact section --}}
                    <div class="space-y-4">
                        <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">{{ __('Contact') }}</p>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-slate-700">{{ __('Full Name') }}</label>
                            <input type="text" wire:model="data.modal.full_name" placeholder="{{ __('Full Name') }}"
                                class="h-11 px-3 rounded-xl border border-neutral-300 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100 transition text-sm">
                            @error('data.modal.full_name') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-slate-700">{{ __('Phone') }}</label>
                                <input type="tel" data-phone-input wire:model="data.modal.phone" placeholder="{{ __('Phone') }}"
                                    class="h-11 px-3 rounded-xl border border-neutral-300 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100 transition text-sm">
                                @error('data.modal.phone') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                                <input type="email" wire:model="data.modal.email" placeholder="{{ __('Email') }}"
                                    class="h-11 px-3 rounded-xl border border-neutral-300 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100 transition text-sm">
                                @error('data.modal.email') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="h-px bg-neutral-100"></div>

                    {{-- Address section --}}
                    <div class="space-y-4">
                        <p class="text-[11px] font-semibold tracking-wide uppercase text-slate-400">{{ __('Address') }}</p>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-slate-700">{{ __('Address') }}</label>
                            <input type="text" wire:model="data.modal.line1" placeholder="{{ __('Street address, P.O. box') }}"
                                class="h-11 px-3 rounded-xl border border-neutral-300 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100 transition text-sm">
                            @error('data.modal.line1') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-slate-700">{{ __('City') }}</label>
                                <input type="text" wire:model="data.modal.city" placeholder="{{ __('City') }}"
                                    class="h-11 px-3 rounded-xl border border-neutral-300 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100 transition text-sm">
                                @error('data.modal.city') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-sm font-medium text-slate-700">{{ __('State / Province') }}</label>
                                <input type="text" wire:model="data.modal.state" placeholder="{{ __('State or province') }}"
                                    class="h-11 px-3 rounded-xl border border-neutral-300 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100 transition text-sm">
                                @error('data.modal.state') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-sm font-medium text-slate-700">{{ __('Country') }}</label>
                            @include('themes.souqify.sections.country-select', [
                                'wireModel' => 'data.modal.country_id',
                                'countries' => $countries,
                                'currentId' => $data['modal']['country_id'],
                            ])
                            @error('data.modal.country_id') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <label class="flex items-center justify-between gap-3 cursor-pointer rounded-xl border border-neutral-200 px-4 py-3 hover:border-neutral-300 transition">
                        <span class="text-sm text-slate-700 font-medium">{{ __('Set as default address') }}</span>
                        <span class="relative inline-flex shrink-0">
                            <input type="checkbox" wire:model="data.modal.is_default" class="peer sr-only">
                            <span class="w-9 h-5 rounded-full bg-neutral-300 peer-checked:bg-blue-700 transition-colors"></span>
                            <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                </div>

                <div class="flex gap-3 px-6 sm:px-8 py-5 border-t border-neutral-100 bg-neutral-50/60">
                    <button type="button" wire:click="closeAddressModal()"
                        class="flex-1 h-12 border border-neutral-300 rounded-full text-neutral-600 hover:bg-neutral-100 transition font-medium text-sm">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="saveNewAddress()" wire:loading.attr="disabled"
                        wire:target="saveNewAddress"
                        class="flex-1 h-12 bg-blue-700 hover:bg-blue-800 text-white rounded-full font-medium text-sm transition flex items-center justify-center gap-2 disabled:opacity-70">
                        <span wire:loading wire:target="saveNewAddress"
                            class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        {{ $data['modal']['address_id'] ? __('Update Address') : __('Save Address') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($hasStripe)
        <script src="https://js.stripe.com/v3/"></script>
    @endif
    @if($hasAuthorizeNet)
        <script
            src="{{ $authNetSandbox ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js' }}"
            charset="utf-8"></script>
    @endif
    @if($has2Checkout)
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
                expInput.addEventListener('input', () => {
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
            setTimeout(initPaymentForms, 30);
        });

        document.addEventListener('submit', async (e) => {
            if (e.target.id !== 'checkout-form') return;

            // ── Validate payment selection ─────────────────────────────────
            const paymentSelected = document.querySelector('input[name="payment_choice"]:checked');
            if (!paymentSelected) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const errEl = document.getElementById('payment-method-error');
                if (errEl) { errEl.textContent = '{{ __('Please select a payment method.') }}'; errEl.classList.remove('hidden'); }
                return;
            }

            const inlineForm = document.getElementById('inline-card-form');
            if (!inlineForm) return;

            e.preventDefault();
            e.stopImmediatePropagation();

            const btn = document.getElementById('pay-btn');
            const gateway = inlineForm.dataset.gateway;
            if (btn) btn.disabled = true;

            if (gateway === 'stripe') {
                if (!_stripe || !_stripeCard) {
                    alert(window.trans('Stripe.js is still loading. Please try again in a moment.'));
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
            } else if (gateway === 'authorize_net') {
                if (typeof Accept === 'undefined') {
                    alert(window.trans('Accept.js is still loading. Please try again.'));
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
                            errEl.textContent = response.messages.message?.[0]?.text ??
                                window.trans('Card error');
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
            } else if (gateway === '2checkout') {
                if (typeof TCO === 'undefined') {
                    alert(window.trans('2Pay.js is still loading. Please try again.'));
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
                            errEl.textContent = data.errorMsg ?? window.trans('Card error');
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
</div>
