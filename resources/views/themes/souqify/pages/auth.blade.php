{{--
Souqify – Customer Login / Register page
$tab 'login' | 'register'
--}}

<div class="bg-zinc-100 min-h-[calc(100vh-200px)] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">

        <div class="bg-gradient-to-r from-blue-700 to-blue-800 text-white px-8 py-6">
            <h1 class="text-2xl font-bold leading-tight">
                {{ $tab === 'login' ? __('Welcome back') : __('Create your account') }}
            </h1>
            <p class="text-sm text-white/80 mt-1">
                {{ $tab === 'login' ? __('Sign in to continue shopping.') : __('Join Souqify and shop curated tech.') }}
            </p>
        </div>

        <div class="p-8">
            <div class="flex border-b border-neutral-200 mb-6">
                <button wire:click="$set('tab', 'login')"
                    class="flex-1 py-3 text-sm font-semibold transition
                           {{ $tab === 'login' ? 'border-b-2 border-blue-700 text-blue-700' : 'text-neutral-500 hover:text-blue-700' }}">
                    {{ __('Sign In') }}
                </button>
                <button wire:click="$set('tab', 'register')"
                    class="flex-1 py-3 text-sm font-semibold transition
                           {{ $tab === 'register' ? 'border-b-2 border-blue-700 text-blue-700' : 'text-neutral-500 hover:text-blue-700' }}">
                    {{ __('Create Account') }}
                </button>
            </div>

            @if (session('social_login_error'))
                <p class="text-red-600 text-xs text-center mb-4">{{ session('social_login_error') }}</p>
            @endif

            <div class="flex flex-col gap-3 mb-6">
                <a href="{{ route('tenant.storefront.social.google') }}"
                    class="w-full flex items-center justify-center gap-2 border border-neutral-300 rounded-lg py-3 text-sm font-semibold text-neutral-700 hover:bg-neutral-50 transition">
                    <svg class="w-4 h-4" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                        <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                        <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                    </svg>
                    {{ __('Continue with Google') }}
                </a>
                <a href="{{ route('tenant.storefront.social.apple') }}"
                    class="w-full flex items-center justify-center gap-2 bg-black text-white rounded-lg py-3 text-sm font-semibold hover:bg-neutral-900 transition">
                    <svg class="w-4 h-4" viewBox="0 0 384 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"/>
                    </svg>
                    {{ __('Continue with Apple') }}
                </a>
            </div>

            <div class="flex items-center gap-3 mb-6">
                <div class="flex-1 h-px bg-neutral-200"></div>
                <span class="text-[11px] text-neutral-400 uppercase tracking-wide">{{ __('or') }}</span>
                <div class="flex-1 h-px bg-neutral-200"></div>
            </div>

            @if ($tab === 'login')
                <form wire:submit.prevent="login" class="flex flex-col gap-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1.5">{{ __('Email') }}</label>
                        <input type="email" wire:model.lazy="loginEmail"
                            class="w-full border rounded-lg px-4 py-3 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 text-sm {{ $errors->has('loginEmail') ? 'border-red-500' : 'border-neutral-300' }}" />
                        @error('loginEmail') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1.5">{{ __('Password') }}</label>
                        <input type="password" wire:model.lazy="loginPassword"
                            class="w-full border rounded-lg px-4 py-3 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 text-sm {{ $errors->has('loginPassword') ? 'border-red-500' : 'border-neutral-300' }}" />
                        @error('loginPassword') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="login"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white rounded-lg py-3.5 text-sm font-semibold transition mt-2 disabled:opacity-70 flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="login">{{ __('Sign In') }}</span>
                        <span wire:loading wire:target="login">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                            {{ __('Signing in...') }}
                        </span>
                    </button>
                </form>
            @else
                <form wire:submit.prevent="register" class="flex flex-col gap-4">
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1.5">{{ __('Full name') }}</label>
                        <input type="text" wire:model.lazy="regName"
                            class="w-full border rounded-lg px-4 py-3 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 text-sm {{ $errors->has('regName') ? 'border-red-500' : 'border-neutral-300' }}" />
                        @error('regName') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1.5">{{ __('Email') }}</label>
                        <input type="email" wire:model.lazy="regEmail"
                            class="w-full border rounded-lg px-4 py-3 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 text-sm {{ $errors->has('regEmail') ? 'border-red-500' : 'border-neutral-300' }}" />
                        @error('regEmail') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-neutral-600 mb-1.5">{{ __('Phone') }} <span
                                class="text-neutral-400">({{ __('optional') }})</span></label>
                        <input type="tel" data-phone-input wire:model.lazy="regPhone"
                            placeholder="{{ __('Phone number') }}"
                            class="w-full border border-neutral-300 rounded-lg px-4 py-3 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1.5">{{ __('Password') }}</label>
                            <input type="password" wire:model.lazy="regPassword"
                                class="w-full border rounded-lg px-4 py-3 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 text-sm {{ $errors->has('regPassword') ? 'border-red-500' : 'border-neutral-300' }}" />
                            @error('regPassword') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-neutral-600 mb-1.5">{{ __('Confirm') }}</label>
                            <input type="password" wire:model.lazy="regConfirm"
                                class="w-full border border-neutral-300 rounded-lg px-4 py-3 outline-none focus:border-blue-700 focus:ring-2 focus:ring-blue-100 text-sm" />
                        </div>
                    </div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="register"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white rounded-lg py-3.5 text-sm font-semibold transition mt-2 disabled:opacity-70 flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="register">{{ __('Create Account') }}</span>
                        <span wire:loading wire:target="register">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                            {{ __('Creating account...') }}
                        </span>
                    </button>
                </form>
            @endif

            <p class="text-center mt-6 text-sm text-neutral-500">
                <a href="{{ route('tenant.home') }}" class="hover:text-blue-700 transition">
                    ← {{ __('Back to store') }}
                </a>
            </p>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('storefront-auth-tab-changed', function (event) {
            window.bootPhoneInputs();
        });
    </script>
@endpush
