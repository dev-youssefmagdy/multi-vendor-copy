{{-- Mini card, ported from public/souqify-1/carousels.js renderMiniCard(). $p keys:
     url, image, name (Str::limit'd), desc, rating, price, oldPrice, discount --}}
<div class="flex gap-[4px] rounded-[8px] overflow-hidden h-[110px] lg:h-[127px] w-full" style="background:var(--color-bg-main)">
  <a href="{{ $p['url'] }}" class="relative w-[152px] lg:w-[174px] shrink-0 rounded-tl-[7px] overflow-hidden block" style="background:var(--color-page-bg)">
    @if ($p['image'])
      <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="w-full h-full object-cover" />
    @endif
    @if (!empty($p['badge']))
      <span class="absolute top-0 left-0 rtl:left-auto rtl:right-0 text-[9px] lg:text-[11px] px-[6px] py-[3px] rounded-br-[6px] rounded-tl-[7px] tracking-[0.3px]" style="background:var(--color-accent-yellow); color:var(--color-black)">{{ $p['badge'] }}</span>
    @endif
  </a>
  <div class="flex-1 flex flex-col gap-[4px] p-[5px] min-w-0">
    <div class="flex items-center justify-between gap-[4px]">
      <a href="{{ $p['url'] }}" class="text-[14px] lg:text-[16px] font-medium truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</a>
    </div>
    @if (!empty($p['desc']))
      <p class="text-[12px] lg:text-[13px] truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] }}</p>
    @endif
    @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
    <div class="flex items-center gap-[5px]">
      <div class="flex items-center gap-[1px]" style="height:8px">
        @for ($__i = 1; $__i <= 5; $__i++)
          <svg style="height:8px;width:8px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
        @endfor
      </div>
      <span class="text-[10px] lg:text-[11px]" style="color:var(--color-text-subtitle)">({{ $__rv }})</span>
    </div>
    <div class="flex items-end gap-[5px] flex-wrap">
      <p class="text-[14px] lg:text-[16px] font-medium" style="color:var(--color-brand-purple)">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="text-[10px] lg:text-[11px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
      @if (!empty($p['discount']))
        <p class="text-[10px] lg:text-[11px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
      @endif
    </div>
  </div>
</div>
