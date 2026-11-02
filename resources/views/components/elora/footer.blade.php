{{--
    One footer for every Elora variant. Markup is shared; each variant keeps its own footer colours
    (the values its previous footer used). Change a variant's look by editing its row below.

      variant        — Elora home-variant key: v1 | v2 | v3 | v4 | v5 | v6
      storeName / socialLinks / footerCopyright — storefront shared data
--}}
@props(['variant' => 'v1', 'storeName' => '', 'socialLinks' => null, 'footerCopyright' => null])

@php
    $palettes = [
        // bg = footer body, subbar = legal-links strip, bar = copyright strip, muted = secondary link colour
        'v1' => ['bg' => '#111827', 'subbar' => '#484848', 'bar' => '#323232', 'muted' => 'rgba(255,255,255,.7)'], // Default
        'v2' => ['bg' => '#3b3b3b', 'subbar' => '#5b5b5b', 'bar' => '#242424', 'muted' => '#f2f2f2'],             // Purple Edition
        'v3' => ['bg' => '#3b3b3b', 'subbar' => '#5b5b5b', 'bar' => '#242424', 'muted' => '#f2f2f2'],             // Fresh Edition
        'v4' => ['bg' => '#3b3b3b', 'subbar' => '#5b5b5b', 'bar' => '#242424', 'muted' => '#f2f2f2'],             // Bold Edition
        'v5' => ['bg' => '#3b3b3b', 'subbar' => '#5b5b5b', 'bar' => '#242424', 'muted' => '#f2f2f2'],             // Minimal Edition
        'v6' => ['bg' => '#3b3b3b', 'subbar' => '#5b5b5b', 'bar' => '#242424', 'muted' => '#f2f2f2'],             // New In Edition
    ];
    $c = $palettes[$variant] ?? $palettes['v1'];
@endphp

