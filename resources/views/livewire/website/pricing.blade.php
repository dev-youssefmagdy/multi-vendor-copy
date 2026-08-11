<div>
    {{-- Hero --}}
    <section class="pt-28 pb-10 bg-linear-to-br from-orange-50 to-white text-center">
        <div class="max-w-3xl mx-auto px-4">
            <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('Pricing Plans') }}</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-4">
                {{ __('Choose a plan that matches your current stage') }}
            </h1>

            {{-- Pricing uses package's configured term and single price value --}}
        </div>
    </section>

    {{-- Billing Term Switcher (above plans) --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 mb-6">
        <div class="flex justify-center">
            <div class="inline-flex items-center bg-orange-50 rounded-full p-1 mt-4 gap-1">
                <button
                    wire:click="setBillingTerm('monthly')"
                    class="px-5 py-2 rounded-full text-sm font-bold transition-all
                        {{ $billingTerm === 'monthly' ? 'bg-white text-primary shadow' : 'text-gray-500 hover:text-gray-800' }}">
                    {{ __('Monthly') }}
                </button>
                <button
                    wire:click="setBillingTerm('yearly')"
                    class="px-5 py-2 rounded-full text-sm font-bold transition-all flex items-center gap-2
                        {{ $billingTerm === 'yearly' ? 'bg-white text-primary shadow' : 'text-gray-500 hover:text-gray-800' }}">
                    {{ __('Yearly') }}
                    <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ __('Save up to 20%') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Plans Grid --}}
    <section class="pb-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-{{ min($packages->count() + 1, 5) }} gap-6">

                {{-- Free / Basic plan --}}
                <div class="bg-white rounded-2xl border border-gray-200 flex flex-col shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                                <i class="fas fa-bolt text-primary"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-extrabold text-gray-900">{{ __('Basic') }}</h3>
                            </div>
                        </div>
                        <div class="mb-1">
                            <span class="text-4xl font-extrabold text-gray-900">{{ __('Free') }}</span>
                        </div>
                        <p class="text-gray-400 text-xs mt-1">{{ __('No payment required to open your store') }}</p>
                    </div>
                    <div class="p-6 flex-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">{{ __("What's Included") }}</p>
                        <ul class="space-y-3 text-sm" x-data="{ expanded: false }">
                            @php
                                $freeFeatures = [
                                    __('Categories Limit')     => '5',
                                    __('Subcategories Limit')  => '10',
                                    __('Products Limit')       => '10',
                                    __('Orders Limit')         => '100',
                                    __('Additional Languages') => '0',
                                    __('Custom Pages')         => '1',
                                    __('Custom Domain')        => false,
                                    __('Staff Accounts')       => '1',
                                ];
                                $freeVisible = array_slice($freeFeatures, 0, 5, true);
                                $freeHidden  = array_slice($freeFeatures, 5, null, true);
                            @endphp
                            @foreach($freeVisible as $label => $value)
                                <li class="flex items-center justify-between gap-2">
                                    <span class="text-gray-600">{{ $label }}</span>
                                    @if($value === true)
                                        <i class="fas fa-check-circle text-green-500 text-base shrink-0"></i>
                                    @elseif($value === false)
                                        <i class="fas fa-times-circle text-red-400 text-base shrink-0"></i>
                                    @else
                                        <span class="font-semibold text-gray-900 shrink-0">{{ $value }}</span>
                                    @endif
                                </li>
                            @endforeach
                            @if(!empty($freeHidden))
                                <template x-if="expanded">
                                    <div>
                                        @foreach($freeHidden as $label => $value)
                                            <li class="flex items-center justify-between gap-2 mt-3">
                                                <span class="text-gray-600">{{ $label }}</span>
                                                @if($value === true)
                                                    <i class="fas fa-check-circle text-green-500 text-base shrink-0"></i>
                                                @elseif($value === false)
                                                    <i class="fas fa-times-circle text-red-400 text-base shrink-0"></i>
                                                @else
                                                    <span class="font-semibold text-gray-900 shrink-0">{{ $value }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </div>
                                </template>
                                <li>
                                    <button @click="expanded = !expanded"
                                        class="text-xs font-semibold text-primary hover:underline mt-1"
                                        x-text="expanded ? '— {{ __('Show Less') }}' : '+ {{ __('Show More') }}'">
                                    </button>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="p-6 pt-0">
                        <a href="{{ route('website.register') }}"
                            class="btn-outline block text-center py-3 font-semibold rounded-xl text-sm">
                            {{ __('Signup') }}
                        </a>
                    </div>
                </div>

                {{-- Paid / Free packages from DB --}}
                @foreach($packages as $index => $package)
                    @php
                        $isHighlighted   = $index === 0;
                        $features        = (array) ($package->features ?? []);
                        $price    = (float) $package->price;
                        $isFree          = $price === 0.0;
                        $visibleFeatures = array_slice($features, 0, 5, true);
                        $hiddenFeatures  = array_slice($features, 5, null, true);
                        $icons           = ['fa-crown', 'fa-chart-line', 'fa-rocket', 'fa-star'];
                        $icon            = $icons[$index % count($icons)] ?? 'fa-crown';
                        $termValue = data_get($package, 'term.value', data_get($package, 'term', 'monthly'));

                        if ($termValue === 'lifetime') {
                            $displayPrice = $price;
                            $termLabel = __('one-time');
                        } else {
                            $displayPrice = $price;
                            $termLabel = $termValue === 'yearly' ? __('year') : __('month');
                        }
                    @endphp
                    <div class="rounded-2xl flex flex-col overflow-hidden relative
                            {{ $isHighlighted ? 'bg-[#FF4B2B] shadow-2xl scale-105 z-10' : 'bg-white border border-gray-200 shadow-sm' }}">
                        @if($isHighlighted)
                            <div class="absolute top-0 left-0 right-0 bg-orange-700 text-white text-xs font-bold text-center py-1.5 tracking-wide">
                                {{ __('Recommended') }}
                            </div>
                        @endif

                        {{-- Header --}}
                        <div class="p-6 {{ $isHighlighted ? 'pt-10' : '' }} border-b {{ $isHighlighted ? 'border-white/20' : 'border-gray-100' }}">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 {{ $isHighlighted ? 'bg-white/20' : 'bg-orange-100' }} rounded-xl flex items-center justify-center shrink-0">
                                    <i class="fas {{ $icon }} {{ $isHighlighted ? 'text-white' : 'text-primary' }}"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-extrabold {{ $isHighlighted ? 'text-white' : 'text-gray-900' }}">
                                        {{ $package->name }}
                                    </h3>
                                </div>
                            </div>
                            <div>
                                @if($isFree)
                                    <span class="text-4xl font-extrabold {{ $isHighlighted ? 'text-white' : 'text-gray-900' }}">
                                        {{ __('Free') }}
                                    </span>
                                    <p class="{{ $isHighlighted ? 'text-orange-100' : 'text-green-600' }} text-xs font-semibold mt-1">
                                        {{ __('No payment required') }}
                                    </p>
                                @else
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-xs {{ $isHighlighted ? 'text-orange-100' : 'text-gray-400' }}">$</span>
                                        <span class="text-4xl font-extrabold {{ $isHighlighted ? 'text-white' : 'text-gray-900' }}">
                                            {{ number_format($price, 0) }}
                                        </span>
                                        <span class="text-sm {{ $isHighlighted ? 'text-orange-100' : 'text-gray-400' }}">/ {{ $termLabel }}</span>
                                    </div>
                                    @if($package->trial_days > 0)
                                        <p class="{{ $isHighlighted ? 'text-orange-100' : 'text-green-600' }} text-xs font-semibold mt-1">
                                            {{ $package->trial_days }} {{ __('day trial included') }}
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Features list --}}
                        <div class="p-6 flex-1">
                            <p class="text-xs font-bold {{ $isHighlighted ? 'text-orange-100' : 'text-gray-400' }} uppercase tracking-widest mb-4">
                                {{ __("What's Included") }}
                            </p>
                            <ul class="space-y-3 text-sm" x-data="{ expanded: false }">
                                @foreach($visibleFeatures as $label => $value)
                                    <li class="flex items-center justify-between gap-2">
                                        <span class="{{ $isHighlighted ? 'text-orange-100' : 'text-gray-600' }}">{{ __($label) }}</span>
                                        @if($value === true || $value === 'true' || $value === 1)
                                            <i class="fas fa-check-circle {{ $isHighlighted ? 'text-orange-200' : 'text-green-500' }} text-base shrink-0"></i>
                                        @elseif($value === false || $value === 'false' || $value === 0)
                                            <i class="fas fa-times-circle {{ $isHighlighted ? 'text-white/40' : 'text-red-400' }} text-base shrink-0"></i>
                                        @elseif(strtolower((string)$value) === 'unlimited' || (string)$value === __('Unlimited'))
                                            <span class="font-semibold {{ $isHighlighted ? 'text-white' : 'text-primary' }} shrink-0">∞</span>
                                        @else
                                            <span class="font-semibold {{ $isHighlighted ? 'text-white' : 'text-gray-900' }} shrink-0">{{ $value }}</span>
                                        @endif
                                    </li>
                                @endforeach
                                @if(!empty($hiddenFeatures))
                                    <template x-if="expanded">
                                        <div>
                                            @foreach($hiddenFeatures as $label => $value)
                                                <li class="flex items-center justify-between gap-2 mt-3">
                                                    <span class="{{ $isHighlighted ? 'text-orange-100' : 'text-gray-600' }}">{{ __($label) }}</span>
                                                    @if($value === true || $value === 'true' || $value === 1)
                                                        <i class="fas fa-check-circle {{ $isHighlighted ? 'text-orange-200' : 'text-green-500' }} text-base shrink-0"></i>
                                                    @elseif($value === false || $value === 'false' || $value === 0)
                                                        <i class="fas fa-times-circle {{ $isHighlighted ? 'text-white/40' : 'text-red-400' }} text-base shrink-0"></i>
                                                    @elseif(strtolower((string)$value) === 'unlimited')
                                                        <span class="font-semibold {{ $isHighlighted ? 'text-white' : 'text-primary' }} shrink-0">∞</span>
                                                    @else
                                                        <span class="font-semibold {{ $isHighlighted ? 'text-white' : 'text-gray-900' }} shrink-0">{{ $value }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </div>
                                    </template>
                                    <li>
                                        <button @click="expanded = !expanded"
                                            class="text-xs font-semibold {{ $isHighlighted ? 'text-orange-100 hover:text-white' : 'text-primary hover:underline' }} mt-1"
                                            x-text="expanded ? '— {{ __('Show Less') }}' : '+ {{ __('Show More') }}'">
                                        </button>
                                    </li>
                                @endif
                            </ul>
                        </div>

                        {{-- CTA --}}
                        <div class="p-6 pt-0">
                            <a href="{{ $isFree ? route('website.register') : route('website.register', ['packageId' => $package->id]) }}"
                                class="block text-center py-3 font-bold rounded-xl text-sm transition-all
                                    {{ $isHighlighted ? 'bg-white text-primary hover:bg-orange-50' : 'btn-primary' }}">
                                {{ $isFree ? __('Signup') : __('Purchase') }}
                            </a>
                        </div>
                    </div>
                @endforeach

                @if($packages->isEmpty())
                    <div class="col-span-full text-center py-12 text-gray-400">
                        <i class="fas fa-box-open text-4xl mb-3 block"></i>
                        <p class="text-sm">{{ __('No plans available.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- CTA Banner --}}
    <section class="py-20 bg-primary">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-extrabold text-white mb-3">{{ __('Need help choosing a plan?') }}</h2>
            <p class="text-orange-100 mb-8">{{ __('Talk to the team if you want help matching packages to catalog size, launch timing, or domain requirements.') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('website.contact') }}"
                    class="bg-white text-primary font-bold px-8 py-3.5 rounded-full hover:bg-orange-50 transition-colors">
                    {{ __('Contact Sales') }}
                </a>
                <a href="{{ route('website.register') }}"
                    class="border-2 border-white text-white font-bold px-8 py-3.5 rounded-full hover:bg-white/10 transition-colors">
                    {{ __('Start Registration') }}
                </a>
            </div>
        </div>
    </section>
</div>
