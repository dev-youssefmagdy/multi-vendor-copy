    @php
      $newInCards = ($newInProducts ?? collect())->map(fn ($product) => $product->toEloraV4Card($currentCurrency ?? null))->all();
      $newInGroups = array_chunk($newInCards, 3);
    @endphp
    @if (!empty($newInCards))
    <!-- ============ NEW IN ============ -->
    <section
      class="pattern-newin mt-[64px] px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[34px]"
      wire:ignore
    >
      <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">
          {{ __('New In') }}
        </h2>
        <a
          href="{{ route('tenant.storefront.new-in') }}"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-brand-orange-bright)"
          >{{ __('see all') }}</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper newin-swiper">
          <div class="swiper-wrapper" id="newInWrapper">
            @foreach ($newInGroups as $group)
              @php [$left, $top, $bottom] = array_pad($group, 3, null); @endphp
              @if ($left)
                <div class="swiper-slide h-auto">
                  <div class="flex gap-[16px] h-full">
                    <div class="shrink-0 w-[240px]">
                      @include('themes.elora.pages.home-v4.sections.partials.product_card', ['p' => $left])
                    </div>
                    @if ($top || $bottom)
                      <div class="flex-1 min-w-0 flex flex-col gap-[21px]">
                        @if ($top)
                          <div class="flex-1 min-h-0">
                            @include('themes.elora.pages.home-v4.sections.partials.new_in_side_card', ['p' => $top])
                          </div>
                        @endif
                        @if ($bottom)
                          <div class="flex-1 min-h-0">
                            @include('themes.elora.pages.home-v4.sections.partials.new_in_side_card', ['p' => $bottom])
                          </div>
                        @endif
                      </div>
                    @endif
                  </div>
                </div>
              @endif
            @endforeach
          </div>
        </div>
      </div>
    </section>
    @endif
