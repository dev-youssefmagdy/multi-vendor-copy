@php
    $__newInCards = collect($newInProducts ?? [])->map(function ($product) use ($symbol, $rate) {
        $variant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
        $pricing = $product->storefrontPricing($variant);
        $hasDiscount = (bool) $pricing['has_discount'];

        return [
            'id' => $product->id,
            'url' => route('tenant.storefront.product', $product->slug),
            'image' => $product->centralProduct?->primary_image_url ?? $product->primary_image_url ?? null,
            'badge' => __('New In'),
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'desc' => $product->centralProduct?->category?->name ?? '',
            'rating' => number_format((float) ($product->average_rating ?? 0), 1),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
        ];
    });
@endphp
<!-- ============ NEW IN ============ -->
<section class="stripe-bg-purple mt-[64px] px-[16px] lg:px-[56px] py-[24px] lg:py-[24px] flex flex-col gap-[16px] lg:gap-[24px]">
  <div class="flex items-center justify-between">
    <h2 class="font-extrabold text-[26px] lg:text-[40px] text-white">{{ __('New In') }}</h2>
    <a href="{{ route('tenant.storefront.new-in') }}" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px] text-white">{{ __('see all') }}</a>
  </div>
  @if ($__newInCards->isNotEmpty())
    <div class="relative">
      <div id="newInWrapper" class="newin-grid">
        @foreach ($__newInCards as $p)
          <div wire:key="new-in-v2-{{ $p['id'] }}">
            @include('themes.souqify.pages.home-v2.sections.partials.mini_card', ['p' => $p])
          </div>
        @endforeach
      </div>
      <button id="newInPrev" type="button" aria-label="{{ __('Previous') }}" class="swiper-nav-btn swiper-nav-prev on-dark"><img src="{{ asset('souqify-1/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] rotate-90" alt="" /></button>
      <button id="newInNext" type="button" aria-label="{{ __('Next') }}" class="swiper-nav-btn swiper-nav-next on-dark"><img src="{{ asset('souqify-1/assets/icons/icon-chevron-down-dark.svg') }}" class="size-[14px] -rotate-90" alt="" /></button>
    </div>
  @else
    <p class="text-white/80 text-sm py-6">{{ __('No new arrivals yet.') }}</p>
  @endif
</section>
