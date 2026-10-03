{{-- Expects $p: name, weight, price, oldPrice, discount --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex gap-0 h-[110px] lg:h-[169px] items-stretch rounded-[10px] lg:rounded-[11.27px] shadow-[var(--shadow-card-lg)] lg:shadow-none overflow-hidden w-full" style="background:var(--color-bg-main)">
  <div class="relative shrink-0 w-[42%] lg:w-[43.37%]">
    <img src="{{ $p['image'] ?? asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    @if (!empty($p['favData']))
      <button type="button" aria-label="{{ __('Add to favorites') }}" onclick="event.preventDefault(); event.stopPropagation(); eloraHeartToggle(this)"
        data-fav="{{ $p['favData'] }}"
        data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
        data-product-id="{{ $p['id'] ?? '' }}"
        class="absolute top-[6px] start-[6px] lg:top-[7.08px] lg:start-[7.08px] bg-white cursor-pointer shadow lg:shadow-[0_4.72px_4.72px_rgba(0,0,0,0.15)] flex items-center justify-center p-[6px] lg:p-[9.44px] rounded-full">
        <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[13px] lg:size-[23.61px]" />
      </button>
    @else
      <button type="button" aria-label="{{ __('Add to favorites') }}" class="absolute top-[6px] start-[6px] lg:top-[7.08px] lg:start-[7.08px] bg-white cursor-pointer shadow lg:shadow-[0_4.72px_4.72px_rgba(0,0,0,0.15)] flex items-center justify-center p-[6px] lg:p-[9.44px] rounded-full">
        <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[13px] lg:size-[23.61px]" />
      </button>
    @endif
    <div class="absolute top-0 end-0 flex items-center justify-center px-[8px] py-[4px] lg:p-[6.21px] rounded-tl-[6px] rounded-br-[6px] lg:rounded-tl-[8.28px] lg:rounded-br-[8.28px]" style="background:var(--color-yellow)">
      <p class="font-normal text-[10px] lg:text-[14.49px] tracking-[0.3px] lg:tracking-[0.52px] whitespace-nowrap" style="color:var(--color-black-alt)">{{ !empty($p['discount']) ? $p['discount'] : ($p['sold'] ?? __('Sold')) }}</p>
    </div>
  </div>
  <div class="flex flex-1 flex-col gap-[4px] lg:gap-[7.51px] p-[8px] lg:p-[7.51px] items-start min-w-0 justify-center">
    <div class="flex flex-col gap-[4px] lg:gap-[4.72px] items-start w-full">
      <div class="flex items-center justify-between gap-[6px] lg:gap-[2.36px] w-full whitespace-nowrap">
        <p class="font-medium text-[13px] lg:text-[22.54px] lg:leading-[28px] lg:tracking-[0.59px] truncate" style="color:var(--color-black)">{{ $p['name'] }}</p>
        <p class="font-normal text-[11px] lg:text-[18.78px] lg:leading-[30px] lg:tracking-[0.59px] shrink-0" style="color:var(--color-primary)">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[10px] lg:text-[18.78px] lg:leading-[24px] lg:tracking-[0.59px] w-full truncate" style="color:var(--color-subtitle)">{{ !empty($p['description']) ? $p['description'] : __('Premium cotton blend') }}</p>
    </div>
    <div class="flex flex-col gap-[2px] lg:gap-[4.72px] items-start">
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flex gap-[5px] lg:gap-[9.44px] items-center">
        <div class="flex items-center gap-[1px]" style="height:7px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:7px;width:7px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <span class="text-[9px] lg:text-[15.03px] lg:leading-[19px] tracking-[0.3px] lg:tracking-[0.59px] whitespace-nowrap" style="color:var(--color-subtitle)">({{ $__rv }})</span>
      </div>
      <div class="flex gap-[5px] lg:gap-[9.44px] items-end">
        <p class="font-medium text-[13px] lg:text-[22.54px] lg:leading-[28px] whitespace-nowrap" style="color:var(--color-price-blue)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[10px] lg:text-[16.53px] lg:leading-[21px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[10px] lg:text-[15.03px] lg:leading-[19px] tracking-[0.3px] lg:tracking-[0.59px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex flex-col gap-[2px] lg:gap-[4.72px] items-start w-full">
      <div class="flex gap-[4px] lg:gap-[7.51px] items-center w-full">
        <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="size-[11px] lg:size-[22.54px] shrink-0" />
        <p class="font-medium text-[9px] lg:text-[15.03px] lg:leading-[19px] whitespace-nowrap" style="color:var(--color-success)">{{ $p['delivery'] ?? __('Delivered by 24 March') }}</p>
      </div>
      @if (!empty($p['left']))
        <div class="flex gap-[5px] lg:gap-[9.44px] items-center">
          <img src="{{ asset('elora-5/assets/icons/cart-x.svg') }}" alt="" class="size-[10px] lg:size-[18.89px] shrink-0" />
          <p class="font-medium text-[8px] lg:text-[14.17px] lg:leading-[18px] whitespace-nowrap" style="color:var(--color-success)">{{ $p['left'] }}</p>
        </div>
      @endif
    </div>
  </div>
</a>
