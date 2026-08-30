{{-- Expects $p: image, name, weight, badge, badgeBg, badgeText, progress, ordered, rating, price, oldPrice, discount --}}
<div class="swiper-slide !h-[175px] lg:!h-[204px] !w-[300px] lg:!w-[461px]">
  <div class="flex items-center h-full bg-[var(--color-bg-main)] rounded-[10px] shadow-sm overflow-hidden">
    <div class="relative shrink-0 w-[132px] h-[140px] lg:w-[192px] lg:h-[204px]">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
      <span class="absolute top-0 right-0 text-white text-[12px] font-normal px-[8px] py-[4px] rounded-bl-[8px]" style="background:{{ $p['badgeBg'] }}; color:{{ $p['badgeText'] }}">{{ $p['badge'] }}</span>
      <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow">
        <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" class="size-[16px]" alt="" />
      </button>
      <div class="absolute bottom-[8px] right-[8px] bg-[var(--color-bg-main)] rounded-[8px] p-[6px] shadow">
        <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" class="size-[20px]" alt="" />
      </div>
    </div>
    <div class="flex-1 p-[12px] flex flex-col gap-[8px]">
      <div class="flex items-center justify-between">
        <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="text-[14px]" style="color:var(--color-accent-purple)">{{ $p['weight'] }}</p>
      </div>
      <p class="text-[13px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
      <div class="h-[6px] w-full rounded-full" style="background:var(--color-stroke)">
        <div class="h-full rounded-full" style="background:var(--color-accent-purple); width:{{ $p['progress'] }}%"></div>
      </div>
      <p class="text-[11px]" style="color:var(--color-accent-purple)">{{ $p['ordered'] }}</p>
      <div class="flex items-center gap-[6px]">
        <img src="{{ asset('elora-1/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[56px]" />
        <span class="text-[12px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex items-end gap-[6px]">
        <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="text-[11px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="text-[12px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
  </div>
</div>
