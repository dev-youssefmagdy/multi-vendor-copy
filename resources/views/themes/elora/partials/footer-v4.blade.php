    <footer style="background: var(--color-footer-bg)">
    <div
      class="px-[24px] lg:px-[56px] py-[40px] lg:py-[46px] flex flex-col lg:flex-row gap-[40px] lg:gap-[120px] max-w-[1440px] mx-auto"
    >
      <div class="flex flex-col gap-[32px] lg:justify-between shrink-0">
        <div class="flex flex-col gap-[32px]">
          <x-storefront-footer-logo :storeName="$storeName ?? 'ELORA'" class="h-[32px] w-auto" />
          <div class="flex flex-col gap-[16px]">
            <p class="font-medium text-[16px] text-white">
              {{ __('Connect with') }} {{ $storeName ?? 'ELORA' }}
            </p>
            @if (isset($socialLinks) && $socialLinks->isNotEmpty())
            <div class="flex gap-[16px] items-center flex-wrap">
              @foreach ($socialLinks as $link)
                @php
                  $iconName = $link->icon?->name;
                  $iconMap = [
                    'facebook' => 'social-facebook.svg',
                    'twitter' => 'social-twitter.svg',
                    'instagram' => 'social-instagram.svg',
                    'youtube' => 'social-youtube.svg',
                    'linkedin' => 'social-linkedin.svg',
                  ];
                  $iconFile = $iconMap[$iconName] ?? null;
                @endphp
                @if ($iconFile)
                  <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst($iconName) }}">
                    <img
                      src="{{ asset('elora-4/assets/icons/' . $iconFile) }}"
                      alt="{{ ucfirst($iconName) }}"
                      class="{{ $iconName === 'youtube' ? 'size-[32px]' : 'size-[24px]' }}"
                    />
                  </a>
                @endif
              @endforeach
            </div>
            @endif
          </div>
        </div>
        <div class="flex flex-col gap-[12px]">
          <p class="font-semibold text-[16px] text-white">{{ __('We accept') }}</p>
          <div class="flex gap-[12px]">
            <img
              src="{{ asset('elora-4/assets/icons/pay-visa.png') }}"
              alt="{{ __('Visa') }}"
              class="h-[37px] w-[56px] object-cover rounded"
            />
            <img
              src="{{ asset('elora-4/assets/icons/pay-mastercard.png') }}"
              alt="{{ __('Mastercard') }}"
              class="h-[37px] w-[56px] object-cover rounded"
            />
            <img
              src="{{ asset('elora-4/assets/icons/pay-applepay.png') }}"
              alt="{{ __('Apple Pay') }}"
              class="h-[37px] w-[56px] object-cover rounded"
            />
            <img
              src="{{ asset('elora-4/assets/icons/pay-fawry.png') }}"
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
            <a href="{{ route('tenant.storefront.page', 'return-refund-policy') }}" class="hover:text-white transition-colors">{{ __('Return and refund policy') }}</a>
            <a href="{{ route('tenant.storefront.page', 'intellectual-property-policy') }}" class="hover:text-white transition-colors">{{ __('Intellectual property policy') }}</a>
            <a href="{{ route('tenant.storefront.page', 'shipping-info') }}" class="hover:text-white transition-colors">{{ __('Shipping info') }}</a>
            <a href="{{ route('tenant.storefront.page', 'report-suspicious-activity') }}" class="hover:text-white transition-colors">{{ __('Report suspicious activity') }}</a>
          </div>
        </div>
        <div class="flex flex-col gap-[26px]">
          <p class="font-medium text-[20px] text-white">{{ __('Policies') }}</p>
          <div
            class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px]"
            style="color: var(--color-footer-text-muted)"
          >
            <a href="{{ route('tenant.storefront.page', 'shipping-info') }}" class="hover:text-white transition-colors">{{ __('Shipping') }}</a>
            <a href="{{ route('tenant.storefront.page', 'payment-info') }}" class="hover:text-white transition-colors">{{ __('Payment') }}</a>
            <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}" class="hover:text-white transition-colors">{{ __('Privacy') }}</a>
          </div>
        </div>
        <div class="flex flex-col gap-[26px]">
          <p class="font-medium text-[20px] text-white">{{ __('Company info') }}</p>
          <div
            class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px] text-white"
          >
            <a href="{{ route('tenant.storefront.page', 'about-us') }}" class="hover:opacity-80 transition-opacity">{{ __('About') }} {{ $storeName ?? 'ELORA' }}</a>
            <a href="{{ route('tenant.storefront.page', 'contact-us') }}" class="hover:opacity-80 transition-opacity">{{ __('Contact us') }}</a>
            <a href="{{ route('tenant.storefront.page', 'faqs') }}" class="hover:opacity-80 transition-opacity">{{ __('FAQs') }}</a>
          </div>
        </div>
      </div>
    </div>

    <div
      class="flex flex-wrap items-center justify-center gap-[24px] lg:gap-[88px] py-[15px] text-[14px] tracking-[0.5px] text-white"
      style="background: var(--color-footer-subbar)"
    >
      <a href="{{ route('tenant.storefront.page', 'terms-of-use') }}" class="hover:opacity-80 transition-opacity">{{ __('Terms of use') }}</a>
      <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}" class="hover:opacity-80 transition-opacity">{{ __('Privacy policy') }}</a>
      <a href="{{ route('tenant.storefront.page', 'privacy-choices') }}" class="hover:opacity-80 transition-opacity">{{ __('Your privacy choices') }}</a>
      <a href="{{ route('tenant.storefront.page', 'support') }}" class="hover:opacity-80 transition-opacity">{{ __('Support') }}</a>
      <a href="{{ route('tenant.storefront.page', 'faqs') }}" class="hover:opacity-80 transition-opacity">{{ __('FAQ') }}</a>
    </div>
    <div
      class="flex items-center justify-center py-[18px] text-[14px] tracking-[0.5px] text-white text-center"
      style="background: var(--color-footer-bar)"
    >
      {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . ($storeName ?? 'ELORA') . '. ' . __('All Rights Reserved.')) }}
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
      default => null,
  };
