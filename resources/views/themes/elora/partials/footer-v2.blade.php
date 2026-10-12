<footer style="background: var(--color-footer-bg)">
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
            style="color: var(--color-footer-text-muted)"
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
            style="color: var(--color-footer-text-muted)"
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
    style="background: var(--color-footer-subbar)"
    >
    <a href="{{ route('tenant.storefront.page', 'terms-of-use') }}">{{ __('Terms of use') }}</a>
    <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}">{{ __('Privacy policy') }}</a>
    <a href="{{ route('tenant.storefront.page', 'privacy-choices') }}">{{ __('Your privacy choices') }}</a>
    <a href="{{ route('tenant.storefront.page', 'support') }}">{{ __('Support') }}</a>
    <a href="{{ route('tenant.storefront.page', 'faqs') }}">{{ __('FAQ') }}</a>
    </div>
    <div
    class="flex items-center justify-center py-[18px] text-[14px] tracking-[0.5px] text-white text-center"
    style="background: var(--color-footer-bar)"
    >
    {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . $storeName . '. ' . __('All Rights Reserved.')) }}
    </div>
</footer>

@php
  $mobileNavRoute = request()->route()?->getName();
  $mobileNavActiveTab = match (true) {
      $mobileNavRoute === 'tenant.home' => 'home',
      $mobileNavRoute === 'tenant.storefront.favorites' => 'favorites',
      $mobileNavRoute === 'tenant.storefront.offers' => 'deals',
      $mobileNavRoute === 'tenant.storefront.profile' && request('tab', 'orders') === 'orders' => 'orders',
      $mobileNavRoute === 'tenant.storefront.profile' => 'profile',
      $mobileNavRoute === 'tenant.storefront.cart' => 'cart',
      default => null,
  };
@endphp

