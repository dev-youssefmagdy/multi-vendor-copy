{{-- Expects $p: image, name, price, oldPrice, discount, optionally url --}}
<div class="swiper-slide h-auto w-full lg:w-auto">
  <a href="{{ $p['url'] ?? '#' }}" class="group bg-white flex flex-col items-start h-full overflow-hidden transition-all duration-300 ease-out hover:shadow-lg hover:scale-[1.02]">
    <div class="relative w-full shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-[228px] lg:h-[363px] w-full object-cover bg-[var(--color-page-bg)] transition-transform duration-300 group-hover:scale-105" />
      @if (!empty($p['discount']))
        <span class="absolute top-[5px] start-[5px] lg:top-[8px] lg:start-[8px] font-extrabold text-[9px] lg:text-[14px] text-white px-[4px] lg:px-[7px] py-[1px] rounded-[2px] lg:rounded-[3px]" style="background:var(--color-brand-pink)">{{ $p['discount'] }}</span>
      @endif
      <button type="button" aria-label="{{ __('Add to favorites') }}" onclick="event.preventDefault(); eloraHeartToggle(this)"
        data-fav="{{ $p['favData'] ?? '' }}"
        data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
        data-product-id="{{ $p['id'] ?? '' }}"
        class="absolute top-[5px] end-[5px] lg:top-[8px] lg:end-[8px] bg-white/90 rounded-full p-[6px] lg:p-[9px] shadow">
        <svg class="size-[9.5px] lg:size-[15px]" viewBox="0 0 24 24" fill="none" stroke="#1B1B1B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12.62 20.81C12.28 20.93 11.72 20.93 11.38 20.81C8.48 19.82 2 15.69 2 8.69C2 5.6 4.49 3.1 7.56 3.1C9.38 3.1 10.99 3.98 12 5.34C13.01 3.98 14.63 3.1 16.44 3.1C19.51 3.1 22 5.6 22 8.69C22 15.69 15.52 19.82 12.62 20.81Z"/></svg>
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
