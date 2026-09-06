{{-- Deal card, ported from public/souqify-1/carousels.js renderDealCardInner(). $p keys:
     url, image, name (Str::limit'd), weight, rating, price, oldPrice, discount, discountBg, discountColor --}}
<div class="flex flex-col items-start rounded-[8px] h-full overflow-hidden" style="background:var(--color-bg-main)">
  <div class="relative w-full shrink-0">
    <a href="{{ $p['url'] }}" class="block w-full h-[183px] lg:h-[277px] overflow-hidden" style="background:var(--color-card-bg-soft)">
      @if ($p['image'])
        <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="w-full h-full object-cover" />
      @endif
    </a>
    <button type="button" wire:click="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[7px] right-[7px] lg:top-[10px] lg:right-[10px] bg-white rounded-full p-[9px] lg:p-[13px] shadow cursor-pointer">
      <img src="{{ asset('souqify-1/assets/icons/icon-heart-outline.svg') }}" class="size-[15px] lg:size-[22px]" alt="" />
    </button>
    <div class="absolute bottom-[8px] right-[8px] lg:bottom-[14px] lg:right-[14px] bg-white rounded-[5px] p-[8px] lg:p-[11px] shadow flex items-center justify-center">
      <button type="button" wire:click="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to cart') }}">
        <img src="{{ asset('souqify-1/assets/icons/icon-cart-add.svg') }}" class="size-[22px] lg:size-[33px]" alt="" />
      </button>
    </div>
    @if (!empty($p['badge']))
      <div class="absolute top-0 left-0 flex items-center h-[18px] lg:h-[26px]">
        <img src="{{ asset('souqify-1/assets/icons/ribbon-ticket.svg') }}" class="absolute inset-0 h-full w-auto" alt="" />
        <span class="relative pl-[8px] lg:pl-[12px] pr-[16px] lg:pr-[22px] text-white text-[9px] lg:text-[12px] font-medium tracking-[0.5px] whitespace-nowrap">{{ $p['badge'] }}</span>
      </div>
    @endif
  </div>
  <div class="flex flex-col gap-[5px] lg:gap-[8px] p-[5px] lg:p-[8px] w-full">
    <div class="flex items-start justify-between gap-[4px]">
      <a href="{{ $p['url'] }}" class="text-[14px] lg:text-[17px] font-medium truncate" style="color:var(--color-text-heading-alt)">{{ $p['name'] }}</a>
    </div>
    <div class="flex items-center gap-[6px]">
      <img src="{{ asset('souqify-1/assets/icons/icon-star-rating.svg') }}" class="h-[8px] lg:h-[10px] w-[58px] lg:w-[70px]" alt="" />
      <span class="text-[12px] lg:text-[15px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
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
