@php
    use App\Models\StaticPage;
    use App\Enums\ContentStatus;
    use App\Repositories\AppSettingRepository;
    $currentLocale = app()->getLocale();
    $isRtl = $currentLocale === 'ar';
    $currentRoute = request()->route()?->getName() ?? '';
    $staticPages = StaticPage::with('translations.language')
        ->where('status', ContentStatus::Active)
        ->get();
    $headerLogo = app(AppSettingRepository::class)->getByKeys(['header_logo'])->get('header_logo')?->value
        ?: asset('central-website/assets/image/4ad6eed1943b13bf773152f09ecf0d9ac498c5d3.png');
@endphp
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 shadow-sm" style="backdrop-filter:blur(10px)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="{{ route('website.home') }}" class="flex items-center gap-2 shrink-0">
                <img src="{{ $headerLogo }}" alt="Ecommet" class="h-8 w-auto">
            </a>
            <!-- Desktop links -->
            <div class="hidden lg:flex items-center gap-7">
                <a href="{{ route('website.home') }}"
                    class="text-sm font-medium transition-colors {{ $currentRoute === 'website.home' ? 'text-primary' : 'text-gray-600 hover:text-primary' }}">
                    {{ __('Home') }}
                </a>
                <a href="{{ route('website.pricing') }}"
                    class="text-sm font-medium transition-colors {{ $currentRoute === 'website.pricing' ? 'text-primary' : 'text-gray-600 hover:text-primary' }}">
                    {{ __('Pricing') }}
                </a>
                <a href="{{ route('website.how-it-works') }}"
                    class="text-sm font-medium transition-colors {{ $currentRoute === 'website.how-it-works' ? 'text-primary' : 'text-gray-600 hover:text-primary' }}">
                    {{ __('How It Works') }}
                </a>
                <a href="{{ route('website.contact') }}"
                    class="text-sm font-medium transition-colors {{ $currentRoute === 'website.contact' ? 'text-primary' : 'text-gray-600 hover:text-primary' }}">
                    {{ __('Contact') }}
                </a>
                <div class="nav-dropdown-wrap relative">
                    <button type="button"
                        class="text-sm font-medium text-gray-600 hover:text-primary transition-colors flex items-center gap-1 focus:outline-none"
                        aria-haspopup="true" aria-expanded="false">
                        {{ __('More') }} <i class="fas fa-chevron-down text-xs ml-1"></i>
                    </button>
                    <div class="nav-dropdown js-dropdown" style="display:none;">
                        <a href="{{ route('website.about') }}">{{ __('About Us') }}</a>
                        <a href="{{ route('website.terms') }}">{{ __('Terms & Conditions') }}</a>
                        <a href="{{ route('website.faqs') }}">{{ __('FAQs') }}</a>
                        <a href="{{ route('website.blog') }}">{{ __('Blog') }}</a>
                        @foreach ($staticPages as $page)
                            @php $pageTitle = $page->t('title');
                            $pageSlug = $page->t('slug'); @endphp
                            @if ($pageTitle && $pageSlug)
                                <a href="{{ route('website.page', $pageSlug) }}">{{ $pageTitle }}</a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- CTA + Language Switcher + hamburger -->
            <div class="flex items-center gap-3">
                {{-- Language Switcher --}}
                <div
                    class="hidden sm:flex items-center gap-1 border border-gray-200 rounded-full px-1 py-0.5 text-xs font-semibold">
                    <a href="{{ route('locale.switch', 'en') }}"
                        class="px-2.5 py-1 rounded-full transition-colors {{ $currentLocale === 'en' ? 'bg-primary text-white' : 'text-gray-500 hover:text-primary' }}">
                        EN
                    </a>
                    <a href="{{ route('locale.switch', 'ar') }}"
                        class="px-2.5 py-1 rounded-full transition-colors {{ $currentLocale === 'ar' ? 'bg-primary text-white' : 'text-gray-500 hover:text-primary' }}">
                        AR
                    </a>
                </div>
                <a href="{{ route('website.register') }}"
                    class="btn-primary px-5 py-2 text-sm">{{ __('Register Now') }}</a>
                <a href="{{ route('owner.login') }}"
                    class="hidden sm:inline-flex items-center gap-1.5 border border-gray-200 rounded-full px-4 py-2 text-sm font-medium text-gray-700 hover:border-primary hover:text-primary transition-colors">
                    <i class="fas fa-sign-in-alt text-xs"></i>{{ __('Store Login') }}
                </a>
                <button id="menuBtn" class="lg:hidden text-gray-600 hover:text-primary p-2" aria-label="Menu">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden lg:hidden pb-4 border-t border-gray-100 mt-1 max-h-[calc(100vh-80px)] overflow-y-auto">
            <div class="flex flex-col gap-1 pt-3">
                <a href="{{ route('website.home') }}"
                    class="block px-3 py-2 text-sm font-medium rounded-lg {{ $currentRoute === 'website.home' ? 'text-primary' : 'text-gray-600 hover:bg-orange-50 hover:text-primary' }}">
                    {{ __('Home') }}
                </a>
                <a href="{{ route('website.pricing') }}"
                    class="block px-3 py-2 text-sm font-medium rounded-lg {{ $currentRoute === 'website.pricing' ? 'text-primary' : 'text-gray-600 hover:bg-orange-50 hover:text-primary' }}">
                    {{ __('Pricing') }}
                </a>
                <a href="{{ route('website.how-it-works') }}"
                    class="block px-3 py-2 text-sm font-medium rounded-lg {{ $currentRoute === 'website.how-it-works' ? 'text-primary' : 'text-gray-600 hover:bg-orange-50 hover:text-primary' }}">
                    {{ __('How It Works') }}
                </a>
                <a href="{{ route('website.contact') }}"
                    class="block px-3 py-2 text-sm font-medium rounded-lg {{ $currentRoute === 'website.contact' ? 'text-primary' : 'text-gray-600 hover:bg-orange-50 hover:text-primary' }}">
                    {{ __('Contact') }}
                </a>
                <a href="{{ route('website.about') }}"
                    class="block px-3 py-2 text-sm font-medium text-gray-600 hover:bg-orange-50 hover:text-primary rounded-lg">
                    {{ __('About Us') }}
                </a>
                <a href="{{ route('website.faqs') }}"
                    class="block px-3 py-2 text-sm font-medium text-gray-600 hover:bg-orange-50 hover:text-primary rounded-lg">
                    {{ __('FAQs') }}
                </a>
                <a href="{{ route('website.blog') }}"
                    class="block px-3 py-2 text-sm font-medium text-gray-600 hover:bg-orange-50 hover:text-primary rounded-lg">
                    {{ __('Blog') }}
                </a>
                @foreach ($staticPages as $page)
                    @php $pageTitle = $page->t('title');
                    $pageSlug = $page->t('slug'); @endphp
                    @if ($pageTitle && $pageSlug)
                        <a href="{{ route('website.page', $pageSlug) }}"
                            class="block px-3 py-2 text-sm font-medium text-gray-600 hover:bg-orange-50 hover:text-primary rounded-lg">
                            {{ $pageTitle }}
                        </a>
                    @endif
                @endforeach
                <a href="{{ route('owner.login') }}"
                    class="block px-3 py-2 text-sm font-medium text-gray-600 hover:bg-orange-50 hover:text-primary rounded-lg">
                    {{ __('Store Login') }}
                </a>
                {{-- Mobile Language Switcher --}}
                <div class="flex items-center gap-2 px-3 py-2">
                    <span class="text-xs text-gray-400 font-medium">{{ __('Language') }}:</span>
                    <a href="{{ route('locale.switch', 'en') }}"
                        class="text-xs font-bold px-2.5 py-1 rounded-full {{ $currentLocale === 'en' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600' }}">EN</a>
                    <a href="{{ route('locale.switch', 'ar') }}"
                        class="text-xs font-bold px-2.5 py-1 rounded-full {{ $currentLocale === 'ar' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600' }}">AR</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.nav-dropdown-wrap').forEach(function (wrap) {
                var btn = wrap.querySelector('button');
                var menu = wrap.querySelector('.nav-dropdown');
                if (!btn || !menu) return;
                // ensure hidden by default (override hover rules if present)
                menu.style.display = 'none';
                btn.setAttribute('aria-expanded', 'false');
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var isOpen = menu.style.display !== 'none';
                    // close other dropdowns
                    document.querySelectorAll('.nav-dropdown').forEach(function (m) {
                        if (m !== menu) {
                            m.style.display = 'none';
                            var parent = m.closest('.nav-dropdown-wrap');
                            if (parent) {
                                var otherBtn = parent.querySelector('button');
                                if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                    if (isOpen) {
                        menu.style.display = 'none';
                        btn.setAttribute('aria-expanded', 'false');
                    } else {
                        menu.style.display = 'block';
                        btn.setAttribute('aria-expanded', 'true');
                    }
                });
                // close when clicking outside
                document.addEventListener('click', function (e) {
                    if (!wrap.contains(e.target)) {
                        menu.style.display = 'none';
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
                // close with Escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        menu.style.display = 'none';
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        });
    </script>
</nav>
