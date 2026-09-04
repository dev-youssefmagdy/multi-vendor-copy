{{-- Expects $p: image, name, price, oldPrice, discount, rating, url; weight/desc/progress/ordered/sold/stockLeft optional --}}
<div class="swiper-slide h-auto w-full! lg:w-[309px]!">
  <a href="{{ $p['url'] ?? '#' }}" class="flex flex-col bg-[#FDFDFD] rounded-[8px] lg:rounded-[14px] shadow-[0_0_19px_rgba(0,0,0,0.16)] lg:shadow-[0_0_43px_rgba(0,0,0,0.16)] h-full overflow-hidden" style="text-decoration:none">
    <div class="relative w-full h-[150px] lg:h-[269px] shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
      <button type="button" aria-label="Add to favorites" onclick="event.preventDefault()" class="absolute top-[5px] left-[5px] lg:top-[9px] lg:left-[9px] flex items-center justify-center size-[26px] lg:size-[47px] bg-white rounded-full shadow-[0_3px_3px_rgba(0,0,0,0.15)]">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[16px] lg:size-[29px]" alt="" />
      </button>
      <span class="absolute top-0 right-0 flex items-center justify-center font-normal text-[10px] lg:text-[18px] tracking-[0.36px] lg:tracking-[0.64px] px-[5px] lg:px-[8px] py-[3px] lg:py-[6px] rounded-bl-[6px] lg:rounded-bl-[10px]" style="background:#FFD428; color:#242424">{{ $p['sold'] ?? 70 }}% {{ __('Sold') }}</span>
      <div aria-hidden="true" class="absolute bottom-[5px] right-[5px] lg:bottom-[9px] lg:right-[9px] flex items-center justify-center w-[47px] h-[37px] lg:w-[84px] lg:h-[66px] bg-[#FDFDFD] rounded-[13px] lg:rounded-[23px] shadow-sm">
        <img src="{{ asset('elora-3/assets/icons/cart-add-dark.svg') }}" class="size-[20px] lg:size-[35px]" alt="" />
      </div>
    </div>
    <div class="flex-1 flex flex-col gap-[5px] lg:gap-[9px] p-[5px] lg:p-[9px] w-full">
      <div class="flex flex-col gap-[3px] lg:gap-[6px] w-full">
        <div class="flex items-center justify-between gap-[2px] w-full">
          <p class="font-medium text-[16px] lg:text-[28px] tracking-[0.41px] lg:tracking-[0.73px] truncate min-w-0" style="color:#121212">{{ $p['name'] }}</p>
          <p class="font-normal text-[13px] lg:text-[23px] tracking-[0.41px] lg:tracking-[0.73px] shrink-0" style="color:#FE3A6A">{{ $p['weight'] ?? '200g' }}</p>
        </div>
        <p class="font-normal text-[13px] lg:text-[23px] tracking-[0.41px] lg:tracking-[0.73px] truncate w-full" style="color:#ADADAD">{{ $p['desc'] ?? __('Premium quality') }}</p>
      </div>

      <div class="flex flex-col gap-[3px] lg:gap-[3px] w-full">
        <div class="h-[5px] lg:h-[8px] w-full rounded-full" style="background:#D9D9D9">
          <div class="h-full rounded-full" style="background:#E40307; width:{{ $p['progress'] ?? 83 }}%"></div>
        </div>
        <p class="font-normal text-[9px] lg:text-[16px] tracking-[0.3px] lg:tracking-[0.5px] truncate" style="color:#E40307">{{ $p['ordered'] ?? __('5 ordered last 30 min') }}</p>
      </div>

      <div class="flex flex-col gap-[3px] lg:gap-[6px]">
        <div class="flex items-center gap-[5px] lg:gap-[9px]">
          <img src="{{ asset('elora-3/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[56px] lg:h-[15px] lg:w-[100px] shrink-0" />
          <span class="font-normal text-[11px] lg:text-[19px] tracking-[0.41px] lg:tracking-[0.73px] whitespace-nowrap" style="color:#ADADAD">{{ $p['rating'] }}</span>
        </div>
        <div class="flex items-end gap-[5px] lg:gap-[9px]">
          <p class="font-medium text-[16px] lg:text-[28px] whitespace-nowrap" style="color:#FE3A6A">{{ $p['price'] }}</p>
          @if (!empty($p['oldPrice']))
          <p class="font-light text-[11px] lg:text-[20px] line-through whitespace-nowrap" style="color:#ADADAD">{{ $p['oldPrice'] }}</p>
          @endif
          @if (!empty($p['discount']))
          <p class="font-normal text-[11px] lg:text-[19px] tracking-[0.41px] lg:tracking-[0.73px] whitespace-nowrap" style="color:#FF522C">{{ $p['discount'] }}</p>
          @endif
        </div>
      </div>

      <div class="flex items-center gap-[4px] lg:gap-[9px] w-full">
        <img src="{{ asset('elora-3/assets/icons/truck-delivery.svg') }}" alt="" class="size-[16px] lg:size-[28px] shrink-0" />
        <span class="font-medium text-[11px] lg:text-[19px] whitespace-nowrap" style="color:#2AAF2F">{{ __('Delivered by 24 March') }}</span>
      </div>
    </div>
  </a>
</div>
