{{--
Ecommet – Customer Login / Register page
$tab 'login' | 'register'
--}}

<div class="bg-white min-h-screen flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md">

        {{-- Tabs --}}
        <div class="flex border-b border-[#eee] mb-8">
            <button wire:click="$set('tab', 'login')"
                class="flex-1 py-3 text-[14px] font-semibold transition
                           {{ $tab === 'login' ? 'border-b-2 border-gray-darkest text-gray-darkest' : 'text-gray-medium hover:text-gray-darkest' }}">
                {{ __('Sign In') }}
            </button>
            <button wire:click="$set('tab', 'register')"
                class="flex-1 py-3 text-[14px] font-semibold transition
                           {{ $tab === 'register' ? 'border-b-2 border-gray-darkest text-gray-darkest' : 'text-gray-medium hover:text-gray-darkest' }}">
                {{ __('Create Account') }}
            </button>
        </div>

        @if ($tab === 'login')
            {{-- Login form --}}
            <div class="flex flex-col gap-4">
                <div>
                    <input type="email" wire:model.lazy="loginEmail" placeholder="{{ __('Email') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-gray-darkest text-[13px] placeholder-[#999] {{ $errors->has('loginEmail') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('loginEmail') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="password" wire:model.lazy="loginPassword" placeholder="{{ __('Password') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-gray-darkest text-[13px] placeholder-[#999] {{ $errors->has('loginPassword') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('loginPassword') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <button wire:click="login" wire:loading.attr="disabled"
                    class="w-full bg-gray-darkest text-white rounded-full py-3.5 text-[14px] font-semibold hover:bg-black transition mt-2 disabled:opacity-70">
                    <span wire:loading.remove wire:target="login">{{ __('Sign In') }}</span>
                    <span wire:loading wire:target="login">{{ __('Signing in...') }}</span>
                </button>
            </div>
        @else
            {{-- Register form --}}
            <div class="flex flex-col gap-4">
                <div>
                    <input type="text" wire:model.lazy="regName" placeholder="{{ __('Full name') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-gray-darkest text-[13px] placeholder-[#999] {{ $errors->has('regName') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('regName') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="email" wire:model.lazy="regEmail" placeholder="{{ __('Email') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-gray-darkest text-[13px] placeholder-[#999] {{ $errors->has('regEmail') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('regEmail') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="tel" wire:model.lazy="regPhone" placeholder="{{ __('Phone (optional)') }}"
                        class="w-full border border-[#dcdcdc] rounded-[6px] px-4 py-3 outline-none focus:border-gray-darkest text-[13px] placeholder-[#999]">
                </div>
                <div>
                    <input type="password" wire:model.lazy="regPassword" placeholder="{{ __('Password (min 8 chars)') }}"
                        class="w-full border rounded-[6px] px-4 py-3 outline-none focus:border-gray-darkest text-[13px] placeholder-[#999] {{ $errors->has('regPassword') ? 'border-red-500' : 'border-[#dcdcdc]' }}">
                    @error('regPassword') <p class="text-red-500 text-[11px] mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <input type="password" wire:model.lazy="regConfirm" placeholder="{{ __('Confirm password') }}"
                        class="w-full border border-[#dcdcdc] rounded-[6px] px-4 py-3 outline-none focus:border-gray-darkest text-[13px] placeholder-[#999]">
                </div>
                <button wire:click="register" wire:loading.attr="disabled"
                    class="w-full bg-gray-darkest text-white rounded-full py-3.5 text-[14px] font-semibold hover:bg-black transition mt-2 disabled:opacity-70">
                    <span wire:loading.remove wire:target="register">{{ __('Create Account') }}</span>
                    <span wire:loading wire:target="register">{{ __('Creating account...') }}</span>
                </button>
            </div>
        @endif

        <p class="text-center mt-6 text-[13px] text-gray-medium">
            <a href="{{ route('tenant.home') }}" class="hover:underline">{{ __('Back to store') }}</a>
        </p>
    </div>
</div>