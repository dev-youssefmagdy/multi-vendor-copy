@php
    $socialLinksByIcon = (isset($socialLinks) ? $socialLinks : collect())->keyBy(fn($link) => $link->icon?->name);
@endphp
<!-- ============ FOOTER ============ -->
<footer style="background:var(--color-footer-bg)">
  <div class="px-[24px] lg:px-[56px] py-[40px] lg:py-[46px] flex flex-col lg:flex-row gap-[40px] lg:gap-[120px] max-w-[1440px] mx-auto">
    <div class="flex flex-col gap-[32px] lg:justify-between shrink-0">
      <div class="flex flex-col gap-[32px]">
        <x-storefront-logo :storeName="$storeName" class="h-[32px] w-auto" />
        <div class="flex flex-col gap-[16px]">
          <p class="font-medium text-[16px] text-white">{{ __('Connect with') }} {{ $storeName }}</p>
          <div class="flex gap-[16px] items-center">
            <a href="{{ $socialLinksByIcon->get('facebook')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-3/assets/icons/social-facebook.svg') }}" alt="Facebook" class="size-[24px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('twitter')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-3/assets/icons/social-twitter.svg') }}" alt="Twitter" class="size-[24px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('instagram')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-3/assets/icons/social-instagram.svg') }}" alt="Instagram" class="size-[24px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('youtube')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-3/assets/icons/social-youtube.svg') }}" alt="YouTube" class="size-[32px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('linkedin')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-3/assets/icons/social-linkedin.svg') }}" alt="LinkedIn" class="size-[24px]" />
            </a>
          </div>
        </div>
      </div>
      <div class="flex flex-col gap-[12px]">
        <p class="font-semibold text-[16px] text-white">{{ __('We accept') }}</p>
        <div class="flex gap-[12px]">
          <img src="{{ asset('souqify-3/assets/icons/pay-visa.png') }}" alt="Visa" class="h-[37px] w-[56px] object-cover rounded" />
          <img src="{{ asset('souqify-3/assets/icons/pay-mastercard.png') }}" alt="Mastercard" class="h-[37px] w-[56px] object-cover rounded" />
          <img src="{{ asset('souqify-3/assets/icons/pay-applepay.png') }}" alt="Apple Pay" class="h-[37px] w-[56px] object-cover rounded" />
          <img src="{{ asset('souqify-3/assets/icons/pay-fawry.png') }}" alt="Fawry Pay" class="h-[38px] w-[56px] object-cover rounded" />
        </div>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-[32px] sm:gap-[60px] lg:gap-[0] lg:justify-between lg:flex-1">
      <div class="flex flex-col gap-[26px]">
        <p class="font-medium text-[20px] text-white">{{ __('Customer service') }}</p>
        <div class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px]" style="color:var(--color-footer-text-muted)">
          <a href="{{ route('tenant.storefront.page', 'return-refund-policy') }}">{{ __('Return and refund policy') }}</a>
          <a href="{{ route('tenant.storefront.page', 'intellectual-property-policy') }}">{{ __('Intellectual property policy') }}</a>
          <a href="{{ route('tenant.storefront.page', 'shipping-info') }}">{{ __('Shipping info') }}</a>
          <a href="{{ route('tenant.storefront.page', 'report-suspicious-activity') }}">{{ __('Report suspicious activity') }}</a>
        </div>
      </div>
      <div class="flex flex-col gap-[26px]">
        <p class="font-medium text-[20px] text-white">{{ __('Policies') }}</p>
        <div class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px]" style="color:var(--color-footer-text-muted)">
          <a href="{{ route('tenant.storefront.page', 'shipping-info') }}">{{ __('Shipping') }}</a>
          <a href="{{ route('tenant.storefront.page', 'payment-info') }}">{{ __('Payment') }}</a>
          <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}">{{ __('Privacy') }}</a>
        </div>
      </div>
      <div class="flex flex-col gap-[26px]">
        <p class="font-medium text-[20px] text-white">{{ __('Company info') }}</p>
        <div class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px] text-white">
          <a href="{{ route('tenant.storefront.page', 'about-us') }}">{{ __('About') }} {{ $storeName }}</a>
          <a href="{{ route('tenant.storefront.page', 'contact-us') }}">{{ __('Contact us') }}</a>
          <a href="{{ route('tenant.storefront.page', 'faqs') }}">{{ __('FAQs') }}</a>
        </div>
      </div>
    </div>
  </div>

  <div class="flex flex-wrap items-center justify-center gap-[24px] lg:gap-[88px] py-[15px] text-[14px] tracking-[0.5px] text-white" style="background:var(--color-footer-subbar)">
    <a href="{{ route('tenant.storefront.page', 'terms-of-use') }}">{{ __('Terms of use') }}</a>
    <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}">{{ __('Privacy policy') }}</a>
    <a href="{{ route('tenant.storefront.page', 'privacy-choices') }}">{{ __('Your privacy choices') }}</a>
    <a href="{{ route('tenant.storefront.page', 'support') }}">{{ __('Support') }}</a>
    <a href="{{ route('tenant.storefront.page', 'faqs') }}">{{ __('FAQ') }}</a>
  </div>
  <div class="flex items-center justify-center py-[18px] text-[14px] tracking-[0.5px] text-white text-center" style="background:var(--color-footer-bar)">
    {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . ($storeName ?? 'Souqify') . '. ' . __('All Rights Reserved.')) }}
  </div>
