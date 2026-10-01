{{-- Expects $p: url, image, badge, badgeBg, name, weight, desc, rating, price, oldPrice, discount --}}
<a href="{{ $p['url'] ?? '#' }}" class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[8px] h-full">
  <div class="flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
    <div class="absolute flex gap-[8px] h-[183px] lg:h-[227px] items-start left-0 p-[6px] top-0 w-full">
      <div class="absolute flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end left-0 top-0 w-full">
        <div class="absolute left-1/2 -translate-x-1/2 h-[183px] lg:h-[227px] rounded-t-[8px] top-0 w-full overflow-hidden">
          <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        </div>
        <div class="content-stretch flex h-[28px] items-center justify-center p-[6px] relative rounded-bl-[8px] rounded-tr-[8px] shrink-0" style="background:{{ $p['badgeBg'] }}">
          <p class="font-medium text-[14px] text-white tracking-[0.5px] whitespace-nowrap">{{ $p['badge'] }}</p>
        </div>
      </div>
      <button type="button" aria-label="{{ __('Add to favorites') }}"
        @if (!empty($p['favData']))
          onclick="event.preventDefault(); eloraHeartToggle(this)"
          data-fav="{{ $p['favData'] }}"
          data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
          data-product-id="{{ $p['id'] ?? '' }}"
        @endif
        class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px]">
        <img src="{{ asset('elora-1/assets/icons/heart.svg') }}" alt="" class="size-[20px]" />
      </button>
    </div>
    @if (!empty($p['isOutOfStock']))
      <div role="button" aria-disabled="true" onclick="event.preventDefault()" class="bg-[var(--color-bg-main)] flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px] cursor-not-allowed" style="background:var(--color-text-primary)">
        <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" alt="{{ __('Add to cart') }}" class="size-[24px] opacity-40" />
      </div>
    @elseif (!empty($p['hasMultipleVariants']))
      <div role="button" tabindex="0"
        onclick="event.preventDefault(); openVariantModal({{ $p['id'] ?? 'null' }}, {{ \Illuminate\Support\Js::from($p['fullName'] ?? ($p['name'] ?? '')) }}, {{ \Illuminate\Support\Js::from($p['variantModalData'] ?? []) }})"
        class="bg-[var(--color-bg-main)] flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px]" style="background:var(--color-text-primary)">
        <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" alt="{{ __('Add to cart') }}" class="size-[24px]" />
      </div>
    @else
      <div role="button" tabindex="0" wire:click.prevent="addToCart({{ $p['id'] ?? 'null' }})" onclick="event.preventDefault()" class="bg-[var(--color-bg-main)] flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px]" style="background:var(--color-text-primary)">
        <img src="{{ asset('elora-1/assets/icons/cart.svg') }}" alt="{{ __('Add to cart') }}" class="size-[24px]" />
      </div>
  </div>
  <div class="flex flex-col gap-[8px] items-start p-[8px] relative shrink-0 w-full">
    <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
      <div class="flex items-center justify-between gap-[4px] w-full">
        <p class="font-medium text-[var(--color-text-primary)] text-[16px] truncate min-w-0">{{ $p['name'] }}</p>
        <p class="font-normal text-[var(--color-primary)] text-[14px] text-center shrink-0 whitespace-nowrap">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[var(--color-text-subtitle)] text-[14px] w-full">{{ $p['desc'] }}</p>
    </div>
    <div class="flex flex-col gap-[4px] items-start">
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flex gap-[8px] items-center justify-center">
        <div class="flex items-center gap-[1px]" style="height:10px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:10px;width:10px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <p class="font-normal text-[var(--color-text-subtitle)] text-[12px] tracking-[0.5px] whitespace-nowrap">({{ $__rv }})</p>
      </div>
      <div class="flex gap-[8px] items-end">
        <p class="font-medium text-[var(--color-text-primary)] text-[18px] whitespace-nowrap">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[var(--color-text-subtitle)] text-[14px] line-through whitespace-nowrap">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[var(--color-secondary)] text-[12px] tracking-[0.5px] whitespace-nowrap">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    @if (!empty($p['delivered']))
      <div class="flex flex-col gap-[4px] items-start w-full">
        <div class="flex gap-[4px] items-center w-full">
          <img src="{{ asset('elora-1/assets/icons/truck-delivery.svg') }}" alt="" class="size-[18px]" />
          <p class="font-medium text-[var(--color-success)] text-[12px] whitespace-nowrap">{{ $p['delivered'] }}</p>
        </div>
      </div>
  </div>
</a>
