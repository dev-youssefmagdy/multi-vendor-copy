{{-- Expects $p: image, badge, badgeBg, badgeText, name, weight, desc, rating, price, oldPrice, discount, urgency, progress, progressLabel. Optional $wide: bool --}}
@php $imgHeight = !empty($wide) ? 'h-[213px] lg:h-[269px]' : 'h-[183px] lg:h-[227px]'; @endphp
<a href="{{ $p['url'] ?? '#' }}" class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[8px] h-full shadow-sm">
  <div class="flex flex-col gap-[8px] {{ $imgHeight }} items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
    <div class="absolute flex gap-[8px] {{ $imgHeight }} items-start start-0 p-[6px] top-0 w-full">
      <div class="absolute flex flex-col gap-[8px] {{ $imgHeight }} items-end start-0 top-0 w-full">
        <div class="absolute left-1/2 -translate-x-1/2 {{ $imgHeight }} rounded-t-[8px] top-0 w-full overflow-hidden bg-[var(--color-page-bg)]">
          <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        </div>
        <div class="content-stretch flex h-[28px] items-center justify-center p-[6px] relative rounded-es-[8px] rounded-se-[8px] shrink-0" style="background:{{ $p['badgeBg'] }}">
          <p class="font-normal text-[14px] tracking-[0.5px] whitespace-nowrap" style="color:{{ $p['badgeText'] }}">{{ $p['badge'] }}</p>
        </div>
      </div>
      <button type="button" aria-label="{{ __('Add to favorites') }}" onclick="event.preventDefault(); eloraHeartToggle(this)"
        data-fav="{{ $p['favData'] ?? '' }}"
        data-logged-in="{{ auth()->guard('storefront')->check() ? 'true' : 'false' }}"
        data-product-id="{{ $p['id'] ?? '' }}"
        class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px]">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" alt="" class="size-[20px]" />
      </button>
    </div>
    <div class="flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px]" style="background:var(--color-text-primary)">
      <img src="{{ asset('elora-3/assets/icons/cart-add.svg') }}" alt="{{ __('Add to cart') }}" class="size-[24px]" />
    </div>
  </div>
  <div class="flex flex-col gap-[8px] items-start p-[8px] relative shrink-0 w-full">
    <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
      <div class="flex items-center justify-between gap-[4px] w-full">
        <p class="font-medium text-[16px] truncate min-w-0" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        @if (!empty($p['weight']))
          <p class="font-normal text-[14px] text-center shrink-0" style="color:var(--color-brand-pink)">{{ $p['weight'] }}</p>
        @endif
      </div>
      <p class="font-normal text-[14px] w-full truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] }}</p>
    </div>
    @if (!empty($p['progress']))
      <div class="flex flex-col gap-[4px] items-start w-full">
        <div class="h-[6px] w-full rounded-full ordered-progress-track">
          <div class="h-full rounded-full ordered-progress-fill" style="width:{{ $p['progress'] }}%"></div>
        </div>
        <p class="text-[11px] tracking-[0.4px]" style="color:var(--color-progress-fill)">{{ $p['progressLabel'] }}</p>
      </div>
    @endif
    <div class="flex flex-col gap-[4px] items-start">
      @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
      <div class="flex gap-[8px] items-center justify-center">
        <div class="flex items-center gap-[1px]" style="height:10px">
          @for ($__i = 1; $__i <= 5; $__i++)
            <svg style="height:10px;width:10px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
          @endfor
        </div>
        <span class="font-normal text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">({{ $__rv }})</span>
      </div>
      <div class="flex gap-[8px] items-end">
        <p class="font-medium text-[18px] whitespace-nowrap" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[14px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex flex-col gap-[4px] items-start w-full">
      <div class="flex gap-[4px] items-center w-full">
        <img src="{{ asset('elora-3/assets/icons/truck-delivery.svg') }}" alt="" class="size-[18px]" />
        <p class="font-medium text-[12px] whitespace-nowrap" style="color:{{ $p['urgency'] ?? 'var(--color-success)' }}">{{ __('Delivered by') }} {{ now()->addDays(3)->translatedFormat('d F') }}</p>
      </div>
    </div>
  </div>
</a>
