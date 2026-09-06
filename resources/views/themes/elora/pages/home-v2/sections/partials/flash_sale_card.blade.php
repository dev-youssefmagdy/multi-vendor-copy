{{-- Expects $p: url, image, name, weight, desc, badge, badgeBg, badgeText,
     rating (float 0-5), ratingLabel, price, oldPrice, discount, delivered --}}
<a
  href="{{ $p['url'] ?? '#' }}"
  class="relative flex flex-col items-start w-full h-full overflow-hidden"
  style="background: var(--color-bg-main); font-family: var(--font-family-base)"
>
  {{-- Media --}}
  <div class="relative w-full h-[137.69px] lg:h-[284.6px] shrink-0">
    <img
      src="{{ $p['image'] }}"
      alt="{{ $p['name'] }}"
      class="absolute inset-0 w-full h-full object-cover rounded-t-[6.02px] lg:rounded-t-[12.44px]"
    />

    @if (!empty($p['badge']))
      <span
        class="absolute top-[3.76px] lg:top-[7.78px] right-[4.51px] lg:right-[9.33px] z-[1] flex items-center justify-center whitespace-nowrap px-[3.96px] lg:px-[8.18px] w-[46.91px] lg:w-[97.36px] h-[18.47px] lg:h-[38.17px] text-[9.23px] lg:text-[19.09px] leading-[12px] lg:leading-[24px] tracking-[0.33px] lg:tracking-[0.68px] rounded-tl-[5.28px] lg:rounded-tl-[10.91px] rounded-br-[5.28px] lg:rounded-br-[10.91px]"
        style="background: {{ $p['badgeBg'] }}; color: {{ $p['badgeText'] }}"
      >{{ $p['badge'] }}</span>
    @endif

    <button
      type="button"
      aria-label="Add to favorites"
      class="absolute top-[3.76px] lg:top-[7.78px] left-[4.51px] lg:left-[9.33px] z-[1] flex items-center justify-center rounded-full p-[6.02px] lg:p-[12.44px]"
      style="background: var(--color-white); box-shadow: var(--shadow-heart-mobile)"
    >
      <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" alt="" class="size-[15.05px] lg:size-[31.1px]" />
    </button>

    <div
      class="absolute z-[1] top-[120.76px] lg:top-[249.61px] right-[4.51px] lg:right-[9.33px] flex items-center justify-center rounded-[12.04px] lg:rounded-[24.88px] px-[9.03px] lg:px-[18.66px] py-[3.01px] lg:py-[6.22px]"
      style="background: var(--color-bg-main)"
    >
      <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="Add to cart" class="size-[18.06px] lg:size-[37.33px]" />
    </div>
  </div>

  {{-- Body --}}
  <div class="flex flex-col items-start gap-[4.79px] lg:gap-[9.9px] p-[4.79px] lg:p-[9.9px] w-full shrink-0">
    <div class="flex flex-col items-start gap-[3.01px] lg:gap-[6.22px] w-full">
      <div class="flex flex-row items-center justify-between gap-[1.5px] lg:gap-[3.11px] w-full">
        <p
          class="font-medium text-[14.36px] lg:text-[29.69px] leading-[18px] lg:leading-[37px] tracking-[0.38px] lg:tracking-[0.78px] truncate min-w-0"
          style="color: var(--color-text-primary)"
        >{{ $p['name'] }}</p>
        @if (!empty($p['weight']))
          <p
            class="shrink-0 whitespace-nowrap text-center font-normal text-[11.97px] lg:text-[24.74px] leading-[19px] lg:leading-[39px] tracking-[0.38px] lg:tracking-[0.78px]"
            style="color: var(--color-primary)"
          >{{ $p['weight'] }}</p>
        @endif
      </div>
      @if (!empty($p['desc']))
        <p
          class="w-full font-normal text-[11.97px] lg:text-[24.74px] leading-[15px] lg:leading-[31px] tracking-[0.38px] lg:tracking-[0.78px]"
          style="color: var(--color-text-subtitle)"
        >{{ $p['desc'] }}</p>
      @endif
    </div>

    <div class="flex flex-col items-start gap-[3.01px] lg:gap-[6.22px]">
      <div class="flex flex-row items-center justify-center gap-[6.02px] lg:gap-[12.44px]">
        <img
          src="{{ asset('elora-1/assets/icons/star-rating.svg') }}"
          alt=""
          class="h-[8px] w-[55px] lg:h-[16px] lg:w-[110px]"
        />
        @if (!empty($p['ratingLabel']))
          <span
            class="whitespace-nowrap text-center font-normal text-[9.58px] lg:text-[19.79px] leading-[12px] lg:leading-[25px] tracking-[0.38px] lg:tracking-[0.78px]"
            style="color: var(--color-text-subtitle)"
          >{{ $p['ratingLabel'] }}</span>
        @endif
      </div>

      <div class="flex flex-row items-end gap-[6.02px] lg:gap-[12.44px]">
        <p
          class="font-medium text-center text-[14.36px] lg:text-[29.69px] leading-[18px] lg:leading-[37px]"
          style="color: var(--color-text-primary)"
        >{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p
            class="font-light text-center line-through text-[10.53px] lg:text-[21.77px] leading-[13px] lg:leading-[27px]"
            style="color: var(--color-text-subtitle)"
          >{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p
            class="whitespace-nowrap text-center font-normal text-[9.58px] lg:text-[19.79px] leading-[12px] lg:leading-[25px] tracking-[0.38px] lg:tracking-[0.78px]"
            style="color: var(--color-secondary)"
          >{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>

    @if (!empty($p['delivered']))
      <div class="flex flex-row items-center gap-[4.79px] lg:gap-[9.9px] w-full">
        <img src="{{ asset('elora-1/assets/icons/truck-delivery.svg') }}" alt="" class="size-[14.36px] lg:size-[29.69px] shrink-0" />
        <p
          class="whitespace-nowrap font-medium text-[9.58px] lg:text-[19.79px] leading-[12px] lg:leading-[25px] truncate min-w-0"
          style="color: var(--color-success)"
        >{{ $p['delivered'] }}</p>
      </div>
    @endif
  </div>
</a>
