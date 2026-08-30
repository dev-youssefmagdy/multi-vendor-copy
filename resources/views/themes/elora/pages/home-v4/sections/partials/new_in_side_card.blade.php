{{-- New In composite slide's right-side mini card. Expects $p: image, name, weight, price, oldPrice, discount, rating --}}
<div class="bg-[var(--color-bg-main)] flex items-stretch rounded-[6px] lg:rounded-[8px] h-full shadow-[var(--shadow-card-lg)] overflow-hidden">
  <div class="relative shrink-0 w-[36%]">
    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <button type="button" aria-label="Add to favorites" class="absolute top-[5px] left-[5px] lg:top-[8px] lg:left-[8px] bg-white cursor-pointer shadow flex items-center justify-center p-[4px] lg:p-[6px] rounded-full size-[18px] lg:size-[26px]">
      <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[10px] lg:size-[15px]" />
    </button>
    <span class="absolute top-0 right-0 text-[8px] lg:text-[12px] font-normal px-[5px] py-[3px] lg:px-[8px] lg:py-[4px] rounded-bl-[5px] lg:rounded-bl-[7px] whitespace-nowrap" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
    <div class="absolute bottom-[5px] right-[5px] lg:bottom-[8px] lg:right-[8px] flex items-center justify-center rounded-[8px] lg:rounded-[12px] h-[20px] lg:h-[32px] px-[5px] lg:px-[9px]" style="background:var(--color-text-primary)">
      <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" alt="Add to cart" class="size-[11px] lg:size-[17px] invert" />
    </div>
  </div>
  <div class="flex-1 flex flex-col gap-[2px] lg:gap-[6px] p-[6px] lg:p-[12px] min-w-0 justify-center overflow-hidden">
    <div class="flex items-center justify-between gap-[5px] w-full">
      <p class="font-medium text-[12px] lg:text-[20px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
      <p class="font-normal text-[10px] lg:text-[16px] shrink-0" style="color:var(--color-brand-orange-bright)">{{ $p['weight'] }}</p>
    </div>
    <p class="font-normal text-[10px] lg:text-[16px] truncate" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
    <div class="flex gap-[4px] lg:gap-[8px] items-center">
      <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[6px] lg:h-[10px] w-[43px] lg:w-[71px]" />
      <span class="text-[8px] lg:text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
    </div>
    <div class="flex gap-[5px] lg:gap-[8px] items-end">
      <p class="font-medium text-[13px] lg:text-[20px] whitespace-nowrap" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="font-light text-[8px] lg:text-[15px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="font-normal text-[8px] lg:text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      @endif
    </div>
  </div>
</div>
