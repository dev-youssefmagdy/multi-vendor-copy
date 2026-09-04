{{-- Expects $p: image, name, price, oldPrice, discount, optionally url --}}
<div class="swiper-slide h-auto w-full lg:w-auto">
  <a href="{{ $p['url'] ?? '#' }}" class="bg-white flex flex-col items-start h-full overflow-hidden">
    <div class="relative w-full shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-[228px] lg:h-[363px] w-full object-cover bg-[var(--color-page-bg)]" />
      @if (!empty($p['discount']))
        <span class="absolute top-[5px] left-[5px] lg:top-[8px] lg:left-[8px] font-extrabold text-[9px] lg:text-[14px] text-white px-[4px] lg:px-[7px] py-[1px] rounded-[2px] lg:rounded-[3px]" style="background:var(--color-brand-pink)">{{ $p['discount'] }}</span>
      @endif
      <button type="button" aria-label="Add to favorites" onclick="event.preventDefault()" class="absolute top-[5px] right-[5px] lg:top-[8px] lg:right-[8px] bg-white/90 rounded-full p-[6px] lg:p-[9px] shadow">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[9.5px] lg:size-[15px]" alt="" />
      </button>
    </div>
    <div class="p-[7px] lg:p-[11px] flex flex-col gap-0 w-full">
      <p class="text-[9.5px] lg:text-[15px] whitespace-nowrap overflow-hidden text-ellipsis" style="color:var(--color-text-slate)">{{ $p['name'] }}</p>
      <div class="flex items-baseline gap-[3px] lg:gap-[5px]">
        <p class="font-extrabold text-[12px] lg:text-[19px]" style="color:var(--color-brand-pink)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="text-[9px] lg:text-[14px] line-through" style="color:var(--color-text-faint-alt)">{{ $p['oldPrice'] }}</p>
        @endif
      </div>
    </div>
  </a>
</div>