@endphp

{{-- ============ MOBILE BOTTOM NAV ============
     Figma Frame 1984080007: full-width 80px white bar, 24px gutters,
     0 0 20px rgba(0,0,0,.08) shadow. The active tab gets an orange 46x31 pill
     behind its icon; Deals stays orange as the promoted tab. --}}
<div class="lg:hidden h-[80px]" aria-hidden="true"></div>
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 flex items-center justify-between h-[80px] px-[24px]" style="background: var(--color-bg-main, #FDFDFD); box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.08)" aria-label="{{ __('Primary') }}">
  <a href="{{ route('tenant.home') }}" class="flex flex-col items-center gap-[4px] min-w-[34px]" style="color: {{ ($mobileNavActiveTab === 'home' ? 'var(--color-brand-orange, #FB7701)' : 'var(--color-gray, #8F8F8F)') }}" {{ $mobileNavActiveTab === 'home' ? 'aria-current=page' : '' }}>
    <span class="relative flex items-center justify-center">
      @if ($mobileNavActiveTab === 'home')
        <span class="absolute w-[46px] h-[31px] rounded-[23px]" style="background: var(--color-brand-orange, #FB7701)" aria-hidden="true"></span>
        <span class="relative flex" style="color: var(--color-bg-main, #FDFDFD)"><svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 12.204c0-2.289 0-3.433.52-4.381.518-.949 1.467-1.537 3.364-2.715l2-1.241C9.889 2.622 10.892 2 12 2s2.11.622 4.116 1.867l2 1.241c1.897 1.178 2.846 1.766 3.365 2.715.519.948.519 2.092.519 4.38v1.522c0 3.9 0 5.851-1.172 7.063C19.657 22 17.771 22 14 22h-4c-3.771 0-5.657 0-6.828-1.212C2 19.576 2 17.626 2 13.725z"/><path stroke-linecap="round" d="M15 18H9"/></svg></span>
      @else
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M2 12.204c0-2.289 0-3.433.52-4.381.518-.949 1.467-1.537 3.364-2.715l2-1.241C9.889 2.622 10.892 2 12 2s2.11.622 4.116 1.867l2 1.241c1.897 1.178 2.846 1.766 3.365 2.715.519.948.519 2.092.519 4.38v1.522c0 3.9 0 5.851-1.172 7.063C19.657 22 17.771 22 14 22h-4c-3.771 0-5.657 0-6.828-1.212C2 19.576 2 17.626 2 13.725z"/><path stroke-linecap="round" d="M15 18H9"/></svg>
      @endif
    </span>
    <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Home') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.favorites') }}" class="flex flex-col items-center gap-[4px] min-w-[34px]" style="color: {{ ($mobileNavActiveTab === 'favorites' ? 'var(--color-brand-orange, #FB7701)' : 'var(--color-gray, #8F8F8F)') }}" {{ $mobileNavActiveTab === 'favorites' ? 'aria-current=page' : '' }}>
    <span class="relative flex items-center justify-center">
      @if ($mobileNavActiveTab === 'favorites')
        <span class="absolute w-[46px] h-[31px] rounded-[23px]" style="background: var(--color-brand-orange, #FB7701)" aria-hidden="true"></span>
        <span class="relative flex" style="color: var(--color-bg-main, #FDFDFD)"><svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.62 20.81c-.34.12-.9.12-1.24 0C8.48 19.82 2 15.69 2 8.69 2 5.6 4.49 3.1 7.56 3.1c1.82 0 3.43.88 4.44 2.24 1.01-1.36 2.63-2.24 4.44-2.24C19.51 3.1 22 5.6 22 8.69c0 7-6.48 11.13-9.38 12.12Z"/></svg></span>
      @else
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.62 20.81c-.34.12-.9.12-1.24 0C8.48 19.82 2 15.69 2 8.69 2 5.6 4.49 3.1 7.56 3.1c1.82 0 3.43.88 4.44 2.24 1.01-1.36 2.63-2.24 4.44-2.24C19.51 3.1 22 5.6 22 8.69c0 7-6.48 11.13-9.38 12.12Z"/></svg>
      @endif
    </span>
    <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Favorites') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.offers') }}" class="flex flex-col items-center gap-[4px] min-w-[34px]" style="color: {{ 'var(--color-brand-orange, #FB7701)' }}" {{ $mobileNavActiveTab === 'deals' ? 'aria-current=page' : '' }}>
    <span class="relative flex items-center justify-center">
      @if ($mobileNavActiveTab === 'deals')
        <span class="absolute w-[46px] h-[31px] rounded-[23px]" style="background: var(--color-brand-orange, #FB7701)" aria-hidden="true"></span>
        <span class="relative flex" style="color: var(--color-bg-main, #FDFDFD)"><svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 15 6-6"/><circle cx="9.5" cy="9.5" r=".75" fill="currentColor"/><circle cx="14.5" cy="14.5" r=".75" fill="currentColor"/><path d="M5 7.2A2.2 2.2 0 0 1 7.2 5h1a2.2 2.2 0 0 0 1.55-.64l.7-.7a2.2 2.2 0 0 1 3.12 0l.7.7c.412.41.97.64 1.55.64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58.23 1.138.64 1.55l.7.7a2.2 2.2 0 0 1 0 3.12l-.7.7a2.2 2.2 0 0 0-.64 1.55v1a2.2 2.2 0 0 1-2.2 2.2h-1a2.2 2.2 0 0 0-1.55.64l-.7.7a2.2 2.2 0 0 1-3.12 0l-.7-.7a2.2 2.2 0 0 0-1.55-.64h-1a2.2 2.2 0 0 1-2.2-2.2v-1a2.2 2.2 0 0 0-.64-1.55l-.7-.7a2.2 2.2 0 0 1 0-3.12l.7-.7A2.2 2.2 0 0 0 5 8.2z"/></svg></span>
      @else
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 15 6-6"/><circle cx="9.5" cy="9.5" r=".75" fill="currentColor"/><circle cx="14.5" cy="14.5" r=".75" fill="currentColor"/><path d="M5 7.2A2.2 2.2 0 0 1 7.2 5h1a2.2 2.2 0 0 0 1.55-.64l.7-.7a2.2 2.2 0 0 1 3.12 0l.7.7c.412.41.97.64 1.55.64h1a2.2 2.2 0 0 1 2.2 2.2v1c0 .58.23 1.138.64 1.55l.7.7a2.2 2.2 0 0 1 0 3.12l-.7.7a2.2 2.2 0 0 0-.64 1.55v1a2.2 2.2 0 0 1-2.2 2.2h-1a2.2 2.2 0 0 0-1.55.64l-.7.7a2.2 2.2 0 0 1-3.12 0l-.7-.7a2.2 2.2 0 0 0-1.55-.64h-1a2.2 2.2 0 0 1-2.2-2.2v-1a2.2 2.2 0 0 0-.64-1.55l-.7-.7a2.2 2.2 0 0 1 0-3.12l.7-.7A2.2 2.2 0 0 0 5 8.2z"/></svg>
      @endif
    </span>
    <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Deals') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.profile', ['tab' => 'orders']) }}" class="flex flex-col items-center gap-[4px] min-w-[34px]" style="color: {{ ($mobileNavActiveTab === 'orders' ? 'var(--color-brand-orange, #FB7701)' : 'var(--color-gray, #8F8F8F)') }}" {{ $mobileNavActiveTab === 'orders' ? 'aria-current=page' : '' }}>
    <span class="relative flex items-center justify-center">
      @if ($mobileNavActiveTab === 'orders')
        <span class="absolute w-[46px] h-[31px] rounded-[23px]" style="background: var(--color-brand-orange, #FB7701)" aria-hidden="true"></span>
        <span class="relative flex" style="color: var(--color-bg-main, #FDFDFD)"><svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M15.578 3.382l2 1.05c2.151 1.129 3.227 1.693 3.825 2.708C22 8.154 22 9.417 22 11.942v.117c0 2.524 0 3.787-.597 4.801-.598 1.015-1.674 1.58-3.825 2.709l-2 1.049C13.822 21.539 12.944 22 12 22s-1.822-.46-3.578-1.382l-2-1.05c-2.151-1.129-3.227-1.693-3.825-2.708C2 15.846 2 14.583 2 12.06v-.117c0-2.525 0-3.788.597-4.802.598-1.015 1.674-1.58 3.825-2.708l2-1.05C10.178 2.461 11.056 2 12 2s1.822.46 3.578 1.382ZM21 7.5l-4 2M12 12 3 7.5m9 4.5v9.5m0-9.5 4.5-2.25L17 9.5m0 0V13m0-3.5-9.5-5"/></svg></span>
      @else
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M15.578 3.382l2 1.05c2.151 1.129 3.227 1.693 3.825 2.708C22 8.154 22 9.417 22 11.942v.117c0 2.524 0 3.787-.597 4.801-.598 1.015-1.674 1.58-3.825 2.709l-2 1.049C13.822 21.539 12.944 22 12 22s-1.822-.46-3.578-1.382l-2-1.05c-2.151-1.129-3.227-1.693-3.825-2.708C2 15.846 2 14.583 2 12.06v-.117c0-2.525 0-3.788.597-4.802.598-1.015 1.674-1.58 3.825-2.708l2-1.05C10.178 2.461 11.056 2 12 2s1.822.46 3.578 1.382ZM21 7.5l-4 2M12 12 3 7.5m9 4.5v9.5m0-9.5 4.5-2.25L17 9.5m0 0V13m0-3.5-9.5-5"/></svg>
      @endif
    </span>
    <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Orders') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.profile', ['tab' => 'profile']) }}" class="flex flex-col items-center gap-[4px] min-w-[34px]" style="color: {{ ($mobileNavActiveTab === 'profile' ? 'var(--color-brand-orange, #FB7701)' : 'var(--color-gray, #8F8F8F)') }}" {{ $mobileNavActiveTab === 'profile' ? 'aria-current=page' : '' }}>
    <span class="relative flex items-center justify-center">
      @if ($mobileNavActiveTab === 'profile')
        <span class="absolute w-[46px] h-[31px] rounded-[23px]" style="background: var(--color-brand-orange, #FB7701)" aria-hidden="true"></span>
        <span class="relative flex" style="color: var(--color-bg-main, #FDFDFD)"><svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z"/><path d="M20.59 22c0-3.87-3.85-7-8.59-7s-8.59 3.13-8.59 7"/></svg></span>
      @else
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z"/><path d="M20.59 22c0-3.87-3.85-7-8.59-7s-8.59 3.13-8.59 7"/></svg>
      @endif
    </span>
    <span class="text-[12px] leading-[15px] tracking-[0.5px] whitespace-nowrap">{{ __('Profile') }}</span>
  </a>
</nav>

@vite(['resources/css/elora/footer-v4.css', 'resources/js/elora/footer-v4.js'])
