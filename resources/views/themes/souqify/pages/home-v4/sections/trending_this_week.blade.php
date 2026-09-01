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
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
        ];
    });
@endphp
<!-- ============ TRENDING NOW ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] flex flex-col gap-[16px] lg:gap-[28px]">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color:var(--color-text-primary)">{{ __('Trending Now') }}</h2>
    <a href="{{ route('tenant.storefront.best-selling') }}" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-brand-green)">{{ __('see all') }}</a>
  </div>
  @if ($__trendingCards->isNotEmpty())
    <div id="trendingWrapper" class="flex flex-col lg:flex-row gap-[16px] overflow-x-auto no-scrollbar">
      @foreach ($__trendingCards as $p)
        <a href="{{ $p['url'] }}" class="flex rounded-[10px] shadow-sm shrink-0 lg:w-[463px] overflow-hidden" style="background:var(--color-bg-main)" wire:key="trending-v4-{{ $p['id'] }}">
          <div class="relative w-[38%] lg:w-[42%] shrink-0" style="background:var(--color-card-image-bg)">
            @if ($p['image'])
              <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
            @endif
          </div>
          <div class="flex-1 p-[12px] flex flex-col gap-[8px]">
            <div class="flex items-center justify-between">
              <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
            </div>
            <div class="flex items-center gap-[6px]">
              <img src="{{ asset('souqify-3/assets/icons/star-rating.svg') }}" class="h-[8px] w-[56px]" alt="" />
              <span class="text-[12px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
            </div>
            <div class="flex items-end gap-[6px]">
              <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
              @if (!empty($p['oldPrice']))
                <p class="text-[11px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
              @endif
              @if (!empty($p['discount']))
                <p class="text-[12px]" style="color:var(--color-error)">{{ $p['discount'] }}</p>
              @endif
            </div>
          </div>
        </a>
      @endforeach
    </div>
  @else
    <p class="text-sm py-6" style="color:var(--color-footer-text-muted)">{{ __('No trending products yet.') }}</p>
  @endif
</section>
