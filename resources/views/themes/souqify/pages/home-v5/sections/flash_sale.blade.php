@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;
    $__flashCards = collect($flashProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'sold' => $product->orders_count ?? null,
        ];
    });
@endphp
<!-- ============ FLASH SALE ============ -->
<section class="relative px-[16px] lg:px-[56px] py-[28px] lg:py-[48px] overflow-hidden rounded-bl-[80px] rounded-tr-[80px] lg:rounded-bl-[160px] lg:rounded-tr-[160px] mx-[8px] lg:mx-[16px]" style="background:var(--color-primary)" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
    <img src="{{ asset('souqify-4/assets/images/flash-bg-pattern.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30 mix-blend-color-dodge pointer-events-none" />
    <div class="absolute rounded-full flex items-center justify-center size-[36px] lg:size-[42px] top-[16px] right-[56px] lg:top-[32px] lg:right-[255px]" style="background:var(--color-primary)"><img src="{{ asset('souqify-4/assets/icons/flash-lightning.svg') }}" class="size-[22px] lg:size-[29px]" alt="" /></div>
    <div class="absolute rounded-full flex items-center justify-center size-[36px] lg:size-[42px] bottom-[16px] left-[16px]" style="background:var(--color-primary)"><img src="{{ asset('souqify-4/assets/icons/flash-lightning.svg') }}" class="size-[22px] lg:size-[29px]" alt="" /></div>

    <div class="relative flex flex-col gap-[20px] lg:gap-[26px]">
        <div class="flex items-center gap-[8px]">
            <h2 class="font-extrabold text-[26px] lg:text-[40px] text-white">{{ __('Flash Sale') }}</h2>
            <img src="{{ asset('souqify-4/assets/icons/flash-lightning.svg') }}" class="size-[20px] lg:size-[28px]" alt="" />
        </div>

        <div id="flashWrapper" class="grid grid-cols-1 lg:grid-cols-4 gap-[12px] lg:gap-[14px]">
            @foreach ($__flashCards->take(4) as $p)
                <div class="bg-white flex gap-[10px] items-center p-[10px] rounded-[10px] w-full" wire:key="flash-v5-{{ $p['id'] }}">
                    <a href="{{ $p['url'] }}" class="relative rounded-[7px] shrink-0 size-[100px] lg:size-[118px] overflow-hidden" style="background:var(--color-surface)">
                        @if ($p['image'])
                            <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
                        @endif
                    </a>
                    <div class="flex flex-col gap-[6px] flex-1 min-w-0">
                        <a href="{{ $p['url'] }}" class="font-medium text-[15px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</a>
                        <div class="flex items-center gap-[4px]">
                            <span class="font-semibold text-[13px]" style="color:var(--color-primary)">{{ $p['price'] }}</span>
                            @if (!empty($p['oldPrice']))
                                <span class="text-[10px] line-through" style="color:var(--color-gray)">{{ $p['oldPrice'] }}</span>
                            @endif
                        </div>
                        <span class="text-[10px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-[12px] flex-wrap">
            <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="bg-white rounded-full h-[44px] lg:h-[60px] px-[24px] lg:px-[32px] flex items-center justify-center cursor-pointer shadow-[var(--shadow-card-lg)]">
                <span class="font-medium text-[14px] lg:text-[24px] tracking-[0.5px]" style="color:var(--color-primary)">{{ __('Shop now') }}</span>
            </a>
            <div class="flex items-center gap-[6px]">
                <span class="text-white font-semibold text-[12px] lg:text-[17px] tracking-[0.5px] [writing-mode:vertical-rl] rotate-180">{{ __('Ends in') }}</span>
                <div class="bg-[var(--color-primary)] border-2 border-white rounded-[8px] h-[40px] w-[42px] lg:h-[64px] lg:w-[67px] flex items-center justify-center"><span data-flash-hours id="flashH" class="text-white font-semibold text-[18px] lg:text-[35px]">03</span></div>
                <div class="bg-[var(--color-primary)] border-2 border-white rounded-[8px] h-[40px] w-[42px] lg:h-[64px] lg:w-[67px] flex items-center justify-center"><span data-flash-minutes id="flashM" class="text-white font-semibold text-[18px] lg:text-[35px]">06</span></div>
                <div class="bg-[var(--color-primary)] border-2 border-white rounded-[8px] h-[40px] w-[42px] lg:h-[64px] lg:w-[67px] flex items-center justify-center"><span data-flash-seconds id="flashS" class="text-white font-semibold text-[18px] lg:text-[35px]">25</span></div>
            </div>
        </div>
    </div>
</section>
