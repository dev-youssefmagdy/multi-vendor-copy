{{-- Best Seller composite slide's bottom wide card. Expects $p: image, badge, badgeBg, badgeColor, name, weight, desc, rating, price, oldPrice, discount, url --}}
@php $__deliveredDate = \Carbon\Carbon::now()->addDays(3)->translatedFormat('d F'); @endphp
<a href="{{ $p['url'] ?? '#' }}" class="relative flex-1 flex gap-[3px] lg:gap-[5px] bg-[var(--color-bg-main)] rounded-[6px] lg:rounded-[10px] overflow-hidden shadow-[var(--shadow-card)] no-underline">
  <div class="relative w-[40%] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <div class="absolute inset-0 flex flex-col justify-between items-end p-[3px_4px] lg:p-[5px_6.5px]">
      <div class="flex w-full justify-between items-start">
    
        <button type="button" aria-label="{{ __('Add to favorites') }}" class="flex items-center justify-center w-[20px] h-[20px] lg:w-[35px] lg:h-[35px] p-[5px] bg-white rounded-full shadow">
          <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[12px] lg:size-[21px]" alt="" />
        </button>
            <span
          class="flex items-center h-[15px] px-[3px] lg:h-[26px] lg:px-[6px] text-[8px] lg:text-[13px] font-normal tracking-[0.28px] lg:tracking-normal rounded-br-[4px] lg:rounded-br-[8px]"
          style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}"
          >{{ $p['badge'] ??? '' }}</span
        >
      </div>
      <button type="button" aria-label="{{ __('Add to cart') }}" class="flex items-center justify-center w-[36px] h-[28px] lg:w-auto lg:h-[48px] lg:px-[12px] rounded-[10px] lg:rounded-[17px]" style="background: var(--color-bg-main)">
        <img src="{{ asset('elora-2/assets/icons/cart-add-blue.svg') }}" class="size-[15px] lg:size-[26px]" alt="" />
      </button>
    </div>
  </div>
  <div class="flex-1 min-w-0 flex flex-col justify-between p-[4px] lg:p-[7px]">
    <div class="flex flex-col gap-[2px] lg:gap-[4px]">
      <div class="flex items-center justify-between gap-[1px] lg:gap-[4px]">
        <p class="font-medium text-[12px] lg:text-[20px] tracking-[0.31px] lg:tracking-normal truncate" style="color: var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="text-[10px] lg:text-[17px] shrink-0" style="color: #132092">{{ $p['weight'] }}</p>
      </div>
      <p class="text-[10px] lg:text-[17px] tracking-[0.31px] lg:tracking-normal truncate" style="color: var(--color-text-subtitle)">{{ $p['desc'] ?? __('Premium cotton blend') }}</p>
    </div>
    <div class="flex flex-col gap-[2px] lg:gap-[4px]">
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flex items-center gap-[5px] lg:gap-[8px]">
        <div class="flex items-center gap-[1px]" style="height:6px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:6px;width:6px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <span class="text-[8px] lg:text-[13.7px]" style="color: var(--color-text-subtitle)">({{ $__rv }})</span>
      </div>
      <div class="flex items-baseline gap-[5px] lg:gap-[8px]">
        <p class="font-medium text-[12px] lg:text-[20.5px]" style="color: #0018e8">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[9px] lg:text-[15px] line-through" style="color: var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="text-[8px] lg:text-[13.7px] tracking-[0.31px] lg:tracking-normal" style="color: var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex items-center gap-[4px] lg:gap-[7px]">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[12px] lg:size-[20px]" />
      <p class="font-medium text-[8px] lg:text-[13.7px] whitespace-nowrap" style="color: var(--color-success)">{{ __('Delivered by :date', ['date' => $__deliveredDate]) }}</p>
    </div>
  </div>
</a>
