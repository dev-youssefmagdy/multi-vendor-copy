{{-- ============ CATEGORY CIRCLES — Elora v4 ============
     Figma desktop: 16:10554 | mobile: 16:7464
     Signature effect: orange pill + product image floating above it
============================================================ --}}
@if ($categories->isNotEmpty())
<section
  class="px-[16px] lg:px-[56px] py-[24px] lg:py-[57px] flex flex-col gap-[24px] lg:gap-[57px]"
  style="background: var(--color-bg-main)"
  wire:ignore
>

  {{-- ── Header row ─────────────────────────────────────────────── --}}
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[18px] lg:text-[32px] text-black leading-none whitespace-nowrap">
      {{ __('Categories') }}
    </h2>
    <a
      href="{{ route('tenant.storefront.category') }}"
      class="font-normal text-[12px] lg:text-[24px] tracking-[0.5px] whitespace-nowrap"
      style="color: var(--color-brand-orange-bright)"
    >{{ __('see all') }}</a>
  </div>

  {{-- ── Category grid ───────────────────────────────────────────── --}}
  {{--
    Mobile: 4 items, justify-between
    Desktop: up to 8 items, justify-between
    Each item: pill + floating image above + name below
  --}}
  <div class="flex items-end justify-center gap-[16px] lg:gap-[32px]">
    @foreach ($categories->take(8) as $cat)
      @php
        $name = $cat->translationValue('name') ?? $cat->name;
        $name = \Illuminate\Support\Str::limit($name, 15);
        $url  = route('tenant.storefront.category', $cat->slug);
        $img  = $cat->thumb_url ?? null;

        // Hide items 5-8 on mobile (4 items only)
        $hideMobile = $loop->index >= 4 ? 'hidden lg:flex' : 'flex';
      @endphp

      <a
        href="{{ $url }}"
        class="{{ $hideMobile }} flex-col items-center gap-[2px] lg:gap-[3.9px] min-w-0 shrink-0"
        style="text-decoration:none"
      >

        {{-- Pill + floating image wrapper --}}
        {{-- Extra top padding reserves space for the overflowing image --}}
        <div class="relative pt-[23px] lg:pt-[44px]">

          {{-- Orange leaf pill --}}
          <div
            class="w-[66px] h-[37px] lg:w-[128.17px] lg:h-[71.86px]
                   rounded-bl-[32px] rounded-tr-[32px]
                   lg:rounded-bl-[62.14px] lg:rounded-tr-[62.14px]
                   overflow-visible"
            style="background: var(--color-brand-orange)"
          ></div>

          {{-- Product image — floats above the pill --}}
          <div class="absolute inset-x-0 bottom-0 flex items-end justify-center pb-0 pointer-events-none"
               style="top: 0">
            @if ($img)
              <img
                loading="lazy"
                src="{{ $img }}"
                alt="{{ $name }}"
                class="w-[52px] h-[52px] lg:w-[100px] lg:h-[100px] object-contain drop-shadow-sm"
                style="margin-bottom: 4px"
              />
            @else
              {{-- Emoji fallback, centered over pill --}}
              <span class="text-[26px] lg:text-[48px] leading-none" style="margin-bottom:6px">🛍️</span>
            @endif
          </div>

        </div>

        {{-- Category name --}}
        <p class="font-semibold text-[12px] lg:text-[23.3px]
                  leading-[1.5] tracking-[0.5px] lg:tracking-[0.97px]
                  text-black text-center whitespace-nowrap line-clamp-1
                  mt-0">
          {{ $name }}
        </p>

      </a>
    @endforeach
  </div>

</section>
@endif
