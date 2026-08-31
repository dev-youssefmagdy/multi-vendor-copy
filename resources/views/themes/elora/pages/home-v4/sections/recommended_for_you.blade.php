    @php
      $recommendedCards = ($recommendedProducts ?? collect())->map(fn ($product) => $product->toEloraV4Card($currentCurrency ?? null));
    @endphp
    @if ($recommendedCards->isNotEmpty())
    <!-- ============ RECOMMENDED FOR YOU ============ -->
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]"
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          {{ __('Recommended For You') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.category') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-brand-orange-bright)"
          >{{ __('see all') }}</a
        >
      </div>
      <div
        class="grid grid-cols-2 lg:grid-cols-5 gap-[12px] lg:gap-[16px]"
      >
        @foreach ($recommendedCards as $p)
          <div class="h-full" wire:key="recommended-{{ $p['id'] }}">
            @include('themes.elora.pages.home-v4.sections.partials.product_card', ['p' => $p])
          </div>
        @endforeach
      </div>
      @if ($hasMoreRecommended ?? false)
        <div wire:intersect="loadMoreRecommended" class="flex items-center justify-center py-[8px]">
          <div wire:loading wire:target="loadMoreRecommended" class="flex items-center gap-2 text-[14px]" style="color: var(--color-text-subtitle)">
            <svg class="animate-spin size-[18px]" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            {{ __('Loading more...') }}
          </div>
        </div>
      @endif
    </section>
    @endif
