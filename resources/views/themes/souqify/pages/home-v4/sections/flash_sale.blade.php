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
            'badge' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
            'name' => \Illuminate\Support\Str::limit($product->translationValue('name') ?? $product->slug, 30),
            'rating' => number_format($rating, 1) . ($ratingCount > 0 ? " (+{$ratingCount})" : ''),
            'price' => $symbol . number_format((float) $pricing['current_price'] * $rate, 2),
            'oldPrice' => $hasDiscount && $pricing['original_price'] !== null ? $symbol . number_format((float) $pricing['original_price'] * $rate, 2) : null,
            'discount' => $hasDiscount ? (int) round((float) $pricing['discount_percentage']) . '% ' . __('Off') : null,
        ];
    });
@endphp
<!-- ============ FLASH SALE ============ -->
<section class="wave-surface px-[16px] lg:px-[56px] py-[32px] lg:py-[48px]" @if($__flashEnd) data-flash-end="{{ $__flashEnd->timestamp }}" @endif>
  <img src="{{ asset('souqify-3/assets/images/best-seller-swirl-bg.png') }}" alt="" class="wave-overlay" />
  <div class="relative flex flex-col lg:flex-row items-center gap-[28px] lg:gap-[40px]">
    <div class="flex flex-col items-center lg:items-start gap-[18px] lg:gap-[28px] lg:flex-1">
      <div class="flex items-center gap-[8px]">
        <svg viewBox="0 0 24 24" class="size-[26px] lg:size-[40px]" fill="var(--color-badge-yellow)"><path d="M13 2 3 14h7l-1 8 11-14h-7l0-6z"/></svg>
        <h2 class="font-extrabold text-white text-[32px] lg:text-[56px] tracking-[1px]">{{ __('Flash Sale') }}</h2>
      </div>
      <div class="flex items-center gap-[14px] lg:gap-[20px]">
        <span class="text-[11px] lg:text-[14px] font-medium tracking-[3px] -rotate-90 whitespace-nowrap" style="color:rgba(255,255,255,0.55)">{{ __('ENDS IN') }}</span>
        <div class="flex gap-[8px] lg:gap-[12px]">
          <div class="flex items-center justify-center rounded-[8px] bg-white size-[46px] lg:size-[64px]" style="border:2px solid var(--color-brand-green)">
            <span data-flash-hours class="font-bold text-[20px] lg:text-[28px]" style="color:var(--color-brand-green)">03</span>
          </div>
          <div class="flex items-center justify-center rounded-[8px] bg-white size-[46px] lg:size-[64px]" style="border:2px solid var(--color-brand-green)">
            <span data-flash-minutes class="font-bold text-[20px] lg:text-[28px]" style="color:var(--color-brand-green)">06</span>
          </div>
          <div class="flex items-center justify-center rounded-[8px] bg-white size-[46px] lg:size-[64px]" style="border:2px solid var(--color-brand-green)">
            <span data-flash-seconds class="font-bold text-[20px] lg:text-[28px]" style="color:var(--color-brand-green)">25</span>
          </div>
        </div>
      </div>
      <a href="{{ route('tenant.storefront.category') }}?section=flash_sale" class="rounded-full h-[42px] lg:h-[56px] px-[28px] lg:px-[40px] flex items-center justify-center cursor-pointer" style="background:var(--color-white)">
        <span class="font-medium text-[14px] lg:text-[18px] tracking-[0.5px]" style="color:var(--color-brand-green)">{{ __('Shop now') }}</span>
      </a>
    </div>

    @if ($__flashCards->isNotEmpty())
      <div id="flashStack" class="flash-stack h-[230px] w-full max-w-[280px] lg:h-[320px] lg:max-w-[520px] lg:flex-1">
        @foreach ($__flashCards->take(3) as $index => $p)
          @php
            $__layouts = [
                'left-[10px] top-[150px] lg:left-0 lg:top-[80px] rotate-[-8deg] w-[130px] lg:w-[230px] z-10',
                'left-[65px] top-0 lg:left-[150px] lg:top-0 w-[150px] lg:w-[270px] z-20',
                'right-[10px] top-[150px] lg:right-0 lg:top-[60px] rotate-[8deg] w-[125px] lg:w-[225px] z-10',
            ];
          @endphp
          <a href="{{ $p['url'] }}" class="{{ $__layouts[$index] }} absolute rounded-[6px] overflow-hidden shadow-xl" style="background:var(--color-white)" wire:key="flash-v4-{{ $p['id'] }}">
            <div class="relative h-[110px] lg:h-[200px] w-full" style="background:var(--color-card-image-bg)">
              @if ($p['image'])
                <img loading="lazy" src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
              @endif
              <span class="absolute top-0 left-0 flex items-center gap-[3px] rounded-br-[8px] px-[6px] py-[3px] text-white text-[9px] lg:text-[11px] font-medium whitespace-nowrap" style="background:var(--color-brand-green)">🔥 {{ __('Trending') }}</span>
            </div>
            <div class="p-[8px] flex flex-col gap-[4px]">
              <p class="font-medium text-[11px] lg:text-[15px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
              <div class="flex items-end gap-[5px]">
                <p class="font-bold text-[13px] lg:text-[18px]" style="color:var(--color-brand-green)">{{ $p['price'] }}</p>
                @if (!empty($p['oldPrice']))
                  <p class="text-[9px] lg:text-[11px] line-through" style="color:var(--color-gray)">{{ $p['oldPrice'] }}</p>
                @endif
              </div>
              @if (!empty($p['discount']))
                <span class="inline-flex items-center justify-center px-[6px] py-[2px] self-start text-[8px] lg:text-[10px]" style="background:var(--color-badge-yellow); color:var(--color-text-primary)">{{ $p['discount'] }}</span>
              @endif
            </div>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>
