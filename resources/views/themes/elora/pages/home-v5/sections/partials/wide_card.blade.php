{{-- Expects $p: name, weight, price, oldPrice, discount, rating, stock, ordered (optional), progress (optional) --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex gap-0 h-full items-stretch rounded-[10px] lg:rounded-[9.82px] shadow-[var(--shadow-card-lg)] lg:shadow-none overflow-hidden" style="background:var(--color-bg-main)">
  <div class="relative shrink-0 w-[42%] lg:w-[41.78%]">
    <div class="absolute inset-0 overflow-hidden">
      <img src="{{ $p['image'] ?? asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    </div>
    <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] lg:top-[6.17px] lg:left-[6.17px] bg-white cursor-pointer shadow lg:shadow-[0_4.11px_4.11px_rgba(0,0,0,0.15)] flex items-center justify-center p-[6px] lg:p-[8.23px] rounded-full shrink-0 size-[26px] lg:size-[32.92px]">
      <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[15px] lg:size-[20.57px]" />
    </button>
    <div class="absolute top-0 right-0 flex items-center justify-center px-[8px] py-[4px] lg:p-[5.41px] rounded-tl-[6px] rounded-br-[6px] shrink-0" style="background:var(--color-yellow)">
      <p class="font-normal text-[10px] lg:text-[12.62px] lg:leading-[16px] tracking-[0.3px] lg:tracking-[0.45px] whitespace-nowrap" style="color:var(--color-black-alt)">70% Sold</p>
    </div>
    <div class="absolute bottom-[6px] right-[6px] lg:bottom-[6.17px] lg:right-[6.17px] flex items-center justify-center p-[7px] lg:px-[12.34px] lg:py-[4.11px] rounded-full lg:rounded-[16.46px] shrink-0 size-[32px] lg:w-[58.64px] lg:h-[46.29px] bg-white shadow">
      <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="Add to cart" class="size-[16px] lg:size-[24.69px]" />
    </div>
  </div>
  <div class="flex flex-1 flex-col gap-[6px] lg:gap-[13.09px] p-[10px] lg:px-[13.09px] lg:py-[6.55px] items-start min-w-0 justify-center">
    <div class="flex flex-col gap-[3px] lg:gap-[4.11px] items-start w-full">
      <div class="flex items-center justify-between gap-[6px] lg:gap-[2.06px] w-full whitespace-nowrap">
        <p class="font-medium text-[14px] lg:text-[22.91px] lg:leading-[29px] lg:tracking-[0.51px] truncate" style="color:var(--color-black)">{{ $p['name'] }}</p>
        <p class="font-normal text-[12px] lg:text-[24.55px] lg:leading-[26px] lg:tracking-[0.51px] shrink-0" style="color:var(--color-price-blue)">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[11px] lg:text-[19.64px] lg:leading-[25px] lg:tracking-[0.51px] w-full truncate" style="color:var(--color-subtitle)">Premium cotton blend</p>
    </div>
    <div class="flex flex-col gap-[2px] lg:gap-[1.64px] items-start w-full">
      <div class="h-[6px] lg:h-[8.18px] w-full rounded-full" style="background:#D9D9D9">
        <div class="h-full rounded-full" style="background:var(--color-price-blue); width:{{ $p['progress'] ?? 83 }}%"></div>
      </div>
      <p class="text-[10px] lg:text-[16.37px] lg:leading-[21px] lg:tracking-[0.51px]" style="color:var(--color-price-blue)">{{ $p['ordered'] ?? __('5 ordered last 30 min') }}</p>
    </div>
    <div class="flex flex-col gap-[2px] lg:gap-[4.11px] items-start">
      <div class="flex gap-[5px] lg:gap-[8.23px] items-center">
        <img src="{{ asset('elora-5/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[54px] lg:h-[10.28px] lg:w-[70.46px]" />
        <span class="text-[10px] lg:text-[19.64px] lg:leading-[25px] lg:tracking-[0.51px] whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[6px] lg:gap-[8.23px] items-end">
        <p class="font-medium text-[14px] lg:text-[26.19px] lg:leading-[33px] whitespace-nowrap" style="color:var(--color-black)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[10px] lg:text-[14.4px] lg:leading-[18px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[10px] lg:text-[19.64px] lg:leading-[25px] lg:tracking-[0.51px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex flex-col gap-[3px] lg:gap-[4.11px] items-start w-full">
      <div class="flex gap-[5px] lg:gap-[6.55px] items-center w-full">
        <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="size-[13px] lg:size-[19.64px] shrink-0" />
        <p class="font-medium text-[10px] lg:text-[13.09px] lg:leading-[16px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
      </div>
      @if (!empty($p['stock']))
        <div class="flex gap-[5px] lg:gap-[8.23px] items-center">
          <img src="{{ asset('elora-5/assets/icons/cart-x.svg') }}" alt="" class="size-[12px] lg:size-[16.46px] shrink-0" />
          <p class="font-medium text-[10px] lg:text-[12.34px] lg:leading-[16px] whitespace-nowrap" style="color:var(--color-success)">{{ $p['stock'] }}</p>
        </div>
      @endif
    </div>
  </div>
</a>
