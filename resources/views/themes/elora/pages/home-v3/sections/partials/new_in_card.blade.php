{{-- Expects $p: image, name, price, oldPrice, discount, rating, url; weight/desc/sold/stockLeft optional --}}
<div class="swiper-slide">
  <a href="{{ $p['url'] ?? '#' }}" class="bg-[#FDFDFD] flex items-stretch gap-[3px] lg:gap-0 rounded-[6px] lg:rounded-[9px] h-[90px] max-h-[90px] lg:h-[129px] lg:max-h-[129px] shadow-sm overflow-hidden">
    <div class="relative shrink-0 w-[124px] lg:w-[178px]">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover bg-[var(--color-page-bg)]" />
      <button type="button" aria-label="{{ __('Add to favorites') }}" onclick="event.preventDefault(); eloraHeartToggle(this)"
        data-fav="{{ $p['favData'] ?? '' }}"
        data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
        data-product-id="{{ $p['id'] ?? '' }}"
        class="absolute top-[4px] start-[4px] bg-white cursor-pointer shadow-[0_2px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[4px] lg:p-[6px] rounded-full size-[18px] lg:size-[29px]">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" alt="" class="size-[10px] lg:size-[18px]" />
      </button>
      <span class="absolute top-0 end-0 text-[8px] lg:text-[11px] font-normal tracking-[0.3px] lg:tracking-[0.4px] px-[4px] py-[2px] lg:px-[5px] lg:py-[3px] rounded-es-[5px] lg:rounded-es-[6px] whitespace-nowrap" style="background:#FFD428; color:#242424">{{ $p['sold'] ?? 70 }}% {{ __('Sold') }}</span>
      <div aria-hidden="true" class="absolute bottom-[4px] end-[4px] flex items-center justify-center w-[29px] h-[23px] lg:w-[51px] lg:h-[41px] bg-[#FDFDFD] rounded-[8px] lg:rounded-[14px] shadow-sm">
        <img src="{{ asset('elora-3/assets/icons/cart-add.svg') }}" alt="" class="size-[12px] lg:size-[22px]" />
      </div>
    </div>
    <div class="flex-1 flex flex-col gap-[2px] lg:gap-[4px] p-[6px] lg:p-[10px] min-w-0 justify-center overflow-hidden">
      <div class="flex items-center justify-between gap-[4px] lg:gap-[8px] w-full min-w-0">
        <p class="font-medium text-[11px] lg:text-[17px] tracking-[0.3px] lg:tracking-[0.45px] truncate min-w-0" style="color:#121212">{{ $p['name'] }}</p>
        <p class="font-normal text-[9px] lg:text-[14px] tracking-[0.3px] lg:tracking-[0.45px] shrink-0" style="color:#CD0032">{{ $p['weight'] ?? '' }}</p>
      </div>
      <p class="font-normal text-[9px] lg:text-[14px] tracking-[0.3px] lg:tracking-[0.45px] min-w-0 w-full" style="color:#ADADAD">{{ $p['desc'] ?? __('Premium quality') }}</p>
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flex gap-[4px] lg:gap-[7px] items-center">
        <div class="flex items-center gap-[1px]" style="height:8px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:8px;width:8px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <span class="font-normal text-[9px] lg:text-[11px] tracking-[0.3px] lg:tracking-[0.45px] whitespace-nowrap" style="color:#ADADAD">({{ $__rv }})</span>
      </div>
      <div class="flex gap-[4px] lg:gap-[7px] items-end">
        <p class="font-medium text-[11px] lg:text-[17px] whitespace-nowrap" style="color:#FE3A6A">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[9px] lg:text-[13px] line-through whitespace-nowrap" style="color:#ADADAD">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[9px] lg:text-[11px] tracking-[0.3px] lg:tracking-[0.45px] whitespace-nowrap" style="color:#FF522C">{{ $p['discount'] }}</p>
        @endif
      </div>
      <div class="hidden lg:flex items-center gap-[6px] w-full min-w-0">
        <img src="{{ asset('elora-3/assets/icons/truck-delivery.svg') }}" alt="" class="size-[17px] shrink-0" />
        <span class="font-medium text-[11px] truncate" style="color:#2AAF2F">{{ __('Delivered by') }} {{ now()->addDays(3)->translatedFormat('d F') }}</span>
      </div>
    </div>
  </a>
</div>
