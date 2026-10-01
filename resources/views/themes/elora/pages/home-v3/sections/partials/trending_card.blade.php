{{-- Expects $p: image, name, price, oldPrice, discount, rating, url; weight/desc/progress/ordered/sold/stockLeft optional --}}
<div class="swiper-slide h-auto">
  <a href="{{ $p['url'] ?? '#' }}" class="flex flex-col bg-[#FDFDFD] rounded-[11px] shadow-[0_0_34px_rgba(0,0,0,0.16)] h-full overflow-hidden" style="text-decoration:none">
    <div class="relative w-full h-[213px] shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
      <button type="button" aria-label="{{ __('Add to favorites') }}" onclick="event.preventDefault(); eloraHeartToggle(this)"
        data-fav="{{ $p['favData'] ?? '' }}"
        data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
        data-product-id="{{ $p['id'] ?? '' }}"
        class="absolute top-[8px] start-[8px] flex items-center justify-center size-[37px] bg-white rounded-full shadow-[0_5px_5px_rgba(0,0,0,0.15)]">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" class="size-[23px]" alt="" />
      </button>
      <span class="absolute top-0 end-0 flex items-center justify-center font-normal text-[14px] tracking-[0.5px] px-[10px] py-[5px] rounded-es-[12px]" style="background:#FFD428; color:#242424">{{ $p['sold'] ?? 70 }}% {{ __('Sold') }}</span>
      <div aria-hidden="true" class="absolute bottom-[8px] end-[8px] flex items-center justify-center w-[66px] h-[52px] bg-[#FDFDFD] rounded-[19px] shadow-sm">
        <img src="{{ asset('elora-3/assets/icons/cart-add-dark.svg') }}" class="size-[28px]" alt="" />
      </div>
    </div>
    <div class="flex-1 flex flex-col gap-[7px] p-[7px] w-full">
      <div class="flex flex-col gap-[5px] w-full">
        <div class="flex items-center justify-between gap-[2px] w-full">
          <p class="font-medium text-[22px] tracking-[0.58px] truncate min-w-0" style="color:#121212">{{ $p['name'] }}</p>
          <p class="font-normal text-[18.5px] tracking-[0.58px] shrink-0" style="color:#FE3A6A">{{ $p['weight'] ?? '' }}</p>
        </div>
        <p class="font-normal text-[18.5px] tracking-[0.58px] truncate w-full" style="color:#ADADAD">{{ $p['desc'] ?? __('Premium quality') }}</p>
      </div>

      <div class="flex flex-col gap-[4px] w-full">
        <div class="h-[6.4px] w-full rounded-full" style="background:#D9D9D9">
          <div class="h-full rounded-full" style="background:#E40307; width:{{ $p['progress'] ?? 83 }}%"></div>
        </div>
        <p class="font-normal text-[13px] tracking-[0.4px] truncate" style="color:#E40307">{{ $p['ordered'] ?? __('5 ordered last 30 min') }}</p>
      </div>

      <div class="flex flex-col gap-[5px]">
        @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
        <div class="flex items-center gap-[9px]">
          <div class="flex items-center gap-[1px]" style="height:12px">
            @for ($__i = 1; $__i <= 5; $__i++)
              <svg style="height:12px;width:12px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
            @endfor
          </div>
          <span class="font-normal text-[15px] tracking-[0.58px] whitespace-nowrap" style="color:#ADADAD">({{ $__rv }})</span>
        </div>
        <div class="flex items-end gap-[9px]">
          <p class="font-medium text-[22px] whitespace-nowrap" style="color:#FE3A6A">{{ $p['price'] }}</p>
          @if (!empty($p['oldPrice']))
          <p class="font-light text-[16px] line-through whitespace-nowrap" style="color:#ADADAD">{{ $p['oldPrice'] }}</p>
          @endif
          @if (!empty($p['discount']))
          <p class="font-normal text-[15px] tracking-[0.58px] whitespace-nowrap" style="color:#FF522C">{{ $p['discount'] }}</p>
          @endif
        </div>
      </div>

      <div class="flex items-center gap-[7px] w-full">
        <img src="{{ asset('elora-3/assets/icons/truck-delivery.svg') }}" alt="" class="size-[18px] shrink-0" />
        <span class="font-medium text-[15px] whitespace-nowrap" style="color:#2AAF2F">{{ __('Delivered by') }} {{ now()->addDays(3)->translatedFormat('d F') }}</span>
      </div>
    </div>
  </a>
</div>
