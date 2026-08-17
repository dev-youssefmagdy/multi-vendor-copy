{{-- Header/Footer partial for elora theme --}}
{{-- Edit this file directly to change header/footer markup --}}
{{-- Tenant-specific data via tenant() helper only --}}
<!-- ===== FOOTER ===== -->
<footer class="text-white" style="background:#111827">

    {{-- ── Main grid ─────────────────────────────────────────────────────────── --}}
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 py-10 sm:py-14">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 sm:gap-10">

            {{-- Col 1 : Logo + Social + Payments --}}
            <div class="col-span-2 lg:col-span-1 items-center">

                {{-- Logo --}}
                <x-storefront-logo :storeName="$storeName" class="h-9 sm:h-11 w-auto mb-5 mx-auto sm:mx-0" />

                {{-- Connect with ELORA --}}
                <p class="hidden sm:block text-sm font-semibold text-white mb-3">{{ __('Connect with ELORA') }}</p>

                {{-- Social icons: rendered from Store → Appearance → Social Links --}}
                @if (isset($socialLinks) && $socialLinks->isNotEmpty())
                    <div class="flex items-center justify-center sm:justify-start gap-2 mb-6 flex-wrap">
                        @foreach ($socialLinks as $link)
                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                                aria-label="{{ ucfirst($link->icon?->name ?? 'social') }}"
                                class="w-9 h-9 rounded-full flex items-center justify-center text-white hover:opacity-80 transition-opacity flex-shrink-0"
                                style="background:#4D4D4D">
                                @switch($link->icon?->name)
                                    @case('facebook')
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                                        </svg>
                                        @break
                                    @case('twitter')
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z" />
                                        </svg>
                                        @break
                                    @case('instagram')
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="2" y="2" width="20" height="20" rx="5" />
                                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                            <circle cx="17.5" cy="6.5" r="0.5" fill="currentColor" stroke="none" />
                                        </svg>
                                        @break
                                    @case('youtube')
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42C1 8.14 1 11.75 1 11.75s0 3.61.46 5.33a2.78 2.78 0 0 0 1.95 1.96C5.12 19.5 12 19.5 12 19.5s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.96-1.96c.46-1.72.46-5.33.46-5.33s0-3.61-.47-5.33z" />
                                            <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="#3B3B3B" />
                                        </svg>
                                        @break
                                    @case('linkedin')
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z" />
                                            <rect x="2" y="9" width="4" height="12" />
                                            <circle cx="4" cy="4" r="2" />
                                        </svg>
                                        @break
                                    @case('whatsapp')
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.148-.174.198-.298.297-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.626.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                                            <path
                                                d="M12.001 2C6.478 2 2 6.477 2 12c0 1.987.577 3.84 1.573 5.4L2 22l4.735-1.532A9.94 9.94 0 0 0 12.001 22C17.523 22 22 17.523 22 12S17.523 2 12.001 2zm0 18.077a8.03 8.03 0 0 1-4.31-1.25l-.31-.184-3.19 1.032 1.05-3.098-.202-.32A8.02 8.02 0 0 1 3.923 12c0-4.454 3.624-8.077 8.078-8.077 4.454 0 8.077 3.623 8.077 8.077 0 4.454-3.623 8.077-8.077 8.077z" />
                                        </svg>
                                        @break
                                @endswitch
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- We accept --}}
                <div class="hidden sm:block">
                    <p class="text-sm font-semibold text-white mb-3">{{ __('We accept') }}</p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <img loading="lazy" src="{{ asset('elora/assets/images/pay-visa.svg') }}" alt="{{ __('Visa') }}"
                            class="h-7 rounded" />
                        <img loading="lazy" src="{{ asset('elora/assets/images/pay-mastercard.svg') }}" alt="{{ __('Mastercard') }}"
                            class="h-7 rounded" />
                        <img loading="lazy" src="{{ asset('elora/assets/images/pay-applepay.svg') }}" alt="{{ __('Apple Pay') }}"
                            class="h-7 rounded" />
                        <img loading="lazy" src="{{ asset('elora/assets/images/pay-fawry.svg') }}" alt="{{ __('Fawry Pay') }}"
                            class="h-7 rounded" />
                    </div>
                </div>

            </div>

            {{-- Col 2 : Customer service --}}
            <div class="hidden sm:block">
                <h4 class="font-semibold text-sm sm:text-base text-white mb-4 sm:mb-5">{{ __('Customer service') }}</h4>
                <ul class="space-y-2.5 sm:space-y-3 text-xs sm:text-sm text-white/60">
                    <li><a href="{{ route('tenant.storefront.page', 'return-refund-policy') }}"
                            class="hover:text-white transition-colors">{{ __('Return and refund policy') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'intellectual-property-policy') }}"
                            class="hover:text-white transition-colors">{{ __('Intellectual property policy') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'shipping-info') }}"
                            class="hover:text-white transition-colors">{{ __('Shipping info') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'report-suspicious-activity') }}"
                            class="hover:text-white transition-colors">{{ __('Report suspicious activity') }}</a></li>
                </ul>
            </div>

            {{-- Col 3 : Policies --}}
            <div class="hidden sm:block">
                <h4 class="font-semibold text-sm sm:text-base text-white mb-4 sm:mb-5">{{ __('Policies') }}</h4>
                <ul class="space-y-2.5 sm:space-y-3 text-xs sm:text-sm text-white/60">
                    <li><a href="{{ route('tenant.storefront.page', 'shipping-info') }}"
                            class="hover:text-white transition-colors">{{ __('Shipping') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'payment-info') }}"
                            class="hover:text-white transition-colors">{{ __('Payment') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'privacy-policy') }}"
                            class="hover:text-white transition-colors">{{ __('Privacy') }}</a></li>
                </ul>
            </div>

            {{-- Col 4 : Company info --}}
            <div class="hidden sm:block">
                <h4 class="font-semibold text-sm sm:text-base text-white mb-4 sm:mb-5">{{ __('Company info') }}</h4>
                <ul class="space-y-2.5 sm:space-y-3 text-xs sm:text-sm text-white/60">
                    <li><a href="{{ route('tenant.storefront.page', 'about-us') }}"
                            class="hover:text-white transition-colors">{{ __('About') }} {{ $storeName }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'contact-us') }}"
                            class="hover:text-white transition-colors">{{ __('Contact us') }}</a></li>
                    <li><a href="{{ route('tenant.storefront.page', 'faqs') }}"
                            class="hover:text-white transition-colors">{{ __('FAQs') }}</a></li>
                </ul>
            </div>

        </div>
    </div>

    {{-- ── Static pages ───────────────────────────────────────────────────────── --}}
    @php
        $footerLinkedPageSlugs = ['return-refund-policy', 'intellectual-property-policy', 'shipping-info', 'report-suspicious-activity', 'payment-info', 'privacy-policy', 'about-us', 'contact-us', 'faqs', 'terms-of-use', 'privacy-choices', 'support'];
        $remainingStaticPages = ($staticPages ?? collect())->reject(fn($page) => in_array($page->slug, $footerLinkedPageSlugs, true));
    @endphp
    @if ($remainingStaticPages->isNotEmpty())
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 pb-6 border-t border-white/10 pt-6">
            <h4 class="font-semibold text-sm text-white mb-3">{{ __('Pages') }}</h4>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach ($remainingStaticPages as $page)
                    @php $pageTitle = $page->translationValue('title');
                    $pageSlug = $page->slug; @endphp
                    @if ($pageTitle && $pageSlug)
                        <a href="{{ route('tenant.storefront.page', $pageSlug) }}"
                            class="text-xs sm:text-sm text-white/60 hover:text-white transition-colors">{{ $pageTitle }}</a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Bottom links bar ───────────────────────────────────────────────────── --}}
    <div class="py-3" style="background:#484848">
        <div
            class="max-w-screen-xl mx-auto px-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-1.5 text-xs sm:text-sm text-white/70">
            <a href="{{ route('tenant.storefront.page', 'terms-of-use') }}" class="hover:text-white transition-colors">{{ __('Terms of use') }}</a>
            <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}"
                class="hover:text-white transition-colors">{{ __('Privacy policy') }}</a>
            <a href="{{ route('tenant.storefront.page', 'privacy-choices') }}"
                class="hover:text-white transition-colors">{{ __('Your privacy choices') }}</a>
            <a href="{{ route('tenant.storefront.page', 'support') }}" class="hover:text-white transition-colors">{{ __('Support') }}</a>
            <a href="{{ route('tenant.storefront.page', 'faqs') }}" class="hover:text-white transition-colors">{{ __('FAQ') }}</a>
        </div>
    </div>

    {{-- ── Copyright bar ──────────────────────────────────────────────────────── --}}
    <div class="py-3 text-center text-xs sm:text-sm text-white/50" style="background:#323232">
        {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . ($storeName ?? 'ELORA') . '. ' . __('All Rights Reserved.')) }}
    </div>

</footer>