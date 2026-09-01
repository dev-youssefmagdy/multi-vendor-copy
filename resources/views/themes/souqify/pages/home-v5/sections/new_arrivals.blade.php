@php
    $__newInCards = collect($newInProducts ?? [])->map(function ($product) use ($symbol, $rate) {
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
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
        ];
    });
@endphp
<!-- ============ NEW IN ============ -->
<section class="relative px-[16px] lg:px-[56px] py-[28px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px] overflow-hidden">
    <img src="{{ asset('souqify-4/assets/images/newin-bg-pattern.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover mix-blend-difference pointer-events-none opacity-90" />
    <div class="relative flex items-center justify-between">
        <h2 class="font-extrabold text-[24px] lg:text-[40px] text-white">{{ __('New In') }}</h2>
        <a href="{{ route('tenant.storefront.new-in') }}" class="text-white text-[14px] lg:text-[20px] tracking-[0.5px]">{{ __('see all') }}</a>
    </div>
    @if ($__newInCards->isNotEmpty())
        <div class="relative">
            <div class="swiper card-swiper">
                <div class="swiper-wrapper" id="newInWrapper">
                    @foreach ($__newInCards as $p)
                        <div class="swiper-slide h-auto !w-[200px] lg:!w-[227px]" wire:key="new-in-v5-{{ $p['id'] }}">
                            @include('themes.souqify.pages.home-v5.sections.partials.new_in_card', ['p' => $p])
                        </div>
                    @endforeach
                </div>
            </div>
            <button id="newInPrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-4/assets/icons/mobile-arrow-down.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
            <button id="newInNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-4/assets/icons/mobile-arrow-down.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
        </div>
    @else
        <p class="relative text-white/80 text-sm py-6">{{ __('No new arrivals yet.') }}</p>
    @endif
</section>
