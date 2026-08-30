{{-- Expects $p: name, weight, price, oldPrice, discount --}}
<div class="flex gap-0 h-[110px] lg:h-[169px] items-stretch rounded-[10px] shadow-[var(--shadow-card-lg)] overflow-hidden w-full" style="background:var(--color-bg-main)">
  <div class="relative shrink-0 w-[42%]">
    <img src="{{ asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <div class="absolute top-0 left-0 flex items-center justify-center px-[8px] py-[4px] rounded-br-[6px]" style="background:var(--color-yellow)">
      <p class="font-normal text-[10px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black-alt)">70% Sold</p>
    </div>
    <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white cursor-pointer shadow flex items-center justify-center p-[6px] rounded-full">
      <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[13px] lg:size-[16px]" />
    </button>
  </div>
  <div class="flex flex-1 flex-col gap-[4px] p-[8px] lg:p-[10px] items-start min-w-0 justify-center">
    <div class="flex items-center justify-between w-full whitespace-nowrap">
      <p class="font-medium text-[13px] lg:text-[16px]" style="color:var(--color-black)">{{ $p['name'] }}</p>
      <p class="font-normal text-[11px] lg:text-[13px]" style="color:var(--color-price-blue)">{{ $p['weight'] }}</p>
    </div>
    <div class="flex gap-[5px] items-end">
      <p class="font-medium text-[13px] lg:text-[16px] whitespace-nowrap" style="color:var(--color-black)">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="font-light text-[10px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="font-normal text-[10px] lg:text-[12px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      @endif
    </div>
    <div class="flex gap-[4px] items-center w-full">
      <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="size-[11px] lg:size-[13px]" />
      <p class="font-medium text-[9px] lg:text-[11px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>
  </div>
</div>
