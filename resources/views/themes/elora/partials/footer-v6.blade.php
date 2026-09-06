    <!-- ============ FOOTER ============ -->
    <footer style="background: var(--color-footer-bg)">
      <div
        class="px-[24px] lg:px-[56px] py-[40px] lg:py-[46px] flex flex-col lg:flex-row gap-[40px] lg:gap-[120px] max-w-[1440px] mx-auto"
      >
        <div class="flex flex-col gap-[32px] lg:justify-between shrink-0">
          <div class="flex flex-col gap-[32px]">
            <a href="{{ route('tenant.home') }}">
              <x-storefront-logo :storeName="$storeName" class="h-[32px] w-auto" />
            </a>
            <div class="flex flex-col gap-[16px]">
              <p class="font-medium text-[16px] text-white">
                Connect with {{ $storeName }}
              </p>
              @if (isset($socialLinks) && $socialLinks->isNotEmpty())
                <div class="flex gap-[16px] items-center">
                  @foreach ($socialLinks as $link)
                    @php
                      $socialIcon = match ($link->icon?->name) {
                          'facebook' => 'social-facebook.svg',
                          'twitter' => 'social-twitter.svg',
                          'instagram' => 'social-instagram.svg',
                          'youtube' => 'social-youtube.svg',
                          'linkedin' => 'social-linkedin.svg',
                          default => 'social-facebook.svg',
                      };
                    @endphp
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer">
                      <img
                        src="{{ asset('elora-2/assets/icons/' . $socialIcon) }}"
                        alt="{{ ucfirst($link->icon?->name ?? 'social') }}"
                        class="size-[24px]"
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
                src="{{ asset('elora-2/assets/icons/pay-visa.png') }}"
                alt="Visa"
                class="h-[37px] w-[56px] object-cover rounded"
              />
              <img
                src="{{ asset('elora-2/assets/icons/pay-mastercard.png') }}"
                alt="Mastercard"
                class="h-[37px] w-[56px] object-cover rounded"
              />
              <img
                src="{{ asset('elora-2/assets/icons/pay-applepay.png') }}"
                alt="Apple Pay"
                class="h-[37px] w-[56px] object-cover rounded"
              />
              <img
                src="{{ asset('elora-2/assets/icons/pay-fawry.png') }}"
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
            <p class="font-medium text-[20px] text-white">Customer service</p>
            <div
              class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px]"
              style="color: var(--color-footer-text-muted)"
            >
              <a href="{{ route('tenant.storefront.page', 'return-refund-policy') }}">Return and refund policy</a>
              <a href="{{ route('tenant.storefront.page', 'intellectual-property-policy') }}">Intellectual property policy</a>
              <a href="{{ route('tenant.storefront.page', 'shipping-info') }}">Shipping info</a>
              <a href="{{ route('tenant.storefront.page', 'report-suspicious-activity') }}">Report suspicious activity</a>
            </div>
          </div>
          <div class="flex flex-col gap-[26px]">
            <p class="font-medium text-[20px] text-white">Policies</p>
            <div
              class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px]"
              style="color: var(--color-footer-text-muted)"
            >
              <a href="{{ route('tenant.storefront.page', 'shipping-info') }}">Shipping</a>
              <a href="{{ route('tenant.storefront.page', 'payment-info') }}">Payment</a>
              <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}">Privacy</a>
            </div>
          </div>
          <div class="flex flex-col gap-[26px]">
            <p class="font-medium text-[20px] text-white">Company info</p>
            <div
              class="flex flex-col gap-[16px] text-[14px] tracking-[0.5px] text-white"
            >
              <a href="{{ route('tenant.storefront.page', 'about-us') }}">About {{ $storeName }}</a>
              <a href="{{ route('tenant.storefront.page', 'contact-us') }}">Contact us</a>
              <a href="{{ route('tenant.storefront.page', 'faqs') }}">FAQs</a>
            </div>
          </div>
        </div>
      </div>

      <div
        class="flex flex-wrap items-center justify-center gap-[24px] lg:gap-[88px] py-[15px] text-[14px] tracking-[0.5px] text-white"
        style="background: var(--color-footer-subbar)"
      >
        <a href="{{ route('tenant.storefront.page', 'terms-of-use') }}">Terms of use</a>
        <a href="{{ route('tenant.storefront.page', 'privacy-policy') }}">Privacy policy</a>
        <a href="{{ route('tenant.storefront.page', 'privacy-choices') }}">Your privacy choices</a>
        <a href="{{ route('tenant.storefront.page', 'support') }}">Support</a>
        <a href="{{ route('tenant.storefront.page', 'faqs') }}">FAQ</a>
      </div>
      <div
        class="flex items-center justify-center py-[18px] text-[14px] tracking-[0.5px] text-white text-center"
        style="background: var(--color-footer-bar)"
      >
        {{ $footerCopyright ?? (__('Copyright ©') . date('Y') . ' ' . $storeName . '. ' . __('All Rights Reserved.')) }}
      </div>
    </footer>

@vite(['resources/css/elora/footer-v6.css', 'resources/js/elora/footer-v6.js'])