{{-- ============ MOBILE BOTTOM NAV (island) ============
     Figma Frame 1984080008: 80px bar, 16px gutters, holding a 64px white pill
     (radius 129, 0 0 20px rgba(0,0,0,.08)). Home/Favorites sit left, Orders/Profile
     right, and an 81px purple My Cart disc rises 28px above the pill's top edge. --}}
<div class="lg:hidden h-[80px]" aria-hidden="true"></div>
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 flex items-center h-[80px] px-[16px]" aria-label="{{ __('Primary') }}">
  <div class="relative flex-1 min-w-0 flex items-center justify-between h-[64px] px-[16px] rounded-[129px]" style="background: var(--color-bg-main, #FDFDFD); box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.08)">
    <div class="flex items-center gap-[16px]">
      <a href="{{ route('tenant.home') }}" class="relative flex flex-col items-center gap-[4px] pt-[6px]" style="color: {{ $mobileNavActiveTab === 'home' ? 'var(--color-text-primary, #121212)' : 'var(--color-gray, #8F8F8F)' }}" {{ $mobileNavActiveTab === 'home' ? 'aria-current=page' : '' }}>
        @if ($mobileNavActiveTab === 'home')
          <span class="absolute top-0 w-[22px] h-[2px] rounded-b-[8px]" style="background: var(--color-badge-purple, #9D00FF)" aria-hidden="true"></span>
        @endif
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 12.204c0-2.289 0-3.433.52-4.381.518-.949 1.467-1.537 3.364-2.715l2-1.241C9.889 2.622 10.892 2 12 2s2.11.622 4.116 1.867l2 1.241c1.897 1.178 2.846 1.766 3.365 2.715.519.948.519 2.092.519 4.38v1.522c0 3.9 0 5.851-1.172 7.063C19.657 22 17.771 22 14 22h-4c-3.771 0-5.657 0-6.828-1.212C2 19.576 2 17.626 2 13.725z"/><path stroke-linecap="round" d="M15 18H9"/></svg>
        <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Home') }}</span>
      </a>
      <a href="{{ route('tenant.storefront.favorites') }}" class="relative flex flex-col items-center gap-[4px] pt-[6px]" style="color: {{ $mobileNavActiveTab === 'favorites' ? 'var(--color-text-primary, #121212)' : 'var(--color-gray, #8F8F8F)' }}" {{ $mobileNavActiveTab === 'favorites' ? 'aria-current=page' : '' }}>
        @if ($mobileNavActiveTab === 'favorites')
          <span class="absolute top-0 w-[22px] h-[2px] rounded-b-[8px]" style="background: var(--color-badge-purple, #9D00FF)" aria-hidden="true"></span>
        @endif
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.62 20.81c-.34.12-.9.12-1.24 0C8.48 19.82 2 15.69 2 8.69 2 5.6 4.49 3.1 7.56 3.1c1.82 0 3.43.88 4.44 2.24 1.01-1.36 2.63-2.24 4.44-2.24C19.51 3.1 22 5.6 22 8.69c0 7-6.48 11.13-9.38 12.12Z"/></svg>
        <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Favorites') }}</span>
      </a>
    </div>

    <a href="{{ route('tenant.storefront.cart') }}" class="absolute left-1/2 -top-[28px] -translate-x-1/2 flex flex-col items-center justify-center size-[81px] rounded-full border-4 box-border" style="background: var(--color-badge-purple, #9D00FF); border-color: var(--color-bg-main, #FDFDFD); color: var(--color-bg-main, #FDFDFD)" aria-label="{{ __('My Cart') }}" {{ $mobileNavActiveTab === 'cart' ? 'aria-current=page' : '' }}>
      <span class="relative flex">
        <svg class="size-[33px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.71 5.4h15.214c1.378 0 2.373 1.27 1.995 2.548l-1.654 5.6c-.255.86-1.069 1.452-1.995 1.452H8.112c-.927 0-1.741-.592-1.996-1.452L3.71 5.4ZM3.71 5.4 3 3M16.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM8.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
        <span id="elora-v2-footer-cart-badge" class="elora-cart-badge absolute -top-[2px] left-[14px] min-w-[23px] h-[13px] px-[4px] flex items-center justify-center rounded-full text-[11px] leading-none tracking-[0.5px]" style="background: var(--color-bg-main, #FDFDFD); color: var(--color-text-primary, #121212); box-shadow: 0px 2px 1.1px rgba(0, 0, 0, 0.15)">{{ $cartCount ?? 0 }}</span>
      </span>
      <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('My Cart') }}</span>
    </a>

    <div class="flex items-center gap-[16px]">
      <a href="{{ route('tenant.storefront.profile', ['tab' => 'orders']) }}" class="relative flex flex-col items-center gap-[4px] pt-[6px]" style="color: {{ $mobileNavActiveTab === 'orders' ? 'var(--color-text-primary, #121212)' : 'var(--color-gray, #8F8F8F)' }}" {{ $mobileNavActiveTab === 'orders' ? 'aria-current=page' : '' }}>
        @if ($mobileNavActiveTab === 'orders')
          <span class="absolute top-0 w-[22px] h-[2px] rounded-b-[8px]" style="background: var(--color-badge-purple, #9D00FF)" aria-hidden="true"></span>
        @endif
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M15.578 3.382l2 1.05c2.151 1.129 3.227 1.693 3.825 2.708C22 8.154 22 9.417 22 11.942v.117c0 2.524 0 3.787-.597 4.801-.598 1.015-1.674 1.58-3.825 2.709l-2 1.049C13.822 21.539 12.944 22 12 22s-1.822-.46-3.578-1.382l-2-1.05c-2.151-1.129-3.227-1.693-3.825-2.708C2 15.846 2 14.583 2 12.06v-.117c0-2.525 0-3.788.597-4.802.598-1.015 1.674-1.58 3.825-2.708l2-1.05C10.178 2.461 11.056 2 12 2s1.822.46 3.578 1.382ZM21 7.5l-4 2M12 12 3 7.5m9 4.5v9.5m0-9.5 4.5-2.25L17 9.5m0 0V13m0-3.5-9.5-5"/></svg>
        <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Orders') }}</span>
      </a>
      <a href="{{ route('tenant.storefront.profile', ['tab' => 'profile']) }}" class="relative flex flex-col items-center gap-[4px] pt-[6px]" style="color: {{ $mobileNavActiveTab === 'profile' ? 'var(--color-text-primary, #121212)' : 'var(--color-gray, #8F8F8F)' }}" {{ $mobileNavActiveTab === 'profile' ? 'aria-current=page' : '' }}>
        @if ($mobileNavActiveTab === 'profile')
          <span class="absolute top-0 w-[22px] h-[2px] rounded-b-[8px]" style="background: var(--color-badge-purple, #9D00FF)" aria-hidden="true"></span>
        @endif
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z"/><path d="M20.59 22c0-3.87-3.85-7-8.59-7s-8.59 3.13-8.59 7"/></svg>
        <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Profile') }}</span>
      </a>
    </div>
  </div>
</nav>

@vite(['resources/css/elora/footer-v2.css', 'resources/js/elora/footer-v2.js'])
