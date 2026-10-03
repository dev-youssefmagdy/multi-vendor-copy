{{-- Deal card, ported from public/souqify-1/carousels.js renderDealCardInner(). $p keys:
     url, image, name (Str::limit'd), weight, rating, price, oldPrice, discount, discountBg, discountColor --}}
<div class="flex flex-col items-start rounded-[8px] h-full overflow-hidden" style="background:var(--color-bg-main)">
  <div class="relative w-full shrink-0">
    <a href="{{ $p['url'] }}" class="block w-full h-[183px] lg:h-[277px] overflow-hidden" style="background:var(--color-card-bg-soft)">
      @if ($p['image'])
        <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="w-full h-full object-cover" />
      @endif
    </a>
    @if (!empty($p['slug']) && !empty($p['favData']))
      <button type="button" onclick="souqifyToggleFavorite(this)" data-slug="{{ $p['slug'] }}" data-fav='{{ $p['favData'] }}' aria-label="{{ __('Add to favorites') }}" class="absolute top-[7px] right-[7px] rtl:right-auto rtl:left-[7px] lg:top-[10px] lg:right-[10px] lg:rtl:right-auto lg:rtl:left-[10px] bg-white rounded-full p-[9px] lg:p-[13px] shadow cursor-pointer">
        <img class="fav-icon size-[15px] lg:size-[22px]" src="{{ asset('souqify-1/assets/icons/icon-heart-outline.svg') }}" alt="" />
      </button>
    @else
      <button type="button" wire:click="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[7px] right-[7px] rtl:right-auto rtl:left-[7px] lg:top-[10px] lg:right-[10px] lg:rtl:right-auto lg:rtl:left-[10px] bg-white rounded-full p-[9px] lg:p-[13px] shadow cursor-pointer">
        <img src="{{ asset('souqify-1/assets/icons/icon-heart-outline.svg') }}" class="size-[15px] lg:size-[22px]" alt="" />
      </button>
    @endif
    <div class="absolute bottom-[8px] right-[8px] rtl:right-auto rtl:left-[8px] lg:bottom-[14px] lg:right-[14px] lg:rtl:right-auto lg:rtl:left-[14px] bg-white rounded-[5px] p-[8px] lg:p-[11px] shadow flex items-center justify-center">
      <button type="button" wire:click="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to cart') }}">
        <img src="{{ asset('souqify-1/assets/icons/icon-cart-add.svg') }}" class="size-[22px] lg:size-[33px]" alt="" />
      </button>
    </div>
    @if (!empty($p['badge']))
      <div class="absolute top-0 left-0 rtl:left-auto rtl:right-0 flex items-center h-[18px] lg:h-[26px]">
        <img src="{{ asset('souqify-1/assets/icons/ribbon-ticket.svg') }}" class="absolute inset-0 h-full w-auto" alt="" />
        <span class="relative ps-[8px] lg:ps-[12px] pe-[16px] lg:pe-[22px] text-white text-[9px] lg:text-[12px] font-medium tracking-[0.5px] whitespace-nowrap">{{ $p['badge'] }}</span>
      </div>
    @endif
  </div>
  <div class="flex flex-col gap-[5px] lg:gap-[8px] p-[5px] lg:p-[8px] w-full">
    <div class="flex items-start justify-between gap-[4px]">
      <a href="{{ $p['url'] }}" class="text-[14px] lg:text-[17px] font-medium truncate" style="color:var(--color-text-heading-alt)">{{ $p['name'] }}</a>
    </div>
    @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
    <div class="flex items-center gap-[6px]">
      <div class="flex items-center gap-[1px]" style="height:8px">
        @for ($__i = 1; $__i <= 5; $__i++)
          <svg style="height:8px;width:8px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
        @endfor
      </div>
      <span class="text-[12px] lg:text-[15px]" style="color:var(--color-text-subtitle)">({{ $__rv }})</span>
    </div>
    <div class="flex items-center justify-between">
      <div class="flex items-end gap-[5px]">
        <p class="text-[16px] lg:text-[20px] font-bold" style="color:var(--color-brand-purple)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="text-[10px] lg:text-[12px] line-through" style="color:var(--color-gray)">{{ $p['oldPrice'] }}</p>
        @endif
      </div>
      @if (!empty($p['discount']))
        <div class="flex items-center justify-center px-[5px] py-[4px] lg:px-[6px] lg:py-[5px]" style="background:var(--color-badge-discount-yellow, #ffd428)">
          <span class="text-[9px] lg:text-[11px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-primary)">{{ $p['discount'] }}</span>
        </div>
      @endif
    </div>
  </div>
</div>
