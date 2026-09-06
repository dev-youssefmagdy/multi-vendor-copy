{{-- Expects $p: name, weight, price, oldPrice, discount, rating, stock, ordered (optional), progress (optional) --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex gap-[3px] lg:gap-[5px] h-full items-stretch rounded-[6px] lg:rounded-[10px] shadow-[var(--shadow-card-lg)] overflow-hidden" style="background:var(--color-bg-main)">
  <div class="relative shrink-0 w-[43.35%]">
    <div class="absolute inset-0 overflow-hidden">
      <img src="{{ $p['image'] ?? asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    </div>
    <button type="button" aria-label="Add to favorites" class="absolute top-[3.77px] left-[3.77px] bg-white cursor-pointer shadow-[0_2.51px_2.51px_rgba(0,0,0,0.15)] flex items-center justify-center p-[5.03px] rounded-full shrink-0 size-[20.11px] lg:size-[34px]">
      <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[12.57px] lg:size-[22px]" />
    </button>
    <div class="absolute top-0 right-0 flex items-center justify-center p-[3.31px] rounded-tl-[4.41px] rounded-br-[4.41px] shrink-0" style="background:var(--color-yellow)">
      <p class="font-normal text-[7.71px] lg:text-[13px] leading-[10px] tracking-[0.28px] lg:tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black-alt)">70% Sold</p>
    </div>
    <div class="absolute bottom-[3.77px] right-[3.77px] flex items-center justify-center px-[7.54px] py-[2.51px] lg:px-[7px] lg:py-[7px] rounded-[10.06px] lg:rounded-full shrink-0 w-[35.83px] h-[28.29px] lg:size-[48px] bg-white shadow">
      <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="Add to cart" class="size-[15.09px] lg:size-[26px]" />
    </div>
  </div>
  <div class="flex flex-1 flex-col gap-[4px] lg:gap-[6.85px] p-[4px] lg:p-[6.85px] items-start min-w-0 justify-center">
    <div class="flex flex-col gap-[2.51px] lg:gap-[4.31px] items-start w-full">
      <div class="flex items-center justify-between gap-[1.26px] lg:gap-[2.15px] w-full whitespace-nowrap">
        <p class="font-medium text-[12px] lg:text-[20.56px] leading-[15px] lg:leading-[26px] tracking-[0.31px] lg:tracking-[0.54px] truncate min-w-0" style="color:var(--color-black)">{{ $p['name'] }}</p>
        <p class="font-normal text-[10px] lg:text-[17.13px] leading-[16px] lg:leading-[27px] tracking-[0.31px] lg:tracking-[0.54px] shrink-0" style="color:var(--color-primary)">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[10px] lg:text-[17.13px] leading-[13px] lg:leading-[22px] tracking-[0.31px] lg:tracking-[0.54px] w-full truncate" style="color:var(--color-subtitle)">Premium cotton blend</p>
    </div>
    @if (!empty($p['ordered']))
      <div class="flex flex-col gap-[2px] items-start w-full">
        <div class="h-[6px] w-full rounded-full" style="background:#D9D9D9">
          <div class="h-full rounded-full" style="background:var(--color-price-blue); width:{{ $p['progress'] }}%"></div>
        </div>
        <p class="text-[10px]" style="color:var(--color-price-blue)">{{ $p['ordered'] }}</p>
      </div>
    @endif
    <div class="flex flex-col gap-[2.51px] lg:gap-[4.31px] items-start">
      <div class="flex gap-[5.03px] lg:gap-[8.61px] items-center">
        <img src="{{ asset('elora-5/assets/icons/star-rating.svg') }}" alt="" class="h-[6.28px] w-[43.05px] lg:h-[10.76px] lg:w-[73.75px]" />
        <span class="text-[8px] lg:text-[13.70px] leading-[10px] lg:leading-[17px] tracking-[0.31px] lg:tracking-[0.54px] whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[5.03px] lg:gap-[8.61px] items-end">
        <p class="font-medium text-[12px] lg:text-[20.56px] leading-[15px] lg:leading-[26px] whitespace-nowrap" style="color:var(--color-price-blue)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[8.8px] lg:text-[15.07px] leading-[11px] lg:leading-[19px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[8px] lg:text-[13.70px] leading-[10px] lg:leading-[17px] tracking-[0.31px] lg:tracking-[0.54px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex flex-col gap-[2.51px] lg:gap-[4.31px] items-start w-full">
      @if (!empty($p['stock']))
        {{-- Low-stock urgency replaces the routine delivery line rather than stacking below it —
             the spec's delivery/stock wrapper (10.69px tall) is only ever tall enough for one row. --}}
        <div class="flex gap-[5.03px] lg:gap-[8.61px] items-center w-full">
          <img src="{{ asset('elora-5/assets/icons/cart-x.svg') }}" alt="" class="size-[10.06px] lg:size-[17.23px] shrink-0" />
          <p class="font-medium text-[7.54px] lg:text-[12.92px] leading-[10px] lg:leading-[16px] whitespace-nowrap" style="color:var(--color-success)">{{ $p['stock'] }}</p>
        </div>
      @else
        <div class="flex gap-[4px] lg:gap-[6.85px] items-center w-full">
          <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="size-[12px] lg:size-[20.56px] shrink-0" />
          <p class="font-medium text-[8px] lg:text-[13.70px] leading-[10px] lg:leading-[17px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
        </div>
      @endif
    </div>
  </div>
</a>
