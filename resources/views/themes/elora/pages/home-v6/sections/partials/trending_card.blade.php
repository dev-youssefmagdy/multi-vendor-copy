{{-- Expects $p: name, weight, progress, ordered, rating, price, oldPrice, discount, image, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex h-auto lg:h-full bg-[var(--color-bg-main)] rounded-[10.1143px] lg:rounded-[10px] shadow-sm overflow-hidden">
  <div class="relative w-[41.77%] lg:w-[45%] shrink-0">
    <img src="{{ $p['image'] ?? asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
    <span class="absolute top-0 right-0 flex items-center justify-center w-[66.15px] h-[26.01px] lg:w-auto lg:h-auto text-[13.0037px] leading-[16px] tracking-[0.464px] lg:text-[12px] lg:leading-normal lg:tracking-normal font-normal p-[5.573px] lg:px-[8px] lg:py-[4px] rounded-tr-[7.43px] rounded-bl-[7.43px] lg:rounded-tr-none lg:rounded-bl-[8px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
    <button type="button" aria-label="Add to favorites" class="absolute top-[6.358px] left-[6.358px] lg:top-[6px] lg:left-[6px] flex items-center justify-center size-[33.91px] lg:size-auto bg-white rounded-full p-[8.477px] lg:p-[5px] shadow-[0px_4.238px_4.238px_rgba(0,0,0,0.15)] lg:shadow">
      <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[21.19px] lg:size-[16px]" alt="" />
    </button>
    <div class="absolute bottom-[5.298px] right-[6.358px] lg:bottom-[8px] lg:right-[8px] flex items-center justify-center w-[60.4px] h-[47.68px] lg:w-auto lg:h-auto bg-[var(--color-bg-main)] rounded-[16.954px] lg:rounded-[8px] px-[12.715px] py-[4.238px] lg:p-[6px] shadow">
      <img src="{{ asset('elora-2/assets/icons/cart.svg') }}" class="size-[25.43px] lg:size-[20px]" alt="" />
    </div>
  </div>
  <div class="flex-1 px-[13.486px] py-[6.743px] lg:px-[12px] lg:py-[12px] flex flex-col gap-[13.49px] lg:gap-[8px]">
    <div class="flex items-center justify-between gap-[2.12px] lg:gap-0">
      <p class="font-medium text-[23.6px] leading-[30px] tracking-[0.53px] lg:text-[16px] lg:leading-normal lg:tracking-normal" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
      <p class="text-[25.29px] leading-[26px] tracking-[0.53px] lg:text-[14px] lg:leading-normal lg:tracking-normal" style="color:var(--color-accent-green)">{{ $p['weight'] }}</p>
    </div>
    <p class="text-[20.23px] leading-[25px] tracking-[0.53px] lg:text-[13px] lg:leading-normal lg:tracking-normal" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
    <div class="h-[8.43px] lg:h-[6px] w-full rounded-[40.457px] lg:rounded-full" style="background:var(--color-progress-track)">
      <div class="h-full rounded-[40.457px] lg:rounded-full" style="background:var(--color-accent-green); width:{{ $p['progress'] }}%"></div>
    </div>
    <p class="text-[16.86px] leading-[21px] tracking-[0.53px] text-center lg:text-[11px] lg:leading-normal lg:tracking-normal lg:text-left" style="color:var(--color-accent-green)">{{ $p['ordered'] }}</p>
    <div class="flex items-center justify-center gap-[8.48px] lg:justify-start lg:gap-[6px]">
      <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[10.59px] w-[72.57px] lg:h-[8px] lg:w-[56px]" />
      <span class="text-[20.23px] leading-[25px] tracking-[0.53px] lg:text-[12px] lg:leading-normal lg:tracking-normal" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
    </div>
    <div class="flex items-end gap-[8.48px] lg:gap-[6px]">
      <p class="font-medium text-[26.97px] leading-[34px] text-center lg:text-[16px] lg:leading-normal lg:text-left" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="font-light text-[14.83px] leading-[19px] line-through lg:font-normal lg:text-[11px] lg:leading-normal" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="text-[20.23px] leading-[25px] tracking-[0.53px] lg:text-[12px] lg:leading-normal lg:tracking-normal" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      @endif
    </div>
    <div class="flex flex-col gap-[4.24px] items-start w-full lg:hidden">
      <div class="flex gap-[4.24px] items-center w-full">
        <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[20.23px]" />
        <p class="font-medium text-[13.49px] leading-[17px] text-right" style="color:var(--color-success)">Delivered by 24 March</p>
      </div>
      <div class="flex gap-[8.48px] items-center w-full">
        <img src="{{ asset('elora-2/assets/icons/cart-x.svg') }}" alt="" class="size-[16.95px]" />
        <p class="font-medium text-[12.72px] leading-[16px] text-right" style="color:var(--color-success)">Only 5 left</p>
      </div>
    </div>
  </div>
</a>
