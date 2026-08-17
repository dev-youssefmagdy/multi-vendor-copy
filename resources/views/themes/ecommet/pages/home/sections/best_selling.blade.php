        {{-- ── Best-Selling Items ───────────────────────────────────────────────── --}}
        <div
            class="flex items-center md:justify-center overflow-x-auto no-scrollbar gap-5 md:gap-8 max-w-[1280px] mx-auto px-4 mb-4 md:mb-6 border-b md:border-none border-[#eaeaea]">
            <h2
                class="flex items-center gap-1.5 md:gap-2 text-primary text-[14px] md:text-2xl font-bold pb-3 relative whitespace-nowrap">
                <svg class="w-5 h-5 md:w-6 md:h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ __('Best-Selling Items') }}
                <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-primary rounded-t-sm md:hidden"></div>
            </h2>
            <a href="{{ route('tenant.storefront.best-selling') }}"
                class="flex md:hidden items-center gap-1.5 text-gray-500 hover:text-gray-darkest transition text-[14px] font-bold pb-3 whitespace-nowrap">
                {{ __('View All') }}
            </a>
        </div>

        @if ($bestSelling->isNotEmpty())
            <section class="max-w-[1280px] mx-auto px-2 md:px-4">
                <div id="bestSellingItemsGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
                    @foreach ($bestSelling as $product)
                        @include('themes.ecommet.pages._product-card', ['product' => $product, 'badge' => __('Best-Selling')])
                    @endforeach
                </div>

                @if ($hasMoreBestSelling)
                    <div class="flex justify-center mt-10 mb-16">
                        <button type="button" wire:click="loadMoreBestSelling"
                            class="flex items-center gap-2 bg-gray-darkest text-white px-7 py-2.5 rounded-full font-bold hover:bg-black transition text-sm">
                            <span wire:loading.remove wire:target="loadMoreBestSelling">{{ __('See More') }}</span>
                            <span wire:loading wire:target="loadMoreBestSelling">{{ __('Loading...') }}</span>
                            <svg wire:loading.remove wire:target="loadMoreBestSelling" class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                @else
                    <div class="mb-16"></div>
                @endif
            </section>
        @else
            <p class="text-center text-gray-400 py-10 mb-16">{{ __('No best-selling products available yet.') }}</p>
        @endif
