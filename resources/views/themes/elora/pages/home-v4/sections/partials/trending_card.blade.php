{{-- Expects $p: image, name, price, oldPrice, discount, rating, ordered, progress --}}
<div class="swiper-slide h-auto">
  <div class="flex bg-[var(--color-bg-main)] rounded-[10px] shadow-sm h-full overflow-hidden">
    <div class="relative w-[45%] shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
      <span class="absolute top-0 right-0 text-[12px] lg:text-[16px] font-normal px-[8px] py-[4px] rounded-bl-[8px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
      <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="{{ asset('elora-4/assets/icons/heart.svg') }}" class="size-[16px] lg:size-[22px]" alt="" /></button>
      <div class="absolute bottom-[8px] right-[8px] rounded-[8px] p-[6px] shadow" style="background:var(--color-text-primary)"><img src="{{ asset('elora-4/assets/icons/cart.svg') }}" class="size-[20px] lg:size-[26px] invert" alt="" /></div>
    </div>
    <div class="flex-1 p-[12px] lg:p-[14px] flex flex-col gap-[8px]">
      <div class="flex items-center justify-between">
        <p class="font-medium text-[16px] lg:text-[19px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="text-[14px] lg:text-[16px]" style="color:var(--color-brand-orange-bright)">{{ $p['weight'] ?? '250g' }}</p>
      </div>
      <p class="text-[13px] lg:text-[16px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
      <div class="h-[6px] lg:h-[8px] w-full rounded-full" style="background:var(--color-stroke)">
        <div class="h-full rounded-full" style="background:var(--color-error); width:{{ $p['progress'] }}%"></div>
      </div>
      <p class="text-[11px] lg:text-[13px]" style="color:var(--color-error)">{{ $p['ordered'] }}</p>
      <div class="flex items-center gap-[6px]">
        <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] lg:h-[10px] w-[56px] lg:w-[70px]" />
        <span class="text-[12px] lg:text-[14px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex items-end gap-[6px]">
        <p class="font-medium text-[16px] lg:text-[19px]" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        <p class="text-[11px] lg:text-[13px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        <p class="text-[12px] lg:text-[14px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      </div>
    </div>
  </div>
</div>