<footer class="elora-footer" data-footer-variant="{{ $variant }}" style="--ef-bg: {{ $c['bg'] }}; --ef-sub: {{ $c['subbar'] }}; --ef-bar: {{ $c['bar'] }}; --ef-muted: {{ $c['muted'] }}; background: var(--ef-bg)">
    <div
    class="px-[24px] lg:px-[56px] py-[40px] lg:py-[46px] flex flex-col lg:flex-row gap-[40px] lg:gap-[120px] max-w-[1440px] mx-auto"
    >
    <div class="flex flex-col gap-[32px] lg:justify-between shrink-0">
        <div class="flex flex-col gap-[32px]">
        <x-storefront-footer-logo :storeName="$storeName" class="h-[32px] w-auto" />
        @if (isset($socialLinks) && $socialLinks->isNotEmpty())
        <div class="flex flex-col gap-[16px]">
            <p class="font-medium text-[16px] text-white">
            {{ __('Connect with') }} {{ $storeName }}
            </p>
            <div class="flex gap-[16px] items-center">
            @foreach ($socialLinks as $link)
                @php
                    $iconAsset = match ($link->icon?->name) {
                        'facebook' => 'social-facebook.svg',
                        'twitter' => 'social-twitter.svg',
                        'instagram' => 'social-instagram.svg',
                        'youtube' => 'social-youtube.svg',
                        'linkedin' => 'social-linkedin.svg',
                        default => 'social-facebook.svg',
                    };
                @endphp
                <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                    aria-label="{{ ucfirst($link->icon?->name ?? 'social') }}">
                    <img
                        src="{{ asset('elora-1/assets/icons/' . $iconAsset) }}"
                        alt="{{ ucfirst($link->icon?->name ?? 'social') }}"
                        class="{{ $link->icon?->name === 'youtube' ? 'size-[32px]' : 'size-[24px]' }}"
                    />
                </a>
            @endforeach
            </div>
        </div>
        @endif
        </div>
        <div class="flex flex-col gap-[12px]">
        <p class="font-semibold text-[16px] text-white">{{ __('We accept') }}</p>
        <div class="flex gap-[12px]">
            <img
            src="{{ asset('elora-1/assets/icons/pay-visa.png') }}"
            alt="{{ __('Visa') }}"
            class="h-[37px] w-[56px] object-cover rounded"
            />
            <img
            src="{{ asset('elora-1/assets/icons/pay-mastercard.png') }}"
            alt="{{ __('Mastercard') }}"
            class="h-[37px] w-[56px] object-cover rounded"
            />
            <img
            src="{{ asset('elora-1/assets/icons/pay-applepay.png') }}"
            alt="{{ __('Apple Pay') }}"
            class="h-[37px] w-[56px] object-cover rounded"
            />
            <img
            src="{{ asset('elora-1/assets/icons/pay-fawry.png') }}"
            alt="{{ __('Fawry Pay') }}"
            class="h-[38px] w-[56px] object-cover rounded"
            />
        </div>
        </div>
    </div>

    <div
        class="flex flex-col sm:flex-row gap-[32px] sm:gap-[60px] lg:gap-[0] lg:justify-between lg:flex-1"
    >
        <div class="flex flex-col gap-[26px]">
        <p class="font-medium text-[20px] text-white">{{ __('Customer service') }}</p>
        <div
            class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px]"
            style="color: var(--ef-muted)"
        >
            <a href="{{ route('tenant.storefront.page', 'return-refund-policy') }}">{{ __('Return and refund policy') }}</a>
            <a href="{{ route('tenant.storefront.page', 'intellectual-property-policy') }}">{{ __('Intellectual property policy') }}</a>
            <a href="{{ route('tenant.storefront.page', 'shipping-info') }}">{{ __('Shipping info') }}</a>
            <a href="{{ route('tenant.storefront.page', 'report-suspicious-activity') }}">{{ __('Report suspicious activity') }}</a>
        </div>
        </div>
        <div class="flex flex-col gap-[26px]">
        <p class="font-medium text-[20px] text-white">{{ __('Policies') }}</p>
        <div
            class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px]"
            style="color: var(--ef-muted)"
        >
            <a href="{{ route('tenant.storefront.page', 'shipping-info') }}">{{ __('Shipping') }}</a>
            <a href="{{ route('tenant.storefront.page', 'payment-info') }}">{{ __('Payment') }}</a>
            <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}">{{ __('Privacy') }}</a>
        </div>
        </div>
        <div class="flex flex-col gap-[26px]">
        <p class="font-medium text-[20px] text-white">{{ __('Company info') }}</p>
        <div
            class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px] text-white"
        >
            <a href="{{ route('tenant.storefront.page', 'about-us') }}">{{ __('About') }} {{ $storeName }}</a>
            <a href="{{ route('tenant.storefront.page', 'contact-us') }}">{{ __('Contact us') }}</a>
            <a href="{{ route('tenant.storefront.page', 'faqs') }}">{{ __('FAQs') }}</a>
        </div>
        </div>
    </div>
    </div>

    <div
    class="flex flex-wrap items-center justify-center gap-[24px] lg:gap-[88px] py-[15px] text-[14px] tracking-[0.5px] text-white"
    style="background: var(--ef-sub)"
    >
    <a href="{{ route('tenant.storefront.page', 'terms-of-use') }}">{{ __('Terms of use') }}</a>
    <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}">{{ __('Privacy policy') }}</a>
    <a href="{{ route('tenant.storefront.page', 'privacy-choices') }}">{{ __('Your privacy choices') }}</a>
    <a href="{{ route('tenant.storefront.page', 'support') }}">{{ __('Support') }}</a>
    <a href="{{ route('tenant.storefront.page', 'faqs') }}">{{ __('FAQ') }}</a>
    </div>
    <div
    class="flex items-center justify-center py-[18px] text-[14px] tracking-[0.5px] text-white text-center"
    style="background: var(--ef-bar)"
    >
    {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . $storeName . '. ' . __('All Rights Reserved.')) }}
    </div>
    {{-- Clears the fixed mobile tab bar (80px). Same colour as the copyright bar so no pale gap shows under the footer. --}}
    @if (in_array($variant, ['v2', 'v3', 'v4', 'v5', 'v6'], true))
    <div class="lg:hidden" style="height:80px;background: var(--ef-bar)" aria-hidden="true"></div>
    @endif
</footer>
