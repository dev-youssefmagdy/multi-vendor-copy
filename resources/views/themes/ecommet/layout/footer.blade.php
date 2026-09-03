<footer class="mt-auto relative z-10 w-full font-main text-[#d5d5d5] bg-[#333333] pt-8 md:bg-[#434343]">
    <div class="max-w-[1280px] mx-auto py-0 md:py-12 px-5 sm:px-8">
        <div
            class="grid grid-cols-1 md:grid-cols-[1.4fr_1.2fr_1.2fr_1fr] lg:grid-cols-[1.4fr_1.2fr_1fr_1fr] gap-6 md:gap-8 border-b md:border-none border-[#666] pb-6 md:pb-0">

            {{-- Company info --}}
            <div class="flex flex-col gap-3.5">
                <h3 class="font-bold text-[14px] text-white tracking-wide mb-1">{{ __('Company info') }}</h3>
                <a href="{{ route('tenant.storefront.page', 'about-us') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('About :store', ['store' => $storeName]) }}</a>
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Browse products') }}</a>
                <a href="{{ route('tenant.storefront.cart') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Cart & checkout') }}</a>
                @guest('storefront')
                    <a href="{{ route('tenant.storefront.login') }}"
                        class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Sign in / Register') }}</a>
                @endguest
                @auth('storefront')
                    <a href="{{ route('tenant.storefront.profile') }}"
                        class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('My Account') }}</a>
                @endauth
            </div>

            {{-- Customer service --}}
            <div class="flex flex-col gap-3.5">
                <h3 class="font-bold text-[14px] text-white tracking-wide mb-1">{{ __('Customer service') }}</h3>
                <a href="{{ route('tenant.storefront.cart') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Cart & checkout') }}</a>
                @auth('storefront')
                    <a href="{{ route('tenant.storefront.profile') }}"
                        class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Order tracking') }}</a>
                @else
                    <a href="{{ route('tenant.storefront.login') }}"
                        class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Order tracking') }}</a>
                @endauth
                <a href="{{ route('tenant.storefront.checkout') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Checkout') }}</a>
            </div>

            {{-- Help / Explore --}}
            <div class="flex flex-col gap-3.5">
                <h3 class="font-bold text-[14px] text-white tracking-wide mb-1">{{ __('Explore') }}</h3>
                <a href="{{ route('tenant.storefront.best-selling') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('Best-Selling') }}</a>
                <a href="{{ route('tenant.storefront.full-star') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('5-Star Rated') }}</a>
                <a href="{{ route('tenant.storefront.new-in') }}"
                    class="text-[13px] font-medium hover:text-white transition w-fit">{{ __('New Arrivals') }}</a>
                @if ($rootCategories->isNotEmpty())
                    @foreach ($rootCategories->take(3) as $cat)
                        <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                            class="text-[13px] font-medium hover:text-white transition w-fit">
                            {{ $cat->translationValue('name') ?? $cat->slug }}
                        </a>
                    @endforeach
                @endif
            </div>

            {{-- Logo + social --}}
            <div class="flex flex-col gap-3 mt-4 md:mt-[32px]">
                <a href="{{ route('tenant.home') }}" class="hidden md:flex items-center mb-2">
                    <x-storefront-logo :storeName="$storeName" class="h-[30px] max-w-[120px] object-contain" />
                </a>

                @if ($footerText)
                    <p class="text-[12px] text-[#aaa] leading-relaxed">{{ $footerText }}</p>
                @endif

                @if ($socialLinks->isNotEmpty())
                    <span class="text-white text-[13px] font-bold tracking-wide">{{ __('Connect with us') }}</span>
                    <div class="flex items-center gap-4 text-white mt-0.5">
                        @foreach ($socialLinks as $link)
                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                                aria-label="{{ ucfirst($link->icon?->name ?? 'social') }}"
                                class="hover:opacity-80 transition">
                                @switch($link->icon?->name)
                                    @case('facebook')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                                        </svg>
                                        @break
                                    @case('twitter')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z" />
                                        </svg>
                                        @break
                                    @case('instagram')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect x="2" y="2" width="20" height="20" rx="5" />
                                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                            <circle cx="17.5" cy="6.5" r="0.5" fill="currentColor" stroke="none" />
                                        </svg>
                                        @break
                                    @case('youtube')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42C1 8.14 1 11.75 1 11.75s0 3.61.46 5.33a2.78 2.78 0 0 0 1.95 1.96C5.12 19.5 12 19.5 12 19.5s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.96-1.96c.46-1.72.46-5.33.46-5.33s0-3.61-.47-5.33z" />
                                            <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="#333333" />
                                        </svg>
                                        @break
                                    @case('linkedin')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z" />
                                            <rect x="2" y="9" width="4" height="12" />
                                            <circle cx="4" cy="4" r="2" />
                                        </svg>
                                        @break
                                    @case('whatsapp')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.148-.174.198-.298.297-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                            <path
                                                d="M12.001 2C6.478 2 2 6.477 2 12c0 1.987.577 3.84 1.573 5.4L2 22l4.735-1.532A9.94 9.94 0 0 0 12.001 22C17.523 22 22 17.523 22 12S17.523 2 12.001 2zm0 18.077a8.03 8.03 0 0 1-4.31-1.25l-.31-.184-3.19 1.032 1.05-3.098-.202-.32A8.02 8.02 0 0 1 3.923 12c0-4.454 3.624-8.077 8.078-8.077 4.454 0 8.077 3.623 8.077 8.077 0 4.454-3.623 8.077-8.077 8.077z" />
                                        </svg>
                                        @break
                                    @default
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M10 6v2H5v11h11v-5h2v6a1 1 0 01-1 1H4a1 1 0 01-1-1V7a1 1 0 011-1h6zm11-3v8h-2V6.413l-7.293 7.294-1.414-1.414L17.585 5H13V3h8z" />
                                        </svg>
                                @endswitch
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Static pages --}}
    @php
        $footerLinkedPageSlugs = ['about-us'];
        $remainingStaticPages = ($staticPages ?? collect())->reject(fn($page) => in_array($page->slug, $footerLinkedPageSlugs, true));
    @endphp
    @if ($remainingStaticPages->isNotEmpty())
        <div class="max-w-[1280px] mx-auto px-5 sm:px-8 pb-6 border-t border-[#666] pt-6">
            <h4 class="font-bold text-[14px] text-white tracking-wide mb-3">{{ __('Pages') }}</h4>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach ($remainingStaticPages as $page)
                    @php $pageTitle = $page->translationValue('title');
                    $pageSlug = $page->slug; @endphp
                    @if ($pageTitle && $pageSlug)
                        <a href="{{ route('tenant.storefront.page', $pageSlug) }}"
                            class="text-[13px] font-medium hover:text-white transition w-fit">{{ $pageTitle }}</a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Bottom nav bar (desktop) --}}
    <div class="bg-[#5E5E5E] text-[14px] text-[#000000] font-medium border-t border-[#666] hidden md:block">
        <div
            class="max-w-[1280px] mx-auto py-5 px-5 flex flex-col md:flex-row items-center justify-center md:gap-[80px] gap-4">
            <a href="{{ route('tenant.home') }}" class="hover:text-white transition w-fit">{{ __('Home') }}</a>
            <a href="{{ route('tenant.storefront.best-selling') }}"
                class="hover:text-white transition w-fit">{{ __('Best Sellers') }}</a>
            <a href="{{ route('tenant.storefront.new-in') }}"
                class="hover:text-white transition w-fit">{{ __('New Arrivals') }}</a>
            <a href="{{ route('tenant.storefront.cart') }}"
                class="hover:text-white transition w-fit">{{ __('Cart') }}</a>
            @if ($rootCategories->isNotEmpty())
                <a href="{{ route('tenant.storefront.category', $rootCategories->first()->slug) }}"
                    class="hover:text-white transition w-fit">{{ __('Shop') }}</a>
            @endif
        </div>
    </div>

    {{-- Copyright --}}
    <div
        class="bg-transparent md:bg-[#242424] text-center pt-5 pb-5 md:py-5 text-[11px] md:text-[14px] text-[#A3A3A3] md:text-[#FFFFFF] md:border-t border-[#333] mb-[64px] md:mb-0">
        {{ $footerCopyright ?: ('© ' . date('Y') . ' ' . $storeName . '. ' . __('All Rights Reserved.')) }}
    </div>
</footer>
