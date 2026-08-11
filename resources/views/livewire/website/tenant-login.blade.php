<div class="min-h-screen bg-linear-to-br from-orange-50 to-white flex items-center justify-center py-16 px-4">
    <div class="w-full max-w-md">

        {{-- Logo / heading --}}
        <div class="text-center mb-8">
            <a href="{{ route('website.home') }}" class="inline-block mb-6">
                <img src="{{ asset('central-website/assets') }}/image/4ad6eed1943b13bf773152f09ecf0d9ac498c5d3.png"
                    alt="{{ __('Ecommet') }}" class="h-9 w-auto mx-auto">
            </a>
            <h1 class="text-3xl font-extrabold text-gray-900">{{ __('Sign in to your store') }}</h1>
            <p class="text-gray-500 mt-2 text-sm">
                {{ __("Don't have a store yet?") }}
                <a href="{{ route('website.register') }}" class="text-primary font-semibold hover:underline">
                    {{ __('Create one') }}
                </a>
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">

            {{-- ── Store picker (shown when credentials match multiple stores) ── --}}
            @if($showPicker)
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <button type="button" wire:click="$set('showPicker', false)"
                            class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors text-gray-500">
                            <i class="fas fa-arrow-left text-sm"></i>
                        </button>
                        <div>
                            <h2 class="text-base font-bold text-gray-900">{{ __('Select your store') }}</h2>
                            <p class="text-xs text-gray-500">{{ __('Your credentials match multiple stores.') }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach($matchedStores as $store)
                            <button type="button" wire:click="pickStore('{{ $store['tenant_id'] }}')"
                                wire:loading.attr="disabled" class="w-full flex items-center gap-4 p-4 rounded-xl border border-gray-200
                                                   hover:border-primary hover:bg-orange-50 transition-all text-left group">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0
                                                    group-hover:bg-primary/20 transition-colors">
                                    <i class="fas fa-store text-primary text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $store['name'] }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $store['domain'] }}</p>
                                </div>
                                <i
                                    class="fas fa-chevron-right text-gray-300 group-hover:text-primary ms-auto text-xs transition-colors"></i>
                            </button>
                        @endforeach
                    </div>

                    <div wire:loading wire:target="pickStore" class="flex justify-center mt-4">
                        <span class="text-xs text-gray-400">
                            <i class="fas fa-circle-notch fa-spin mr-1"></i>{{ __('Redirecting…') }}
                        </span>
                    </div>
                </div>

                {{-- ── Login form ─────────────────────────────────────────────── --}}
            @else
                <form wire:submit="login" class="space-y-5">

                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 shrink-0"></i>
                            <div class="text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Email --}}
                    <div>
                        <label for="tenant-email" class="block text-sm font-medium text-gray-700 mb-1.5">
                            {{ __('Email address') }}
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 pointer-events-none">
                                <i class="fas fa-envelope text-sm"></i>
                            </span>
                            <input id="tenant-email" type="email" wire:model="email" autocomplete="email"
                                placeholder="{{ __('you@example.com') }}"
                                class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary
                                              {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="tenant-password" class="block text-sm font-medium text-gray-700 mb-1.5">
                            {{ __('Password') }}
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 pointer-events-none">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input id="tenant-password" type="password" wire:model="password"
                                autocomplete="current-password" placeholder="••••••••"
                                class="w-full pl-9 pr-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary
                                              {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}">
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full btn-primary py-3 text-sm font-semibold rounded-xl disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="login">{{ __('Sign In') }}</span>
                        <span wire:loading wire:target="login" class="flex items-center gap-2">
                            <i class="fas fa-circle-notch fa-spin text-xs"></i>
                            {{ __('Checking stores…') }}
                        </span>
                    </button>

                </form>

                {{-- Divider + central admin link --}}
                <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                    <p class="text-xs text-gray-400">
                        {{ __('Are you a platform admin?') }}
                        <a href="{{ route('admin.login') }}" class="text-primary font-medium hover:underline ms-1">
                            {{ __('Admin login') }}
                        </a>
                    </p>
                </div>
            @endif

        </div>

    </div>
</div>