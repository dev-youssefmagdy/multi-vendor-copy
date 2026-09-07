{{-- Mini card, ported from public/souqify-1/carousels.js renderMiniCard(). $p keys:
     url, image, name (Str::limit'd), desc, rating, price, oldPrice, discount --}}
<div class="flex gap-[4px] rounded-[8px] overflow-hidden h-[110px] lg:h-[127px] w-full" style="background:var(--color-bg-main)">
  <a href="{{ $p['url'] }}" class="relative w-[152px] lg:w-[174px] shrink-0 rounded-tl-[7px] overflow-hidden block" style="background:var(--color-page-bg)">
    @if ($p['image'])
      <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="w-full h-full object-cover" />
    @endif
    @if (!empty($p['badge']))
      <span class="absolute top-0 left-0 text-[9px] lg:text-[11px] px-[6px] py-[3px] rounded-br-[6px] rounded-tl-[7px] tracking-[0.3px]" style="background:var(--color-accent-yellow); color:var(--color-black)">{{ $p['badge'] }}</span>
    @endif
  </a>
  <div class="flex-1 flex flex-col gap-[4px] p-[5px] min-w-0">
    <div class="flex items-center justify-between gap-[4px]">
      <a href="{{ $p['url'] }}" class="text-[14px] lg:text-[16px] font-medium truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</a>
    </div>
    @if (!empty($p['desc']))
      <p class="text-[12px] lg:text-[13px] truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] }}</p>
    @endif
    <div class="flex items-center gap-[5px]">
      <img src="{{ asset('souqify-1/assets/icons/icon-star-rating.svg') }}" class="h-[8px] w-[54px]" alt="" />
      <span class="text-[10px] lg:text-[11px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
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
