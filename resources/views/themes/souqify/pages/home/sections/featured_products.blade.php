    <!-- small screen Promo card -->
    <section class="mb-6 lg:hidden" wire:ignore>
        <div class="p-4 overflow-hidden bg-cover bg-center"
            style="background-image: linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)), url('{{ $promoCardImage }}');">
            <div class="text-[var(--sq-primary)] text-4xl font-bold leading-none">{{ $promoDiscountPct }}%</div>
            <div class="w-12 h-0.5 bg-white my-1"></div>
            <div class="flex justify-between">
                <div>
                    <p class="text-gray-300 text-lg sm:text-xl mb-1">{{ __('PREMIUM TECH') }}</p>
                    <p class="text-[#FF4D00] text-base font-semibold">{{ __('Best Deals') }}</p>
                </div>
                <a href="{{ route('tenant.storefront.category') }}?section=explore"
                    class="h-12 px-6 bg-[var(--sq-primary)] hover:bg-[var(--sq-primary-hover)] transition rounded-full text-white inline-flex items-center font-medium">{{ __('Shop now') }}</a>
            </div>
        </div>
    </section>

    <!-- =========== FEATURED PRODUCTS (dark) =========== -->
    <section class="bg bg-cover bg-center bg-no-repeat"
        style="background-image: linear-gradient(rgba(0,0,0,0.72), rgba(0,0,0,0.72)), url('/souqify/assets/images/featured.webp');"
        wire:ignore>
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white">{{ __('Featured Products') }}</h2>
                <a href="{{ route('tenant.storefront.category') }}?section=featured"
                    class="flex items-center gap-1 text-white hover:text-[var(--sq-primary-muted)] transition text-sm sm:text-base">{{ __('View all products') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                </a>
            </div>
            @php
                // Row split: <=4 products => single row; 5-8 => 4 on the first row, the rest on the second; >8 => halved.
                $featuredTotal = $featuredProds->count();
                $featuredFirstRow = $featuredTotal <= 4 ? $featuredTotal : ($featuredTotal <= 8 ? 4 : (int) ceil($featuredTotal / 2));
                $featuredRows = array_filter([
                    $featuredProds->take($featuredFirstRow),
                    $featuredProds->skip($featuredFirstRow),
                ], fn ($row) => $row->isNotEmpty());
            @endphp
            <div class="flex gap-6">
                <div class="flex-1 max-w-full lg:max-w-[88%]">
                    @forelse ($featuredRows as $row)
                    <div class="swiper featured-product-slide {{ $loop->last ? '' : 'mb-7' }}">
                        <div class="swiper-wrapper">
                            @foreach ($row as $product)
                            <div class="swiper-slide  w-[calc(50%-6px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]">
                                @include('themes.souqify.pages._product-card', [])
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-white/70 py-6">{{ __('No products yet.') }}</p>
                    @endforelse
                </div>
                <!-- large screen Promo card -->
                <div class="hidden lg:flex flex-col items-center justify-center text-center rounded-2xl p-8 overflow-hidden bg-cover bg-center w-36"
                    style="background-image: linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)), url('{{ $promoCardImage }}');">
                    <div class="text-white text-7xl sm:text-8xl font-bold leading-none">{{ $promoDiscountPct }}<br />%</div>
                    <div class="w-12 h-0.5 bg-white my-6"></div>
                    <p class="text-gray-300 text-lg sm:text-xl mb-1">{{ __('PREMIUM TECH') }}</p>
                    <p class="text-white text-base font-semibold mb-8">{{ __('Best Deals') }}</p>
                    <a href="{{ route('tenant.storefront.category') }}?section=featured"
                        class="h-12 px-6 bg-[var(--sq-primary)] hover:bg-[var(--sq-primary-hover)] transition rounded-full text-white inline-flex items-center font-medium">{{ __('Shop now') }}</a>
                </div>
            </div>
        </div>
    </section>
