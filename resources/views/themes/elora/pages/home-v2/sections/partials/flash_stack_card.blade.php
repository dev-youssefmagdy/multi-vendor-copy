{{-- Expects $p: image, name, weight, price, oldPrice, discount, badge, badgeBg, badgeText, deliveredColor, desc (optional)
     and $layout: rotate, translateY, scale, z --}}
<div class="swiper-slide" style="transform:translateY({{ $layout['translateY'] }}px) rotate({{ $layout['rotate'] }}deg) scale({{ $layout['scale'] }}); z-index:{{ $layout['z'] }};">
  <div class="rounded-[6px] bg-[var(--color-bg-main)] shadow-xl overflow-hidden">
    <div class="relative">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-[130px] lg:h-[238px] w-full object-cover" />
      <span class="absolute top-0 left-0 text-[11px] lg:text-[16px] font-normal tracking-[0.3px] px-[7px] py-[5px] rounded-tl-[6px] rounded-br-[9px]" style="background:{{ $p['badgeBg'] }}; color:{{ $p['badgeText'] }}">{{ $p['badge'] }}</span>
      <button type="button" aria-label="Add to favorites" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[6px] shadow">
        <img src="assets/icons/heart.svg" class="size-[14px] lg:size-[20px]" alt="" />
      </button>
    </div>
    <div class="p-[8px] lg:p-[12px] flex flex-col gap-[4px]">
      <div class="flex items-center justify-between">
        <p class="font-medium text-[12px] lg:text-[19px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="text-[10px] lg:text-[16px]" style="color:var(--color-primary)">{{ $p['weight'] }}</p>
      </div>
      @if (!empty($p['desc']))
        <p class="text-[10px] lg:text-[15px]" style="color:var(--color-text-subtitle)">{{ $p['desc'] }}</p>
      @endif
      <div class="flex items-end gap-[6px]">
        <p class="font-medium text-[14px] lg:text-[19px]" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        <p class="text-[9px] lg:text-[13px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        <p class="text-[10px] lg:text-[13px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      </div>
      <p class="text-[9px] lg:text-[13px] font-medium" style="color:{{ $p['deliveredColor'] }}">Delivered by 24 March · Only 5 left</p>
    </div>
  </div>
</div>
