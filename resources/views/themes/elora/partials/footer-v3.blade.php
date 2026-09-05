    <footer class="hidden lg:block" style="background: var(--color-footer-bg)">
      <div
        class="px-[24px] lg:px-[56px] py-[40px] lg:py-[46px] flex flex-col lg:flex-row gap-[40px] lg:gap-[120px] max-w-[1440px] mx-auto"
      >
        <div class="flex flex-col gap-[32px] lg:justify-between shrink-0">
          <div class="flex flex-col gap-[32px]">
            <img
              src="{{ asset('elora-3/assets/icons/logo-elora-white.svg') }}"
              alt="{{ $storeName }}"
              class="h-[32px] w-auto"
            />
            <div class="flex flex-col gap-[16px]">
              <p class="font-medium text-[16px] text-white">
                {{ __('Connect with') }} {{ $storeName }}
              </p>
              @if (isset($socialLinks) && $socialLinks->isNotEmpty())
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
                        src="{{ asset('elora-3/assets/icons/' . $iconAsset) }}"
                        alt="{{ ucfirst($link->icon?->name ?? 'social') }}"
                        class="{{ $link->icon?->name === 'youtube' ? 'size-[32px]' : 'size-[24px]' }}"
                      />
                    </a>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
          <div class="flex flex-col gap-[12px]">
            <p class="font-semibold text-[16px] text-white">We accept</p>
            <div class="flex gap-[12px]">
              <img
                src="{{ asset('elora-3/assets/icons/pay-visa.png') }}"
                alt="Visa"
                class="h-[37px] w-[56px] object-cover rounded"
              />
              <img
                src="{{ asset('elora-3/assets/icons/pay-mastercard.png') }}"
                alt="Mastercard"
                class="h-[37px] w-[56px] object-cover rounded"
              />
              <img
                src="{{ asset('elora-3/assets/icons/pay-applepay.png') }}"
                alt="Apple Pay"
                class="h-[37px] w-[56px] object-cover rounded"
              />
              <img
                src="{{ asset('elora-3/assets/icons/pay-fawry.png') }}"
                alt="Fawry Pay"
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
        {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . ($storeName ?? 'ELORA') . '. ' . __('All Rights Reserved.')) }}
      </div>
    </footer>

    @php
      $mobileNavRoute = request()->route()?->getName();
      $mobileNavActiveTab = match (true) {
          $mobileNavRoute === 'tenant.home' => 'home',
          $mobileNavRoute === 'tenant.storefront.favorites' => 'favorites',
          $mobileNavRoute === 'tenant.storefront.profile' && request('tab', 'orders') === 'orders' => 'orders',
          $mobileNavRoute === 'tenant.storefront.profile' => 'profile',
          $mobileNavRoute === 'tenant.storefront.cart' => 'cart',
          default => null,
      };
    @endphp

    <!-- ============ MOBILE BOTTOM NAV ============ -->
    <nav
      class="lg:hidden fixed bottom-0 inset-x-0 z-30 flex flex-row justify-between items-center px-[24px] h-[80px]"
      style="background: var(--color-bg-main); box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.08)"
      aria-label="{{ __('Primary') }}"
    >
      <a
        href="{{ route('tenant.home') }}"
        class="flex flex-col items-center justify-center gap-[4px] py-[8px] px-[8px] rounded-[16px]"
        style="color: {{ $mobileNavActiveTab === 'home' ? 'var(--color-brand-pink)' : 'var(--color-gray)' }}"
        {{ $mobileNavActiveTab === 'home' ? 'aria-current=page' : '' }}
      >
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 3.6c-.45 0-.9.14-1.28.42l-6 4.5c-.6.45-.95 1.16-.95 1.92v7.06c0 1.1.9 2 2 2h2.5a1 1 0 0 0 1-1v-3.5a1.73 1.73 0 0 1 3.46 0v3.5a1 1 0 0 0 1 1h2.5c1.1 0 2-.9 2-2v-7.06c0-.76-.35-1.47-.95-1.92l-6-4.5A2.15 2.15 0 0 0 12 3.6Z"
            fill="currentColor"
          />
        </svg>
        <span
          class="{{ $mobileNavActiveTab === 'home' ? 'font-medium text-[14px]' : 'font-normal text-[12px]' }} tracking-[0.5px]"
          >{{ __('Home') }}</span
        >
      </a>

      <a
        href="{{ route('tenant.storefront.favorites') }}"
        class="flex flex-col items-center justify-center gap-[4px]"
        style="color: {{ $mobileNavActiveTab === 'favorites' ? 'var(--color-brand-pink)' : 'var(--color-gray)' }}"
        {{ $mobileNavActiveTab === 'favorites' ? 'aria-current=page' : '' }}
      >
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12.62 20.81c-.34.12-.9.12-1.24 0C8.48 19.82 2 15.69 2 8.69 2 5.6 4.49 3.1 7.56 3.1c1.82 0 3.43.88 4.44 2.24 1.01-1.36 2.63-2.24 4.44-2.24C19.51 3.1 22 5.6 22 8.69c0 7-6.48 11.13-9.38 12.12Z"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <span
          class="{{ $mobileNavActiveTab === 'favorites' ? 'font-medium text-[14px]' : 'font-normal text-[12px]' }} tracking-[0.5px]"
          >{{ __('Favorites') }}</span
        >
      </a>

      <a
        href="{{ route('tenant.storefront.profile', ['tab' => 'orders']) }}"
        class="flex flex-col items-center justify-center gap-[4px]"
        style="color: {{ $mobileNavActiveTab === 'orders' ? 'var(--color-brand-pink)' : 'var(--color-gray)' }}"
        {{ $mobileNavActiveTab === 'orders' ? 'aria-current=page' : '' }}
      >
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M3.71 5.4h15.214c1.378 0 2.373 1.27 1.995 2.548l-1.654 5.6c-.255.86-1.069 1.452-1.995 1.452H8.112c-.927 0-1.741-.592-1.996-1.452L3.71 5.4ZM3.71 5.4 3 3M16.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM8.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <span
          class="{{ $mobileNavActiveTab === 'orders' ? 'font-medium text-[14px]' : 'font-normal text-[12px]' }} tracking-[0.5px]"
          >{{ __('Orders') }}</span
        >
      </a>

      <a
        href="{{ route('tenant.storefront.profile', ['tab' => 'profile']) }}"
        class="flex flex-col items-center justify-center gap-[4px]"
        style="color: {{ $mobileNavActiveTab === 'profile' ? 'var(--color-brand-pink)' : 'var(--color-gray)' }}"
        {{ $mobileNavActiveTab === 'profile' ? 'aria-current=page' : '' }}
      >
        <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Z"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M20.59 22c0-3.87-3.85-7-8.59-7s-8.59 3.13-8.59 7"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <span
          class="{{ $mobileNavActiveTab === 'profile' ? 'font-medium text-[14px]' : 'font-normal text-[12px]' }} tracking-[0.5px]"
          >{{ __('Profile') }}</span
        >
      </a>

      <a
        href="{{ route('tenant.storefront.cart') }}"
        class="flex flex-col items-center justify-center gap-[4px]"
        style="color: {{ $mobileNavActiveTab === 'cart' ? 'var(--color-brand-pink)' : 'var(--color-gray)' }}"
        {{ $mobileNavActiveTab === 'cart' ? 'aria-current=page' : '' }}
      >
        <div class="relative size-[24px]">
          <svg class="size-[24px]" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path
              d="M6.5 8h11l.9 11.1a2 2 0 0 1-2 2.15H7.6a2 2 0 0 1-2-2.15L6.5 8Z"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M9 8V6.5a3 3 0 0 1 6 0V8"
              stroke="currentColor"
              stroke-width="1.6"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          <span
            class="absolute -top-[6px] -right-[10px] min-w-[18px] h-[16px] px-[3px] flex items-center justify-center rounded-full text-[10px] leading-none"
            style="background: var(--color-brand-pink); color: var(--color-bg-main); box-shadow: 0px 2px 1.1px rgba(0, 0, 0, 0.15)"
            >{{ $cartCount }}</span
          >
        </div>
        <span
          class="{{ $mobileNavActiveTab === 'cart' ? 'font-medium text-[14px]' : 'font-normal text-[12px]' }} tracking-[0.5px]"
          >{{ __('Cart') }}</span
        >
      </a>
    </nav>

@vite(['resources/css/elora/footer-v3.css', 'resources/js/elora/footer-v3.js'])
