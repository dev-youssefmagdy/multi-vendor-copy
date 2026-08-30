{{-- Expects $p: name, weight, price, oldPrice, discount, rating, stock, ordered (optional), progress (optional) --}}
<div class="flex gap-0 h-full items-stretch rounded-[10px] shadow-[var(--shadow-card-lg)] overflow-hidden" style="background:var(--color-bg-main)">
  <div class="relative flex flex-col gap-[7px] items-end justify-end px-[5px] py-[4px] shrink-0 w-[42%]">
    <div class="absolute inset-0 overflow-hidden">
      <img src="{{ asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    </div>
    <div class="relative flex items-center justify-center px-[8px] py-[4px] rounded-bl-[6px] rounded-tr-[6px] shrink-0" style="background:var(--color-yellow)">
      <p class="font-normal text-[10px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black-alt)">70% Sold</p>
    </div>
    <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white cursor-pointer shadow flex items-center justify-center p-[6px] rounded-full shrink-0 size-[26px] lg:size-[32px]">
      <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[15px] lg:size-[19px]" />
    </button>
    <div class="relative flex items-center justify-center p-[7px] rounded-full shrink-0 size-[32px] lg:size-[38px] bg-white shadow">
      <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="Add to cart" class="size-[16px] lg:size-[20px]" />
    </div>
  </div>
  <div class="flex flex-1 flex-col gap-[6px] p-[10px] lg:p-[12px] items-start min-w-0 justify-center">
    <div class="flex flex-col gap-[3px] items-start w-full">
      <div class="flex items-center justify-between w-full whitespace-nowrap">
        <p class="font-medium text-[14px] lg:text-[18px]" style="color:var(--color-black)">{{ $p['name'] }}</p>
        <p class="font-normal text-[12px] lg:text-[15px]" style="color:var(--color-price-blue)">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[11px] lg:text-[14px] w-full" style="color:var(--color-subtitle)">Premium cotton blend</p>
    </div>
    @if (!empty($p['ordered']))
      <div class="flex flex-col gap-[2px] items-start w-full">
        <div class="h-[6px] w-full rounded-full" style="background:#D9D9D9">
          <div class="h-full rounded-full" style="background:var(--color-price-blue); width:{{ $p['progress'] }}%"></div>
        </div>
        <p class="text-[10px] lg:text-[12px]" style="color:var(--color-price-blue)">{{ $p['ordered'] }}</p>
      </div>
    @endif
    <div class="flex gap-[5px] items-center">
      <img src="{{ asset('elora-5/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[54px]" />
      <span class="text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['rating'] }}</span>
    </div>
    <div class="flex gap-[6px] items-end">
      <p class="font-medium text-[14px] lg:text-[18px] whitespace-nowrap" style="color:var(--color-black)">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="font-light text-[10px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="font-normal text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      @endif
    </div>
    <div class="flex flex-col gap-[3px] items-start w-full">
      <div class="flex gap-[5px] items-center w-full">
        <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="size-[13px] lg:size-[15px]" />
        <p class="font-medium text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
      </div>
      @if (!empty($p['stock']))
        <div class="flex gap-[5px] items-center">
          <img src="{{ asset('elora-5/assets/icons/cart-x.svg') }}" alt="" class="size-[12px] lg:size-[14px]" />
          <p class="font-medium text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-success)">{{ $p['stock'] }}</p>
        </div>
      @endif
    </div>
  </div>
</div>
