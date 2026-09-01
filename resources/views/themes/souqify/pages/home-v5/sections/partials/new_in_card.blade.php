{{-- Card markup ported from public/souqify-4/carousels.js renderNewInCard(). $p keys:
     id, url, image, name (Str::limit'd), rating, price, oldPrice, discount --}}
<div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[10px] h-full shadow-[var(--shadow-card)]">
    <a href="{{ $p['url'] }}" class="relative w-full h-[145px] lg:h-[165px] rounded-t-[10px] overflow-hidden shrink-0 block" style="background:var(--color-surface)">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        @endif
        @if (!empty($p['discount']))
            <span class="absolute top-0 left-0 text-[12px] font-normal px-[8px] py-[4px] rounded-br-[10px]" style="background:var(--color-accent-yellow); color:var(--color-black)">{{ $p['discount'] }}</span>
        @endif
        <button type="button" wire:click="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[6px] shadow"><img src="{{ asset('souqify-4/assets/icons/newin-heart.svg') }}" class="size-[16px]" alt="" /></button>
        <span class="absolute bottom-[6px] right-[6px] bg-[var(--color-bg-main)] rounded-full p-[6px] shadow"><img src="{{ asset('souqify-4/assets/icons/newin-cart.svg') }}" class="size-[20px]" alt="" /></span>
    </a>
    <div class="flex-1 w-full p-[10px] flex flex-col gap-[6px]">
        <a href="{{ $p['url'] }}" class="font-medium text-[16px] truncate block" style="color:var(--color-text-primary)">{{ $p['name'] }}</a>
        <div class="flex items-center gap-[6px]">
            <img src="{{ asset('souqify-4/assets/icons/newin-star-rating.svg') }}" alt="" class="h-[10px] w-[69px]" />
            <span class="text-[12px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
        </div>
        <div class="flex items-end gap-[6px] flex-wrap">
            <p class="font-medium text-[16px]" style="color:var(--color-primary)">{{ $p['price'] }}</p>
            @if (!empty($p['oldPrice']))
                <p class="text-[12px] line-through font-light" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
            @endif
            @if (!empty($p['discount']))
                <p class="text-[12px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
            @endif
        </div>
    </div>
</div>
