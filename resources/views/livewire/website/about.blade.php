<div>
    <section class="pt-28 pb-16 bg-linear-to-br from-orange-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div data-reveal>
                    <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('About Us') }}</span>
                    <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-5">
                        {{ $brandName }} {{ __('helps merchants launch and scale with less operational drag') }}
                    </h1>
                    <p class="text-gray-500 text-lg leading-relaxed mb-6">
                        {{ __('We built the platform around a central-first commerce workflow: tenant provisioning, shared catalog sync, plan assignment, and domain onboarding without making operators manage every step manually.') }}
                    </p>
                    <a href="{{ route('website.pricing') }}"  class="btn-primary px-8 py-3.5 text-base">
                        {{ __('View Pricing') }}
                    </a>
                </div>
                <div class="flex-1 hidden lg:block" data-reveal>
                    <div class="relative">
                        <div class="absolute inset-0 bg-orange-100 rounded-3xl rotate-3"></div>
                        <img src="{{ asset('central-website/assets') }}/image/about.png"
                            alt="About {{ $brandName }}" class="relative w-full rounded-2xl shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-orange-50 rounded-2xl p-10" data-reveal>
                    <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-bullseye text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-4">{{ __('Our Mission') }}</h3>
                    <p class="text-gray-600 leading-relaxed">
                        {{ __('Help central operators and merchants launch faster with a maintainable multi-tenant commerce foundation that covers catalog sync, tenant setup, and day-one store operations.') }}
                    </p>
                </div>
                <div class="bg-gray-900 rounded-2xl p-10" data-reveal>
                    <div class="w-14 h-14 bg-primary/20 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-eye text-primary text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-extrabold text-white mb-4">{{ __('Our Vision') }}</h3>
                    <p class="text-gray-400 leading-relaxed">
                        {{ __('A marketplace platform where central operations stay consistent, tenant storefronts stay isolated, and public-facing onboarding stays simple for merchants.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14" data-reveal>
                <h2 class="text-3xl font-extrabold text-gray-900 section-title">{{ __('Platform Snapshot') }}</h2>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($stats as $stat)
                    <div class="bg-white rounded-2xl p-8 text-center shadow-sm" data-reveal>
                        <span class="text-5xl font-extrabold text-primary">{{ $stat['value'] }}</span>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-2 mb-1">{{ __($stat['label']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14" data-reveal>
                <h2 class="text-3xl font-extrabold text-gray-900 section-title">{{ __('Principles') }}</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach([
                    ['icon' => 'fa-heart',         'title' => 'Merchant First',       'desc' => 'Signup, domain choice, and plan assignment should reduce friction instead of creating admin work.'],
                    ['icon' => 'fa-shield-alt',     'title' => 'Separation by Default','desc' => 'Central content and plans stay manageable while tenant data and runtime state remain isolated.'],
                    ['icon' => 'fa-lightbulb',      'title' => 'Pragmatic Growth',     'desc' => 'We favor systems that are easy to operate, easy to extend, and easy to keep in sync across tenants.'],
                    ['icon' => 'fa-users',          'title' => 'Shared Knowledge',     'desc' => 'FAQs, blog articles, and help content are centrally published so the public site reflects real operational updates.'],
                    ['icon' => 'fa-hands-helping',  'title' => 'Accessible Onboarding','desc' => 'Free entry, optional custom domain requests, and clear plan steps help smaller merchants start without heavy setup costs.'],
                    ['icon' => 'fa-chart-line',     'title' => 'Built to Scale',       'desc' => 'The same registration path can handle trial stores, paid packages, catalog-based provisioning, and later tenant expansion.'],
                ] as $principle)
                    <div class="bg-gray-50 rounded-2xl p-8" data-reveal>
                        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                            <i class="fas {{ $principle['icon'] }} text-primary text-lg"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">{{ __($principle['title']) }}</h4>
                        <p class="text-gray-500 text-sm">{{ __($principle['desc']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 bg-primary">
        <div class="max-w-3xl mx-auto px-4 text-center" data-reveal>
            <h2 class="text-3xl font-extrabold text-white mb-4">{{ __('Ready to Provision Your Store?') }}</h2>
            <div class="flex flex-wrap gap-4 justify-center mt-8">
                <a href="{{ route('website.pricing') }}"
                    class="bg-white text-primary font-bold px-8 py-3.5 rounded-full hover:bg-orange-50 transition-colors">
                    {{ __('View Pricing') }}
                </a>
                <a href="{{ route('website.contact') }}"
                    class="border-2 border-white text-white font-bold px-8 py-3.5 rounded-full hover:bg-white/10 transition-colors">
                    {{ __('Contact') }}
                </a>
            </div>
        </div>
    </section>
</div>
