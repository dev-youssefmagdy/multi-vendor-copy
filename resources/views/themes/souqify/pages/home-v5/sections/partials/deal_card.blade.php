{{-- Card markup ported from public/souqify-4/carousels.js renderDealCard(). $p keys:
     id, url, image, name (Str::limit'd), rating, price, oldPrice, discount, badgeBg, badgeText --}}
<div class="flex flex-col items-start rounded-[6px] h-full overflow-hidden shadow-[var(--shadow-card)]" style="background:var(--color-white)">
    <a href="{{ $p['url'] }}" class="relative w-full h-[210px] lg:h-[236px] shrink-0 block" style="background:var(--color-surface)">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        @endif
        <span class="absolute bottom-0 left-1/2 -translate-x-1/2 bg-white flex items-center justify-center rounded-[6px] shadow size-[42px]">
            <img src="{{ asset('souqify-4/assets/icons/trending-cart.svg') }}" class="size-[26px]" alt="{{ __('Add to cart') }}" />
        </span>
        <button type="button" wire:click.stop="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[7px] shadow"><img src="{{ asset('souqify-4/assets/icons/trending-heart.svg') }}" class="size-[18px]" alt="" /></button>
        @if (!empty($p['badge']))
            <div class="ribbon-red-gradient absolute bottom-0 left-0 flex items-center gap-[8px] px-[6px] py-[3px] rounded-tr-[18px]">
                <span class="text-[10px] font-medium whitespace-nowrap" style="color:var(--color-black)">{{ $p['badge'] }}</span>
            </div>
        @endif
    </a>
    <div class="bg-white w-full flex flex-col gap-[4px] px-[8px] py-[9px]">
        <div class="flex items-start justify-between gap-[4px]">
            <a href="{{ $p['url'] }}" class="font-medium text-[15px] truncate" style="color:var(--color-black-alt)">{{ $p['name'] }}</a>
        </div>
        <div class="flex items-center gap-[6px]">
            <img src="{{ asset('souqify-4/assets/icons/trending-star-rating.svg') }}" alt="" class="h-[9px] w-[62px]" onerror="this.style.display='none'" />
            <span class="text-[12px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
        </div>
        <div class="flex items-center justify-between gap-[6px]">
            <div class="flex items-center gap-[5px]">
                <span class="font-bold text-[17px]" style="color:var(--color-primary)">{{ $p['price'] }}</span>
                @if (!empty($p['oldPrice']))
                    <span class="text-[11px] line-through" style="color:var(--color-gray)">{{ $p['oldPrice'] }}</span>
                @endif
            </div>
            @if (!empty($p['discount']))
                <span class="text-[10px] tracking-[0.5px] px-[6px] py-[4px] whitespace-nowrap" style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['badgeText'] ?? 'var(--color-black)' }}">{{ $p['discount'] }}</span>
            @endif
        </div>
    </div>
</div>
