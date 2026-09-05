{{-- Expects $p: name, weight, progress, ordered, rating, price, oldPrice, discount, image, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex h-auto bg-[var(--color-bg-main)] rounded-[10.1143px] shadow-sm overflow-hidden">
  <div class="relative w-[41.77%] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
    <span class="absolute top-0 right-0 flex items-center justify-center whitespace-nowrap text-[13.0037px] leading-[16px] tracking-[0.464px] font-normal p-[5.573px] rounded-tr-[7.43px] rounded-bl-[7.43px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
    <button type="button" aria-label="Add to favorites" class="absolute top-[6.358px] left-[6.358px] flex items-center justify-center size-[33.91px] bg-white rounded-full p-[8.477px] shadow-[0px_4.238px_4.238px_rgba(0,0,0,0.15)]">
      <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[21.19px]" alt="" />
    </button>
    <div class="absolute bottom-[5.298px] right-[6.358px] flex items-center justify-center w-[60.4px] h-[47.68px] bg-[var(--color-bg-main)] rounded-[16.954px] px-[12.715px] py-[4.238px] shadow">
      <img src="{{ asset('elora-2/assets/icons/cart-add.svg') }}" class="size-[25.43px]" alt="" />
    </div>
  </div>
  <div class="flex-1 px-[13.486px] py-[6.743px] flex flex-col gap-[13.49px]">
    <div class="flex items-center justify-between gap-[2.12px]">
      <p class="min-w-0 flex-1 line-clamp-1 font-medium text-[23.6px] leading-[30px] tracking-[0.53px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
      <p class="shrink-0 text-[25.29px] leading-[26px] tracking-[0.53px]" style="color:var(--color-accent-green)">{{ $p['weight'] }}</p>
    </div>
    <p class="text-[20.23px] leading-[25px] tracking-[0.53px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
    <div class="h-[8.43px] w-full rounded-[40.457px]" style="background:var(--color-progress-track)">
      <div class="h-full rounded-[40.457px]" style="background:var(--color-accent-green); width:{{ $p['progress'] }}%"></div>
    </div>
    <p class="text-[16.86px] leading-[21px] tracking-[0.53px] text-center" style="color:var(--color-accent-green)">{{ $p['ordered'] }}</p>
    <div class="flex items-center justify-start gap-[8.48px] w-full">
      <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[10.59px] w-[72.57px]" />
      <span class="text-[20.23px] leading-[25px] tracking-[0.53px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
    </div>
    <div class="flex items-end gap-[8.48px]">
      <p class="font-medium text-[26.97px] leading-[34px] text-center" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="font-light text-[14.83px] leading-[19px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="text-[20.23px] leading-[25px] tracking-[0.53px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      @endif
    </div>
    <div class="flex gap-[4.24px] items-center w-full">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[20.23px]" />
      <p class="font-medium text-[13.49px] leading-[17px] text-right" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>
  </div>
</a>
