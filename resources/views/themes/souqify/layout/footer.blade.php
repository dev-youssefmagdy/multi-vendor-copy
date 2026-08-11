@php
$shopCategories = ($categories ?? collect())->take(6);
@endphp

<!-- =========== FOOTER =========== -->
<footer class="hidden lg:block bg-slate-950 text-white pt-12">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12">
            <!-- Brand col -->
            <div class="lg:col-span-4">
                <x-storefront-logo :storeName="$storeName" class="h-10 w-auto mb-3" />
                <p class="text-neutral-400 text-sm leading-6 mb-6">
                    {{ $footerText ?? __('Elevating the everyday through curated premium experiences and cutting-edge technology.') }}
                </p>
                <p class="text-base font-medium mb-3">{{ __('Connect with') }} {{ $storeName }}</p>
                <div class="flex items-center gap-4 mb-6 flex-wrap">
                    @if (isset($socialLinks) && $socialLinks->isNotEmpty())
                    @foreach ($socialLinks as $link)
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                        aria-label="{{ $link->icon?->name ? ucfirst($link->icon->name) : __('social') }}"
                        class="w-9 h-9 rounded-full bg-white/10 hover:bg-blue-700 transition flex items-center justify-center">
                        @switch($link->icon?->name)
                        @case('facebook')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                        @break
                        @case('twitter')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723 9.93 9.93 0 01-3.127 1.184 4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.937 4.937 0 004.604 3.417 9.868 9.868 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.054 0 13.999-7.496 13.999-13.986 0-.209 0-.42-.015-.63A9.936 9.936 0 0024 4.59z" />
                        </svg>
                        @break
                        @case('instagram')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z" />
                        </svg>
                        @break
                        @case('youtube')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                        </svg>
                        @break
                        @case('linkedin')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.063 2.063 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                        </svg>
                        @break
                        @case('whatsapp')
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.148-.174.198-.298.297-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                            <path
                                d="M12.001 2C6.478 2 2 6.477 2 12c0 1.987.577 3.84 1.573 5.4L2 22l4.735-1.532A9.94 9.94 0 0 0 12.001 22C17.523 22 22 17.523 22 12S17.523 2 12.001 2zm0 18.077a8.03 8.03 0 0 1-4.31-1.25l-.31-.184-3.19 1.032 1.05-3.098-.202-.32A8.02 8.02 0 0 1 3.923 12c0-4.454 3.624-8.077 8.078-8.077 4.454 0 8.077 3.623 8.077 8.077 0 4.454-3.623 8.077-8.077 8.077z" />
                        </svg>
                        @break
                        @endswitch
                    </a>
                    @endforeach
                    @endif
                </div>
            </div>
            <!-- Shop col -->
            <div class="lg:col-span-2">
                <h4 class="text-xl font-medium mb-4">{{ __('Shop') }}</h4>
                <ul class="space-y-2 text-neutral-400">
                    @forelse ($shopCategories as $cat)
                    <li>
                        <a href="{{ route('tenant.storefront.category', $cat->slug) }}"
                            class="hover:text-white transition">{{ $cat->name }}</a>
                    </li>
                    @empty
                    <li>
                        <a href="{{ route('tenant.storefront.best-selling') }}"
                            class="hover:text-white transition">{{ __('All Products') }}</a>
                    </li>
                    @endforelse
                </ul>
            </div>
            <!-- Company col -->
            <div class="lg:col-span-2">
                <h4 class="text-xl font-medium mb-4">{{ __('Company') }}</h4>
                <ul class="space-y-2 text-neutral-400">
                    <li><a href="{{ route('tenant.storefront.page', 'about-us') }}"
                            class="hover:text-white transition">{{ __('About Us') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'help-center') }}"
                            class="hover:text-white transition">{{ __('Help Center') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'contact-us') }}"
                            class="hover:text-white transition">{{ __('Contact us') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'faqs') }}"
                            class="hover:text-white transition">{{ __('FAQs') }}</a></li>
                </ul>
            </div>
            <!-- Support col -->
            <div class="lg:col-span-2">
                <h4 class="text-xl font-medium mb-4">{{ __('Support') }}</h4>
                <ul class="space-y-2 text-neutral-400">
                    <li><a href="{{ route('tenant.storefront.page', 'privacy-policy') }}"
                            class="hover:text-white transition">{{ __('Privacy policy') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'terms-of-use') }}"
                            class="hover:text-white transition">{{ __('Terms of use') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'shipping-info') }}"
                            class="hover:text-white transition">{{ __('Shipping info') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'return-refund-policy') }}"
                            class="hover:text-white transition">{{ __('Returns') }}</a></li>
                </ul>
            </div>
            <!-- Payment col -->
            <div class="lg:col-span-2">
                <h4 class="text-base font-semibold mb-3">{{ __('We accept') }}</h4>
                <!-- Payment chips -->
                <div class="lg:flex items-center gap-1   flex-wrap hidden">
                    <img loading="lazy" src="{{ asset('souqify/assets/images/pay-visa.svg') }}" alt="" class="h-7">
                    <img loading="lazy" src="{{ asset('souqify/assets/images/pay-tabby.svg') }}" alt="">
                    <img loading="lazy" src="{{ asset('souqify/assets/images/pay-tamara.svg') }}" alt="">
                </div>
            </div>
        </div>
    </div>
    <!-- Static pages -->
    @php
        $footerLinkedPageSlugs = ['about-us', 'help-center', 'contact-us', 'faqs', 'privacy-policy', 'terms-of-use', 'shipping-info', 'return-refund-policy', 'privacy-choices', 'support'];
        $remainingStaticPages = ($staticPages ?? collect())->reject(fn($page) => in_array($page->slug, $footerLinkedPageSlugs, true));
    @endphp
    @if ($remainingStaticPages->isNotEmpty())
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-8 border-t border-white/10 pt-6">
        <h4 class="text-xl font-medium mb-4 text-white">{{ __('Pages') }}</h4>
        <ul class="flex flex-wrap gap-x-6 gap-y-2 text-neutral-400">
            @foreach ($remainingStaticPages as $page)
                @php $pageTitle = $page->translationValue('title'); $pageSlug = $page->slug; @endphp
                @if ($pageTitle && $pageSlug)
                    <li>
                        <a href="{{ route('tenant.storefront.page', $pageSlug) }}"
                            class="hover:text-white transition">{{ $pageTitle }}</a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Sub footer -->
    <div class="bg-blue-700">
        <div
            class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-center gap-4 sm:gap-12">
            <a href="{{ route('tenant.storefront.page', 'terms-of-use') }}"
                class="text-white text-sm hover:underline">{{ __('Terms of use') }}</a>
            <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}"
                class="text-white text-sm hover:underline">{{ __('Privacy policy') }}</a>
            <a href="{{ route('tenant.storefront.page', 'privacy-choices') }}"
                class="text-white text-sm hover:underline">{{ __('Your privacy choices') }}</a>
            <a href="{{ route('tenant.storefront.page', 'support') }}"
                class="text-white text-sm hover:underline">{{ __('Support') }}</a>
            <a href="{{ route('tenant.storefront.page', 'faqs') }}"
                class="text-white text-sm hover:underline">{{ __('FAQ') }}</a>
        </div>
    </div>
    <!-- Copyright -->
    <div class="bg-slate-950">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-5 text-center">
            <p class="text-white text-sm">
                {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . ($storeName ?? 'Souqify') . '. ' . __('All Rights Reserved.')) }}
            </p>
        </div>
    </div>
</footer>
<div class="mt-20 lg:hidden"></div>
