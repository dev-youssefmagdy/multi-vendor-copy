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
                        <input type="tel" wire:model.lazy="regPhone"
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