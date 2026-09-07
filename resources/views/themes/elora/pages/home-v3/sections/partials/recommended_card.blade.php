{{-- Expects $p: image, name, price, oldPrice, discount, rating, url; weight/desc/sold optional --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex flex-col bg-[#FDFDFD] rounded-[8px] lg:rounded-[11px] shadow-[0_0_19px_rgba(0,0,0,0.16)] lg:shadow-[0_0_34px_rgba(0,0,0,0.16)] h-full overflow-hidden" style="text-decoration:none">
  <div class="relative w-full h-[140px] lg:h-[213px] shrink-0">
    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <button type="button" aria-label="Add to favorites" onclick="event.preventDefault()" class="absolute top-[6px] left-[6px] lg:top-[8px] lg:left-[8px] flex items-center justify-center size-[26px] lg:size-[37px] bg-white rounded-full shadow-[0_3px_3px_rgba(0,0,0,0.15)] lg:shadow-[0_5px_5px_rgba(0,0,0,0.15)]">
      <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[16px] lg:size-[23px]" alt="" />
    </button>
    <span class="absolute top-0 right-0 flex items-center justify-center font-normal text-[10px] lg:text-[14px] tracking-[0.4px] lg:tracking-[0.5px] px-[6px] lg:px-[10px] py-[3px] lg:py-[5px] rounded-bl-[8px] lg:rounded-bl-[12px]" style="background:#FFD428; color:#242424">{{ $p['sold'] ?? 70 }}% {{ __('Sold') }}</span>
    <div aria-hidden="true" class="absolute bottom-[6px] right-[6px] lg:bottom-[8px] lg:right-[8px] flex items-center justify-center w-[42px] h-[33px] lg:w-[66px] lg:h-[52px] bg-[#FDFDFD] rounded-[13px] lg:rounded-[19px] shadow-sm">
      <img src="{{ asset('elora-3/assets/icons/cart-add-dark.svg') }}" class="size-[18px] lg:size-[28px]" alt="" />
    </div>
  </div>
  <div class="flex-1 flex flex-col gap-[5px] lg:gap-[7px] p-[6px] lg:p-[7px] w-full">
    <div class="flex flex-col gap-[3px] lg:gap-[5px] w-full">
      <div class="flex items-center justify-between gap-[2px] w-full">
        <p class="font-medium text-[13px] lg:text-[18px] tracking-[0.4px] lg:tracking-[0.5px] truncate min-w-0" style="color:#121212">{{ $p['name'] }}</p>
        <p class="font-normal text-[11px] lg:text-[15px] tracking-[0.4px] lg:tracking-[0.5px] shrink-0" style="color:#FE3A6A">{{ $p['weight'] ?? '200g' }}</p>
      </div>
      <p class="font-normal text-[11px] lg:text-[15px] tracking-[0.4px] lg:tracking-[0.5px] truncate w-full" style="color:#ADADAD">{{ $p['desc'] ?? __('Premium quality') }}</p>
    </div>

    <div class="flex flex-col gap-[3px]">
      <div class="flex items-center gap-[5px] lg:gap-[9px]">
        <img src="{{ asset('elora-3/assets/icons/star-rating.svg') }}" alt="" class="h-[9px] w-[62px] lg:h-[12px] lg:w-[80px] shrink-0" />
        <span class="font-normal text-[10px] lg:text-[13px] tracking-[0.4px] lg:tracking-[0.5px] whitespace-nowrap" style="color:#ADADAD">{{ $p['rating'] }}</span>
      </div>
      <div class="flex items-end gap-[5px] lg:gap-[9px]">
        <p class="font-medium text-[14px] lg:text-[19px] whitespace-nowrap" style="color:#FE3A6A">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
        <p class="font-light text-[11px] lg:text-[14px] line-through whitespace-nowrap" style="color:#ADADAD">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
        <p class="font-normal text-[10px] lg:text-[13px] tracking-[0.4px] lg:tracking-[0.5px] whitespace-nowrap" style="color:#FF522C">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>

    <div class="flex items-center gap-[5px] lg:gap-[7px] w-full">
      <img src="{{ asset('elora-3/assets/icons/truck-delivery.svg') }}" alt="" class="size-[13px] lg:size-[18px] shrink-0" />
      <span class="font-medium text-[10px] lg:text-[13px] whitespace-nowrap truncate" style="color:#2AAF2F">{{ __('Delivered by 24 March') }}</span>
    </div>
  </div>
</a>
