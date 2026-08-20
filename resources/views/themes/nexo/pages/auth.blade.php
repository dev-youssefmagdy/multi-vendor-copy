{{--
Nexo – Customer Login / Register page
$tab 'login' | 'register'
--}}
@push('body-attrs')data-page="index"@endpush

<div class="bg-white min-h-screen flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md">

        {{-- Tabs --}}
        <div class="flex border-b border-[#eee] mb-8">
            <button wire:click="$set('tab', 'login')"
                class="flex-1 py-3 text-[14px] font-semibold transition
                       {{ $tab === 'login' ? 'border-b-2 border-[#171717] text-[#171717]' : 'text-[#888] hover:text-[#171717]' }}">
                {{ __('Sign In') }}
            </button>
            <button wire:click="$set('tab', 'register')"
                class="flex-1 py-3 text-[14px] font-semibold transition
                       {{ $tab === 'register' ? 'border-b-2 border-[#171717] text-[#171717]' : 'text-[#888] hover:text-[#171717]' }}">
                {{ __('Create Account') }}
            </button>
        </div>

        @if (session('social_login_error'))
            <p class="text-red-500 text-[12px] text-center mb-4">{{ session('social_login_error') }}</p>
        @endif

        <div class="flex flex-col gap-3 mb-6">
            <a href="{{ route('tenant.storefront.social.google') }}"
                class="w-full flex items-center justify-center gap-2 border border-[#dcdcdc] rounded-full py-3 text-[13px] font-semibold text-[#171717] hover:bg-[#f7f7f7] transition">
                <svg class="w-4 h-4" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                    <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                </svg>
                {{ __('Continue with Google') }}
            </a>
            <a href="{{ route('tenant.storefront.social.apple') }}"
                class="w-full flex items-center justify-center gap-2 bg-black text-white rounded-full py-3 text-[13px] font-semibold hover:bg-[#111] transition">
                <svg class="w-4 h-4" viewBox="0 0 384 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                </svg>
                {{ __('Continue with Apple') }}
            </a>
        </div>

        <div class="flex items-center gap-3 mb-6">
            <div class="flex-1 h-px bg-[#eee]"></div>
            <span class="text-[11px] text-[#999] uppercase tracking-wide">{{ __('or') }}</span>
            <div class="flex-1 h-px bg-[#eee]"></div>
        </div>

        @if ($tab === 'login')
            <div class="flex flex-col gap-4">
                <div>
                    <input type="email" wire:model.lazy="loginEmail" placeholder="{{ __('Email') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-[#171717] text-[13px] placeholder-[#999] {{ $errors->has('loginEmail') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('loginEmail') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="password" wire:model.lazy="loginPassword" placeholder="{{ __('Password') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-[#171717] text-[13px] placeholder-[#999] {{ $errors->has('loginPassword') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('loginPassword') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <button wire:click="login" wire:loading.attr="disabled"
                    class="w-full bg-[#171717] text-white rounded-full py-3.5 text-[14px] font-semibold hover:bg-black transition mt-2 disabled:opacity-70">
                    <span wire:loading.remove wire:target="login">{{ __('Sign In') }}</span>
                    <span wire:loading wire:target="login">{{ __('Signing in...') }}</span>
                </button>
            </div>
        @else
            <div class="flex flex-col gap-4">
                <div>
                    <input type="text" wire:model.lazy="regName" placeholder="{{ __('Full name') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-[#171717] text-[13px] placeholder-[#999] {{ $errors->has('regName') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('regName') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="email" wire:model.lazy="regEmail" placeholder="{{ __('Email') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-[#171717] text-[13px] placeholder-[#999] {{ $errors->has('regEmail') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('regEmail') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div wire:ignore>
                    <input type="tel" value="" data-phone-input wire-event="regPhone" placeholder="{{ __('Phone (optional)') }}"
                        class="w-full border border-[#dcdcdc] rounded-[6px] px-4 py-3 outline-none focus:border-[#171717] text-[13px] placeholder-[#999]">
                </div>
                <div>
                    <input type="password" wire:model.lazy="regPassword" placeholder="{{ __('Password (min 8 chars)') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-[#171717] text-[13px] placeholder-[#999] {{ $errors->has('regPassword') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('regPassword') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="password" wire:model.lazy="regConfirm" placeholder="{{ __('Confirm password') }}"
                        class="w-full border border-[#dcdcdc] rounded-[6px] px-4 py-3 outline-none focus:border-[#171717] text-[13px] placeholder-[#999]">
                </div>
                <button wire:click="register" wire:loading.attr="disabled"
                    class="w-full bg-[#171717] text-white rounded-full py-3.5 text-[14px] font-semibold hover:bg-black transition mt-2 disabled:opacity-70">
                    <span wire:loading.remove wire:target="register">{{ __('Create Account') }}</span>
                    <span wire:loading wire:target="register">{{ __('Creating account...') }}</span>
                </button>
            </div>
        @endif

        <p class="text-center mt-6 text-[13px] text-[#888]">
            <a href="{{ route('tenant.home') }}" class="hover:underline">{{ __('Back to store') }}</a>
        </p>
    </div>
</div>


@push('scripts')
    <script>
        document.addEventListener('storefront-auth-tab-changed', function (event) {
            window.bootPhoneInputs();
        });
    </script>
@endpush
