{{-- Expects $p: name, price, oldPrice, discount, image, badge, badgeBg, badgeColor, weight, rating, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex gap-[3px] lg:gap-[5px] rounded-[6px] lg:rounded-[10px] overflow-hidden h-full no-underline" style="background:var(--color-bg-main)">
  <div class="relative w-[43%] lg:w-[45%] lg:max-h-[148px] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <button
      type="button"
      aria-label="{{ __('Add to favorites') }}"
      class="flex absolute top-[3.77px] lg:top-1.5 start-[3.77px] lg:start-1.5 items-center justify-center w-[20.11px] h-[20.11px] lg:w-8.75 lg:h-8.75 p-[5.03px] lg:p-1.25 bg-white rounded-full shadow-[0px_2.51429px_2.51429px_rgba(0,0,0,0.15)] lg:shadow"
    >
      <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" alt="" class="size-[12.57px] lg:size-5.25" />
    </button>
    <span
      class="absolute flex items-center justify-center top-0 end-0 h-[15.43px] lg:h-auto px-[3.31px] lg:px-[6px] lg:py-[4px] text-[7.71px] lg:text-[12px] font-normal max-lg:leading-[10px] max-lg:tracking-[0.2755px] rounded-tr-none rounded-bl-[4.41px] lg:rounded-tr-[8px] lg:rounded-bl-[8px]"
      style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}"
      >{{ $p['badge'] ??? '' }}</span
    >
    <button
      type="button"
      aria-label="{{ __('Add to cart') }}"
      class="flex absolute bottom-[3.77px] lg:bottom-1.5 end-[3.77px] lg:end-1.5 items-center justify-center w-[35.83px] lg:w-auto h-[28.29px] lg:h-12 px-[7.54px] lg:px-3 py-[2.51px] rounded-[10.06px] lg:rounded-[17px]"
      style="background: var(--color-bg-main)"
    >
      <img src="{{ asset('elora-2/assets/icons/cart-add-blue.svg') }}" alt="" class="size-[15.09px] lg:size-6.5" />
    </button>
  </div>
  <div class="flex-1 p-[4px] lg:p-[8px] flex flex-col gap-[2.51px] lg:gap-[4px] min-w-0">
    <div class="flex items-center justify-between gap-[1.26px]">
      <p class="font-medium text-[12px] lg:text-[16px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
      <p class="text-[10px] lg:text-[14px] shrink-0" style="color: #132092">{{ $p['weight'] }}</p>
    </div>
    <p class="text-[10px] lg:text-[13px]" style="color:var(--color-text-subtitle)">{{ __('Premium cotton blend') }}</p>
    @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
    <div class="flex items-center gap-[5.03px] lg:gap-[6px]">
      <div class="flex items-center gap-[1px]" style="height:6px">
        @for ($__i = 1; $__i <= 5; $__i++)
          <svg style="height:6px;width:6px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
        @endfor
      </div>
      <span class="text-[8px] lg:text-[12px]" style="color:var(--color-text-subtitle)">({{ $__rv }})</span>
    </div>
    <div class="flex items-end gap-[5.03px] lg:gap-[6px]">
      <p class="font-medium text-[12px] lg:text-[16px]" style="color: #0018e8">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="font-light text-[8.8px] lg:text-[11px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="text-[8px] lg:text-[12px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      @endif
    </div>
    <div class="mt-auto flex items-center gap-[4px] lg:hidden">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[12px]" />
      <p class="font-medium text-[8px] whitespace-nowrap" style="color:var(--color-success)">{{ __('Delivered by :date', ['date' => '24 March']) }}</p>
    </div>
    <p class="hidden lg:block mt-auto text-[11px] font-medium" style="color:var(--color-success)">{{ __('Delivered by :date', ['date' => '24 March']) }}</p>
  </div>
</a>
