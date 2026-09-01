@php
    $__trendingCards = collect($trendingNowProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];
        $rating = (float) ($product->average_rating ?? 0);
        $ratingCount = $product->relationLoaded('rates') ? $product->rates->count() : $product->rates()->count();

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('off') : null,
        ];
    });
@endphp
<!-- ============ TRENDING NOW ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] flex flex-col gap-[16px] lg:gap-[34px] bg-white">
    <div class="flex items-center justify-between">
        <h2 class="font-extrabold text-[22px] lg:text-[40px]" style="color:var(--color-text-primary)">{{ __('Trending Now') }}</h2>
        <a href="{{ route('tenant.storefront.best-selling') }}" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-primary)">{{ __('see all') }}</a>
    </div>
    @if ($__trendingCards->isNotEmpty())
        <div class="relative">
            <div class="swiper card-swiper">
                <div class="swiper-wrapper" id="trendingWrapper">
                    @foreach ($__trendingCards as $p)
                        <div class="swiper-slide h-auto !w-[190px] lg:!w-[226px]" wire:key="trending-v5-{{ $p['id'] }}">
                            @include('themes.souqify.pages.home-v5.sections.partials.deal_card', ['p' => $p])
                        </div>
                    @endforeach
                </div>
            </div>
            <button id="trendingPrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-4/assets/icons/mobile-arrow-down.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
            <button id="trendingNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-4/assets/icons/mobile-arrow-down.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
        </div>
    @else
        <p class="text-sm py-6" style="color:var(--color-text-muted)">{{ __('No trending products yet.') }}</p>
    @endif
</section>
