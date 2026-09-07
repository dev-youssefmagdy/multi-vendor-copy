{{-- ============ FLASH SALE STRIP — Elora v4 ============
     Figma desktop: 16:10591 | mobile: 16:7485 (mobile is a separate "card
     effect" single-card carousel, not the desktop 2x3 grid)
     Desktop: Pure JS/CSS slider — no Swiper. Mobile: Swiper.
======================================================== --}}
@php
  $flashSaleProducts = ($flashProducts ?? collect())
    ->map(fn($product) => $product->toEloraV4Card($currentCurrency ?? null));

  $flashSaleMinPrice = ($flashProducts ?? collect())
    ->map(fn($product) => $product->storefrontPricing()['current_price'])
    ->filter()->min();

  $symbol = data_get($currentCurrency ?? null, 'symbol', '$');
  $rate   = (float) data_get($currentCurrency ?? null, 'conversion_rate', 1.0);

  $flashSaleMinPriceLabel = $flashSaleMinPrice !== null
    ? $symbol . number_format($flashSaleMinPrice * $rate, 2)
    : null;

  $flashSaleDiscountPct = ($flashSales ?? collect())->max('discount_percentage');

  $flashEndsAt    = optional(($flashSales ?? collect())->first())->end_date;
  $flashEndsAtIso = $flashEndsAt ? $flashEndsAt->toIso8601String() : null;

  $flashId = 'flash-' . uniqid();
  $firstFlashProduct = $flashSaleProducts->first();
@endphp

@if ($flashSaleProducts->isNotEmpty())
<section
  class="texture-bg texture-hard ps-[16px] lg:ps-[56px] py-[24px] lg:py-[40px] flex flex-col gap-[24px] lg:gap-[28px]"
  aria-label="{{ __('Flash Sale') }}"
  data-flash-countdown="{{ $flashEndsAtIso }}"
>
  <img src="{{ asset('elora-4/assets/images/flash-sale-texture.png') }}" alt="" class="texture-overlay" aria-hidden="true" />

  {{-- ══════════════════ DESKTOP ══════════════════ --}}
  <div class="relative hidden lg:flex lg:flex-row items-center gap-[32px] w-full">

    {{-- LEFT: illustration + badge + countdown ──────────────── --}}
    <div class="relative flex flex-col items-start gap-[20px] shrink-0">

      {{-- Illustration + badge --}}
      <div class="relative">
        <img
          src="{{ asset('elora-4/assets/images/flash-sale-illustration.png') }}"
          alt="{{ __('Flash Sale') }}"
          class="w-[283px] h-auto"
        />
        <div
          class="absolute top-[14px] left-[10px]
                 flex items-center justify-center
                 h-[62px] w-[130px]
                 rounded-full border-2 border-white"
          style="background: var(--color-brand-orange)"
        >
          <span class="font-normal text-[22px] text-white tracking-[0.5px] whitespace-nowrap">
            {{ $flashSaleDiscountPct ? round($flashSaleDiscountPct) . '% ' . __('Off') : __('Flash Sale') }}
          </span>
        </div>
      </div>

      {{-- Countdown timer ──────────────────────────────────── --}}
      <div
        class="flex items-center gap-[5px]"
        aria-label="{{ __('Flash sale ends in') }}"
      >
        <span
          class="text-white text-[17px] tracking-[0.8px] font-normal whitespace-nowrap leading-none"
          style="writing-mode: vertical-rl; transform: rotate(180deg);"
          aria-hidden="true"
        >{{ __('Ends in') }}</span>

        <div class="flex items-center justify-center h-[73px] w-[76px] rounded-[11px] border-2 border-white shrink-0" style="background: var(--color-brand-orange-bright)" role="timer" aria-label="{{ __('Hours') }}">
          <span class="font-semibold text-[40px] text-white leading-none tabular-nums" data-flash-hh>00</span>
        </div>
        <div class="flex items-center justify-center h-[73px] w-[76px] rounded-[11px] border-2 border-white shrink-0" style="background: var(--color-brand-orange-bright)" role="timer" aria-label="{{ __('Minutes') }}">
          <span class="font-semibold text-[40px] text-white leading-none tabular-nums" data-flash-mm>00</span>
        </div>
        <div class="flex items-center justify-center h-[73px] w-[76px] rounded-[11px] border-2 border-white shrink-0" style="background: var(--color-brand-orange-bright)" role="timer" aria-label="{{ __('Seconds') }}">
          <span class="font-semibold text-[40px] text-white leading-none tabular-nums" data-flash-ss>00</span>
        </div>
      </div>

    </div>{{-- /left --}}

    {{-- RIGHT: pure JS slider ────────────────────────────────── --}}
    <div class="relative flex-1 min-w-0 w-full" data-flash-slider="{{ $flashId }}">

      {{-- Viewport (clips overflow) --}}
      <div class="flash-viewport" id="{{ $flashId }}">
        {{-- Track — scrolls horizontally, cards wrap into 3-row columns --}}
        <div class="flash-track" id="{{ $flashId }}-track">
          @foreach ($flashSaleProducts as $p)
            <div class="flash-slide">
              @include('themes.elora.pages.home-v4.sections.partials.flash_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>

      {{-- "Only $X.XX" price pill --}}
      @if ($flashSaleMinPriceLabel)
        <div class="absolute bottom-[-20px] left-0 z-10 bg-white border-black rounded-full h-[47.54px] flex items-center justify-center px-[24px]" style="border-width: 1.49px; border-style: solid;">
          <p class="font-bold text-[20px] whitespace-nowrap" style="color: var(--color-brand-orange)">
            {{ __('Only :price', ['price' => $flashSaleMinPriceLabel]) }}
          </p>
        </div>
      @endif

    </div>{{-- /right --}}

  </div>{{-- /desktop --}}

  {{-- ══════════════════ MOBILE — "card effect" carousel ══════════════════ --}}
  <div class="lg:hidden flex flex-col items-center gap-[12px]">

    <img
      src="{{ asset('elora-4/assets/images/flash-sale-illustration.png') }}"
      alt="{{ __('Flash Sale') }}"
      class="w-[220px] h-auto"
    />

    {{-- Countdown timer — white boxes / orange border+text on mobile.
         relative + z-1: the absolutely-positioned .texture-overlay image
         paints above normal-flow siblings regardless of DOM order (a
         positioned element always stacks over a non-positioned one), which
         was tinting these white boxes a washed-out yellow. --}}
    <div class="relative z-[1] flex items-center gap-[3.4px]" aria-label="{{ __('Flash sale ends in') }}">
      <span
        class="text-black text-[13.6px] tracking-[0.57px] font-semibold whitespace-nowrap leading-none"
        style="writing-mode: vertical-rl; transform: rotate(180deg);"
        aria-hidden="true"
      >{{ __('Ends in') }}</span>

      <div class="flex items-center justify-center h-[49.87px] w-[52.13px] rounded-[7.93px] border shrink-0" style="background: #FFFFFF; border-color: var(--color-brand-orange)" role="timer" aria-label="{{ __('Hours') }}">
        <span class="font-semibold text-[27.2px] leading-none tabular-nums" style="color: var(--color-brand-orange)" data-flash-hh>00</span>
      </div>
      <div class="flex items-center justify-center h-[49.87px] w-[52.13px] rounded-[7.93px] border shrink-0" style="background: #FFFFFF; border-color: var(--color-brand-orange)" role="timer" aria-label="{{ __('Minutes') }}">
        <span class="font-semibold text-[27.2px] leading-none tabular-nums" style="color: var(--color-brand-orange)" data-flash-mm>00</span>
      </div>
      <div class="flex items-center justify-center h-[49.87px] w-[52.13px] rounded-[7.93px] border shrink-0" style="background: #FFFFFF; border-color: var(--color-brand-orange)" role="timer" aria-label="{{ __('Seconds') }}">
        <span class="font-semibold text-[27.2px] leading-none tabular-nums" style="color: var(--color-brand-orange)" data-flash-ss>00</span>
      </div>
    </div>

    {{-- Card-effect carousel: one product fully visible, a static card
         peeks from behind it, floating badge + price pill overlap its
         corners and swap to match the active product. --}}
    <div class="relative z-[1] w-[262px] h-[191px] mt-[24px]">
      <div
        class="absolute inset-0 rounded-[16px]"
        style="background: #F4F4F4; box-shadow: 0 0 40px rgba(0,0,0,0.12); transform: rotate(-6deg) translate(6px, 4px)"
        aria-hidden="true"
      ></div>

      <div class="swiper flash-mobile-swiper" style="box-shadow: 0 0 40px rgba(0,0,0,0.12)">
        <div class="swiper-wrapper" id="flashMobileWrapper">
          @foreach ($flashSaleProducts as $p)
            <div class="swiper-slide" data-discount="{{ $p['discount'] }}" data-price="{{ $p['price'] }}">
              <a href="{{ $p['url'] ?? '#' }}" class="relative block w-full h-full overflow-hidden rounded-[16px]">
                <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 w-full h-full object-cover" />
              </a>
            </div>
          @endforeach
        </div>
      </div>

      @if ($firstFlashProduct && !empty($firstFlashProduct['discount']))
        <span
          id="flashMobileBadge"
          class="absolute -top-[10px] -right-[14px] z-10 flex items-center justify-center px-[10px] h-[32px] rounded-[30px] border border-white text-[14px] font-normal text-white whitespace-nowrap"
          style="background: var(--color-brand-orange)"
        >{{ $firstFlashProduct['discount'] }}</span>
      @endif

      @if ($firstFlashProduct)
        <span
          id="flashMobilePrice"
          class="absolute -bottom-[10px] -left-[10px] z-10 flex items-center justify-center px-[10px] h-[32px] rounded-[30px] border border-black bg-white text-[14px] font-bold whitespace-nowrap"
          style="color: var(--color-brand-orange)"
          data-template="{{ __('Only :price', ['price' => '%s']) }}"
        >{{ __('Only :price', ['price' => $firstFlashProduct['price']]) }}</span>
      @endif
    </div>

  </div>{{-- /mobile --}}

  {{-- "Explore all" CTA (shared) --}}
  <div class="flex items-center justify-center pt-[8px] lg:pt-[4px]">
    <a
      href="{{ route('tenant.storefront.category') }}"
      class="border border-white rounded-full h-[38px] lg:h-[64px] w-[121px] lg:w-auto px-[8px] lg:px-[48px] gap-[8px] lg:gap-0 flex items-center justify-center font-medium text-white text-[14px] lg:text-[20px] tracking-[0.5px] opacity-85 transition-opacity duration-150 hover:opacity-75"
      style="background: var(--color-brand-orange)"
    >
      {{ __('Explore all') }}
    </a>
  </div>

</section>
@endif
