    @php
      $trendingCards = ($trendingNowProducts ?? collect())->map(fn ($product) => $product->toEloraV4Card($currentCurrency ?? null));
    @endphp
    @if ($trendingCards->isNotEmpty())
    <!-- ============ TRENDING NOW ============ -->
    <section
      class="pattern-trending px-[16px] lg:px-[56px] py-[24px] flex flex-col gap-[16px] lg:gap-[34px]"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          {{ __('Trending Now') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-brand-orange-bright)"
          >{{ __('see all') }}</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper trending-swiper">
          <div class="swiper-wrapper" id="trendingWrapper">
            @foreach ($trendingCards as $p)
              @include('themes.elora.pages.home-v4.sections.partials.trending_card', ['p' => $p])
            @endforeach
          </div>
        </div>
      </div>
    </section>
    @endif
