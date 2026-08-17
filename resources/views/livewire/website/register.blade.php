<div class="min-h-screen bg-linear-to-br from-orange-50 to-white flex items-start justify-center py-16 px-4">
    <div class="w-full max-w-lg">

        {{-- Heading --}}
        @if(!$emailSent)
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900">{{ __('Create Your Store') }}</h1>
                <p class="text-gray-500 mt-2 text-sm">
                    {{ __('Already have an account?') }}
                    <a href="{{ route('admin.login') }}" class="text-primary font-semibold hover:underline">{{ __('Sign in') }}</a>
                </p>
            </div>
        @endif

        {{-- ── EMAIL SENT SUCCESS ──────────────────────────────────────────── --}}
        @if($emailSent)
            <div class="text-center bg-white rounded-2xl border border-gray-100 shadow-sm p-10">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fas fa-envelope-open-text text-green-600 text-2xl"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 mb-3">{{ __('Check your email!') }}</h1>
                <p class="text-gray-500 text-sm leading-relaxed mb-5">
                    {{ __("We've sent a registration link to") }}
                    <span class="font-semibold text-gray-900">{{ $email }}</span>.
                    {{ __('Click the link in the email to complete your store setup.') }}
                </p>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 text-start">
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wide mb-1">{{ __('Link expires in 48 hours') }}</p>
                    <p class="text-gray-500 text-xs leading-relaxed">
                        {{ __("If you don't see the email, check your spam folder. The link is valid for 48 hours.") }}
                    </p>
                </div>

                @if($resendStatus)
                    <p class="text-xs text-gray-500 mb-3">{{ $resendStatus }}</p>
                @endif

                <button wire:click="resend" wire:loading.attr="disabled"
                    class="w-full mb-3 py-3 text-sm font-semibold text-primary border border-primary rounded-xl hover:bg-primary hover:text-white transition-colors disabled:opacity-60">
                    <span wire:loading.remove wire:target="resend">{{ __('Resend email') }}</span>
                    <span wire:loading wire:target="resend">{{ __('Resending...') }}</span>
                </button>

                <a href="{{ route('website.home') }}"
                    class="block text-center py-3 text-sm text-gray-500 border border-gray-200 rounded-xl hover:border-primary hover:text-primary transition-colors">
                    {{ __('Back to Home') }}
                </a>
            </div>

        {{-- ── FORM ─────────────────────────────────────────────────────────── --}}
        @else

            {{-- Step progress --}}
            <div class="flex items-center gap-0 mb-8">
                @foreach($stepLabels as $n => $label)
                    <div class="flex-1 flex flex-col items-center gap-1.5">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-all
                            {{ $step > $n ? 'bg-green-500 text-white' : ($step === $n ? 'bg-primary text-white ring-4 ring-primary/20' : 'bg-gray-100 text-gray-400') }}">
                            @if($step > $n)
                                <i class="fas fa-check text-xs"></i>
                            @else
                                {{ $n }}
                            @endif
                        </div>
                        <span class="text-xs font-{{ $step === $n ? 'semibold' : 'normal' }} {{ $step === $n ? 'text-gray-900' : 'text-gray-400' }} text-center">
                            {{ $label }}
                        </span>
                    </div>
                    @if($n < count($stepLabels))
                        <div class="flex-1 h-0.5 mb-5 {{ $step > $n ? 'bg-green-400' : 'bg-gray-200' }} transition-colors"></div>
                    @endif
                @endforeach
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">

                {{-- ── STEP 1: Plan ─────────────────────────────────────────── --}}
                @if($step === 1)
                    <h2 class="text-lg font-extrabold text-gray-900 mb-6">{{ __('Choose Your Plan') }}</h2>

                    <div class="space-y-2 mb-6">
                        <label class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition-all
                            {{ $packageId === '' ? 'border-primary bg-orange-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                            <input wire:model.live="packageId" type="radio" value="" class="accent-primary">
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900 text-sm">{{ __('Free Plan') }}</div>
                                <div class="text-gray-500 text-xs">{{ __('Start free, upgrade later') }}</div>
                            </div>
                            <div class="font-extrabold text-green-600 text-sm">$0</div>
                        </label>

                        @foreach($packages as $pkg)
                            <label class="flex items-center gap-3 p-4 rounded-xl cursor-pointer border-2 transition-all
                                {{ $packageId == $pkg->id ? 'border-primary bg-orange-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                <input wire:model.live="packageId" type="radio" value="{{ $pkg->id }}" class="accent-primary">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 text-sm">{{ $pkg->name }}</div>
                                    @if($pkg->trial_days > 0)
                                        <div class="text-xs text-green-600 mt-0.5">{{ $pkg->trial_days }} {{ __('days free trial') }}</div>
                                    @endif
                                </div>
                                <div class="font-extrabold text-sm {{ $packageId == $pkg->id ? 'text-primary' : 'text-gray-900' }}">
                                    ${{ number_format($pkg->price, 0) }}
                                </div>
                            </label>
                        @endforeach
                        @error('packageId')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button wire:click="nextStep" type="button"
                        class="btn-primary w-full py-3.5 font-bold text-sm rounded-xl">
                        {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                    </button>
                @endif

                {{-- ── STEP 2: Email + Phone + Social ──────────────────────── --}}
                @if($step === 2)
                    <h2 class="text-lg font-extrabold text-gray-900 mb-6">{{ __('Your Details') }}</h2>

                    @if (session('social_login_error'))
                        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{{ session('social_login_error') }}</div>
                    @endif

                    <div class="flex flex-col gap-3 mb-5">
                        <button type="button" onclick="openSocialPopup('google')"
                            class="w-full flex items-center justify-center gap-2 border border-gray-200 rounded-xl py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                                <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                            </svg>
                            {{ __('Continue with Google') }}
                        </button>
                        <button type="button" onclick="openSocialPopup('apple')"
                            class="w-full flex items-center justify-center gap-2 bg-black text-white rounded-xl py-3 text-sm font-semibold hover:bg-gray-900 transition">
                            <svg class="w-4 h-4" viewBox="0 0 384 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                            </svg>
                            {{ __('Continue with Apple') }}
                        </button>
                    </div>

                    <div id="social-popup-error" class="hidden mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600"></div>

                    <div class="flex items-center gap-3 mb-5">
                        <div class="flex-1 h-px bg-gray-200"></div>
                        <span class="text-[11px] text-gray-400 uppercase tracking-wide">{{ __('or') }}</span>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    </div>

                    <div class="space-y-5">

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Email address') }} <span class="text-red-500">*</span></label>
                            <input wire:model="email" type="email" placeholder="you@example.com"
                                class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Phone — no wire:model on the display input; synced to a hidden input --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ __('Phone') }} <span class="font-normal text-gray-400">({{ __('optional') }})</span>
                            </label>
                            <input id="register-phone-display" type="tel" data-phone-input placeholder="+1 555 000 0000"
                                class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                            <input type="hidden" id="register-phone-hidden" wire:model="phone">
                            @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 mt-2">
                            <button wire:click="prevStep" type="button"
                                class="w-full sm:flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:border-gray-300 transition-colors">
                                <i class="fas fa-arrow-left me-1.5 text-xs"></i> {{ __('Back') }}
                            </button>
                            <button wire:click="proceed" type="button"
                                wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-not-allowed"
                                wire:target="proceed"
                                class="btn-primary w-full sm:flex-1 py-3.5 font-bold text-sm rounded-xl">
                                <span wire:loading.remove wire:target="proceed">
                                    {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                                </span>
                                <span wire:loading wire:target="proceed">{{ __('Processing…') }}</span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- ── STEP 3: Payment ──────────────────────────────────────── --}}
                @if($step === 3)
                    @if($selectedPackage && (float) $selectedPackage->price > 0)

                        <h2 class="text-lg font-extrabold text-gray-900 mb-2">{{ __('Select Payment Method') }}</h2>
                        <p class="text-gray-500 text-sm mb-6">{{ __('Choose how you would like to pay.') }}</p>

                        @if(session('registration_payment_cancelled'))
                            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                                {{ __('Payment was cancelled. You can choose a gateway and try again.') }}
                            </div>
                        @endif

                        @error('payment')
                            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{{ $message }}</div>
                        @enderror

                        {{-- Plan summary --}}
                        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-5 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ __('Selected Plan') }}</p>
                                <p class="font-extrabold text-gray-900 text-sm mt-0.5">{{ $selectedPackage->name }}</p>
                            </div>
                            <div class="text-2xl font-extrabold text-primary">
                                ${{ number_format($selectedPackage->price, 0) }}
                            </div>
                        </div>

                        {{-- Gateway list --}}
                        <div class="space-y-3 mb-5">
                            @forelse($gateways as $gateway)
                                <label class="flex items-center gap-4 p-4 rounded-xl cursor-pointer border-2 transition-all
                                    {{ $gatewayCode === $gateway->code ? 'border-primary bg-orange-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                    <input wire:model.live="gatewayCode" type="radio" value="{{ $gateway->code }}" class="accent-primary">
                                    <div class="flex items-center gap-3 flex-1">
                                        @if($gateway->logoFile)
                                            <img src="{{ $gateway->logoFile->full_path }}" alt="{{ $gateway->name }}" class="h-6 w-auto object-contain">
                                        @else
                                            <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center shrink-0">
                                                <i class="fas fa-credit-card text-gray-500 text-sm"></i>
                                            </div>
                                        @endif
                                        <span class="font-semibold text-gray-900 text-sm">{{ $gateway->name }}</span>
                                    </div>
                                    @if($gateway->mode->value === 'test')
                                        <span class="text-xs bg-amber-100 text-amber-700 font-bold px-2 py-0.5 rounded-full">Test</span>
                                    @endif
                                </label>
                            @empty
                                <div class="p-6 text-center text-gray-400 text-sm border border-dashed border-gray-200 rounded-xl">
                                    <i class="fas fa-credit-card text-2xl mb-2 block text-gray-300"></i>
                                    {{ __('No payment gateways are configured yet.') }}
                                </div>
                            @endforelse
                            @error('gatewayCode')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Inline card form --}}
                        @if($activeInlineGateway)
                            <div id="register-inline-card-form"
                                class="mb-5 rounded-xl border border-gray-200 bg-gray-50 p-4"
                                data-gateway="{{ $activeInlineGateway['code'] }}"
                                @if ($activeInlineGateway['code'] === 'stripe')
                                    data-stripe-key="{{ $activeInlineGateway['creds']['key'] ?? '' }}"
                                @elseif ($activeInlineGateway['code'] === 'authorize_net')
                                    data-auth-login="{{ $activeInlineGateway['creds']['login_id'] ?? '' }}"
                                    data-auth-client="{{ $activeInlineGateway['creds']['client_key'] ?? ($activeInlineGateway['creds']['transaction_key'] ?? '') }}"
                                    data-sandbox="{{ $activeInlineGateway['mode'] === 'test' ? '1' : '0' }}"
                                @elseif ($activeInlineGateway['code'] === '2checkout')
                                    data-2co-seller="{{ $activeInlineGateway['creds']['seller_id'] ?? '' }}"
                                @endif>
                                <h4 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <i class="fas fa-lock text-green-600 text-xs"></i>
                                    {{ __('Secure payment details') }}
                                </h4>

                                @if($activeInlineGateway['code'] === 'stripe')
                                    <div id="register-stripe-card-element"
                                        class="min-h-11 rounded-xl border border-gray-200 bg-white px-4 py-3"></div>
                                    <p id="register-stripe-card-errors" class="mt-2 hidden text-xs text-red-500"></p>
                                    <input type="hidden" wire:model="stripeToken">
                                @else
                                    <div class="space-y-3">
                                        <input type="text" id="register-card-number" inputmode="numeric" autocomplete="cc-number"
                                            placeholder="{{ __('Card number') }}"
                                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm tracking-widest text-gray-900 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        <div class="grid grid-cols-2 gap-3">
                                            <input type="text" id="register-card-expiry" inputmode="numeric" autocomplete="cc-exp"
                                                placeholder="{{ __('MM / YY') }}" maxlength="7"
                                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20">
                                            <input type="text" id="register-card-cvc" inputmode="numeric" autocomplete="cc-csc"
                                                placeholder="{{ __('CVC') }}" maxlength="4"
                                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition-all focus:border-primary focus:ring-2 focus:ring-primary/20">
                                        </div>
                                        @if($activeInlineGateway['code'] === 'authorize_net')
                                            <input type="hidden" wire:model="authnetDescriptor">
                                            <input type="hidden" wire:model="authnetValue">
                                        @elseif($activeInlineGateway['code'] === '2checkout')
                                            <input type="hidden" wire:model="twocoToken">
                                        @endif
                                        <p id="register-card-errors" class="hidden text-xs text-red-500"></p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button wire:click="prevStep" type="button"
                                class="w-full sm:flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:border-gray-300 transition-colors">
                                <i class="fas fa-arrow-left me-1.5 text-xs"></i> {{ __('Back') }}
                            </button>
                            <button id="register-pay-btn" type="button"
                                wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-not-allowed"
                                wire:target="proceed"
                                class="btn-primary w-full sm:flex-1 py-3 font-bold text-sm rounded-xl">
                                <span wire:loading.remove wire:target="proceed">
                                    {{ __('Proceed to Payment') }} <i class="fas fa-credit-card ms-1.5"></i>
                                </span>
                                <span wire:loading wire:target="proceed" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    {{ __('Processing…') }}
                                </span>
                            </button>
                        </div>

                    @else
                        {{-- Free plan – no payment needed --}}
                        <h2 class="text-lg font-extrabold text-gray-900 mb-2">{{ __('Almost Done!') }}</h2>
                        <p class="text-gray-500 text-sm mb-6">{{ __("You've chosen the free plan. We'll email you a link to complete your store setup.") }}</p>

                        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-5 flex items-center gap-3">
                            <i class="fas fa-check-circle text-green-500 text-xl shrink-0"></i>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ __('Free Plan') }}</p>
                                <p class="text-xs text-gray-500">{{ __('No payment required. Start immediately.') }}</p>
                            </div>
                        </div>

                        @error('payment')
                            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{{ $message }}</div>
                        @enderror

                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-5 text-xs text-gray-500 leading-relaxed">
                            {{ __('By registering you agree to our') }}
                            <a href="{{ route('website.terms') }}" class="text-primary hover:underline" target="_blank">{{ __('Terms of Service') }}</a>.
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button wire:click="prevStep" type="button"
                                class="w-full sm:flex-1 py-3 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:border-gray-300 transition-colors">
                                <i class="fas fa-arrow-left me-1.5 text-xs"></i> {{ __('Back') }}
                            </button>
                            <button wire:click="proceed" type="button"
                                wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-not-allowed"
                                wire:target="proceed"
                                class="btn-primary w-full sm:flex-1 py-3 font-bold text-sm rounded-xl">
                                <span wire:loading.remove wire:target="proceed">
                                    {{ __('Send Registration Link') }} <i class="fas fa-paper-plane ms-1.5"></i>
                                </span>
                                <span wire:loading wire:target="proceed" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    {{ __('Sending…') }}
                                </span>
                            </button>
                        </div>
                    @endif
                @endif

            </div>
        @endif

    </div>
