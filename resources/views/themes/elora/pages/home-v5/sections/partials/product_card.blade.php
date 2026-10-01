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
      <p class="font-normal text-[11px] lg:text-[14px] tracking-[0.3px] whitespace-nowrap" style="color:{{ !empty($p['alt']) ? '#fff' : 'var(--color-black-alt)' }}">{{ !empty($p['discount']) ? $p['discount'] : (!empty($p['alt']) ? __('30% OFF') : ($p['sold'] ?? __('Sold'))) }}</p>
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
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flex gap-[5px] lg:gap-[8px] items-center justify-center">
        <div class="flex items-center gap-[1px]" style="height:7px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:7px;width:7px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <span class="text-[9px] lg:text-[12px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-subtitle)">({{ $__rv }})</span>
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
    </div>
  </div>
</a>
