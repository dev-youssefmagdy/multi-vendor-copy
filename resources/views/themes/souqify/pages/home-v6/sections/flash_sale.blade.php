@php
    $__flashEnd = optional($flashSales ?? collect())->first()?->end_at;
    $__flashCards = collect($flashProducts ?? [])->take(3)->map(function ($product) use ($symbol, $rate) {
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
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
        ];
    })->values();
    $__flashLeft = $__flashCards->get(0);
    $__flashCenter = $__flashCards->get(1) ?? $__flashCards->get(0);
    $__flashRight = $__flashCards->get(2) ?? $__flashCards->get(0);
@endphp
<!-- ============ FLASH SALE ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[42px]" style="background:var(--color-brand-pink)" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
    <div class="flex items-center justify-between lg:justify-start lg:gap-[52px] mb-[16px] lg:mb-[24px]">
        <h2 class="font-semibold text-[28px] lg:text-[60px] text-white leading-[1.1]">{{ __('Flash Sale') }}</h2>
        <div class="flex items-center gap-[8px] lg:gap-[12px]">
            <span class="text-[11px] lg:text-[16px] text-white/80 mr-[4px] hidden lg:inline">{{ __('Ends in') }}</span>
            <span class="flex flex-col items-center justify-center rounded-full bg-white size-[40px] lg:size-[62px]">
                <span class="font-semibold text-[18px] lg:text-[32px]" style="color:var(--color-brand-pink)" data-flash-hours data-countdown="hours">03</span>
            </span>
            <span class="flex flex-col items-center justify-center rounded-full bg-white size-[40px] lg:size-[62px]">
                <span class="font-semibold text-[18px] lg:text-[32px]" style="color:var(--color-brand-pink)" data-flash-minutes data-countdown="minutes">06</span>
            </span>
            <span class="flex flex-col items-center justify-center rounded-full bg-white size-[40px] lg:size-[62px]">
                <span class="font-semibold text-[18px] lg:text-[32px]" style="color:var(--color-brand-pink)" data-flash-seconds data-countdown="seconds">25</span>
            </span>
        </div>
    </div>

    @if ($__flashCenter)
        <div class="flash-stack relative h-[260px] w-full max-w-[300px] mx-auto lg:h-[473px] lg:max-w-[1328px] lg:mx-0">
            <!-- Card left (rotated) -->
            <div class="absolute left-[8px] top-[130px] lg:left-[56px] lg:top-[29px] rotate-[-6deg] w-[145px] lg:w-[290px] rounded-[6px] shadow-xl overflow-hidden" style="background:var(--color-bg-main)">
                <a href="{{ $__flashLeft['url'] }}" class="relative block">
                    <div class="w-full h-[125px] lg:h-[256px]" style="background:var(--color-surface)">
                        @if ($__flashLeft['image'])
                            <img loading="lazy" src="{{ $__flashLeft['image'] }}" alt="{{ $__flashLeft['name'] }}" class="w-full h-full object-cover" />
                        @endif
                    </div>
                    <span class="absolute top-0 left-0 text-[10px] lg:text-[14px] font-medium text-white tracking-[0.3px] px-[8px] py-[4px] rounded-br-[10px]" style="background:var(--color-brand-pink)">{{ __('Trending Now') }}</span>
                    <button type="button" wire:click.stop.prevent="addToCart({{ $__flashLeft['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[6px] shadow"><img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" class="size-[14px] lg:size-[18px]" alt="" /></button>
                </a>
                <div class="p-[8px] lg:p-[14px] flex flex-col gap-[4px]">
                    <a href="{{ $__flashLeft['url'] }}" class="font-medium text-[11px] lg:text-[16px] truncate block" style="color:var(--color-text-heading)">{{ $__flashLeft['name'] }}</a>
                    <div class="flex items-end gap-[6px]">
                        <p class="font-bold text-[13px] lg:text-[18px]" style="color:var(--color-brand-pink)">{{ $__flashLeft['price'] }}</p>
                        @if (!empty($__flashLeft['oldPrice']))
                            <p class="text-[9px] lg:text-[12px] line-through" style="color:var(--color-gray)">{{ $__flashLeft['oldPrice'] }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card center (front, largest) -->
            <div class="absolute left-[60px] top-0 lg:left-[425px] lg:top-0 w-[165px] lg:w-[340px] rounded-[6px] shadow-2xl overflow-hidden z-20" style="background:var(--color-bg-main)">
                <a href="{{ $__flashCenter['url'] }}" class="relative block">
                    <div class="w-full h-[150px] lg:h-[300px]" style="background:var(--color-surface)">
                        @if ($__flashCenter['image'])
                            <img loading="lazy" src="{{ $__flashCenter['image'] }}" alt="{{ $__flashCenter['name'] }}" class="w-full h-full object-cover" />
                        @endif
                    </div>
                    <span class="absolute top-0 left-0 text-[11px] lg:text-[15px] font-medium text-white tracking-[0.3px] px-[9px] py-[5px] rounded-br-[11px]" style="background:var(--color-brand-pink)">{{ __('Trending Now') }}</span>
                    <button type="button" wire:click.stop.prevent="addToCart({{ $__flashCenter['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[8px] right-[8px] bg-white rounded-full p-[7px] shadow"><img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" class="size-[16px] lg:size-[20px]" alt="" /></button>
                </a>
                <div class="p-[10px] lg:p-[16px] flex flex-col gap-[6px]">
                    <a href="{{ $__flashCenter['url'] }}" class="font-medium text-[13px] lg:text-[19px] truncate block" style="color:var(--color-text-heading)">{{ $__flashCenter['name'] }}</a>
                    <div class="flex items-center gap-[4px]">
                        <img src="{{ asset('souqify-5/assets/icons/star-rating.svg') }}" alt="" class="h-[9px] w-[62px]" />
                        <span class="text-[11px] lg:text-[14px]" style="color:var(--color-text-subtitle)">{{ $__flashCenter['rating'] }}</span>
                    </div>
                    <div class="flex items-end gap-[8px]">
                        <p class="font-bold text-[16px] lg:text-[24px]" style="color:var(--color-brand-pink)">{{ $__flashCenter['price'] }}</p>
                        @if (!empty($__flashCenter['oldPrice']))
                            <p class="text-[10px] lg:text-[14px] line-through" style="color:var(--color-gray)">{{ $__flashCenter['oldPrice'] }}</p>
                        @endif
                        @if (!empty($__flashCenter['discount']))
                            <span class="text-white text-[10px] lg:text-[13px] px-[6px] py-[2px]" style="background:var(--color-error)">{{ $__flashCenter['discount'] }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card right (rotated) -->
            <div class="absolute right-[8px] top-[130px] lg:right-[56px] lg:top-[29px] rotate-[6deg] w-[140px] lg:w-[285px] rounded-[6px] shadow-xl overflow-hidden" style="background:var(--color-bg-main)">
                <a href="{{ $__flashRight['url'] }}" class="relative block">
                    <div class="w-full h-[120px] lg:h-[250px]" style="background:var(--color-surface)">
                        @if ($__flashRight['image'])
                            <img loading="lazy" src="{{ $__flashRight['image'] }}" alt="{{ $__flashRight['name'] }}" class="w-full h-full object-cover" />
                        @endif
                    </div>
                    <span class="absolute top-0 left-0 text-[10px] lg:text-[14px] font-medium text-white tracking-[0.3px] px-[8px] py-[4px] rounded-br-[10px]" style="background:var(--color-brand-pink)">{{ __('Trending Now') }}</span>
                    <button type="button" wire:click.stop.prevent="addToCart({{ $__flashRight['id'] }})" aria-label="{{ __('Add to favorites') }}" class="absolute top-[6px] right-[6px] bg-white rounded-full p-[6px] shadow"><img src="{{ asset('souqify-5/assets/icons/heart.svg') }}" class="size-[14px] lg:size-[18px]" alt="" /></button>
                </a>
                <div class="p-[8px] lg:p-[14px] flex flex-col gap-[4px]">
                    <a href="{{ $__flashRight['url'] }}" class="font-medium text-[11px] lg:text-[16px] truncate block" style="color:var(--color-text-heading)">{{ $__flashRight['name'] }}</a>
                    <div class="flex items-end gap-[6px]">
                        <p class="font-bold text-[13px] lg:text-[18px]" style="color:var(--color-brand-pink)">{{ $__flashRight['price'] }}</p>
                        @if (!empty($__flashRight['oldPrice']))
                            <p class="text-[9px] lg:text-[12px] line-through" style="color:var(--color-gray)">{{ $__flashRight['oldPrice'] }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <p class="text-white/80 text-sm py-6 text-center">{{ __('No flash sale products yet.') }}</p>
    @endif

    <div class="flex justify-center mt-[16px] lg:mt-[24px]">
        <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="border-2 border-white rounded-full h-[44px] lg:h-[67px] px-[32px] lg:px-[40px] flex items-center justify-center cursor-pointer">
            <span class="font-medium text-white text-[14px] lg:text-[24px] tracking-[0.5px]">{{ __('Shop now') }}</span>
        </a>
    </div>
</section>