</div>

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
    let registerStripe = null;
    let registerStripeCard = null;

    function loadSdkScript(src) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = () => reject(new Error('Failed to load ' + src));
            document.head.appendChild(s);
        });
    }

    async function initRegisterPaymentForms() {
        const container = document.getElementById('register-inline-card-form');
        if (!container || container.dataset.initialized) return;

        const gateway = container.dataset.gateway;

        if (gateway === 'stripe') {
            const stripeKey = container.dataset.stripeKey;
            if (!stripeKey) return;

            if (typeof Stripe === 'undefined') {
                try { await loadSdkScript('https://js.stripe.com/v3/'); } catch { return; }
            }

            registerStripe = Stripe(stripeKey);
            const elements = registerStripe.elements();
            registerStripeCard = elements.create('card', {
                style: {
                    base: {
                        fontSize: '14px',
                        color: '#111827',
                        fontFamily: 'inherit',
                        '::placeholder': { color: '#9ca3af' },
                    },
                    invalid: { color: '#dc2626' },
                },
                hidePostalCode: true,
            });

            registerStripeCard.mount('#register-stripe-card-element');
            registerStripeCard.on('change', (event) => {
                const errEl = document.getElementById('register-stripe-card-errors');
                if (!errEl) return;
                errEl.textContent = event.error?.message ?? '';
                errEl.classList.toggle('hidden', !event.error);
            });

            container.dataset.initialized = '1';
            return;
        }

        if (gateway === 'authorize_net') {
            const sandbox = container.dataset.sandbox === '1';
            const src = sandbox ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js';
            if (typeof Accept === 'undefined') {
                try { await loadSdkScript(src); } catch { return; }
            }
        }

        if (gateway === '2checkout') {
            if (typeof TCO === 'undefined') {
                try { await loadSdkScript('https://2pay-js.2checkout.com/v1/2pay.js'); } catch { return; }
            }
        }

        setupRegisterCardFormatting();
        container.dataset.initialized = '1';
    }

    function setupRegisterCardFormatting() {
        const numInput = document.getElementById('register-card-number');
        if (numInput && !numInput.dataset.fmt) {
            numInput.dataset.fmt = '1';
            numInput.addEventListener('input', () => {
                let value = numInput.value.replace(/\D/g, '').slice(0, 16);
                numInput.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            });
        }

        const expInput = document.getElementById('register-card-expiry');
        if (expInput && !expInput.dataset.fmt) {
            expInput.dataset.fmt = '1';
            expInput.addEventListener('input', () => {
                let value = expInput.value.replace(/\D/g, '').slice(0, 4);
                if (value.length > 2) value = value.slice(0, 2) + ' / ' + value.slice(2);
                expInput.value = value;
            });
        }
    }

    initRegisterPaymentForms();

    Livewire.on('registerPaymentMethodChanged', () => {
        registerStripe = null;
        registerStripeCard = null;
        setTimeout(initRegisterPaymentForms, 50);
    });

    document.addEventListener('livewire:updated', () => {
        const container = document.getElementById('register-inline-card-form');
        if (container && !container.dataset.initialized) {
            registerStripe = null;
            registerStripeCard = null;
        }
        setTimeout(initRegisterPaymentForms, 50);
    });

    // Intercept the Proceed to Payment button to tokenize inline card gateways
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('#register-pay-btn');
        if (!button) return;

        const inlineForm = document.getElementById('register-inline-card-form');
        if (!inlineForm) return; // no inline form – let Livewire handle it

        event.preventDefault();
        event.stopImmediatePropagation();

        const gateway = inlineForm.dataset.gateway;
        button.disabled = true;

        if (gateway === 'stripe') {
            if (!registerStripe || !registerStripeCard) {
                await initRegisterPaymentForms();
            }
            if (!registerStripe || !registerStripeCard) {
                button.disabled = false;
                return;
            }

            const { token, error } = await registerStripe.createToken(registerStripeCard);
            if (error) {
                const errEl = document.getElementById('register-stripe-card-errors');
                if (errEl) {
                    errEl.textContent = error.message;
                    errEl.classList.remove('hidden');
                }
                button.disabled = false;
                return;
            }

            await $wire.set('stripeToken', token.id);
            $wire.call('proceed');
            return;
        }

        if (gateway === 'authorize_net') {
            if (typeof Accept === 'undefined') {
                await initRegisterPaymentForms();
            }
            if (typeof Accept === 'undefined') {
                button.disabled = false;
                return;
            }

            const rawExp = document.getElementById('register-card-expiry')?.value.replace(/\s/g, '') ?? '';
            const [mon, yr] = rawExp.split('/');
            const secureData = {
                authData: {
                    apiLoginID: inlineForm.dataset.authLogin,
                    clientKey: inlineForm.dataset.authClient,
                },
                cardData: {
                    cardNumber: document.getElementById('register-card-number')?.value.replace(/\s/g, '') ?? '',
                    month: (mon ?? '').trim(),
                    year: '20' + (yr ?? '').trim(),
                    cardCode: document.getElementById('register-card-cvc')?.value ?? '',
                },
            };

            Accept.dispatchData(secureData, async (response) => {
                if (response.messages.resultCode === 'Error') {
                    const errEl = document.getElementById('register-card-errors');
                    if (errEl) {
                        errEl.textContent = response.messages.message?.[0]?.text ?? 'Card error';
                        errEl.classList.remove('hidden');
                    }
                    button.disabled = false;
                    return;
                }

                await $wire.set('authnetDescriptor', response.opaqueData.dataDescriptor);
                await $wire.set('authnetValue', response.opaqueData.dataValue);
                $wire.call('proceed');
            });
            return;
        }

        if (gateway === '2checkout') {
            if (typeof TCO === 'undefined') {
                await initRegisterPaymentForms();
            }
            if (typeof TCO === 'undefined') {
                button.disabled = false;
                return;
            }

            const rawExp = document.getElementById('register-card-expiry')?.value.replace(/\s/g, '') ?? '';
            const [mon, yr] = rawExp.split('/');

            TCO.requestToken({
                sellerId: inlineForm.dataset['2coSeller'],
                publishableKey: inlineForm.dataset['2coSeller'],
                ccNo: document.getElementById('register-card-number')?.value.replace(/\s/g, '') ?? '',
                cvv: document.getElementById('register-card-cvc')?.value ?? '',
                expMonth: (mon ?? '').trim(),
                expYear: '20' + (yr ?? '').trim(),
            }, async (data) => {
                if (data.errorCode > 0) {
                    const errEl = document.getElementById('register-card-errors');
                    if (errEl) {
                        errEl.textContent = data.errorMsg ?? 'Card error';
                        errEl.classList.remove('hidden');
                    }
                    button.disabled = false;
                    return;
                }

                await $wire.set('twocoToken', data.token.token);
                $wire.call('proceed');
            });
        }
    });

    $wire.on('scrollToTop', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ── Phone input → hidden wire:model sync ─────────────────────────────
    (function syncRegisterPhone() {
        const displayInput = document.getElementById('register-phone-display');
        const hiddenInput  = document.getElementById('register-phone-hidden');

        if (!displayInput || !hiddenInput) return;

        function pushToWire() {
            const val = displayInput.value.trim();
            if (val && typeof val === 'string' && !val.includes('[object')) {
                hiddenInput.value = val;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        displayInput.addEventListener('blur',          pushToWire);
        displayInput.addEventListener('countrychange', pushToWire);
        displayInput.addEventListener('change',        pushToWire);
    })();

    // ── Social OAuth popup ────────────────────────────────────────────────────
    let _socialPopup = null;

    window.openSocialPopup = function (provider) {
        const packageId = @json($packageId ?? '');
        const baseUrl = provider === 'google'
            ? @json(route('website.social.google', ['intent' => 'register-popup']))
            : @json(route('website.social.apple', ['intent' => 'register-popup']));

        const url = baseUrl + (packageId ? '?package_id=' + encodeURIComponent(packageId) : '');

        const w = 600, h = 700;
        const left = Math.max(0, (screen.width  / 2) - (w / 2));
        const top  = Math.max(0, (screen.height / 2) - (h / 2));

        _socialPopup = window.open(
            url,
            'social_auth_popup',
            'width=' + w + ',height=' + h + ',left=' + left + ',top=' + top +
            ',toolbar=no,menubar=no,scrollbars=yes,resizable=yes'
        );

        if (!_socialPopup || _socialPopup.closed) {
            window.location.href = url;
        }
    };

    window.addEventListener('message', function (event) {
        const expectedOrigin = @json(config('app.url'));

        if (event.origin !== expectedOrigin && event.origin !== window.location.origin) {
            return;
        }

        const data = event.data;
        if (!data || typeof data !== 'object') return;
        if (data.type !== 'social_auth_success' && data.type !== 'social_auth_error') return;

        if (_socialPopup && !_socialPopup.closed) {
            _socialPopup.close();
            _socialPopup = null;
        }

        const errEl = document.getElementById('social-popup-error');

        if (data.type === 'social_auth_error') {
            if (errEl) {
                errEl.textContent = data.message || @json(__('Sign in failed. Please try again.'));
                errEl.classList.remove('hidden');
            }
            return;
        }

        if (errEl) errEl.classList.add('hidden');

        if (data.existing_account) {
            if (errEl) {
                errEl.textContent = @json(__('An account with this email already exists. Please sign in instead.'));
                errEl.classList.remove('hidden');
                errEl.classList.remove('border-red-200', 'bg-red-50', 'text-red-600');
                errEl.classList.add('border-blue-200', 'bg-blue-50', 'text-blue-700');
            }
        }

        $wire.call('socialAuthComplete', data.email, data.name, data.provider);
    });
</script>
@endscript
