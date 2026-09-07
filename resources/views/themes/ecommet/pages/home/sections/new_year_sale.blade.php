        {{-- ── New Year Sale – category pills + paginated product grid ─────────── --}}
        <section class="max-w-7xl mx-auto px-4 mt-8 md:mt-12 mb-4">
            <div class="text-center mb-6">
                <h3 class="text-primary font-bold text-[24px] tracking-wide mb-1">{{ __('New Year Sale') }}</h3>
                <h2 class="text-gray-darkest font-black text-2xl tracking-widest uppercase">
                    {{ __('Explore your interests') }}
                </h2>
            </div>

            {{-- Category pills --}}
            @include('themes.ecommet.pages._categories-slider', [
                'allHref' => '#',
                'allActive' => true,
                'items' => $categories->map(fn($cat) => [
                    'href' => route('tenant.storefront.category', $cat->slug),
                    'label' => $cat->name ?? $cat->slug,
                    'active' => false,
                ]),
            ])

            {{-- New-In product grid --}}
            @if ($newInProducts->isNotEmpty())
                <div id="newInItemsGrid" class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach ($newInProducts as $product)
                        @include('themes.ecommet.pages._product-card', ['product' => $product, 'badge' => null])
                    @endforeach
                </div>

                @if ($hasMoreNewIn)
                    <div class="flex justify-center mt-12 mb-4">
                        <button type="button" wire:click="loadMoreNewIn"
                            class="bg-[#222222] text-white rounded-full px-8 py-3 text-[14px] font-bold tracking-wide flex items-center justify-center gap-2 hover:bg-black transition cursor-pointer w-fit">
                            <span wire:loading.remove wire:target="loadMoreNewIn">{{ __('See More') }}</span>
                            <span wire:loading wire:target="loadMoreNewIn">{{ __('Loading...') }}</span>
                            <svg wire:loading.remove wire:target="loadMoreNewIn" class="w-5 h-5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                @endif
            @else
                <p class="text-center text-gray-400 py-10">{{ __('No new products available yet.') }}</p>
            @endif
        </section>
