{{-- Card markup ported from public/souqify-5/carousels.js renderTrendingCard(). $p keys:
     id, url, image, name (Str::limit'd), weight, desc, rating, price, oldPrice, discount --}}
<div class="flex h-full rounded-[10px] overflow-hidden" style="background:var(--color-bg-main); box-shadow:var(--shadow-card)">
    <a href="{{ $p['url'] }}" class="relative w-[124px] shrink-0 block" style="background:var(--color-surface)">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        @endif
        <button type="button" onclick="souqifyToggleFavorite(this)" data-slug="{{ $p['slug'] ?? '' }}" data-fav='{{ $p['fav'] ?? '{}' }}' aria-label="{{ __('Wishlist') }}" class="absolute top-[6px] left-[6px] rtl:left-auto rtl:right-[6px] bg-white rounded-full p-[5px] shadow"><img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" class="size-[14px]" alt="" /></button>
        @if (empty($p['outOfStock']))
            <button type="button" wire:click.stop.prevent="addToCart({{ $p['id'] }})" wire:loading.attr="disabled" wire:target="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to cart') }}" class="absolute bottom-[6px] right-[6px] rtl:right-auto rtl:left-[6px] rounded-[8px] p-[5px] shadow" style="background:var(--color-black-alt)"><img src="{{ asset('souqify-5/assets/icons/cart-add.svg') }}" class="size-[16px] invert" alt="" /></button>
        @else
            <span class="absolute bottom-[6px] right-[6px] rtl:right-auto rtl:left-[6px] rounded-[8px] p-[5px] shadow" style="background:var(--color-black-alt)" aria-hidden="true"><img src="{{ asset('souqify-5/assets/icons/cart-add.svg') }}" class="size-[16px] invert" alt="" /></span>
        @endif
    </a>
    <div class="flex-1 p-[10px] flex flex-col gap-[6px] min-w-0">
        <div class="flex items-center justify-between gap-[4px]">
            <a href="{{ $p['url'] }}" class="font-medium text-[14px] truncate" style="color:var(--color-text-heading)">{{ $p['name'] }}</a>
            @if (!empty($p['weight']))
                <span class="text-[12px] shrink-0" style="color:var(--color-brand-pink)">{{ $p['weight'] }}</span>
            @endif
        </div>
        @if (!empty($p['desc']))
            <p class="text-[12px] truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] }}</p>
        @endif
        @php $__rv = (float)($p['ratingValue'] ?? $p['rating'] ?? 0); $__fs = (int)round(min(5, max(0, $__rv))); @endphp
        <div class="flex items-center gap-[5px]">
            <div class="flex items-center gap-[1px]" style="height:8px">
              @for ($__i = 1; $__i <= 5; $__i++)
                <svg style="height:8px;width:8px" class="{{ $__i <= $__fs ? 'text-[#FFB00A]' : 'text-[#d1d5db]' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.967a1 1 0 00.95.69h4.169c.969 0 1.371 1.24.588 1.81l-3.37 2.448a1 1 0 00-.364 1.118l1.287 3.966c.3.921-.755 1.688-1.54 1.118l-3.37-2.447a1 1 0 00-1.176 0l-3.37 2.447c-.784.57-1.838-.197-1.539-1.118l1.287-3.966a1 1 0 00-.364-1.118L2.05 9.394c-.783-.57-.38-1.81.588-1.81h4.169a1 1 0 00.95-.69l1.286-3.967z"/></svg>
              @endfor
            </div>
            <span class="text-[11px]" style="color:var(--color-text-subtitle)">({{ $__rv }})</span>
        </div>
        <div class="flex items-end gap-[6px]">
            <span class="font-medium text-[15px]" style="color:var(--color-text-heading)">{{ $p['price'] }}</span>
            @if (!empty($p['oldPrice']))
                <span class="text-[10px] line-through" style="color:var(--color-gray)">{{ $p['oldPrice'] }}</span>
            @endif
            @if (!empty($p['discount']))
                <span class="text-[11px]" style="color:var(--color-brand-pink)">{{ $p['discount'] }}</span>
            @endif
        </div>
    </div>
</div>
