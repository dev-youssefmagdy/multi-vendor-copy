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
              <img src="{{ asset('souqify-1/assets/icons/social-facebook.svg') }}" alt="Facebook" class="size-[24px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('twitter')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-1/assets/icons/social-twitter.svg') }}" alt="Twitter" class="size-[24px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('instagram')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-1/assets/icons/social-instagram.svg') }}" alt="Instagram" class="size-[24px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('youtube')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-1/assets/icons/social-youtube.svg') }}" alt="YouTube" class="size-[32px]" />
            </a>
            <a href="{{ $socialLinksByIcon->get('linkedin')?->url ?? '#' }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ asset('souqify-1/assets/icons/social-linkedin.svg') }}" alt="LinkedIn" class="size-[24px]" />
            </a>
          </div>
        </div>
      </div>
      <div class="flex flex-col gap-[12px]">
        <p class="font-semibold text-[16px] text-white">{{ __('We accept') }}</p>
        <div class="flex gap-[12px]">
          <img src="{{ asset('souqify-1/assets/images/pay-visa.png') }}" alt="Visa" class="h-[37px] w-[56px] object-cover rounded" />
          <img src="{{ asset('souqify-1/assets/images/pay-mastercard.png') }}" alt="Mastercard" class="h-[37px] w-[56px] object-cover rounded" />
          <img src="{{ asset('souqify-1/assets/images/pay-applepay.png') }}" alt="Apple Pay" class="h-[37px] w-[56px] object-cover rounded" />
          <img src="{{ asset('souqify-1/assets/images/pay-fawry.png') }}" alt="Fawry Pay" class="h-[38px] w-[56px] object-cover rounded" />
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
<nav class="lg:hidden flex items-center justify-between px-[16px] py-[10px] border-t-2 bg-white" style="border-color:var(--color-gray)">
  <a href="{{ route('tenant.home') }}" class="flex flex-col items-center gap-[4px] px-[8px] py-[4px] rounded-[10px] {{ request()->routeIs('tenant.home') ? '' : '' }}" style="background:rgba(174,1,237,0.12)">
    <img src="{{ asset('souqify-1/assets/icons/icon-nav-home.svg') }}" alt="" class="size-[24px]" />
    <span class="text-[12px] tracking-[0.5px]" style="color:var(--color-accent-purple-bright)">{{ __('Home') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.favorites') }}" class="flex flex-col items-center gap-[4px]">
    <img src="{{ asset('souqify-1/assets/icons/icon-nav-heart.svg') }}" alt="" class="size-[24px]" />
    <span class="text-[12px] tracking-[0.5px]" style="color:var(--color-gray)">{{ __('Favorites') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.cart') }}" class="flex flex-col items-center gap-[4px] relative">
    <span class="relative">
      <img src="{{ asset('souqify-1/assets/icons/icon-nav-cart.svg') }}" alt="" class="size-[24px]" />
      <span class="souqify-cart-badge absolute -top-[2px] -right-[10px] flex items-center justify-center rounded-full text-white text-[10px] font-semibold h-[11px] w-[15px] {{ $cartCount > 0 ? '' : 'hidden' }}" style="background:var(--color-accent-purple-bright)">{{ $cartCount }}</span>
    </span>
    <span class="text-[12px] tracking-[0.5px]" style="color:var(--color-gray)">{{ __('My Cart') }}</span>
  </a>
  <a href="{{ route('tenant.storefront.profile') }}" class="flex flex-col items-center gap-[4px]">
    <img src="{{ asset('souqify-1/assets/icons/icon-nav-orders-box.svg') }}" alt="" class="size-[24px]" />
    <span class="text-[12px] tracking-[0.5px]" style="color:var(--color-gray)">{{ __('Orders') }}</span>
  </a>
  @auth('storefront')
    <a href="{{ route('tenant.storefront.profile') }}" class="flex flex-col items-center gap-[4px]">
      <img src="{{ asset('souqify-1/assets/icons/icon-nav-user.svg') }}" alt="" class="size-[24px]" />
      <span class="text-[12px] tracking-[0.5px]" style="color:var(--color-gray)">{{ __('Profile') }}</span>
    </a>
  @else
    <a href="{{ route('tenant.storefront.login') }}" class="flex flex-col items-center gap-[4px]">
      <img src="{{ asset('souqify-1/assets/icons/icon-nav-user.svg') }}" alt="" class="size-[24px]" />
      <span class="text-[12px] tracking-[0.5px]" style="color:var(--color-gray)">{{ __('Profile') }}</span>
    </a>
  @endauth
</nav>
