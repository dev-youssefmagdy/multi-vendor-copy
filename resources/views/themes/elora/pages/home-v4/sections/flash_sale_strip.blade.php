{{-- ============ FLASH SALE STRIP — Elora v4 ============
     Figma desktop: 16:10591 | mobile: 16:7485
     Pure JS/CSS slider — no Swiper
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
@endphp

@if ($flashSaleProducts->isNotEmpty())
<section
  class="texture-bg texture-hard px-[16px] lg:px-[56px] py-[24px] lg:py-[40px] flex flex-col gap-[24px] lg:gap-[28px]"
  aria-label="{{ __('Flash Sale') }}"
>
  <img src="{{ asset('elora-4/assets/images/flash-sale-texture.png') }}" alt="" class="texture-overlay" aria-hidden="true" />

  {{-- ── Main row ─────────────────────────────────────────────── --}}
  <div class="relative flex flex-col lg:flex-row items-center gap-[20px] lg:gap-[32px] w-full">

    {{-- LEFT: illustration + badge + countdown ──────────────── --}}
    <div class="relative flex flex-col items-start gap-[16px] lg:gap-[20px] shrink-0">

      {{-- Illustration + badge --}}
      <div class="relative">
        <img
          src="{{ asset('elora-4/assets/images/flash-sale-illustration.png') }}"
          alt="{{ __('Flash Sale') }}"
          class="w-[160px] lg:w-[283px] h-auto"
        />
        <div
          class="absolute top-[6px] left-[4px] lg:top-[14px] lg:left-[10px]
                 flex items-center justify-center
                 h-[34px] w-[72px] lg:h-[62px] lg:w-[130px]
                 rounded-full border-2 border-white"
          style="background: var(--color-brand-orange)"
        >
          <span class="font-normal text-[13px] lg:text-[22px] text-white tracking-[0.5px] whitespace-nowrap">
            {{ $flashSaleDiscountPct ? round($flashSaleDiscountPct) . '% ' . __('Off') : __('Flash Sale') }}
          </span>
        </div>
      </div>

      {{-- Countdown timer ──────────────────────────────────── --}}
      <div
        class="flex items-center gap-[8px] lg:gap-[10px]"
        aria-label="{{ __('Flash sale ends in') }}"
        data-flash-countdown="{{ $flashEndsAtIso }}"
      >
        <span
          class="text-white text-[11px] lg:text-[17px] tracking-[0.8px] font-normal whitespace-nowrap leading-none"
          style="writing-mode: vertical-rl; transform: rotate(180deg);"
          aria-hidden="true"
        >{{ __('Ends in') }}</span>

        <div class="flex items-center justify-center h-[48px] w-[50px] lg:h-[73px] lg:w-[76px] rounded-[11px] border-2 border-white shrink-0" style="background: var(--color-brand-orange-bright)" role="timer" aria-label="{{ __('Hours') }}">
          <span class="font-semibold text-[26px] lg:text-[40px] text-white leading-none tabular-nums" data-flash-hh>00</span>
        </div>
        <span class="text-white font-bold text-[20px] lg:text-[32px] leading-none select-none" aria-hidden="true">:</span>
        <div class="flex items-center justify-center h-[48px] w-[50px] lg:h-[73px] lg:w-[76px] rounded-[11px] border-2 border-white shrink-0" style="background: var(--color-brand-orange-bright)" role="timer" aria-label="{{ __('Minutes') }}">
          <span class="font-semibold text-[26px] lg:text-[40px] text-white leading-none tabular-nums" data-flash-mm>00</span>
        </div>
        <span class="text-white font-bold text-[20px] lg:text-[32px] leading-none select-none" aria-hidden="true">:</span>
        <div class="flex items-center justify-center h-[48px] w-[50px] lg:h-[73px] lg:w-[76px] rounded-[11px] border-2 border-white shrink-0" style="background: var(--color-brand-orange-bright)" role="timer" aria-label="{{ __('Seconds') }}">
          <span class="font-semibold text-[26px] lg:text-[40px] text-white leading-none tabular-nums" data-flash-ss>00</span>
        </div>
      </div>

    </div>{{-- /left --}}

    {{-- RIGHT: pure JS slider ────────────────────────────────── --}}
    <div class="relative flex-1 min-w-0 w-full" data-flash-slider="{{ $flashId }}">

      {{-- Viewport (clips overflow) --}}
      <div class="flash-viewport" id="{{ $flashId }}">
        {{-- Track — scrolls horizontally on desktop, wraps 2-col on mobile --}}
        <div class="flash-track" id="{{ $flashId }}-track">
          @foreach ($flashSaleProducts as $p)
            <div class="flash-slide">
              @include('themes.elora.pages.home-v4.sections.partials.flash_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>

      {{-- Prev / Next arrows (desktop only) --}}
      @if ($flashSaleProducts->count() > 2)
        <button
          type="button"
          class="flash-arrow flash-prev hidden lg:flex"
          data-flash-target="{{ $flashId }}"
          aria-label="{{ __('Previous') }}"
        >
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <button
          type="button"
          class="flash-arrow flash-next hidden lg:flex"
          data-flash-target="{{ $flashId }}"
          aria-label="{{ __('Next') }}"
        >
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      @endif

      {{-- Dot indicators --}}
      <div class="flash-dots" id="{{ $flashId }}-dots"></div>

      {{-- "Only $X.XX" price pill --}}
      @if ($flashSaleMinPriceLabel)
        <div class="absolute bottom-[-20px] left-0 z-10 bg-white border-2 border-black rounded-full h-[40px] lg:h-[48px] flex items-center justify-center px-[18px] lg:px-[24px]">
          <p class="font-bold text-[13px] lg:text-[20px] whitespace-nowrap" style="color: var(--color-brand-orange)">
            {{ __('Only :price', ['price' => $flashSaleMinPriceLabel]) }}
          </p>
        </div>
      @endif

    </div>{{-- /right --}}

  </div>{{-- /main row --}}

  {{-- "Explore all" CTA --}}
  <div class="flex items-center justify-center pt-[8px] lg:pt-[4px]">
    <a
      href="{{ route('tenant.storefront.category') }}"
      class="border border-white rounded-full h-[46px] lg:h-[64px] px-[32px] lg:px-[48px] flex items-center justify-center font-medium text-white text-[15px] lg:text-[20px] tracking-[0.5px] transition-opacity duration-150 hover:opacity-85"
      style="background: var(--color-brand-orange)"
    >
      {{ __('Explore all') }}
    </a>
  </div>

</section>
@endif
