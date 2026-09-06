{{-- Expects $p: url, image, name, weight, desc, rating (float 0-5, real backend
     average), ratingLabel, price, oldPrice, discount, delivered, stockLeft,
     badgeLabel, badgeBg, badgeText --}}
@php
  $filledStars = (int) round(min(5, max(0, (float) ($p['rating'] ?? 0))));
  $starClip = 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)';
@endphp
<a
  href="{{ $p['url'] ?? '#' }}"
  class="flex flex-col items-start w-full h-full rounded-[6px] lg:rounded-[8px] shadow-[var(--shadow-card-lg)] no-underline box-border"
  style="background: var(--color-bg-main); font-family: 'Outfit', sans-serif"
>
  {{-- Media --}}
  <div class="relative flex flex-col justify-end items-end p-[4px_4px] lg:p-[5px_6px] gap-[5px] lg:gap-[8px] w-full h-[115px] lg:h-[183px] isolate overflow-hidden box-border">
    <img
      src="{{ $p['image'] }}"
      alt="{{ $p['name'] }}"
      class="absolute inset-0 z-0 w-full h-full object-cover rounded-t-[6px] lg:rounded-t-[8px]"
    />

    @if (!empty($p['badgeLabel']))
      <span
        class="absolute top-[4px] right-[4px] lg:top-[6px] lg:right-[6px] z-[1] flex items-center justify-center whitespace-nowrap px-[4px] lg:px-[6px] h-[15px] lg:h-[28px] rounded-tr-[4px] lg:rounded-tr-[8px] rounded-bl-[4px] lg:rounded-bl-[8px] font-normal text-[8px] lg:text-[14px] leading-[10px] lg:leading-[18px] tracking-[0.3px] lg:tracking-[0.5px]"
        style="background: {{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color: {{ $p['badgeText'] ?? 'var(--color-black)' }}"
      >{{ $p['badgeLabel'] }}</span>
    @endif

    <button
      type="button"
      aria-label="Add to favorites"
      class="absolute top-[4px] left-[4px] lg:top-[6px] lg:left-[6px] z-[1] flex items-center justify-center p-[5px] lg:p-[8px] size-[20px] lg:size-[32px] rounded-full border-0"
      style="background: var(--color-white); box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.15)"
    >
      <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" alt="" class="size-[13px] lg:size-[20px]" />
    </button>

    <div
      class="relative z-[1] flex items-center justify-center px-[8px] lg:px-[12px] py-[3px] lg:py-[4px] w-[36px] lg:w-[57px] h-[28px] lg:h-[45px] rounded-[10px] lg:rounded-[16px]"
      style="background: var(--color-bg-main)"
    >
      <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="Add to cart" class="size-[15px] lg:size-[24px]" />
    </div>
  </div>

  {{-- Body --}}
  <div class="flex flex-col items-start p-[4px] lg:p-[8px] gap-[4px] lg:gap-[8px] w-full flex-1 min-h-0 box-border">
    <div class="flex flex-col items-start gap-[2px] lg:gap-[4px] w-full">
      <div class="flex flex-row items-center justify-between gap-[4px] lg:gap-[2px] w-full">
        <p
          class="m-0 truncate min-w-0 font-medium text-[12px] lg:text-[16px] leading-[15px] lg:leading-[20px] tracking-[0.31px] lg:tracking-[0.5px]"
          style="color: var(--color-text-primary)"
        >{{ $p['name'] }}</p>
        @if (!empty($p['weight']))
          <p
            class="shrink-0 whitespace-nowrap font-normal text-[10px] lg:text-[14px] leading-[16px] lg:leading-[25px] tracking-[0.31px] lg:tracking-[0.5px]"
            style="color: var(--color-primary)"
          >{{ $p['weight'] }}</p>
        @endif
      </div>
      @if (!empty($p['desc']))
        <p
          class="w-full truncate font-normal text-[10px] lg:text-[14px] leading-[13px] lg:leading-[18px] tracking-[0.31px] lg:tracking-[0.5px]"
          style="color: var(--color-text-subtitle)"
        >{{ $p['desc'] }}</p>
      @endif
    </div>

    <div class="flex flex-col items-start gap-[2px] lg:gap-[4px]">
      <div class="flex flex-row items-center gap-[5px] lg:gap-[8px]">
        <div class="flex flex-row items-start gap-[2.51px] lg:gap-[4px]">
          @for ($i = 0; $i < 5; $i++)
            <span
              class="block shrink-0 w-[6.6px] h-[6.28px] lg:w-[10.5px] lg:h-[10px]"
              style="background: {{ $i < $filledStars ? '#FFE100' : 'var(--color-stroke)' }}; clip-path: {{ $starClip }}"
            ></span>
          @endfor
        </div>
        @if (!empty($p['ratingLabel']))
          <span
            class="whitespace-nowrap font-normal text-[8px] lg:text-[12px] leading-[10px] lg:leading-[15px] tracking-[0.3px] lg:tracking-[0.5px]"
            style="color: var(--color-text-subtitle)"
          >{{ $p['ratingLabel'] }}</span>
        @endif
      </div>

      <div class="flex flex-row items-end gap-[5px] lg:gap-[8px]">
        <p
          class="whitespace-nowrap font-medium text-[12px] lg:text-[18px] leading-[15px] lg:leading-[23px]"
          style="color: var(--color-text-primary)"
        >{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <span
            class="whitespace-nowrap font-light line-through text-[8.8px] lg:text-[14px] leading-[11px] lg:leading-[18px]"
            style="color: var(--color-text-subtitle)"
          >{{ $p['oldPrice'] }}</span>
        @endif
        @if (!empty($p['discount']))
          <p
            class="whitespace-nowrap font-normal text-[8px] lg:text-[12px] leading-[10px] lg:leading-[15px] tracking-[0.3px] lg:tracking-[0.5px]"
            style="color: var(--color-secondary)"
          >{{ $p['discount'] }}</p>
        @endif
      </div>

      @if (!empty($p['stockLeft']))
        <div class="flex flex-row items-center gap-[4px] w-full min-w-0">
          <img src="{{ asset('elora-1/assets/icons/cart-x.svg') }}" alt="" class="block lg:hidden shrink-0 w-[12px] h-[12px]" />
          <img src="{{ asset('elora-1/assets/icons/cart-x-green.svg') }}" alt="" class="hidden lg:block shrink-0 size-[16px]" />
          <p
            class="truncate whitespace-nowrap font-medium text-[8px] lg:text-[12px] leading-[10px] lg:leading-[15px] text-[var(--color-error)] lg:text-[var(--color-success)]"
          >{{ $p['stockLeft'] }}</p>
        </div>
      @elseif (!empty($p['delivered']))
        <div class="flex flex-row items-center gap-[4px] w-full min-w-0">
          <img src="{{ asset('elora-1/assets/icons/truck-delivery.svg') }}" alt="" class="block lg:hidden shrink-0 w-[12px] h-[12px]" />
          <img src="{{ asset('elora-1/assets/icons/truck-delivery-green.svg') }}" alt="" class="hidden lg:block shrink-0 size-[18px]" />
          <p
            class="truncate whitespace-nowrap font-medium text-[8px] lg:text-[12px] leading-[10px] lg:leading-[15px] text-[var(--color-error)] lg:text-[var(--color-success)]"
          >{{ $p['delivered'] }}</p>
        </div>
      @endif
    </div>
  </div>
</a>
