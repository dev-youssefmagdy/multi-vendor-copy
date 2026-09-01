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
            'badge' => '🔥 ' . __('Trending Now'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
        ];
    });
@endphp
<!-- ============ NEW IN ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[42px] flex flex-col gap-[16px] lg:gap-[28px]" style="background: linear-gradient(120deg, color-mix(in srgb, var(--color-brand-green) 10%, var(--color-bg-main)), color-mix(in srgb, var(--color-badge-yellow) 12%, var(--color-bg-main)))">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color:var(--color-text-primary)">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-brand-green)">{{ __('see all') }}</a>
  </div>
  @if ($__newInCards->isNotEmpty())
    <div class="relative">
      <div class="swiper card-swiper">
        <div class="swiper-wrapper" id="newInWrapper">
          @foreach ($__newInCards as $p)
            <div class="swiper-slide h-auto !w-[160px] lg:!w-[210px]" wire:key="new-in-v4-{{ $p['id'] }}">
              @include('themes.souqify.pages.home-v2.sections.partials.product_card', ['p' => $p])
            </div>
          @endforeach
        </div>
      </div>
      <button id="newInPrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev"><img src="{{ asset('souqify-3/assets/icons/icon-chevron-down.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
      <button id="newInNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next"><img src="{{ asset('souqify-3/assets/icons/icon-chevron-down.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
    </div>
  @else
    <p class="text-sm py-6" style="color:var(--color-footer-text-muted)">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>
