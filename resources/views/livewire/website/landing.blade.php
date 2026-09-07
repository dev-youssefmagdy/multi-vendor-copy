<div>
    {{-- Hero --}}
    <section class="pt-28 pb-16 bg-linear-to-br from-orange-50 via-white to-orange-50 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-10 items-center">
                <div>
                    <span class="inline-flex items-center gap-2 bg-orange-100 text-primary text-sm font-semibold px-4 py-1.5 rounded-full mb-5">
                        {{ __('Our platform is the key to your success') }}
                        <img src="{{ asset('central-website/assets') }}/image/icon-trophy.png" alt="" class="w-5 h-5">
                    </span>
                    <h1 class="text-4xl sm:text-5xl lg:text-[3.4rem] font-extrabold text-gray-900 leading-tight mb-6">
                        {{ __('Build Your Online Store in') }} <span class="text-primary">{{ __('Minutes') }}</span>
                    </h1>
                    <p class="text-lg text-gray-500 mb-8 max-w-xl">
                        {{ __('Manage products, payments and sales with ease. Everything you need to grow your business is just one click away. Start today and simplify your e-commerce journey.') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="{{ route('website.register') }}"  class="btn-primary px-8 py-3.5 text-base">{{ __('Start Now') }}</a>
                        <a href="{{ route('website.how-it-works') }}"
                            class="w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-colors">
                            <i class="fas fa-play text-sm"></i>
                        </a>
                    </div>
                </div>
                <div class="flex justify-center">
                    <div class="relative w-full max-w-lg">
                        <div class="absolute -top-10 -left-10 w-72 h-72 bg-orange-200 rounded-full opacity-30 blur-3xl"></div>
                        <div class="absolute -bottom-10 -right-10 w-72 h-72 bg-orange-300 rounded-full opacity-25 blur-3xl"></div>
                        <img src="{{ asset('central-website/assets') }}/image/c5b31ec6dd67c7260142577196b62dc79423b3da.png"
                            alt="{{ __('Ecommet platform preview') }}" class="relative w-full rounded-3xl">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Achievements / Stats --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-extrabold text-gray-900 section-title">{{ __('Our Overall Achievements') }}</h2>
                <p class="text-gray-500 mt-4 max-w-2xl mx-auto">{{ __('Join thousands of entrepreneurs building their online stores with ease.') }}</p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($stats as $stat)
                    <div class="bg-orange-50 rounded-2xl p-8 text-center hover:shadow-lg transition-shadow">
                        <span class="text-4xl sm:text-5xl font-extrabold text-primary">{{ $stat['value'] }}</span>
                        <p class="text-xs font-bold text-gray-500 tracking-widest uppercase mt-3 mb-1">{{ __($stat['label']) }}</p>
                        <p class="text-sm text-gray-400">{{ __($stat['description']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-extrabold text-gray-900 section-title">{{ __('Features') }}</h2>
                <p class="text-gray-500 mt-4">{{ __('With') }} <span class="text-primary font-bold">Ecommet</span> {{ __('your business is easier and you stay worry-free.') }}</p>
            </div>
            {{-- Desktop: 2 columns with center image --}}
            <div class="features-grid">
                {{-- Top-left --}}
                <div class="bg-white rounded-2xl p-7 shadow-sm">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-lg font-bold text-primary mb-2">{{ __('Scalable for Growth') }}</h3>
                    <p class="text-gray-500 text-sm">{{ __('The platform grows with your business, providing flexibility to add new products, features, and users easily.') }}</p>
                </div>
                {{-- Center image --}}
                <div class="feature-img-col flex items-center justify-center py-8">
                    <img src="{{ asset('central-website/assets') }}/image/feature-img.png"
                        alt="{{ __('Features') }}" class="w-full max-w-65 mx-auto">
                </div>
                {{-- Top-right --}}
                <div class="bg-white rounded-2xl p-7 shadow-sm">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-hand-holding-heart text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-lg font-bold text-primary mb-2">{{ __('Easy-to-Use') }}</h3>
                    <p class="text-gray-500 text-sm">{{ __('Manage products, orders, and sales from one simple, intuitive interface.') }}</p>
                </div>
                {{-- Bottom-left --}}
                <div class="bg-white rounded-2xl p-7 shadow-sm">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-th-large text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-lg font-bold text-primary mb-2">{{ __('Category') }}</h3>
                    <p class="text-gray-500 text-sm">{{ __('The advantage is that you can simply choose a category right away.') }}</p>
                </div>
                {{-- Bottom-right --}}
                <div class="bg-white rounded-2xl p-7 shadow-sm">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-lg font-bold text-primary mb-2">{{ __('Secure') }}</h3>
                    <p class="text-gray-500 text-sm">{{ __("Ensure your customers' data is protected with top-tier security and trusted protocols.") }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Templates --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-extrabold text-gray-900 section-title">{{ __('Templates') }}</h2>
            </div>

            @if($templates->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($templates as $template)
                        <div class="tmpl-card">
                            @if($template->previewFile)
                                <img src="{{ $template->previewFile->full_path }}" alt="{{ $template->name }}" class="w-full">
                            @else
                                <div class="bg-gray-100 aspect-video flex items-center justify-center">
                                    <i class="fas fa-image text-4xl text-gray-300"></i>
                                </div>
                            @endif
                            <div class="tmpl-overlay">
                                @if($template->previewFile)
                                    <button type="button"
                                        onclick="openTemplatePreview('{{ addslashes($template->name) }}', '{{ $template->previewFile->full_path }}')"
                                        class="bg-white text-gray-800 font-semibold px-6 py-2.5 rounded-full hover:bg-primary hover:text-white transition-colors text-sm">
                                        {{ __('Preview') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-10">
                    <a href="{{ route('website.templates') }}" class="text-primary font-semibold hover:underline">{{ __('See More') }} <i class="fas fa-arrow-right text-xs ms-1"></i></a>
                </div>

                {{-- Preview Modal --}}
                <div id="tmpl-preview-modal"
                    class="fixed inset-0 flex items-center justify-center p-4 sm:p-8"
                    style="display:none!important; z-index:9999; background:rgba(0,0,0,0.85);"
                    onclick="if(event.target===this) closeTemplatePreview()">
                    <div class="relative w-full max-w-5xl max-h-[90vh] flex flex-col rounded-2xl overflow-hidden bg-white shadow-2xl">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                            <h3 id="tmpl-preview-name" class="font-bold text-gray-900 text-lg"></h3>
                            <button type="button" onclick="closeTemplatePreview()"
                                class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                                <i class="fas fa-times text-gray-600"></i>
                            </button>
                        </div>
                        <div class="overflow-auto flex-1 bg-gray-50 flex items-start justify-center p-4">
                            <img id="tmpl-preview-img" src="" alt="" class="w-full rounded-lg shadow-sm object-contain">
                        </div>
                        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between shrink-0">
                            <p class="text-sm text-gray-500">{{ __('Full-width template preview') }}</p>
                            <a href="{{ route('website.register') }}" class="btn-primary px-6 py-2.5 text-sm">
                                {{ __('Use This Template') }} <i class="fas fa-arrow-right ms-1.5 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <script>
                    function openTemplatePreview(name, src) {
                        document.getElementById('tmpl-preview-name').textContent = name;
                        document.getElementById('tmpl-preview-img').src = src;
                        document.getElementById('tmpl-preview-img').alt = name;
                        var modal = document.getElementById('tmpl-preview-modal');
                        modal.style.removeProperty('display');
                        modal.style.display = 'flex';
                        document.body.style.overflow = 'hidden';
                    }
                    function closeTemplatePreview() {
                        document.getElementById('tmpl-preview-modal').style.display = 'none';
                        document.body.style.overflow = '';
                    }
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') closeTemplatePreview();
                    });
                </script>
            @else
                <div class="rounded-3xl border border-dashed border-gray-200 bg-gray-50 px-8 py-16 text-center">
                    <h3 class="text-xl font-extrabold text-gray-900 mb-2">{{ __('No templates available yet') }}</h3>
                    <p class="text-gray-500">{{ __('Templates will appear here once they are activated from the admin panel.') }}</p>
                </div>
            @endif
        </div>
    </section>

    {{-- Categories --}}
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-extrabold text-gray-900 section-title">{{ __('Categories') }}</h2>
                <p class="text-gray-500 mt-4 max-w-2xl mx-auto">{{ __('Thousands of ready-made products across multiple categories to help you launch and sell with ease.') }}</p>
            </div>

            @if($categories->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                    @php
                        $catIcons = ['fa-tshirt', 'fa-palette', 'fa-heartbeat', 'fa-chair', 'fa-shopping-cart', 'fa-laptop', 'fa-paw', 'fa-child'];
                    @endphp
                    @foreach($categories as $index => $category)
                        <div class="cat-card bg-white rounded-2xl p-6 text-center cursor-pointer">
                            <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                @if($category->thumb_url)
                                    <img src="{{ $category->thumb_url }}" alt="{{ $category->name }}" class="w-10 h-10 object-contain">
                                @else
                                    <i class="fas {{ $catIcons[$index % count($catIcons)] }} text-2xl text-primary"></i>
                                @endif
                            </div>
                            <h5 class="font-semibold text-gray-800">{{ $category->name }}</h5>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach([
                            ['icon' => 'fa-tshirt', 'name' => 'Fashion'],
                            ['icon' => 'fa-palette', 'name' => 'Beauty'],
                            ['icon' => 'fa-heartbeat', 'name' => 'Fitness'],
                            ['icon' => 'fa-chair', 'name' => 'Furniture'],
                            ['icon' => 'fa-shopping-cart', 'name' => 'Grocery'],
                            ['icon' => 'fa-laptop', 'name' => 'Electronics'],
                            ['icon' => 'fa-paw', 'name' => 'Pets'],
                            ['icon' => 'fa-child', 'name' => 'Kids'],
                        ] as $cat)
                            <div class="cat-card bg-white rounded-2xl p-6 text-center cursor-pointer">
                                <div class="w-16 h-16 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fas {{ $cat['icon'] }} text-2xl text-primary"></i>
                                </div>
                                <h5 class="font-semibold text-gray-800">{{ __($cat['name']) }}</h5>
                            </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Services / How It Works --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-14 items-center">
                {{-- Image --}}
                <div class="hidden lg:flex justify-center">
                    <img src="{{ asset('central-website/assets') }}/image/services-imgrtl.png"
                        alt="{{ __('Services') }}" class="w-full max-w-md rounded-2xl">
                </div>
                {{-- Service items --}}
                <div>
                    @foreach([
                            ['icon' => 'fa-tools', 'title' => 'Template Customization', 'desc' => 'Tailored designs that fit your brand.'],
                            ['icon' => 'fa-rocket', 'title' => 'Store Setup', 'desc' => 'From setup to launch, we\'ve got you covered.'],
                            ['icon' => 'fa-cog', 'title' => 'Optimization', 'desc' => 'Better performance, better results.'],
                            ['icon' => 'fa-headphones', 'title' => 'Expert Support', 'desc' => 'Guidance whenever you need it.'],
                        ] as $index => $service)
                            <div class="flex items-start gap-5 mb-8">
                                <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                                    <i class="fas {{ $service['icon'] }} text-xl text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 mb-1">{{ __($service['title']) }}</h4>
                                    <p class="text-gray-500 text-sm">{{ __($service['desc']) }}</p>
                                </div>
                            </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section class="py-20 bg-linear-to-br from-[#FF4B2B] to-[#FF622E] relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-80 h-80 bg-white rounded-full translate-x-1/3 translate-y-1/3"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-14">
                <span class="inline-block bg-white/20 text-white text-sm font-semibold px-4 py-1.5 rounded-full mb-4">{{ __('Pricing Plan') }}</span>
                <h2 class="text-3xl font-extrabold text-white">{{ __('Choose the Perfect Plan for Your Business') }}</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 max-w-5xl mx-auto">
                {{-- Free plan --}}
                <article class="bg-white rounded-3xl p-8 flex flex-col shadow-xl">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-wrench text-primary text-lg"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">{{ __('Basic') }}</h3>
                    </div>
                    <div class="mb-6">
                        <span class="text-5xl font-extrabold text-gray-900">{{ __('Free') }}</span>
                        <span class="text-gray-400 text-sm ms-1">/ {{ __('monthly') }}</span>
                    </div>
                    <h5 class="font-semibold text-gray-700 mb-3">{{ __("What's Included") }}</h5>
                    <hr class="mb-4">
                    <ul class="space-y-3 flex-1 text-sm text-gray-600 mb-8">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-primary w-4"></i>{{ __('2 Categories') }}</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-primary w-4"></i>{{ __('10 Products') }}</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-primary w-4"></i>{{ __('100 Orders') }}</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-primary w-4"></i>{{ __('Custom Domain') }}</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-primary w-4"></i>{{ __('QR Builder') }}</li>
                        <li class="flex items-center gap-2 text-gray-400"><i class="fas fa-times w-4"></i>{{ __('Blog') }}</li>
                        <li class="flex items-center gap-2 text-gray-400"><i class="fas fa-times w-4"></i>{{ __('Google Analytics') }}</li>
                    </ul>
                    <a href="{{ route('website.register') }}"
                        class="btn-outline block text-center py-3 font-semibold rounded-xl">{{ __('Signup') }}</a>
                </article>

                @foreach($packages->take(2) as $package)
                    <article class="bg-white rounded-3xl p-8 flex flex-col {{ $loop->first ? 'ring-2 ring-primary shadow-2xl scale-[1.02]' : 'shadow-xl' }}">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                <i class="fas {{ $loop->first ? 'fa-chart-line' : 'fa-crown' }} text-primary text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ $package->name }}</h3>
                                @if($loop->first)
                                    <span class="text-xs bg-primary text-white px-2 py-0.5 rounded-full font-semibold">{{ __('Popular') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-6">
                            <span class="text-5xl font-extrabold text-gray-900">${{ number_format((float) $package->price, 0) }}</span>
                            <span class="text-gray-400 text-sm ms-1">/ {{ __($package->term->value) }}</span>
                        </div>
                        <h5 class="font-semibold text-gray-700 mb-3">{{ __("What's Included") }}</h5>
                        <hr class="mb-4">
                        <p class="text-sm text-gray-500 leading-relaxed mb-6 flex-1">
                            {{ $package->description ?: __('Published package managed from the central admin dashboard.') }}
                        </p>
                        <a href="{{ route('website.register', ['packageId' => $package->id]) }}"
                            class="btn-primary block text-center py-3 font-semibold rounded-xl">
                            {{ __('Purchase') }}
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-orange-50 rounded-3xl p-10 lg:p-16 grid lg:grid-cols-2 gap-10 items-center">
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-900 mb-4">{{ __('Ready to Build Your Store?') }}</h2>
                    <p class="text-gray-500 mb-8 max-w-lg">{{ __('Create your store, choose a package, and start selling today. It only takes a few minutes to get started.') }}</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('website.register') }}"  class="btn-primary px-8 py-3.5 text-base">{{ __('Start Free') }}</a>
                        <a href="{{ route('website.contact') }}"  class="btn-outline px-8 py-3.5 text-base">{{ __('Contact Sales') }}</a>
                    </div>
                </div>
                <div class="hidden lg:flex justify-center">
                    <img src="{{ asset('central-website/assets') }}/image/c5b31ec6dd67c7260142577196b62dc79423b3da.png"
                        alt="{{ __('Get Started') }}" class="w-full max-w-sm">
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-extrabold text-gray-900 section-title">{{ __('Frequently Asked Questions') }}</h2>
            </div>

            @if($faqs->isNotEmpty())
                <div class="space-y-4">
                    @foreach($faqs as $faq)
                        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                            <button type="button" onclick="this.parentElement.querySelector('.faq-body').classList.toggle('open'); this.querySelector('.faq-chevron').classList.toggle('rotate-180')"
                                class="w-full flex items-center justify-between px-6 py-5 text-left font-semibold text-gray-900 hover:bg-orange-50/50 transition-colors">
                                <span>{{ $faq->question }}</span>
                                <i class="fas fa-chevron-down text-primary text-sm faq-chevron transition-transform duration-300"></i>
                            </button>
                            <div class="faq-body px-6 text-gray-500 text-sm leading-relaxed">
                                {!! $faq->answer !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="space-y-4">
                    @foreach([
                            ['q' => 'How do I create my online store?', 'a' => 'Simply sign up, choose a template, and start adding your products. Your store will be live in minutes.'],
                            ['q' => 'Can I use my own domain name?', 'a' => 'Yes! You can connect your custom domain or use a free subdomain provided by our platform.'],
                            ['q' => 'What payment methods are supported?', 'a' => 'We support a variety of payment gateways including credit cards, bank transfers, and popular digital wallets.'],
                            ['q' => 'Is there a free plan available?', 'a' => 'Yes, our Basic plan is completely free with essential features to get you started.'],
                        ] as $item)
                            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                                <button type="button" onclick="this.parentElement.querySelector('.faq-body').classList.toggle('open'); this.querySelector('.faq-chevron').classList.toggle('rotate-180')"
                                    class="w-full flex items-center justify-between px-6 py-5 text-left font-semibold text-gray-900 hover:bg-orange-50/50 transition-colors">
                                    <span>{{ __($item['q']) }}</span>
                                    <i class="fas fa-chevron-down text-primary text-sm faq-chevron transition-transform duration-300"></i>
                                </button>
                                <div class="faq-body px-6 text-gray-500 text-sm leading-relaxed">
                                    {{ __($item['a']) }}
                                </div>
                            </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Newsletter --}}
    <section class="py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-reveal>
            <h2 class="text-3xl font-extrabold text-gray-900 section-title mb-4">{{ __('Subscribe to our newsletter') }}</h2>
            <p class="text-gray-500 mb-8">{{ __('Stay updated with the latest features, templates, and tips for your store.') }}</p>

            @if($newsletterSent)
                <div class="max-w-lg mx-auto rounded-2xl border border-green-200 bg-green-50 px-6 py-5 flex items-center gap-4">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center shrink-0">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-semibold text-green-800 text-sm">{{ __("You're subscribed!") }}</p>
                        <p class="text-green-700 text-xs mt-0.5">{{ __('Thanks for subscribing. We\'ll keep you updated.') }}</p>
                    </div>
                </div>
            @else
                <form wire:submit.prevent="subscribeNewsletter" class="flex flex-col sm:flex-row gap-3 max-w-lg mx-auto">
                    <div class="flex-1">
                        <input wire:model="newsletterEmail" type="email" placeholder="{{ __('Enter your email') }}"
                            class="w-full px-5 py-3.5 rounded-full border {{ $errors->has('newsletterEmail') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm">
                        @error('newsletterEmail')
                            <p class="text-red-500 text-xs mt-1.5 text-left ps-4">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="subscribeNewsletter"
                        class="btn-primary px-8 py-3.5 text-sm shrink-0">
                        <span wire:loading.remove wire:target="subscribeNewsletter">{{ __('Subscribe') }}</span>
                        <span wire:loading wire:target="subscribeNewsletter">{{ __('Subscribing…') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </section>
</div>
