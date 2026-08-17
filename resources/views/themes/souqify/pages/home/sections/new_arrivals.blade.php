    <!-- =========== NEW ARRIVALS (orange band) =========== -->
    <section class="bg-gradient-to-r from-orange-700 via-orange-500 to-orange-700" wire:ignore>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-5 lg:py-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white">{{ __('New Arrivals') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}?section=new_arrivals"
                    class="flex items-center gap-1 text-white hover:text-amber-100 transition text-sm sm:text-base">{{ __('View all products') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            <div class="swiper product-slide-swiper">
                <div class="swiper-wrapper">
                    @forelse ($newArrivals->take(5) as $product)
                    <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                        @include('themes.souqify.pages._product-card', ['badge' => __('New In')])
                    </div>
                    @empty
                    <p class="col-span-full text-center text-white/70 py-6">{{ __('No products yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
