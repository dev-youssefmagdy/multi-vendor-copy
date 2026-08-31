{{-- Expects $p: image, name, price, oldPrice, discount, optionally url --}}
<div class="swiper-slide h-auto !w-[172px] lg:!w-[272px]">
  <a href="{{ $p['url'] ?? '#' }}" class="bg-white flex flex-col items-start rounded-[6px] h-full overflow-hidden">
    <div class="relative w-full shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-[210px] lg:h-[363px] w-full object-cover bg-[var(--color-page-bg)]" />
      @if (!empty($p['discount']))
        <span class="absolute top-[8px] left-[8px] font-extrabold text-[13px] lg:text-[19px] text-white px-[7px] py-[1px] rounded-[3px]" style="background:var(--color-brand-pink)">{{ $p['discount'] }}</span>
      @endif
      <button type="button" aria-label="Add to favorites" onclick="event.preventDefault()" class="absolute top-[8px] right-[8px] bg-white/90 rounded-full p-[8px] lg:p-[9px] shadow">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[15px] lg:size-[15px]" alt="" />
      </button>
    </div>
    <div class="p-[10px] lg:p-[11px] flex flex-col gap-[6px] w-full">
      <p class="text-[13px] lg:text-[15px] whitespace-nowrap overflow-hidden text-ellipsis" style="color:var(--color-text-slate)">{{ $p['name'] }}</p>
      <div class="flex items-baseline gap-[8px]">
        <p class="font-extrabold text-[17px] lg:text-[19px]" style="color:var(--color-brand-pink)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="text-[12px] lg:text-[14px] line-through" style="color:var(--color-text-faint-alt)">{{ $p['oldPrice'] }}</p>
        @endif
      </div>
    </div>
  </a>
</div>
