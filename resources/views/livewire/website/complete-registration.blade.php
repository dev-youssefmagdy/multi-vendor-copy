<div class="min-h-screen bg-linear-to-br from-orange-50 to-white flex items-start justify-center py-16 px-4">
    <div class="w-full max-w-lg">

        {{-- ── INVALID TOKEN ──────────────────────────────────────────────── --}}
        @if($invalid)
            <div class="text-center bg-white rounded-2xl border border-gray-100 shadow-sm p-10">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 mb-3">{{ __('Link Expired or Invalid') }}</h1>
                <p class="text-gray-500 text-sm leading-relaxed mb-5">
                    {{ __('This registration link is no longer valid. It may have expired or already been used.') }}
                </p>
                <a href="{{ route('website.register') }}"
                    class="btn-primary block text-center py-3 font-bold text-sm rounded-xl mb-3">
                    {{ __('Start Registration Again') }} <i class="fas fa-arrow-right ms-1.5"></i>
                </a>
                <a href="{{ route('website.home') }}"
                    class="block text-center py-3 text-sm text-gray-500 border border-gray-200 rounded-xl hover:border-primary hover:text-primary transition-colors">
                    {{ __('Back to Home') }}
                </a>
            </div>

            {{-- ── SUCCESS ─────────────────────────────────────────────────────── --}}
        @elseif($registered)
            <div class="text-center bg-white rounded-2xl border border-gray-100 shadow-sm p-10">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 mb-3">{{ __('Your store is live!') }}</h1>

                @if($hasDomainRequest)
                    <p class="text-gray-500 text-sm leading-relaxed mb-5">
                        {{ __("We've provisioned your store. Your temporary subdomain is ready while your custom domain is being verified.") }}
                    </p>

                    {{-- DNS Connection Check Section --}}
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-5 text-start">
                        <p class="text-xs font-bold text-amber-600 uppercase tracking-wide mb-1">
                            {{ __('Custom Domain DNS Setup') }}
                        </p>
                        <p class="text-gray-600 text-sm leading-relaxed mb-4">
                            {{ __('Configure the following DNS records at your domain registrar, then click "Check Connection" to verify.') }}
                        </p>

                        {{-- DNS Records Table --}}
                        @if($requiredDnsRecords->isNotEmpty())
                            <div class="overflow-x-auto rounded-lg border border-amber-200 mb-4">
                                <table class="w-full text-xs">
                                    <thead class="bg-amber-100">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-bold text-amber-800">{{ __('Type') }}</th>
                                            <th class="px-3 py-2 text-left font-bold text-amber-800">{{ __('Name') }}</th>
                                            <th class="px-3 py-2 text-left font-bold text-amber-800">{{ __('Value') }}</th>
                                            <th class="px-3 py-2 text-left font-bold text-amber-800">{{ __('TTL') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-100">
                                        @foreach($requiredDnsRecords as $rec)
                                            <tr class="bg-white">
                                                <td class="px-3 py-2 font-mono font-bold text-primary">{{ $rec->type }}</td>
                                                <td class="px-3 py-2 font-mono">{{ $rec->name }}</td>
                                                <td class="px-3 py-2 font-mono break-all">{{ $rec->value }}</td>
                                                <td class="px-3 py-2 text-gray-500">{{ $rec->ttl }}</td>
                                            </tr>
                                            @if($rec->description)
                                                <tr class="bg-amber-50">
                                                    <td colspan="4" class="px-3 py-1.5 text-gray-500 italic">{{ $rec->description }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- DNS Check Results --}}
                        @if(count($dnsCheckResults) > 0)
                            <div class="space-y-1.5 mb-4">
                                @foreach($dnsCheckResults as $check)
                                    <div class="flex items-center gap-2 text-xs px-3 py-2 rounded-lg {{ $check['ok'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                                        <i class="fas {{ $check['ok'] ? 'fa-check-circle text-green-500' : 'fa-times-circle text-red-400' }}"></i>
                                        <span class="font-mono font-bold {{ $check['ok'] ? 'text-green-800' : 'text-red-700' }}">{{ $check['type'] }}</span>
                                        <span class="font-mono text-gray-600">{{ $check['name'] }}</span>
                                        <span class="text-gray-400">→</span>
                                        <span class="font-mono text-gray-600 break-all">{{ $check['value'] }}</span>
                                        <span class="ms-auto font-semibold {{ $check['ok'] ? 'text-green-600' : 'text-red-500' }}">
                                            {{ $check['ok'] ? __('OK') : __('Not found') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($dnsConnected)
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-green-50 border border-green-200 mb-4">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span class="text-green-700 text-sm font-semibold">{{ __('DNS is connected! Your domain is pointing correctly.') }}</span>
                            </div>
                        @endif

                        <button wire:click="checkDns" type="button"
                            wire:loading.attr="disabled" wire:loading.class="opacity-75 cursor-not-allowed"
                            wire:target="checkDns"
                            class="w-full py-2.5 rounded-xl border-2 border-amber-400 text-amber-700 font-bold text-sm hover:bg-amber-100 transition-colors">
                            <span wire:loading.remove wire:target="checkDns">
                                <i class="fas fa-sync me-1.5"></i> {{ __('Check DNS Connection') }}
                            </span>
                            <span wire:loading wire:target="checkDns" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('Checking DNS…') }}
                            </span>
                        </button>
                    </div>

                    <p class="text-gray-400 text-xs mb-5">
                        {{ __('Use your subdomain to access your dashboard in the meantime:') }}</p>
                @else
                    <p class="text-gray-500 text-sm leading-relaxed mb-5">
                        {{ __("We've provisioned your isolated database and synced your catalog. Your vendor dashboard is ready at:") }}
                    </p>
                @endif

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 font-mono text-sm text-primary break-all mb-6">
                    {{ $tenantDomain }}
                </div>
                <div class="flex flex-col gap-3">
                    <a href="http://{{ $tenantDomain }}/admin/login"
                        @if($hasDomainRequest && !$dnsConnected) style="pointer-events:none;opacity:0.5;" aria-disabled="true" @endif
                        class="btn-primary block text-center py-3 font-bold text-sm rounded-xl {{ $hasDomainRequest && !$dnsConnected ? 'opacity-50 cursor-not-allowed' : '' }}">
                        {{ __('Go to My Dashboard') }} <i class="fas fa-arrow-right ms-1.5"></i>
                    </a>
                    @if($hasDomainRequest && !$dnsConnected)
                        <p class="text-xs text-gray-400 text-center">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ __('Verify your DNS connection above to enable dashboard access via your custom domain.') }}
                        </p>
                    @endif
                    <a href="{{ route('website.home') }}"
                        class="block text-center py-3 text-sm text-gray-500 border border-gray-200 rounded-xl hover:border-primary hover:text-primary transition-colors">
                        {{ __('Back to Home') }}
                    </a>
                </div>
            </div>

            {{-- ── FORM ─────────────────────────────────────────────────────────── --}}
        @else
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900">{{ __('Complete Your Store Setup') }}</h1>
                <p class="text-gray-500 mt-2 text-sm">{{ __('Fill in your store details to get started.') }}</p>
            </div>

            {{-- Registration summary --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-5 flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <i class="fas fa-user text-primary text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $email }}</p>
                    @if(filled($phone))
                        <p class="text-xs text-gray-500 mt-0.5">{{ $phone }}</p>
                    @endif
                    <span
                        class="inline-block mt-1.5 text-xs font-semibold text-primary bg-orange-50 border border-orange-200 px-2 py-0.5 rounded-full">
                        {{ $planName }}
                    </span>
                </div>
            </div>

            {{-- Form card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
                <h2 class="text-lg font-extrabold text-gray-900 mb-6">{{ __('Store Details') }}</h2>

                {{-- ── Step indicator ── --}}
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $step === 1 ? 'bg-primary text-white' : 'bg-green-500 text-white' }}">
                            @if($step > 1)<i class="fas fa-check text-xs"></i>@else 1 @endif
                        </div>
                        <span class="text-sm font-medium {{ $step === 1 ? 'text-gray-900' : 'text-gray-400' }}">{{ __('Store Setup') }}</span>
                    </div>
                    <div class="flex-1 h-px bg-gray-200"></div>
                    @if($categoriesCount > 0)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $step === 2 ? 'bg-primary text-white' : ($step > 2 ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                                @if($step > 2)<i class="fas fa-check text-xs"></i>@else 2 @endif
                            </div>
                            <span class="text-sm font-medium {{ $step === 2 ? 'text-gray-900' : 'text-gray-400' }}">{{ __('Choose Categories') }}</span>
                        </div>
                        <div class="flex-1 h-px bg-gray-200"></div>
                    @endif
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $step === 3 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500' }}">3</div>
                        <span class="text-sm font-medium {{ $step === 3 ? 'text-gray-900' : 'text-gray-400' }}">{{ __('Target Countries') }}</span>
                    </div>
                </div>

                {{-- ── STEP 1: Shop setup ──────────────────────────────────── --}}
                @if($step === 1)
                <div class="space-y-5">

                    {{-- Full name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Full name') }} <span
                                class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" placeholder="{{ __('Full name') }}"
                            class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Password') }} <span
                                class="text-red-500">*</span></label>
                        <input wire:model="password" type="password" placeholder="{{ __('Min 8 characters') }}"
                            class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Confirm password --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Confirm Password') }} <span
                                class="text-red-500">*</span></label>
                        <input wire:model="passwordConfirmation" type="password" placeholder="{{ __('Repeat password') }}"
                            class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('passwordConfirmation') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                        @error('passwordConfirmation')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Shop name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Shop Name') }} <span
                                class="text-red-500">*</span></label>
                        <input wire:model.live="shopName" type="text" placeholder="{{ __('My Awesome Store') }}"
                            class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('shopName') || ($shopNameTaken && $domainType === 'subdomain') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                        @if(filled($shopName))
                            @if($domainType === 'subdomain' && $shopNameTaken)
                                <p class="text-red-500 text-xs mt-1 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    {{ __('The subdomain') }} <span class="font-mono font-semibold">{{ \Illuminate\Support\Str::slug($shopName) }}.{{ $centralDomain }}</span> {{ __('is already taken. Choose a different name or use your own domain.') }}
                                </p>
                            @else
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ __('Free subdomain:') }} <span
                                        class="text-primary font-mono">{{ \Illuminate\Support\Str::slug($shopName) }}.{{ $centralDomain }}</span>
                                </p>
                            @endif
                        @endif
                        @error('shopName')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Profit percentage --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            {{ __('Profit Percentage (%)') }} <span
                                class="font-normal text-gray-400">({{ __('optional') }})</span>
                        </label>
                        <p class="text-xs text-gray-400 mb-2">{{ __('Markup applied to central catalog prices.') }}</p>
                        <input wire:model="profitPercentage" type="number" step="0.01" min="0" max="1000" placeholder="0"
                            class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('profitPercentage') ? 'border-red-400' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                        @error('profitPercentage')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Domain type --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">{{ __('Choose Your Domain') }}</label>
                        <div class="space-y-2">
                            <label wire:click="$set('domainType','subdomain')"
                                class="flex items-start gap-4 p-4 rounded-xl cursor-pointer border-2 transition-all {{ $domainType === 'subdomain' ? 'border-primary bg-orange-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                <div
                                    class="mt-0.5 w-5 h-5 rounded-full border-2 shrink-0 flex items-center justify-center transition-all {{ $domainType === 'subdomain' ? 'border-primary bg-primary' : 'border-gray-300' }}">
                                    @if($domainType === 'subdomain')
                                    <div class="w-2 h-2 rounded-full bg-white"></div>@endif
                                </div>
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-900 text-sm">{{ __('Use a free subdomain') }}</div>
                                    <div class="text-gray-500 text-xs mt-0.5">{{ __('Get started instantly.') }}</div>
                                    @if(filled($shopName))
                                        @if($shopNameTaken)
                                            <div class="text-xs text-red-500 mt-1.5 font-semibold">{{ __('Subdomain taken') }}</div>
                                        @else
                                            <div class="font-mono text-xs text-primary mt-1.5">
                                                {{ \Illuminate\Support\Str::slug($shopName) }}.{{ $centralDomain }}</div>
                                        @endif
                                    @endif
                                </div>
                                <span
                                    class="text-xs font-bold text-green-600 bg-green-50 border border-green-200 px-2 py-0.5 rounded-full whitespace-nowrap">{{ __('FREE') }}</span>
                            </label>

                            <label wire:click="$set('domainType','custom')"
                                class="flex items-start gap-4 p-4 rounded-xl cursor-pointer border-2 transition-all {{ $domainType === 'custom' ? 'border-primary bg-orange-50' : 'border-gray-200 bg-gray-50 hover:border-gray-300' }}">
                                <div
                                    class="mt-0.5 w-5 h-5 rounded-full border-2 shrink-0 flex items-center justify-center transition-all {{ $domainType === 'custom' ? 'border-primary bg-primary' : 'border-gray-300' }}">
                                    @if($domainType === 'custom')
                                    <div class="w-2 h-2 rounded-full bg-white"></div>@endif
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 text-sm">{{ __('I have my own domain') }}</div>
                                    <div class="text-gray-500 text-xs mt-0.5">
                                        {{ __('Connect your custom domain. DNS verification required.') }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    @if($domainType === 'custom')
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('Your Domain Name') }}</label>
                            <input wire:model.live="customDomain" type="text" placeholder="mystore.com"
                                class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all {{ $errors->has('customDomain') ? 'border-red-400' : 'border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                            <p class="text-xs text-gray-400 mt-1">
                                {{ __('Enter without http:// — e.g.') }} <span class="text-primary font-mono">mystore.com</span>
                            </p>
                            @error('customDomain')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- DNS Records Preview --}}
                        @if(count($dnsRecordsForDisplay) > 0)
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                <p class="text-xs font-bold text-blue-700 uppercase tracking-wide mb-2">
                                    <i class="fas fa-info-circle me-1"></i> {{ __('DNS Records You\'ll Need to Add') }}
                                </p>
                                <p class="text-xs text-blue-600 mb-3 leading-relaxed">
                                    {{ __('After registering, add these records at your domain registrar to connect your domain:') }}
                                </p>
                                <div class="overflow-x-auto rounded-lg border border-blue-200">
                                    <table class="w-full text-xs">
                                        <thead class="bg-blue-100">
                                            <tr>
                                                <th class="px-3 py-1.5 text-left font-bold text-blue-800">{{ __('Type') }}</th>
                                                <th class="px-3 py-1.5 text-left font-bold text-blue-800">{{ __('Name') }}</th>
                                                <th class="px-3 py-1.5 text-left font-bold text-blue-800">{{ __('Value') }}</th>
                                                <th class="px-3 py-1.5 text-left font-bold text-blue-800">{{ __('TTL') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-blue-100">
                                            @foreach($dnsRecordsForDisplay as $rec)
                                                <tr class="bg-white">
                                                    <td class="px-3 py-2 font-mono font-bold text-primary">{{ $rec['type'] }}</td>
                                                    <td class="px-3 py-2 font-mono">{{ $rec['name'] }}</td>
                                                    <td class="px-3 py-2 font-mono break-all">{{ $rec['value'] }}</td>
                                                    <td class="px-3 py-2 text-gray-500">{{ $rec['ttl'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Terms --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-500 leading-relaxed">
                        {{ __('By registering you agree to our') }}
                        <a href="{{ route('website.terms') }}" class="text-primary hover:underline"
                            target="_blank">{{ __('Terms of Service') }}</a>.
                        {{ __('Your isolated database will be provisioned immediately after registration.') }}
                    </div>

                    {{-- All-categories notice (plan gives access to everything) --}}
                    @if($categoriesCount === 0)
                        <div class="flex items-start gap-3 p-4 rounded-xl border border-green-200 bg-green-50">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <div>
                                <div class="text-sm font-bold text-green-800">{{ __('All categories included in your plan') }}</div>
                                <div class="text-xs text-green-700 mt-0.5 leading-relaxed">
                                    {{ __('Great news! Your plan gives you access to all product categories. They will be synced to your store automatically — no selection needed.') }}
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Submit step 1 --}}
                    <button wire:click="submitStep1" type="button" wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed" wire:target="submitStep1"
                        class="btn-primary w-full py-3.5 font-bold text-sm rounded-xl">
                        <span wire:loading.remove wire:target="submitStep1">
                            {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                        </span>
                        <span wire:loading wire:target="submitStep1" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ __('Processing…') }}
                        </span>
                    </button>
                </div>
                @endif

                {{-- ── STEP 2: Category selection ────────────────────────── --}}
                @if($step === 2)
                <div class="space-y-5">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900 mb-1">
                            {{ __('Select Your Product Categories') }}
                        </h2>
                        <p class="text-sm text-gray-600 mb-4">
                            {{ __('Your plan allows up to :count categories.', ['count' => $categoriesCount]) }}
                        </p>

                        @if($rootCategories->isEmpty())
                            <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-500">
                                <i class="fas fa-info-circle me-1.5 text-gray-400"></i>
                                {{ __('No categories are available yet.') }}
                            </div>
                        @else
                            {{-- Category tree selector --}}
                            <div class="space-y-2 max-h-80 overflow-y-auto border border-gray-200 rounded-xl p-3">
                                @foreach($rootCategories as $root)
                                    @php
                                        $rootSelected = in_array((string) $root->id, $selectedCategoryIds);
                                        $hasChildren = $root->children->isNotEmpty();
                                    @endphp
                                    <div x-data="{ open: {{ $rootSelected ? 'true' : 'false' }} }"
                                         class="border border-gray-100 rounded-lg overflow-hidden">

                                        {{-- Root row --}}
                                        <label class="flex items-center gap-3 p-3 cursor-pointer
                                               {{ $rootSelected ? 'bg-primary/5' : 'hover:bg-gray-50' }} transition-colors">
                                            <input
                                                type="checkbox"
                                                value="{{ $root->id }}"
                                                class="w-4 h-4 rounded text-primary"
                                                wire:model.live="selectedCategoryIds"
                                                @if(!$rootSelected && count($selectedCategoryIds) >= $categoriesCount)
                                                    disabled title="{{ __('Maximum categories reached') }}"
                                                @endif
                                            >
                                            <span class="flex-1 font-medium text-sm text-gray-900">
                                                {{ $root->translationValue('name') ?: $root->id }}
                                            </span>
                                            @if($hasChildren)
                                                <button type="button" @click.prevent="open = !open"
                                                    class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                                                    <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-90' : ''"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                            clip-rule="evenodd"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </label>

                                        {{-- Level 2 children --}}
                                        @if($hasChildren)
                                            <div x-show="open" x-cloak class="bg-gray-50 border-t border-gray-100 px-4 py-2 space-y-1">
                                                @foreach($root->children as $child)
                                                    @php $hasGrandchildren = $child->children->isNotEmpty(); @endphp
                                                    <div x-data="{ open2: false }">
                                                        <div class="flex items-center gap-2 py-1.5">
                                                            <span class="w-3 border-t border-gray-300 flex-shrink-0"></span>
                                                            <span class="text-sm text-gray-600 flex-1">
                                                                {{ $child->translationValue('name') ?: $child->id }}
                                                            </span>
                                                            @if($hasGrandchildren)
                                                                <button type="button" @click.prevent="open2 = !open2"
                                                                    class="text-gray-400 hover:text-gray-600 p-0.5">
                                                                    <svg class="w-3.5 h-3.5 transition-transform" :class="open2 ? 'rotate-90' : ''"
                                                                        viewBox="0 0 20 20" fill="currentColor">
                                                                        <path fill-rule="evenodd"
                                                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                                            clip-rule="evenodd"/>
                                                                    </svg>
                                                                </button>
                                                            @endif
                                                        </div>

                                                        {{-- Level 3 grandchildren --}}
                                                        @if($hasGrandchildren)
                                                            <div x-show="open2" x-cloak class="ml-5 space-y-1">
                                                                @foreach($child->children as $grandchild)
                                                                    <div class="flex items-center gap-2 py-1">
                                                                        <span class="w-3 border-t border-gray-200 flex-shrink-0"></span>
                                                                        <span class="text-xs text-gray-500">
                                                                            {{ $grandchild->translationValue('name') ?: $grandchild->id }}
                                                                        </span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                                <i class="fas fa-info-circle"></i>
                                {{ __(':selected / :max root categories selected. Sub-categories are included automatically.', [
                                    'selected' => count($selectedCategoryIds),
                                    'max' => $categoriesCount,
                                ]) }}
                            </p>

                            {{-- Preview of what will be synced --}}
                            @if(!empty($categoryPreviewTree))
                                <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                                    <p class="text-xs font-semibold text-blue-700 mb-2 flex items-center gap-1.5">
                                        <i class="fas fa-layer-group"></i>
                                        {{ __('Categories that will be available in your store:') }}
                                    </p>
                                    <div class="space-y-1">
                                        @foreach($categoryPreviewTree as $root)
                                            <div>
                                                <span class="text-xs font-medium text-blue-900">
                                                    <i class="fas fa-folder text-blue-400 me-1"></i>{{ $root['name'] }}
                                                </span>
                                                @if(!empty($root['children']))
                                                    <div class="ml-4 mt-0.5 space-y-0.5">
                                                        @foreach($root['children'] as $child)
                                                            <div>
                                                                <span class="text-xs text-blue-700">
                                                                    <i class="fas fa-folder-open text-blue-300 me-1"></i>{{ $child['name'] }}
                                                                </span>
                                                                @if(!empty($child['children']))
                                                                    <div class="ml-4 mt-0.5">
                                                                        @foreach($child['children'] as $grand)
                                                                            <span class="text-xs text-blue-500 block">
                                                                                <i class="fas fa-tag text-blue-200 me-1"></i>{{ $grand['name'] }}
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        @error('selectedCategoryIds')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        @error('selectedCategoryIds.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="prevStep" type="button"
                            class="flex-1 py-3.5 font-bold text-sm rounded-xl border-2 border-gray-200 text-gray-600 hover:border-gray-300 transition-colors">
                            <i class="fas fa-arrow-left me-1.5"></i> {{ __('Back') }}
                        </button>

                        <button wire:click="submitStep2" type="button" wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed" wire:target="submitStep2"
                            class="flex-1 btn-primary py-3.5 font-bold text-sm rounded-xl">
                            <span wire:loading.remove wire:target="submitStep2">
                                {{ __('Continue') }} <i class="fas fa-arrow-right ms-1.5"></i>
                            </span>
                            <span wire:loading wire:target="submitStep2">
                                {{ __('Please wait…') }}
                            </span>
                        </button>
                    </div>
                </div>
                @endif

                {{-- ── STEP 3: Target countries selection ──────────────────── --}}
                @if($step === 3)
                <div class="space-y-5">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900 mb-1">
                            {{ __('Which countries will you sell to?') }}
                        </h2>
                        <p class="text-sm text-gray-600 mb-4">
                            {{ __('Countries marked Free are included at no extra cost. Select all the countries you plan to ship to.') }}
                        </p>

                        <input wire:model.live.debounce.300ms="countrySearch" type="text"
                            placeholder="{{ __('Search countries...') }}"
                            class="w-full px-4 py-2.5 rounded-xl border text-sm outline-none transition-all border-gray-200 bg-gray-50 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 mb-3">

                        @if($countries->isEmpty())
                            <div class="p-4 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-500">
                                <i class="fas fa-info-circle me-1.5 text-gray-400"></i>
                                {{ __('No countries match your search.') }}
                            </div>
                        @else
                            <div class="max-h-80 overflow-y-auto rounded-xl border border-gray-200 divide-y divide-gray-100">
                                @foreach($countries as $country)
                                    <label class="flex items-center justify-between gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50">
                                        <span class="flex items-center gap-2 text-sm text-gray-800">
                                            <input type="checkbox" wire:model="selectedCountryIds" value="{{ $country->id }}"
                                                class="rounded border-gray-300 text-primary focus:ring-primary/30">
                                            @if($country->flag_emoji) <span>{{ $country->flag_emoji }}</span> @endif
                                            {{ $country->name ?: $country->iso2 }}
                                        </span>
                                        @if($country->is_free)
                                            <span class="text-xs font-bold text-green-600 bg-green-50 border border-green-200 px-2 py-0.5 rounded-full whitespace-nowrap">{{ __('FREE') }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-400 mt-2">
                                {{ __(':count countries selected', ['count' => count($selectedCountryIds)]) }}
                            </p>
                        @endif

                        @error('selectedCountryIds')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        @error('selectedCountryIds.*')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="prevStep" type="button"
                            class="flex-1 py-3.5 font-bold text-sm rounded-xl border-2 border-gray-200 text-gray-600 hover:border-gray-300 transition-colors">
                            <i class="fas fa-arrow-left me-1.5"></i> {{ __('Back') }}
                        </button>

                        <button wire:click="submitStep3" type="button" wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed" wire:target="submitStep3"
                            class="flex-1 btn-primary py-3.5 font-bold text-sm rounded-xl">
                            <span wire:loading.remove wire:target="submitStep3">
                                {{ __('Create My Store') }} <i class="fas fa-rocket ms-1.5"></i>
                            </span>
                            <span wire:loading wire:target="submitStep3" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('Provisioning database…') }}
                            </span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        @endif

    </div>
</div>

@if($categoriesCount > 0)
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
        <style>
            .select2-container .select2-selection--multiple { min-height: 3rem; border-radius: 0.75rem; border-color: #e5e7eb; }
            .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: #fff7ed; border-color: #fdba74; }
        </style>
    @endpush
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @endpush
@endif

@script
<script>
    $wire.on('scrollToTop', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
</script>
@endscript
