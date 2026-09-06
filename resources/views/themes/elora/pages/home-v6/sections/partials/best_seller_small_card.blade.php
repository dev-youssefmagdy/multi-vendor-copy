{{-- Best Seller composite slide's small card (top-left/top-right of the right column). Expects $p: image, name, badge, badgeBg, badgeColor, weight, weightColor, rating, price, priceColor, oldPrice, url --}}
<a
  href="{{ $p['url'] ?? '#' }}"
  class="relative flex flex-col items-start w-[111.5px] h-[168.96px] lg:w-[191.29px] lg:h-[289.87px] bg-[var(--color-bg-main)] rounded-[5.06818px] lg:rounded-[8.69494px] overflow-hidden shadow-[var(--shadow-card)] no-underline transition-[width,height,border-radius] duration-200 ease-in-out"
>
  <div class="relative w-full h-[97.16px] lg:h-[166.69px] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <div class="absolute inset-0 flex justify-between items-start p-[3.18571px] lg:p-[5.46539px]">
 
    
           <button
        type="button"
        aria-label="Add to favorites"
        class="flex items-center justify-center w-[16.99px] h-[16.99px] lg:w-[29.15px] lg:h-[29.15px] p-[4.25px] lg:p-[7.29px] bg-white rounded-[26.5476px] lg:rounded-[45.5449px] shadow-[0px_2.12381px_2.12381px_rgba(0,0,0,0.15)] lg:shadow-[0px_3.64359px_3.64359px_rgba(0,0,0,0.15)] transition-transform duration-150 ease active:scale-95"
      >
        <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" alt="" class="size-[10.62px] lg:size-[18.22px]" />
      </button>

        <span
        class="flex items-center justify-center h-[13.03px] px-[2.79px] lg:h-[22.36px] lg:px-[4.79px] text-[6.51604px] lg:text-[11.1789px] font-normal leading-[8px] lg:leading-[14px] tracking-[0.232716px] lg:tracking-[0.399245px] rounded-tr-[3.723px] rounded-bl-[3.723px] lg:rounded-tr-[6.38792px] lg:rounded-bl-[6.38792px]"
        style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}"
        >{{ $p['badge'] ?? '70% Sold' }}</span
      >
    </div>
    <button
      type="button"
      aria-label="Add to cart"
      class="absolute bottom-[3.19px] right-[3.19px] lg:bottom-[5.47px] lg:right-[5.47px] flex items-center justify-center w-[30.26px] h-[23.89px] px-[6.37px] py-[2.12px] lg:w-[51.92px] lg:h-[40.99px] lg:px-[10.93px] lg:py-[3.64px] rounded-[8.49524px] lg:rounded-[14.5744px] transition-transform duration-150 ease active:scale-95"
      style="background: var(--color-bg-main)"
    >
      <img src="{{ asset('elora-2/assets/icons/cart-add.svg') }}" alt="" class="size-[13px] lg:size-[22px]" />
    </button>
  </div>
  <div class="flex-1 flex flex-col gap-[3.37879px] lg:gap-[5.79663px] items-start p-[3.37879px] lg:p-[5.79663px] w-full min-h-0 min-w-0">
    <div class="flex flex-col gap-[2.12px] lg:gap-[3.64px] items-start w-full">
      <div class="flex items-center justify-between gap-[1.06px] lg:gap-[1.82px] w-full">
        <p class="font-medium text-[10.1364px] lg:text-[17.3899px] leading-[13px] lg:leading-[22px] tracking-[0.265476px] lg:tracking-[0.455449px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="text-[8.44697px] lg:text-[14.4916px] leading-[13px] lg:leading-[23px] tracking-[0.265476px] lg:tracking-[0.455449px] shrink-0" style="color:{{ $p['weightColor'] ?? 'var(--color-primary)' }}">{{ $p['weight'] }}</p>
      </div>
      <p class="text-[8.44697px] lg:text-[14.4916px] leading-[11px] lg:leading-[18px] tracking-[0.265476px] lg:tracking-[0.455449px] w-full truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] ?? 'Premium cotton blend' }}</p>
    </div>
    <div class="flex flex-col gap-[2.12px] lg:gap-[3.64px] items-start">
      <div class="flex items-center gap-[4.25px] lg:gap-[7.29px]">
        <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[5.31px] w-[36.37px] lg:h-[9.11px] lg:w-[62.39px]" />
        <span class="text-[6.75758px] lg:text-[11.5933px] leading-[9px] lg:leading-[15px] tracking-[0.265476px] lg:tracking-[0.455449px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex items-end gap-[4.25px] lg:gap-[7.29px]">
        <p class="font-medium text-[10.1364px] lg:text-[17.3899px] leading-[13px] lg:leading-[22px]" style="color:{{ $p['priceColor'] ?? 'var(--color-text-primary)' }}">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[7.43333px] lg:text-[12.7526px] leading-[9px] lg:leading-[16px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="text-[6.75758px] lg:text-[11.5933px] leading-[9px] lg:leading-[15px] tracking-[0.265476px] lg:tracking-[0.455449px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="mt-auto flex gap-[3.38px] lg:gap-[5.8px] items-center w-full">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[10.14px] lg:size-[17.39px]" />
      <p class="font-medium text-[6.75758px] lg:text-[11.5933px] leading-[9px] lg:leading-[15px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>
  </div>
</a>
