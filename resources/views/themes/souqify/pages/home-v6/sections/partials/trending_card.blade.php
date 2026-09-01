{{-- Card markup ported from public/souqify-5/carousels.js renderTrendingCard(). $p keys:
     id, url, image, name (Str::limit'd), weight, desc, rating, price, oldPrice, discount --}}
<div class="flex h-full rounded-[10px] overflow-hidden" style="background:var(--color-bg-main); box-shadow:var(--shadow-card)">
    <a href="{{ $p['url'] }}" class="relative w-[124px] shrink-0 block" style="background:var(--color-surface)">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        @endif
        <button type="button" wire:click.stop.prevent="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" class="size-[14px]" alt="" /></button>
        <span class="absolute bottom-[6px] right-[6px] rounded-[8px] p-[5px] shadow" style="background:var(--color-black-alt)"><img src="{{ asset('souqify-5/assets/icons/cart-add.svg') }}" class="size-[16px] invert" alt="" /></span>
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
        <div class="flex items-center gap-[5px]">
            <img src="{{ asset('souqify-5/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[56px]" />
            <span class="text-[11px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
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
