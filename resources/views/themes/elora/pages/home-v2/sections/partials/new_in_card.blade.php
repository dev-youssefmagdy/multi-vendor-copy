{{-- Expects $p: url, image, name, weight, desc, badgeLabel, badgeBg, badgeText,
     rating (float 0-5, real backend average), ratingLabel, price, oldPrice,
     discount, delivered, stockLeft --}}
@php
  $filledStars = (int) round(min(5, max(0, (float) ($p['rating'] ?? 0))));
  $starClip = 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)';
@endphp
<div class="swiper-slide new-in-slide">
  <a
    href="{{ $p['url'] ?? '#' }}"
    class="flex flex-col items-start w-full h-full overflow-hidden"
    style="background: var(--color-bg-main); font-family: 'Outfit', sans-serif"
  >
    {{-- Media --}}
    <div class="relative flex flex-col justify-end items-end p-[3.14px] md:p-[5px_6px] gap-[5.03px] md:gap-[8px] w-full h-[115.03px] md:h-[183px] isolate box-border">
      <img
        src="{{ $p['image'] }}"
        alt="{{ $p['name'] }}"
        class="absolute inset-0 z-0 w-full h-full object-cover rounded-t-[5.03px] md:rounded-t-[8px]"
      />

      @if (!empty($p['badgeLabel']))
        <span
          class="absolute top-[3.14px] right-[3.77px] md:top-[5px] md:right-[6px] z-[1] flex items-center justify-center whitespace-nowrap px-[3.3px] md:px-[6px] w-[39.61px] md:w-[103px] h-[15.43px] md:h-[28px] rounded-tr-[4.41px] md:rounded-tr-[8px] rounded-bl-[4.41px] md:rounded-bl-[8px] font-normal text-[7.71px] md:text-[14px] leading-[10px] md:leading-[18px] tracking-[0.28px] md:tracking-[0.5px]"
          style="background: {{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color: {{ $p['badgeText'] ?? 'var(--color-black)' }}"
        >{{ $p['badgeLabel'] }}</span>
      @endif

      <button
        type="button"
        aria-label="Add to favorites"
        class="absolute top-[3.14px] left-[3.77px] md:top-[5px] md:left-[6px] z-[1] flex items-center justify-center p-[5.03px] md:p-[8px] w-[20.11px] md:w-[32px] h-[20.11px] md:h-[32px] rounded-full border-0"
        style="background: var(--color-white); box-shadow: 0px 2.51px 2.51px rgba(0, 0, 0, 0.15)"
      >
        <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" alt="" class="w-[12.57px] h-[12.57px] md:w-[20px] md:h-[20px]" />
      </button>

      <div
        class="relative z-[1] flex items-center justify-center px-[7.54px] md:px-[12px] py-[2.51px] md:py-[4px] w-[35.83px] md:w-[57px] h-[28.29px] md:h-[45px] rounded-[10.06px] md:rounded-[16px]"
        style="background: var(--color-bg-main)"
      >
        <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="Add to cart" class="w-[15.09px] h-[15.09px] md:w-[24px] md:h-[24px]" />
      </div>
    </div>

    {{-- Body --}}
    <div class="flex flex-col items-start p-[4px] md:p-[8px] gap-[4px] md:gap-[8px] w-full flex-1 min-h-0 box-border">
      <div class="flex flex-col items-start gap-[2.51px] md:gap-[4px] w-full">
        <div class="flex flex-row items-center justify-between gap-[1.26px] md:gap-[2px] w-full">
          <p
            class="m-0 truncate min-w-0 font-medium text-[12px] md:text-[16px] leading-[15px] md:leading-[20px] tracking-[0.31px] md:tracking-[0.5px]"
            style="color: var(--color-text-primary)"
          >{{ $p['name'] }}</p>
          @if (!empty($p['weight']))
            <p
              class="shrink-0 whitespace-nowrap font-normal text-[10px] md:text-[14px] leading-[16px] md:leading-[25px] tracking-[0.31px] md:tracking-[0.5px]"
              style="color: var(--color-primary)"
            >{{ $p['weight'] }}</p>
          @endif
        </div>
        @if (!empty($p['desc']))
          <p
            class="w-full truncate font-normal text-[10px] md:text-[14px] leading-[13px] md:leading-[18px] tracking-[0.31px] md:tracking-[0.5px]"
            style="color: var(--color-text-subtitle)"
          >{{ $p['desc'] }}</p>
        @endif
      </div>

      <div class="flex flex-col items-start gap-[2.51px] md:gap-[4px]">
        <div class="flex flex-row items-center gap-[5.03px] md:gap-[8px]">
          <div class="flex flex-row items-start gap-[2.51px] md:gap-[4px]">
            @for ($i = 0; $i < 5; $i++)
              <span
                class="block shrink-0 w-[6.6px] h-[6.28px] md:w-[10.5px] md:h-[10px]"
                style="background: {{ $i < $filledStars ? '#FFE100' : 'var(--color-stroke)' }}; clip-path: {{ $starClip }}"
              ></span>
            @endfor
          </div>
          @if (!empty($p['ratingLabel']))
            <span
              class="whitespace-nowrap font-normal text-[8px] md:text-[12px] leading-[10px] md:leading-[15px] tracking-[0.31px] md:tracking-[0.5px]"
              style="color: var(--color-text-subtitle)"
            >{{ $p['ratingLabel'] }}</span>
          @endif
        </div>

        <div class="flex flex-row items-end gap-[5.03px] md:gap-[8px]">
          <p
            class="whitespace-nowrap font-medium text-[12px] md:text-[18px] leading-[15px] md:leading-[23px]"
            style="color: var(--color-text-primary)"
          >{{ $p['price'] }}</p>
          @if (!empty($p['oldPrice']))
            <span
              class="whitespace-nowrap font-light line-through text-[8.8px] md:text-[14px] leading-[11px] md:leading-[18px]"
              style="color: var(--color-text-subtitle)"
            >{{ $p['oldPrice'] }}</span>
          @endif
          @if (!empty($p['discount']))
            <p
              class="whitespace-nowrap font-normal text-[8px] md:text-[12px] leading-[10px] md:leading-[15px] tracking-[0.31px] md:tracking-[0.5px]"
              style="color: var(--color-secondary)"
            >{{ $p['discount'] }}</p>
          @endif
        </div>

        @if (!empty($p['stockLeft']))
          <div class="flex flex-row items-center gap-[4px] md:gap-[8px]">
            <img src="{{ asset('elora-1/assets/icons/cart-x.svg') }}" alt="" class="block md:hidden shrink-0 w-[12px] h-[12px]" />
            <img src="{{ asset('elora-1/assets/icons/cart-x-green.svg') }}" alt="" class="hidden md:block shrink-0 md:w-[16px] md:h-[16px]" />
            <span
              class="truncate whitespace-nowrap font-medium text-[8px] md:text-[12px] leading-[10px] md:leading-[15px] text-[var(--color-error)] md:text-[var(--color-success)]"
            >{{ $p['stockLeft'] }}</span>
          </div>
        @elseif (!empty($p['delivered']))
          <div class="flex flex-row items-center gap-[2.51px] md:gap-[4px] w-full">
            <img src="{{ asset('elora-1/assets/icons/truck-delivery.svg') }}" alt="" class="block md:hidden shrink-0 w-[11.31px] h-[11.31px]" />
            <img src="{{ asset('elora-1/assets/icons/truck-delivery-green.svg') }}" alt="" class="hidden md:block shrink-0 md:w-[18px] md:h-[18px]" />
            <span
              class="truncate whitespace-nowrap font-medium text-[7.54px] md:text-[12px] leading-[10px] md:leading-[15px] text-[var(--color-error)] md:text-[var(--color-success)]"
            >{{ $p['delivered'] }}</span>
          </div>
        @endif
      </div>
    </div>
  </a>
</div>