</footer>

<!-- ============ MOBILE BOTTOM NAV ============ -->
<nav class="lg:hidden fixed bottom-0 inset-x-0 z-30 flex items-center justify-between px-[16px] py-[8px]" style="background:var(--color-bg-main); border-top:2px solid var(--color-gray)">
  <a href="{{ route('tenant.home') }}" data-mobile-tab class="mobile-tab {{ request()->routeIs('tenant.home') ? 'is-active-tab' : '' }} flex flex-col items-center gap-[4px] w-[58px] relative">
    <span class="tab-indicator absolute -top-[10px] size-[8px] rounded-full" style="background:var(--color-brand-green)"></span>
    <img src="{{ asset('souqify-3/assets/icons/icon-nav-home.svg') }}" alt="" class="size-[22px]" />
    <span class="text-[13px]" style="color:var(--color-text-primary)">{{ __('Home') }}</span>
  </a>
  @auth('storefront')
    <a href="{{ route('tenant.storefront.profile') }}" data-mobile-tab class="mobile-tab flex flex-col items-center gap-[4px]">
      <img src="{{ asset('souqify-3/assets/icons/icon-nav-orders.svg') }}" alt="" class="size-[22px] opacity-60" />
      <span class="text-[11px]" style="color:var(--color-gray)">{{ __('My Orders') }}</span>
    </a>
  @else
    <a href="{{ route('tenant.storefront.login') }}" data-mobile-tab class="mobile-tab flex flex-col items-center gap-[4px]">
      <img src="{{ asset('souqify-3/assets/icons/icon-nav-orders.svg') }}" alt="" class="size-[22px] opacity-60" />
      <span class="text-[11px]" style="color:var(--color-gray)">{{ __('My Orders') }}</span>
    </a>
  @endauth
  <a href="{{ route('tenant.storefront.best-selling') }}" data-mobile-tab class="mobile-tab flex flex-col items-center gap-[4px]">
    <img src="{{ asset('souqify-3/assets/icons/icon-nav-deals.svg') }}" alt="" class="size-[22px] opacity-60" />
    <span class="text-[11px]" style="color:var(--color-gray)">{{ __('Deals') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.favorites') }}" data-mobile-tab class="mobile-tab flex flex-col items-center gap-[4px]">
    <img src="{{ asset('souqify-3/assets/icons/icon-nav-favorites.svg') }}" alt="" class="size-[22px] opacity-60" />
    <span class="text-[11px]" style="color:var(--color-gray)">{{ __('Favorites') }}</span>
  </a>
  @auth('storefront')
    <a href="{{ route('tenant.storefront.profile') }}" data-mobile-tab class="mobile-tab flex flex-col items-center gap-[4px] w-[37px]">
      <img src="{{ asset('souqify-3/assets/icons/icon-nav-profile.svg') }}" alt="" class="size-[22px] opacity-60" />
      <span class="text-[11px]" style="color:var(--color-gray)">{{ __('Profile') }}</span>
    </a>
  @else
    <a href="{{ route('tenant.storefront.login') }}" data-mobile-tab class="mobile-tab flex flex-col items-center gap-[4px] w-[37px]">
      <img src="{{ asset('souqify-3/assets/icons/icon-nav-profile.svg') }}" alt="" class="size-[22px] opacity-60" />
      <span class="text-[11px]" style="color:var(--color-gray)">{{ __('Profile') }}</span>
    </a>
  @endauth
</nav>
