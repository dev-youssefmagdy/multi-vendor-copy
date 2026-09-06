{{-- Card markup ported from public/souqify-5/carousels.js renderDealCard(). $p keys:
     id, url, image, name (Str::limit'd), weight, rating, price, oldPrice, discount, badge, delivery --}}
<div class="flex flex-col items-start rounded-[8px] h-full overflow-hidden" style="background:var(--color-bg-main)">
    <a href="{{ $p['url'] }}" class="relative w-full h-[183px] lg:h-[227px] shrink-0 block" style="background:var(--color-surface)">
        @if ($p['image'])
            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover rounded-t-[8px]" />
        @endif
        @if (!empty($p['delivery']))
            <span class="delivery-ribbon absolute left-0 bottom-0 flex items-center gap-[6px] px-[6px] py-[3px] rounded-tr-[10px]">
                <img src="{{ asset('souqify-5/assets/icons/truck-delivery.svg') }}" alt="" class="size-[13px]" />
                <span class="text-[9px] font-medium whitespace-nowrap" style="color:var(--color-black)">{{ $p['delivery'] }}</span>
            </span>
        @endif
        @if (!empty($p['badge']))
            <span class="absolute left-0 top-0 flex items-center gap-[4px] px-[8px] py-[4px] rounded-br-[12px]" style="background:var(--color-brand-pink)">
                <span class="text-[11px] font-medium text-white tracking-[0.4px] whitespace-nowrap">{{ $p['badge'] }}</span>
            </span>
        @endif
        <button type="button" wire:click.stop.prevent="addToCart({{ $p['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[8px] right-[8px] bg-white rounded-full p-[7px] shadow-md">
            <img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" class="size-[16px]" alt="" />
        </button>
    </a>
    <div class="flex flex-col gap-[6px] p-[8px] w-full">
        <div class="flex items-start justify-between gap-[4px]">
            <a href="{{ $p['url'] }}" class="font-medium text-[14px] truncate" style="color:var(--color-text-heading)">{{ $p['name'] }}</a>
            @if (!empty($p['weight']))
                <p class="font-semibold text-[12px] shrink-0" style="color:var(--color-primary)">{{ $p['weight'] }}</p>
            @endif
        </div>
        <div class="flex items-center gap-[5px]">
            <img src="{{ asset('souqify-5/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[58px]" />
            <span class="text-[12px] tracking-[0.3px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
        </div>
        <div class="flex items-center justify-between w-full">
            <span class="flex items-end gap-[4px]">
                <span class="font-bold text-[16px]" style="color:var(--color-brand-pink)">{{ $p['price'] }}</span>
                @if (!empty($p['oldPrice']))
                    <span class="text-[10px] line-through" style="color:var(--color-gray)">{{ $p['oldPrice'] }}</span>
                @endif
            </span>
            @if (!empty($p['discount']))
                <span class="text-white text-[9px] tracking-[0.4px] px-[6px] py-[3px]" style="background:var(--color-error)">{{ $p['discount'] }}</span>
            @endif
        </div>
    </div>
</div>
