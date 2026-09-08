{{-- Expects $p: name, weight, price, oldPrice, discount, rating, stock, alt (bool), image (optional) --}}
@php
    $priceColor = !empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-price-blue)';
    $deliveredColor = !empty($p['alt']) ? 'var(--color-error)' : 'var(--color-success)';
@endphp
<a href="{{ $p['url'] ?? '#' }}" class="flex flex-col items-start rounded-[8px] h-full shadow-[var(--shadow-card-lg)]" style="background:var(--color-bg-main)">
  <div class="relative flex flex-col gap-[4px] lg:gap-[6px] h-[130px] lg:h-[190px] items-end justify-end px-[5px] lg:px-[7px] py-[4px] lg:py-[5px] shrink-0 w-full">
    <div class="absolute start-0 top-0 h-full w-full rounded-t-[6px] lg:rounded-t-[8px] overflow-hidden">
      <img src="{{ $p['image'] ?? asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    </div>
    <div class="absolute top-0 start-0 flex items-center justify-center px-[10px] py-[5px] rounded-bl-[8px] rounded-tr-[6px] lg:rounded-tr-[8px] shrink-0" style="background:{{ !empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-yellow)' }}">
      <p class="font-normal text-[11px] lg:text-[14px] tracking-[0.3px] whitespace-nowrap" style="color:{{ !empty($p['alt']) ? '#fff' : 'var(--color-black-alt)' }}">{{ !empty($p['discount']) ? $p['discount'] : (!empty($p['alt']) ? __('30% OFF') : __('70% Sold')) }}</p>
    </div>
    @if (!empty($p['favData']))
      <button type="button" aria-label="{{ __('Add to favorites') }}" onclick="event.preventDefault(); event.stopPropagation(); eloraHeartToggle(this)"
        data-fav="{{ $p['favData'] }}"
        data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
        data-product-id="{{ $p['id'] ?? '' }}"
        class="absolute top-[5px] end-[5px] bg-white cursor-pointer shadow flex items-center justify-center p-[5px] lg:p-[8px] rounded-full shrink-0 size-[22px] lg:size-[32px]">
        <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[13px] lg:size-[19px]" />
      </button>
    @else
      <button type="button" aria-label="{{ __('Add to favorites') }}" class="absolute top-[5px] end-[5px] bg-white cursor-pointer shadow flex items-center justify-center p-[5px] lg:p-[8px] rounded-full shrink-0 size-[22px] lg:size-[32px]">
        <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="size-[13px] lg:size-[19px]" />
      </button>
    @endif
    @if (!empty($p['isOutOfStock']))
      <div class="relative flex items-center justify-center p-[7px] lg:p-[10px] rounded-full shrink-0 size-[34px] lg:size-[48px] bg-white shadow opacity-50 cursor-not-allowed">
        <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="{{ __('Add to cart') }}" class="size-[18px] lg:size-[26px]" />
      </div>
    @elseif (!empty($p['hasMultipleVariants']))
      <button type="button"
        onclick="event.preventDefault(); event.stopPropagation(); openVariantModal({{ $p['id'] ?? 'null' }}, {{ \Illuminate\Support\Js::from($p['nameJs'] ?? ($p['name'] ?? '')) }}, {{ \Illuminate\Support\Js::from($p['variantModalData'] ?? []) }})"
        class="relative flex items-center justify-center p-[7px] lg:p-[10px] rounded-full shrink-0 size-[34px] lg:size-[48px] bg-white shadow">
        <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="{{ __('Add to cart') }}" class="size-[18px] lg:size-[26px]" />
      </button>
    @else
      <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 'null' }})" onclick="event.preventDefault(); event.stopPropagation()"
        class="relative flex items-center justify-center p-[7px] lg:p-[10px] rounded-full shrink-0 size-[34px] lg:size-[48px] bg-white shadow">
        <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="{{ __('Add to cart') }}" class="size-[18px] lg:size-[26px]" />
      </button>
    @endif
  </div>
  <div class="flex flex-col gap-[4px] lg:gap-[6px] items-start p-[6px] lg:p-[8px] relative shrink-0 w-full">
    <div class="flex flex-col gap-[2px] items-start w-full">
      <div class="flex items-center justify-between gap-[6px] w-full whitespace-nowrap">
        <p class="font-medium text-[13px] lg:text-[19px] truncate" style="color:var(--color-black)">{{ $p['name'] }}</p>
        <p class="font-normal text-[11px] lg:text-[15px] shrink-0" style="color:{{ $priceColor }}">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[10px] lg:text-[15px] w-full truncate" style="color:var(--color-subtitle)">{{ !empty($p['description']) ? $p['description'] : __('Premium cotton blend') }}</p>
    </div>
    <div class="flex flex-col gap-[2px] items-start">
      <div class="flex gap-[5px] lg:gap-[8px] items-center justify-center">
        <img src="{{ asset('elora-5/assets/icons/star-rating.svg') }}" alt="" class="h-[7px] lg:h-[10px] w-[48px] lg:w-[68px]" />
        <span class="text-[9px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[5px] lg:gap-[8px] items-end flex-wrap">
        <p class="font-medium text-[13px] lg:text-[19px] whitespace-nowrap" style="color:var(--color-black)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[9px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[9px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex flex-col gap-[2px] items-start w-full">
      <div class="flex gap-[5px] lg:gap-[7px] items-center w-full">
        <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="size-[12px] lg:size-[16px] shrink-0" />
        <p class="font-medium text-[9px] lg:text-[12px] whitespace-nowrap truncate" style="color:{{ $deliveredColor }}">{{ $p['delivery'] ?? __('Delivered by 24 March') }}</p>
      </div>
      @if (!empty($p['stock']))
        <div class="flex gap-[5px] lg:gap-[8px] items-center">
          <img src="{{ asset('elora-5/assets/icons/cart-x.svg') }}" alt="" class="size-[12px] lg:size-[15px] shrink-0" />
          <p class="font-medium text-[9px] lg:text-[11px] whitespace-nowrap" style="color:{{ $deliveredColor }}">{{ $p['stock'] }}</p>
        </div>
      @endif
    </div>
  </div>
</a>
