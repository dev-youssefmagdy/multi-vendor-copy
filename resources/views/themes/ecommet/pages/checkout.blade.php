{{--
    Ecommet – Checkout page
    Passed from CheckoutPage::render():
      $cartItems             array  (each item includes display_name, display_image, display_variant, formatted_subtotal)
      $cartTotal             float
      $cartDiscount          float
      $cartFinalTotal        float
      $appliedCoupon         Coupon|null
    $gateways              Collection<array{id:int,code:string,name:string,mode:string,creds:array,source:string}>
      $savedAddresses        Collection<CustomerAddress>
      $hasAddresses          bool
      $formattedTotal        string
      $formattedDiscount     string
    $formattedShipping     string
    $formattedFinalTotal   string
    $shippingAvailable     bool
    $shippingMessage       string|null
    $shippingZoneLabel     string|null
    $shippingRateLabel     string|null
      $activeInlineGateway   array|null  (code, creds, mode)
      $hasStripe             bool
      $hasAuthorizeNet       bool
      $has2Checkout          bool
      $authNetSandbox        bool
      $currentCurrency       Currency|null
--}}

<main class="flex-grow bg-white pb-20">
    <div class="max-w-[1100px] mx-auto px-5 sm:px-8 mt-3 md:mt-6">

        {{-- ── Breadcrumb ──────────────────────────────────────────────────── --}}
        <div class="hidden md:flex items-center gap-2 text-[11px] font-[500] text-[#999] mb-8">
            <a href="{{ route('tenant.home') }}"  class="hover:text-black transition">{{ __('Home') }}</a>
            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" width="5" alt="">
            <a href="{{ route('tenant.storefront.cart') }}"  class="hover:text-black transition">{{ __('Cart') }}</a>
            <img loading="lazy" src="{{ asset('ecommet/assets/images/home/arrow-Breadcrumb.png') }}" width="5" alt="">
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
                <div class="flex-1 w-full max-w-full md:max-w-[420px]">

                    {{-- ───────────────── SHIPPING ADDRESS ────────────────── --}}
                    <section class="mb-10">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[20px] font-[500] text-[#101828]">{{ __('Shipping address') }}</h2>
                            <button type="button" wire:click="openAddressModal('shipping', true)"
                                class="text-[13px] font-semibold text-[#2272e2] hover:underline flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ __('Add new address') }}
                            </button>
                        </div>

                        @if ($hasAddresses)
                            {{-- Saved address cards --}}
                            <div class="flex flex-col gap-2.5">
                                @foreach ($savedAddresses as $addr)
                                    <label wire:key="ship-addr-{{ $addr->id }}"
                                        class="flex items-start gap-3 rounded-[8px] border px-4 py-3 cursor-pointer transition
                                               {{ $data['shipping']['address_id'] === $addr->id ? 'border-[#242424] bg-white' : 'border-[#dcdcdc] bg-[#fafafa] hover:border-[#868686]' }}">
                                        <input
                                            type="radio"
                                            wire:click="selectShippingAddress({{ $addr->id }})"
                                            name="shipping_address_sel"
                                            value="{{ $addr->id }}"
                                            @checked($data['shipping']['address_id'] === $addr->id)
                                            class="mt-0.5 accent-black shrink-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-[13px] font-semibold text-[#242424]">{{ $addr->full_name }}</span>
                                                <div class="flex items-center gap-2">
                                                    @if ($addr->is_default)
                                                        <span class="text-[10px] bg-[#242424] text-white px-2 py-0.5 rounded-full">{{ __('Default') }}</span>
                                                    @endif
                                                    <button type="button"
                                                        wire:click.stop="openAddressModal('shipping', false, {{ $addr->id }})"
                                                        class="text-[11px] text-[#2272e2] hover:underline shrink-0">{{ __('Edit') }}</button>
                                                </div>
                                            </div>
                                            @if ($addr->phone)
                                                <span class="text-[12px] text-[#666]">{{ $addr->phone }}</span>
                                            @endif
                                            <p class="text-[12px] text-[#666] mt-0.5 leading-snug">{{ $addr->oneliner }}</p>
                                        </div>
                                    </label>
                                @endforeach
                                <button type="button" wire:click="openAddressModal('shipping', true)"
                                    class="border border-dashed border-[#dcdcdc] rounded-[8px] py-3 text-[13px] text-[#888] hover:border-[#2272e2] hover:text-[#2272e2] transition flex items-center justify-center gap-2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    {{ __('Add new address') }}
                                </button>
                            </div>
                        @else
                            {{-- Manual entry when no saved addresses --}}
                            <div class="flex flex-col gap-2.5">
                                <p class="text-[13px] text-[#555]">{{ __('No saved addresses found. Please add a new address.') }}</p>
                            </div>
                        @endif

                        {{-- Validation errors when saved addresses exist --}}
                        @if ($hasAddresses)
                            @error('data.shipping.name')<p class="text-[11px] text-[#dc2626] mt-2">{{ $message }}</p>@enderror
                            @error('data.shipping.address')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
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
                            <span class="text-[11px] text-[#888] font-medium tracking-wide">{{ __('Billing address is same as shipping') }}</span>
                        </label>

                        @if (!$data['billing']['same_as_shipping'])
                            <div class="mt-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-[18px] font-[500] text-[#101828]">{{ __('Billing address') }}</h3>
                                    <button type="button" wire:click="openAddressModal('billing', true)"
                                        class="text-[13px] font-semibold text-[#2272e2] hover:underline flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        {{ __('Add new address') }}
                                    </button>
                                </div>

                                @if ($hasAddresses)
                                    <div class="flex flex-col gap-2.5">
                                        @foreach ($savedAddresses as $addr)
                                            <label wire:key="bill-addr-{{ $addr->id }}"
                                                class="flex items-start gap-3 rounded-[8px] border px-4 py-3 cursor-pointer transition
                                                       {{ $data['billing']['address_id'] === $addr->id ? 'border-[#242424] bg-white' : 'border-[#dcdcdc] bg-[#fafafa] hover:border-[#868686]' }}">
                                                <input
                                                    type="radio"
                                                    wire:click="selectBillingAddress({{ $addr->id }})"
                                                    name="billing_address_sel"
                                                    value="{{ $addr->id }}"
                                                    @checked($data['billing']['address_id'] === $addr->id)
                                                    class="mt-0.5 accent-black shrink-0">
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="text-[13px] font-semibold text-[#242424]">{{ $addr->full_name }}</span>
                                                        <div class="flex items-center gap-2">
                                                            @if ($addr->is_default)
                                                                <span class="text-[10px] bg-[#242424] text-white px-2 py-0.5 rounded-full">{{ __('Default') }}</span>
                                                            @endif
                                                            <button type="button"
                                                                wire:click.stop="openAddressModal('billing', false, {{ $addr->id }})"
                                                                class="text-[11px] text-[#2272e2] hover:underline shrink-0">{{ __('Edit') }}</button>
                                                        </div>
                                                    </div>
                                                    @if ($addr->phone)
                                                        <span class="text-[12px] text-[#666]">{{ $addr->phone }}</span>
                                                    @endif
                                                    <p class="text-[12px] text-[#666] mt-0.5 leading-snug">{{ $addr->oneliner }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex flex-col gap-3">
                                        <div>
                                            <input wire:model="data.billing.name" type="text" placeholder="{{ __('Full name') }}"
                                                class="w-full border @error('data.billing.name') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition">
                                            @error('data.billing.name')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <input wire:model="data.billing.email" type="email" placeholder="{{ __('Email address') }}"
                                                class="w-full border @error('data.billing.email') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition">
                                            @error('data.billing.email')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <input wire:model="data.billing.phone" type="text" placeholder="{{ __('Phone number') }}"
                                                class="w-full border @error('data.billing.phone') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition">
                                            @error('data.billing.phone')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <textarea wire:model="data.billing.address" rows="2" placeholder="{{ __('Street address, city, country') }}"
                                                class="w-full border @error('data.billing.address') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[6px] px-4 py-2.5 outline-none text-[13px] text-[#333] placeholder-[#aaa] bg-white focus:border-[#242424] transition resize-none"></textarea>
                                            @error('data.billing.address')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
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
                                        name="payment_choice"
                                        value="{{ $gateway['code'] }}"
                                        @checked($data['payment']['method'] === $gateway['code'])
                                        class="accent-black">
                                    <div class="flex items-center gap-2.5 flex-1">
                                        <svg class="w-4 h-4 text-[#555]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                        <span class="text-[14px] font-medium text-[#242424]">{{ $gateway['name'] }}</span>
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
                            <div id="inline-card-form"
                                 class="mt-4 border border-[#dcdcdc] rounded-[8px] p-4 bg-[#fafafe]"
                                 data-gateway="{{ $activeInlineGateway['code'] }}"
                                 @if ($activeInlineGateway['code'] === 'stripe')
                                     data-stripe-key="{{ $activeInlineGateway['creds']['key'] ?? '' }}"
                                 @elseif ($activeInlineGateway['code'] === 'authorize_net')
                                     data-auth-login="{{ $activeInlineGateway['creds']['login_id'] ?? '' }}"
                                     data-auth-client="{{ $activeInlineGateway['creds']['client_key'] ?? $activeInlineGateway['creds']['transaction_key'] ?? '' }}"
                                     data-sandbox="{{ $activeInlineGateway['mode'] === 'test' ? '1' : '0' }}"
                                 @elseif ($activeInlineGateway['code'] === '2checkout')
                                     data-2co-seller="{{ $activeInlineGateway['creds']['seller_id'] ?? '' }}"
                                 @endif
                            >
                                <h4 class="text-[13px] font-semibold text-[#333] mb-3 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-[#16a34a] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
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
                                            <input type="hidden" id="auth-net-value"      wire:model="data.payment.authnet_value">
                                        @elseif ($activeInlineGateway['code'] === '2checkout')
                                            <input type="hidden" id="twoco-token-input" wire:model="data.payment.twoco_token">
                                        @endif
                                        <p id="card-errors" class="text-[11px] text-[#dc2626] hidden"></p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Pay button --}}
                        @if (!$hasAddresses)
                            <p class="text-red-600 text-sm font-medium text-center mt-4">
                                {{ __('Please add a shipping address before placing your order.') }}
                            </p>
                        @endif
                        <button type="submit"
                            id="pay-btn"
                            @disabled(!$shippingAvailable || !$hasAddresses)
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-60 cursor-not-allowed"
                            class="w-full bg-[#1b1b1b] text-white rounded-full py-4 mt-6 text-[14px] font-[600] tracking-wide hover:bg-black transition {{ !$shippingAvailable || !$hasAddresses ? 'opacity-50 cursor-not-allowed' : '' }}">
                            <span wire:loading.remove>Pay {{ $formattedFinalTotal }}</span>
                            <span wire:loading class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                {{ __('Processing...') }}
                            </span>
                        </button>
                    </section>

                </div>
                {{-- end LEFT COLUMN --}}

                {{-- ══════════════════════ RIGHT COLUMN ══════════════════════ --}}
                <div class="flex-1 w-full max-w-full lg:max-w-[380px] pt-1">

                    {{-- Grand total header --}}
                    <div class="hidden md:block mb-6">
                        <div class="text-[14px] text-[#808080]">{{ __('Total') }}</div>
                        <div class="text-[32px] font-[500] text-[#101828] leading-none">{{ $formattedFinalTotal }}</div>
                    </div>

                    @if($shippingThreshold > 0 && $cartShipping == 0)
                    {{-- Free shipping progress (only show when shipping is actually free) --}}
                    <div class="bg-[#fffdf7] border border-[#f5e6c4] rounded-2xl p-4 mb-6">
                        <p class="text-[13px] text-[#242424] font-medium mb-2">
                            @if ($remainingForFreeShipping <= 0)
                                {{ __("You've reached free shipping!") }}
                            @else
                                {{ __('Add :weight more to qualify for free shipping', ['weight' => $remainingForFreeShipping >= 1000 ? number_format($remainingForFreeShipping / 1000, 2) . __('kg') : number_format($remainingForFreeShipping) . __('g')]) }}
                            @endif
                        </p>
                        <div class="h-2 bg-[#f5e6c4] rounded-full overflow-hidden">
                            <div class="h-full bg-[#242424] rounded-full transition-all duration-500" style="width: {{ $shippingPct }}%"></div>
                        </div>
                    </div>
                    @endif

                    {{-- Order items --}}
                    <div class="flex flex-col gap-5 mt-4 md:mt-[30px]">
                        @foreach ($cartItems as $item)
                            <div class="flex items-start gap-3.5">
                                <div class="w-[75px] h-[55px] md:w-[90px] md:h-[65px] bg-[#f5f5f5] shrink-0 flex items-center justify-center overflow-hidden rounded-[4px]">
                                    @if ($item['display_image'])
                                        <img loading="lazy" src="{{ $item['display_image'] }}" alt="{{ $item['display_name'] }}" class="w-full h-full object-contain p-1">
                                    @else
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-[#242424] text-[12px] md:text-[13px] font-[400] leading-[1.4] line-clamp-2">{{ $item['display_name'] }}</h3>
                                    @if ($item['display_variant'])
                                        <p class="text-[11px] text-[#888] mt-0.5">{{ $item['display_variant'] }}</p>
                                    @endif
                                    <div class="flex items-center justify-between mt-1.5">
                                        <span class="text-[12px] text-[#888]">{{ __('Qty: :qty', ['qty' => $item['qty']]) }}</span>
                                        <span class="text-[13px] font-[500] text-[#242424]">{{ $item['formatted_subtotal'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Coupon --}}
                    <div class="mt-5">
                        @if ($appliedCoupon)
                            <div class="flex items-center justify-between gap-3 bg-[#f0fdf4] border border-[#bbf7d0] rounded-[6px] px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#16a34a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a2 2 0 012-2z"/>
                                    </svg>
                                    <span class="text-[13px] font-medium text-[#166534]">{{ $appliedCoupon->code }}</span>
                                </div>
                                <button type="button" wire:click="removeCoupon" class="text-[12px] text-[#dc2626] hover:underline">{{ __('Remove') }}</button>
                            </div>
                        @else
                            <div class="flex items-center border border-[#dcdcdc] rounded-[6px] h-[40px] overflow-hidden bg-white">
                                <input wire:model="data.coupon.code" type="text" placeholder="{{ __('Discount code') }}"
                                    class="flex-1 px-4 py-2 text-[13px] outline-none text-[#333] placeholder-[#999] h-full bg-transparent" autocomplete="off">
                                <button type="button" wire:click="applyCoupon"
                                    class="bg-[#787878] text-white px-5 h-full text-[13px] font-[500] hover:bg-[#555] transition shrink-0">{{ __('Apply') }}</button>
                            </div>
                            @error('data.coupon.code')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <div class="w-full h-px bg-[#f0f0f0] my-6"></div>

                    {{-- Totals --}}
                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between">
                            <span class="text-[#242424] text-[15px]">{{ __('Subtotal') }}</span>
                            <span class="text-[#242424] text-[15px] font-[500]">{{ $formattedTotal }}</span>
                        </div>
                        @if ($cartDiscount > 0)
                            <div class="flex justify-between">
                                <span class="text-[#808080] text-[14px]">{{ __('Discount') }}</span>
                                <span class="text-[#16a34a] text-[14px] font-[500]">- {{ $formattedDiscount }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-[#808080] text-[14px]">{{ __('Shipping') }}</span>
                            <span class="text-[#808080] text-[14px]">
                                @if ($cartShipping > 0)
                                    {{ $formattedShipping }}
                                @else
                                    {{ __('Free') }}
                                @endif
                            </span>
                        </div>
                        @if ($shippingZoneLabel || $shippingRateLabel)
                            <div class="text-right text-[11px] text-[#8a8a8a] -mt-1">
                                {{ collect([$shippingZoneLabel, $shippingRateLabel])->filter()->implode(' / ') }}
                            </div>
                        @endif
                        @if (!$shippingAvailable && $shippingMessage)
                            <div class="text-[11px] text-[#dc2626] -mt-1">{{ $shippingMessage }}</div>
                        @endif
                        <div class="w-full h-px bg-[#f0f0f0] my-2"></div>
                        <div class="flex justify-between">
                            <span class="text-[16px] font-bold text-[#242424]">{{ __('Total due') }}</span>
                            <span class="text-[16px] font-bold text-[#242424]">{{ $formattedFinalTotal }}</span>
                        </div>
                    </div>

                    {{-- ── Shipping estimate notice ──────────────────────────── --}}
                    @if (isset($shippingEstimate) && $shippingEstimate['base_days'] > 0)
                        <div class="mt-2 rounded-[8px] border border-[#e5e7eb] bg-[#f9fafb] px-4 py-3 text-[13px] text-[#374151]">
                            <div class="flex items-center gap-2 font-medium text-[#111827] mb-1">
                                <svg class="w-4 h-4 text-[#6b7280]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ __('Estimated delivery: :days days', ['days' => $shippingEstimate['total_days']]) }}
                            </div>

                            @if ($shippingEstimate['stop_days'] > 0)
                                <div class="mt-1 rounded-[6px] border border-[#fde68a] bg-[#fffbeb] px-3 py-2 text-[12px] text-[#92400e]">
                                    <span class="font-semibold">{{ __('Shipping delay notice:') }}</span>
                                    {{ __('An additional :days day(s) have been added due to:', ['days' => $shippingEstimate['stop_days']]) }}
                                    <ul class="mt-1 list-disc list-inside space-y-0.5">
                                        @foreach ($shippingEstimate['stops'] as $stop)
                                            <li>
                                                <span class="font-medium">{{ $stop['reason'] ?: __('Scheduled stop') }}</span>
                                                ({{ \Carbon\Carbon::parse($stop['from'])->format('M d') }} – {{ \Carbon\Carbon::parse($stop['to'])->format('M d') }},
                                                +{{ $stop['extra_days'] }} {{ __('day(s)') }})
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <div class="text-[12px] text-[#6b7280]">
                                    {{ __('Standard delivery in :days day(s)', ['days' => $shippingEstimate['base_days']]) }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="w-full h-px bg-[#f0f0f0] my-6"></div>

                    <p class="text-[13px] text-[#6A7282]">
                        {{ __('By placing your order you agree to our') }}
                        <a href="{{ route('tenant.home') }}" class="underline hover:text-[#555] underline-offset-2">{{ __('Terms') }}</a>
                        {{ __('and') }} <a href="{{ route('tenant.home') }}" class="underline hover:text-[#555] underline-offset-2">{{ __('Privacy Policy') }}</a>.
                    </p>
                </div>
                {{-- end RIGHT COLUMN --}}

            </div>
        </form>

    </div>
    @if ($data['modal']['show'])
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 z-[1040]" wire:click="closeAddressModal"></div>

    {{-- Dialog --}}
    <div class="fixed inset-0 z-[1050] flex items-center justify-center px-4 py-8 overflow-y-auto">
        <div class="relative w-full max-w-[560px] bg-white rounded-[12px] shadow-2xl"
             wire:click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#ececec]">
                <h5 class="text-[17px] font-[500] text-[#101828]">
                    @if ($data['modal']['address_id'])
                        {{ $data['modal']['context'] === 'shipping' ? __('Edit Shipping Address') : __('Edit Billing Address') }}
                    @else
                        {{ $data['modal']['context'] === 'shipping' ? __('Add Shipping Address') : __('Add Billing Address') }}
                    @endif
                </h5>
                <button type="button" wire:click="closeAddressModal"
                    class="text-[#999] hover:text-[#242424] transition p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[12px] font-medium text-[#555] mb-1.5">{{ __('Full name') }} <span class="text-[#dc2626]">*</span></label>
                        <input wire:model="data.modal.full_name" type="text" placeholder="{{ __('Full name') }}"
                            class="w-full border @error('data.modal.full_name') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[8px] px-4 py-2.5 text-[13px] outline-none focus:border-[#242424] transition bg-white">
                        @error('data.modal.full_name')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-[#555] mb-1.5">{{ __('Phone') }}</label>
                        <input wire:model="data.modal.phone" type="text" placeholder="{{ __('Phone number') }}"
                            class="w-full border @error('data.modal.phone') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[8px] px-4 py-2.5 text-[13px] outline-none focus:border-[#242424] transition bg-white">
                        @error('data.modal.phone')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-[#555] mb-1.5">{{ __('Email address') }}</label>
                    <input wire:model="data.modal.email" type="email" placeholder="{{ __('Email address') }}"
                        class="w-full border @error('data.modal.email') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[8px] px-4 py-2.5 text-[13px] outline-none focus:border-[#242424] transition bg-white">
                    @error('data.modal.email')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-[12px] font-medium text-[#555] mb-1.5">{{ __('Street Address') }} <span class="text-[#dc2626]">*</span></label>
                    <input wire:model="data.modal.line1" type="text" placeholder="{{ __('Street, building, apartment') }}"
                        class="w-full border @error('data.modal.line1') border-[#dc2626] @else border-[#dcdcdc] @enderror rounded-[8px] px-4 py-2.5 text-[13px] outline-none focus:border-[#242424] transition bg-white">
                    @error('data.modal.line1')<p class="text-[11px] text-[#dc2626] mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[12px] font-medium text-[#555] mb-1.5">{{ __('City') }}</label>
                        <input wire:model="data.modal.city" type="text" placeholder="{{ __('City') }}"
                            class="w-full border border-[#dcdcdc] rounded-[8px] px-4 py-2.5 text-[13px] outline-none focus:border-[#242424] transition bg-white">
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-[#555] mb-1.5">{{ __('State / Region') }}</label>
                        <input wire:model="data.modal.state" type="text" placeholder="{{ __('State') }}"
                            class="w-full border border-[#dcdcdc] rounded-[8px] px-4 py-2.5 text-[13px] outline-none focus:border-[#242424] transition bg-white">
                    </div>
                    <div>
                        <label class="block text-[12px] font-medium text-[#555] mb-1.5">{{ __('Country') }} *</label>
                        @include('themes.ecommet.sections.country-select', [
                            'wireModel' => 'data.modal.country_id',
                            'countries' => $countries,
                            'currentId' => $data['modal']['country_id'],
                        ])
                        @error('data.modal.country_id') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input wire:model="data.modal.is_default" type="checkbox"
                        class="w-3.5 h-3.5 appearance-none border border-[#ccc] rounded-[2px] checked:bg-black checked:border-black cursor-pointer relative
                               before:content-[''] before:absolute before:hidden checked:before:block
                               before:w-[4px] before:h-[8px] before:border-r-[1.5px] before:border-b-[1.5px]
                               before:border-white before:transform before:rotate-45 before:left-[4px] before:top-[1px]">
                    <span class="text-[12px] text-[#555] font-medium">{{ __('Set as default address') }}</span>
                </label>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-[#ececec] flex justify-end gap-3 bg-[#fdfdfd]">
                <button type="button" wire:click="closeAddressModal"
                    class="px-5 py-2.5 bg-[#f5f5f5] text-[#555] rounded-[6px] text-[13px] font-[500] hover:bg-[#e8e8e8] transition">
                    {{ __('Cancel') }}
                </button>
                <button type="button" wire:click="saveNewAddress"
                    wire:loading.attr="disabled" wire:target="saveNewAddress"
                    class="px-5 py-2.5 bg-[#1b1b1b] text-white rounded-[6px] text-[13px] font-[500] hover:bg-black transition disabled:opacity-60">
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
        <script src="{{ $authNetSandbox ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js' }}" charset="utf-8"></script>
    @endif
    @if ($has2Checkout)
        <script src="https://2pay-js.2checkout.com/v1/2pay.js"></script>
    @endif

    @script
    <script>
        let _stripe   = null;
        let _stripeCard = null;

        function initPaymentForms() {
            const container = document.getElementById('inline-card-form');
            if (!container || container.dataset.initialized) return;

            const gateway = container.dataset.gateway;

            if (gateway === 'stripe') {
                const stripeKey = container.dataset.stripeKey;
                if (!stripeKey || typeof Stripe === 'undefined') return;

                _stripe     = Stripe(stripeKey);
                const elms  = _stripe.elements();
                _stripeCard = elms.create('card', {
                    style: {
                        base: {
                            fontSize: '13px',
                            color: '#333333',
                            fontFamily: 'inherit',
                            '::placeholder': { color: '#aaaaaa' },
                        },
                        invalid: { color: '#dc2626' },
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

            // ── Validate payment selection ─────────────────────────────────────
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

            const btn     = document.getElementById('pay-btn');
            const gateway = inlineForm.dataset.gateway;
            if (btn) btn.disabled = true;

            // ── Stripe ───────────────────────────────────────────────
            if (gateway === 'stripe') {
                if (!_stripe || !_stripeCard) {
                    alert(window.trans('Stripe.js is still loading. Please try again in a moment.'));
                    if (btn) btn.disabled = false;
                    return;
                }
                const { token, error } = await _stripe.createToken(_stripeCard);
                if (error) {
                    const errEl = document.getElementById('stripe-card-errors');
                    if (errEl) { errEl.textContent = error.message; errEl.classList.remove('hidden'); }
                    if (btn) btn.disabled = false;
                    return;
                }
                await $wire.set('data.payment.stripe_token', token.id);
                $wire.call('placeOrder');
            }

            // ── Authorize.Net Accept.js ──────────────────────────────
            else if (gateway === 'authorize_net') {
                if (typeof Accept === 'undefined') {
                    alert(window.trans('Accept.js is still loading. Please try again.'));
                    if (btn) btn.disabled = false;
                    return;
                }
                const rawExp  = document.getElementById('card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                const secureData = {
                    authData: { apiLoginID: inlineForm.dataset.authLogin, clientKey: inlineForm.dataset.authClient },
                    cardData: {
                        cardNumber: document.getElementById('card-number')?.value.replace(/\s/g, '') ?? '',
                        month:      (mon ?? '').trim(),
                        year:       '20' + (yr ?? '').trim(),
                        cardCode:   document.getElementById('card-cvc')?.value ?? '',
                    },
                };
                Accept.dispatchData(secureData, async (response) => {
                    if (response.messages.resultCode === 'Error') {
                        const errEl = document.getElementById('card-errors');
                        if (errEl) { errEl.textContent = response.messages.message?.[0]?.text ?? window.trans('Card error'); errEl.classList.remove('hidden'); }
                        if (btn) btn.disabled = false;
                        return;
                    }
                    await $wire.set('data.payment.authnet_desc',  response.opaqueData.dataDescriptor);
                    await $wire.set('data.payment.authnet_value', response.opaqueData.dataValue);
                    $wire.call('placeOrder');
                });
            }

            // ── 2Checkout 2Pay.js ────────────────────────────────────
            else if (gateway === '2checkout') {
                if (typeof TCO === 'undefined') {
                    alert(window.trans('2Pay.js is still loading. Please try again.'));
                    if (btn) btn.disabled = false;
                    return;
                }
                const rawExp = document.getElementById('card-expiry')?.value.replace(/\s/g, '') ?? '';
                const [mon, yr] = rawExp.split('/');
                TCO.requestToken({
                    sellerId:       inlineForm.dataset['2coSeller'],
                    publishableKey: inlineForm.dataset['2coSeller'],
                    ccNo:           document.getElementById('card-number')?.value.replace(/\s/g, '') ?? '',
                    cvv:            document.getElementById('card-cvc')?.value ?? '',
                    expMonth:       (mon ?? '').trim(),
                    expYear:        '20' + (yr ?? '').trim(),
                }, async (data) => {
                    if (data.errorCode > 0) {
                        const errEl = document.getElementById('card-errors');
                        if (errEl) { errEl.textContent = data.errorMsg ?? window.trans('Card error'); errEl.classList.remove('hidden'); }
                        if (btn) btn.disabled = false;
                        return;
                    }
                    await $wire.set('data.payment.twoco_token', data.token.token);
                    $wire.call('placeOrder');
                });
            }
        }, { capture: true });
    </script>
    @endscript

</main>

