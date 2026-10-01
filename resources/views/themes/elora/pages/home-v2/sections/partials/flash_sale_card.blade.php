{{-- Expects $p: url, image, name, weight, desc, badge, badgeBg, badgeText,
     rating (float 0-5), ratingLabel, price, oldPrice, discount, delivered, stockLeft
     (delivered and stockLeft are mutually exclusive — stockLeft takes priority when both are set) --}}
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
        class="absolute top-[3.76px] lg:top-[7.78px] end-[4.51px] lg:end-[9.33px] z-[1] flex items-center justify-center whitespace-nowrap px-[3.96px] lg:px-[8.18px] w-[46.91px] lg:w-[97.36px] h-[18.47px] lg:h-[38.17px] text-[9.23px] lg:text-[19.09px] leading-[12px] lg:leading-[24px] tracking-[0.33px] lg:tracking-[0.68px] rounded-tl-[5.28px] lg:rounded-tl-[10.91px] rounded-br-[5.28px] lg:rounded-br-[10.91px]"
        style="background: {{ $p['badgeBg'] }}; color: {{ $p['badgeText'] }}"
      >{{ $p['badge'] }}</span>
    @endif

    <button
      type="button"
      aria-label="{{ __('Add to favorites') }}"
      @if (!empty($p['favData']))
        onclick="event.preventDefault(); eloraHeartToggle(this)"
        data-fav="{{ $p['favData'] }}"
        data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
        data-product-id="{{ $p['id'] ?? '' }}"
      @endif
      class="absolute top-[3.76px] lg:top-[7.78px] start-[4.51px] lg:start-[9.33px] z-[1] flex items-center justify-center rounded-full p-[6.02px] lg:p-[12.44px]"
      style="background: var(--color-white); box-shadow: var(--shadow-heart-mobile)"
    >
      <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" alt="" class="size-[15.05px] lg:size-[31.1px]" />
    </button>

    @if (!empty($p['isOutOfStock']))
      <button
        type="button" disabled onclick="event.preventDefault()"
        class="absolute z-[1] top-[120.76px] lg:top-[249.61px] end-[4.51px] lg:end-[9.33px] flex items-center justify-center rounded-[12.04px] lg:rounded-[24.88px] px-[9.03px] lg:px-[18.66px] py-[3.01px] lg:py-[6.22px] cursor-not-allowed"
        style="background: var(--color-bg-main)"
      >
        <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="{{ __('Add to cart') }}" class="size-[18.06px] lg:size-[37.33px] opacity-40" />
      </button>
    @elseif (!empty($p['hasMultipleVariants']))
      <button
        type="button"
        onclick="event.preventDefault(); openVariantModal({{ $p['id'] ?? 'null' }}, {{ \Illuminate\Support\Js::from($p['fullName'] ?? ($p['name'] ?? '')) }}, {{ \Illuminate\Support\Js::from($p['variantModalData'] ?? []) }})"
        class="absolute z-[1] top-[120.76px] lg:top-[249.61px] end-[4.51px] lg:end-[9.33px] flex items-center justify-center rounded-[12.04px] lg:rounded-[24.88px] px-[9.03px] lg:px-[18.66px] py-[3.01px] lg:py-[6.22px]"
        style="background: var(--color-bg-main)"
      >
        <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="{{ __('Add to cart') }}" class="size-[18.06px] lg:size-[37.33px]" />
      </button>
    @else
      <button
        type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 'null' }})" onclick="event.preventDefault()"
        class="absolute z-[1] top-[120.76px] lg:top-[249.61px] end-[4.51px] lg:end-[9.33px] flex items-center justify-center rounded-[12.04px] lg:rounded-[24.88px] px-[9.03px] lg:px-[18.66px] py-[3.01px] lg:py-[6.22px]"
        style="background: var(--color-bg-main)"
      >
        <img src="{{ asset('elora-1/assets/icons/cart-plus.svg') }}" alt="{{ __('Add to cart') }}" class="size-[18.06px] lg:size-[37.33px]" />
      </button>
    @endif
  </div>

  {{-- Body --}}
  <div class="flex flex-col items-start justify-center gap-[4.79px] lg:gap-[9.9px] p-[4.79px] lg:p-[9.9px] w-full flex-1 min-h-0">
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
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flex flex-row items-center justify-center gap-[6.02px] lg:gap-[12.44px]">
        <div class="flex items-center gap-[1px]" style="height:8px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:8px;width:8px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
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

    @if (!empty($p['stockLeft']))
      <div class="flex flex-row items-center gap-[4.79px] lg:gap-[9.9px] w-full">
        <img src="{{ asset('elora-1/assets/icons/cart-x.svg') }}" alt="" class="size-[14.36px] lg:size-[29.69px] shrink-0" />
        <p
          class="whitespace-nowrap font-medium text-[9.58px] lg:text-[19.79px] leading-[12px] lg:leading-[25px] truncate min-w-0"
          style="color: var(--color-secondary)"
        >{{ $p['stockLeft'] }}</p>
      </div>
    @elseif (!empty($p['delivered']))
      <div class="flex flex-row items-center gap-[4.79px] lg:gap-[9.9px] w-full">
        <img src="{{ asset('elora-1/assets/icons/truck-delivery-green.svg') }}" alt="" class="size-[14.36px] lg:size-[29.69px] shrink-0" />
        <p
          class="whitespace-nowrap font-medium text-[9.58px] lg:text-[19.79px] leading-[12px] lg:leading-[25px] truncate min-w-0"
          style="color: var(--color-success)"
        >{{ $p['delivered'] }}</p>
      </div>
  </div>
</a>
