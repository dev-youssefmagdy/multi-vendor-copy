{{-- Recommended For You grid card — shorter image band than product_card, no badge/delivery rows. Expects $p: image, name, weight, weightColor, rating, price, priceColor, oldPrice, discount, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] shadow-[var(--shadow-card)] overflow-hidden">
  <div class="flex flex-col gap-[8px] h-[160px] lg:h-[227px] items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
    <div class="absolute flex gap-[8px] h-[160px] lg:h-[227px] items-start left-0 p-[6px] top-0 w-full">
      <div class="absolute flex flex-col gap-[8px] h-[160px] lg:h-[227px] items-end left-0 top-0 w-full">
        <div class="absolute left-1/2 -translate-x-1/2 h-[160px] lg:h-[227px] rounded-t-[6px] top-0 w-full overflow-hidden">
          <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        </div>
      </div>
      <button type="button" aria-label="{{ __('Add to favorites') }}" class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px] ms-auto">
        <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" alt="" class="size-[20px]" />
      </button>
    </div>
    <div class="bg-[var(--color-bg-main)] flex h-[40px] items-center justify-center px-[10px] py-[4px] relative rounded-[16px] shrink-0 w-[50px]">
      <img src="{{ asset('elora-2/assets/icons/cart.svg') }}" alt="{{ __('Add to cart') }}" class="size-[20px]" />
    </div>
  </div>
  <div class="flex flex-col gap-[6px] items-start p-[8px] relative shrink-0 w-full">
    <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
      <div class="flex items-center justify-between w-full whitespace-nowrap">
        <p class="font-medium text-[var(--color-text-primary)] text-[14px] lg:text-[16px]">{{ $p['name'] }}</p>
        <p class="font-normal text-[12px] lg:text-[14px] text-center" style="color:{{ $p['weightColor'] ?? 'var(--color-accent-green)' }}">{{ $p['weight'] }}</p>
      </div>
    </div>
    @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
    <div class="flex gap-[6px] items-center">
      <div class="flex items-center gap-[1px]" style="height:9px">
        @for ($__i = 1; $__i <= 5; $__i++)
          <svg style="height:9px;width:9px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
        @endfor
      </div>
      <p class="font-normal text-[var(--color-text-subtitle)] text-[11px] tracking-[0.5px] whitespace-nowrap">({{ $__rv }})</p>
    </div>
    <div class="flex gap-[6px] items-end">
      <p class="font-medium text-[16px] whitespace-nowrap" style="color:{{ $p['priceColor'] ?? 'var(--color-text-primary)' }}">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="font-light text-[var(--color-text-subtitle)] text-[12px] line-through whitespace-nowrap">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="font-normal text-[var(--color-secondary)] text-[11px] tracking-[0.5px] whitespace-nowrap">{{ $p['discount'] }}</p>
      @endif
    </div>
  </div>
</a>
