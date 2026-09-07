<div>
    <section class="pt-28 pb-14 bg-linear-to-br from-orange-50 to-white text-center">
        <div class="max-w-2xl mx-auto px-4" data-reveal>
            <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('How It Works') }}</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-4">{{ __('How It Works') }}</h1>
            <p class="text-gray-500 text-lg">{{ __('Start selling online in three simple steps') }} —
                {{ __('no technical skills required.') }}
            </p>
        </div>
    </section>

    <!-- STEPS — DESKTOP RADIAL LAYOUT -->
    <section class="py-16 bg-white overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Desktop: central image with 4 steps around it -->
            <div class="hidden lg:grid grid-cols-3 gap-8 items-center" data-reveal>
                <!-- Left steps -->
                <div class="space-y-12">
                    <!-- Step 1 -->
                    <div
                        class="step-card bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-right hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-end gap-4">
                            <div>
                                <p class="text-gray-500 text-sm leading-relaxed">
                                    {{ __('Pick a subscription plan that fits your business needs.') }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center shrink-0 text-white font-extrabold text-lg shadow-md">
                                1</div>
                        </div>
                    </div>
                    <!-- Step 4 -->
                    <div
                        class="step-card bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-right hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-end gap-4">
                            <div>
                                <p class="text-gray-500 text-sm leading-relaxed">
                                    {{ __('Go live, manage orders, and grow your online business.') }}
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 rounded-xl bg-gray-900 flex items-center justify-center shrink-0 text-white font-extrabold text-lg shadow-md">
                                4</div>
                        </div>
                    </div>
                </div>

                <!-- Center image -->
                <div class="relative flex items-center justify-center">
                    <div class="absolute w-72 h-72 bg-orange-100 rounded-full opacity-50"></div>
                    <div class="absolute w-56 h-56 bg-orange-200 rounded-full opacity-40"></div>
                    <img src="{{ asset('central-website/assets') }}/image/how-it-works.png"
                        alt="{{ __('How Ecommet Works') }}" class="relative z-10 w-64 drop-shadow-xl">
                </div>

                <!-- Right steps -->
                <div class="space-y-12">
                    <!-- Step 2 -->
                    <div
                        class="step-card bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center shrink-0 text-white font-extrabold text-lg shadow-md">
                                2</div>
                            <div>
                                <p class="text-gray-500 text-sm leading-relaxed">
                                    {{ __('Choose a ready-made theme and customize it to match your brand.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Step 3 -->
                    <div
                        class="step-card bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center shrink-0 text-white font-extrabold text-lg shadow-md">
                                3</div>
                            <div>
                                <p class="text-gray-500 text-sm leading-relaxed">
                                    {{ __('Upload products, set prices, and configure payment options easily.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile: vertical list -->
            <div class="lg:hidden space-y-6 max-w-lg mx-auto">
                <div class="flex gap-4 items-start" data-reveal>
                    <div class="flex flex-col items-center shrink-0">
                        <div
                            class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white font-extrabold shadow-md">
                            1</div>
                        <div class="w-0.5 h-full bg-orange-200 mt-2 min-h-8"></div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">{{ __('Choose Your Plan') }}</h3>
                        <p class="text-gray-500 text-sm">
                            {{ __('Pick a subscription plan that fits your business needs.') }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-4 items-start" data-reveal>
                    <div class="flex flex-col items-center shrink-0">
                        <div
                            class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white font-extrabold shadow-md">
                            2</div>
                        <div class="w-0.5 h-full bg-orange-200 mt-2 min-h-8"></div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">{{ __('Pick a Template') }}</h3>
                        <p class="text-gray-500 text-sm">
                            {{ __('Choose a ready-made theme and customize it to match your brand.') }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-4 items-start" data-reveal>
                    <div class="flex flex-col items-center shrink-0">
                        <div
                            class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white font-extrabold shadow-md">
                            3</div>
                        <div class="w-0.5 h-full bg-orange-200 mt-2 min-h-8"></div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">{{ __('Add Your Products') }}</h3>
                        <p class="text-gray-500 text-sm">
                            {{ __('Upload products, set prices, and configure payment options easily.') }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-4 items-start" data-reveal>
                    <div class="flex flex-col items-center shrink-0">
                        <div
                            class="w-10 h-10 rounded-xl bg-gray-900 flex items-center justify-center text-white font-extrabold shadow-md">
                            4</div>
                    </div>
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">{{ __('Launch & Grow') }}</h3>
                        <p class="text-gray-500 text-sm">
                            {{ __('Go live, manage orders, and grow your online business.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURE HIGHLIGHTS -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-reveal>
                <h2 class="section-title text-3xl font-extrabold text-gray-900">
                    {{ __('Everything You Need to Succeed') }}
                </h2>
                <p class="text-gray-500 mt-3 max-w-xl mx-auto">
                    {{ __('Ecommet gives you professional tools without the complexity.') }}
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow"
                    data-reveal>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-paint-brush text-primary text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ __('Drag-and-Drop Builder') }}</h3>
                    <p class="text-gray-500 text-sm">
                        {{ __('Customise every aspect of your store with an intuitive visual editor — no code needed.') }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow"
                    data-reveal>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-credit-card text-primary text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ __('Integrated Payments') }}</h3>
                    <p class="text-gray-500 text-sm">
                        {{ __('Accept credit cards, PayPal, and local payment methods right out of the box.') }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow"
                    data-reveal>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-truck text-primary text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ __('Shipping Management') }}</h3>
                    <p class="text-gray-500 text-sm">
                        {{ __('Set shipping zones, rates and track deliveries from your centralized dashboard.') }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow"
                    data-reveal>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-chart-bar text-primary text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ __('Analytics & Reports') }}</h3>
                    <p class="text-gray-500 text-sm">
                        {{ __("Understand what's working with real-time sales metrics and customer insights.") }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow"
                    data-reveal>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-globe text-primary text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ __('Multi-Language') }}</h3>
                    <p class="text-gray-500 text-sm">
                        {{ __('Serve customers worldwide with full support for multiple languages and currencies.') }}
                    </p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-shadow"
                    data-reveal>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-headset text-primary text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-2">{{ __('Expert Support') }}</h3>
                    <p class="text-gray-500 text-sm">
                        {{ __('Get help whenever you need it via live chat, email, and our comprehensive help center.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-16 bg-primary text-white text-center">
        <div class="max-w-2xl mx-auto px-4" data-reveal>
            <h2 class="text-3xl font-extrabold mb-3">{{ __('Ready to Build Your Store?') }}</h2>
            <p class="text-orange-100 mb-8">{{ __('Join thousands of businesses already selling on Ecommet.') }}</p>
            <a href="{{ route('website.pricing') }}"
                class="inline-block bg-white text-primary font-bold px-8 py-3.5 rounded-full hover:bg-orange-50 transition-colors">{{ __('Get Started Free') }}</a>
        </div>
    </section>
</div>


@push('styles')
    <style>
        .step-connector {
            position: absolute;
            top: 50%;
            left: 100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, #FF4B2B, #FF622E);
            transform: translateY(-50%);
            z-index: 0
        }

        .step-card {
            position: relative;
            z-index: 1
        }

        @media(max-width:1023px) {
            .step-connector {
                display: none
            }
        }
    </style>
@endpush