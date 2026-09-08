    <!-- =========== SPECIAL OFFERS (dark) =========== -->
    <section class="bg-zinc-900" style="background-image: linear-gradient(#00236046, #003b9a51, #002d7b5a, #0049c654), url('/souqify/assets/images/special.jpeg');" wire:ignore>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white">{{ __('Special Offers') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}?section=special_offers"
                    class="flex items-center gap-1 text-white hover:text-blue-200 transition text-sm sm:text-base">{{ __('View all products') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            <div class="swiper product-slide-swiper">
                <div class="swiper-wrapper">
                    @forelse (($specialProds->isEmpty() ? $paginatedProducts : $specialProds)->take(5) as $product)
                    <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                        @include('themes.souqify.pages._product-card', ['badge' => __('Special')])
                    </div>
                    @empty
                    <p class="col-span-full text-center text-white/70 py-6">{{ __('No products yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
