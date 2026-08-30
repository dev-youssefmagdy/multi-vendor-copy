    @php
      $features = [
        ['icon' => 'icon-feature-truck.svg', 'title' => 'Free Shipping', 'subtitle' => 'Free shipping on all your order'],
        ['icon' => 'icon-feature-headphones.svg', 'title' => 'Customer Support 24/7', 'subtitle' => 'Instant access to Support'],
        ['icon' => 'icon-feature-bag.svg', 'title' => '100% Secure Payment', 'subtitle' => 'We ensure your money is save'],
        ['icon' => 'icon-feature-package.svg', 'title' => 'Money-Back Guarantee', 'subtitle' => '30 Days Money-Back Guarantee'],
      ];
    @endphp
    <section class="overflow-hidden" style="background: var(--color-primary)">
      <div class="hidden lg:flex items-center justify-center gap-[40px] px-[56px] py-[13px]">
        @foreach ($features as $feature)
          <div class="flex items-center gap-[14px]">
            <img src="{{ asset('elora-5/assets/icons/' . $feature['icon']) }}" alt="" class="size-[39px]" />
            <div class="flex flex-col leading-tight">
              <span class="font-semibold text-[16px] text-white whitespace-nowrap">{{ $feature['title'] }}</span>
              <span class="font-normal text-[14px] whitespace-nowrap" style="color: var(--color-stroke)">{{ $feature['subtitle'] }}</span>
            </div>
          </div>
        @endforeach
      </div>
      <div id="featureStripMobile" class="lg:hidden flex items-center justify-center gap-[10px] px-[16px] py-[10px]" data-icon-base="{{ asset('elora-5/assets/icons/') }}/">
        <div class="flex items-center gap-[10px]">
          <img src="{{ asset('elora-5/assets/icons/' . $features[0]['icon']) }}" alt="" class="size-[26px] shrink-0" />
          <div class="flex flex-col leading-tight">
            <span class="font-semibold text-[13px] text-white whitespace-nowrap">{{ $features[0]['title'] }}</span>
            <span class="font-normal text-[11px] whitespace-nowrap" style="color: var(--color-stroke)">{{ $features[0]['subtitle'] }}</span>
          </div>
        </div>
      </div>
    </section>
