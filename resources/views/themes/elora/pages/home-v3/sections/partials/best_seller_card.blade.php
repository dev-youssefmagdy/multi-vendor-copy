{{-- Expects $p: image, name, price, oldPrice, discount, rating, url; weight/desc/progress/ordered/sold/stockLeft optional --}}
{{-- No w-full! here: Tailwind puts that important utility in a layer, which
     beats even an id rule, so the phone width is left to the section CSS. --}}
<div class="swiper-slide h-auto lg:w-[309px]!">
  <a href="{{ $p['url'] ?? '#' }}" class="flex flex-col bg-[#FDFDFD] rounded-[7.41px] lg:rounded-[14px] shadow-[0_0_22.65px_rgba(0,0,0,0.16)] lg:shadow-[0_0_43px_rgba(0,0,0,0.16)] h-full overflow-hidden" style="text-decoration:none">
    <div class="relative w-full h-[142px] lg:h-[269px] shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
      <button type="button" aria-label="Add to favorites" onclick="event.preventDefault()" class="absolute top-[4.66px] left-[4.66px] lg:top-[9px] lg:left-[9px] flex items-center justify-center size-[24.83px] lg:size-[47px] bg-white rounded-full shadow-[0_3.1px_3.1px_rgba(0,0,0,0.15)]">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[15.52px] lg:size-[29px]" alt="" />
      </button>
      <span class="absolute top-0 right-0 flex items-center justify-center font-normal text-[9.52px] lg:text-[18px] leading-[12px] tracking-[0.34px] lg:tracking-[0.64px] px-[4.08px] lg:px-[8px] py-[4.08px] lg:py-[6px] rounded-bl-[5.44px] lg:rounded-bl-[10px]" style="background:#FFD428; color:#242424">{{ $p['sold'] ?? 70 }}% {{ __('Sold') }}</span>
      <div aria-hidden="true" class="absolute bottom-[4.66px] right-[4.66px] lg:bottom-[9px] lg:right-[9px] flex items-center justify-center w-[44.24px] h-[34.92px] lg:w-[84px] lg:h-[66px] bg-[#FDFDFD] rounded-[12.42px] lg:rounded-[23px]">
        <img src="{{ asset('elora-3/assets/icons/cart-add-dark.svg') }}" class="size-[18.63px] lg:size-[35px]" alt="" />
      </div>
    </div>
    <div class="flex-1 flex flex-col gap-[4.94px] lg:gap-[9px] p-[4.94px] lg:p-[9px] w-full">
      <div class="flex flex-col gap-[3.1px] lg:gap-[6px] w-full">
        <div class="flex items-center justify-between gap-[1.55px] w-full">
          <p class="font-medium text-[14.82px] lg:text-[28px] leading-[19px] tracking-[0.39px] lg:tracking-[0.73px] truncate min-w-0" style="color:#121212">{{ $p['name'] }}</p>
          <p class="font-normal text-[12.35px] lg:text-[23px] leading-[19px] tracking-[0.39px] lg:tracking-[0.73px] shrink-0" style="color:#FE3A6A">{{ $p['weight'] ?? '200g' }}</p>
        </div>
        <p class="font-normal text-[12.35px] lg:text-[23px] leading-[16px] tracking-[0.39px] lg:tracking-[0.73px] truncate w-full" style="color:#ADADAD">{{ $p['desc'] ?? __('Premium quality') }}</p>
      </div>

      <div class="flex flex-col gap-[0.85px] lg:gap-[3px] w-full">
        <div class="h-[4.26px] lg:h-[8px] w-full rounded-full" style="background:#D9D9D9">
          <div class="h-full rounded-full" style="background:#E40307; width:{{ $p['progress'] ?? 83 }}%"></div>
        </div>
        <p class="font-normal text-[8.52px] lg:text-[16px] leading-[11px] tracking-[0.27px] lg:tracking-[0.5px] truncate" style="color:#E40307">{{ $p['ordered'] ?? __('5 ordered last 30 min') }}</p>
      </div>

      <div class="flex flex-col gap-[3.1px] lg:gap-[6px]">
        <div class="flex items-center gap-[6.21px] lg:gap-[9px]">
          <img src="{{ asset('elora-3/assets/icons/star-rating.svg') }}" alt="" class="h-[7.76px] w-[53.16px] lg:h-[15px] lg:w-[100px] shrink-0" />
          <span class="font-normal text-[9.88px] lg:text-[19px] leading-[12px] tracking-[0.39px] lg:tracking-[0.73px] whitespace-nowrap" style="color:#ADADAD">{{ $p['rating'] }}</span>
        </div>
        <div class="flex items-end gap-[6.21px] lg:gap-[9px]">
          <p class="font-medium text-[14.82px] lg:text-[28px] leading-[19px] whitespace-nowrap" style="color:#FE3A6A">{{ $p['price'] }}</p>
          @if (!empty($p['oldPrice']))
          <p class="font-light text-[10.87px] lg:text-[20px] leading-[14px] line-through whitespace-nowrap" style="color:#ADADAD">{{ $p['oldPrice'] }}</p>
          @endif
          @if (!empty($p['discount']))
          <p class="font-normal text-[9.88px] lg:text-[19px] leading-[12px] tracking-[0.39px] lg:tracking-[0.73px] whitespace-nowrap" style="color:#FF522C">{{ $p['discount'] }}</p>
          @endif
        </div>
      </div>

      <div class="flex flex-col gap-[3.1px] lg:gap-[3px] w-full">
        <div class="flex items-center gap-[4.94px] lg:gap-[9px] w-full">
          <img src="{{ asset('elora-3/assets/icons/truck-delivery.svg') }}" alt="" class="size-[14.82px] lg:size-[28px] shrink-0" />
          <span class="font-medium text-[9.88px] lg:text-[19px] leading-[12px] whitespace-nowrap" style="color:#2AAF2F">{{ __('Delivered by 24 March') }}</span>
        </div>
      </div>
    </div>
  </a>
</div>
