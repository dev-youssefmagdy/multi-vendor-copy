        {{-- ── Flash / Lightning Deals ─────────────────────────────────────────── --}}
        @if ($flashSales->isNotEmpty())
            <section class="max-w-7xl mx-auto w-full">
                <div class="flex items-center justify-between mb-4 px-4 md:px-0">
                    <div
                        class="flex items-center gap-1.5 md:gap-2 text-primary font-black text-[13px] md:text-lg uppercase tracking-wide whitespace-nowrap">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ __('Lightning Deals') }}
                    </div>
                    <span
                        class="hidden md:block text-[11px] md:text-[13px] font-bold text-gray-darkest">{{ __('Limited time offer') }}</span>
                </div>

                <div class="flex overflow-x-auto gap-3 md:gap-4 md:grid md:grid-cols-2 lg:grid-cols-5 px-4 md:px-0 pb-3"
                    style="scrollbar-width: none;">
                    @foreach ($flashSales as $sale)
                        @foreach ($sale->products->take(5) as $product)
                            @php
                                $saleImg = $product->centralProduct?->primary_image_url ?? $product->primary_image_url;
                                $saleVariant = $product->variants->firstWhere('active', true) ?? $product->variants->first();
                                $salePricing = $product->storefrontPricing($saleVariant);
                                $endTs = $sale->end_date ? $sale->end_date->timestamp : 0;
                                $disc = (int) round((float) $salePricing['discount_percentage']);
                            @endphp
                            <a href="{{ route('tenant.storefront.product', $product->slug) }}"
                                class="shrink-0 w-[160px] md:w-auto cursor-pointer bg-white rounded-xl overflow-hidden shadow-sm flex flex-col hover:shadow-md transition">

                                <div class="bg-[#f8f8f8] flex items-center justify-center min-h-[180px]">
                                    @if ($saleImg)
                                        <img loading="lazy" src="{{ $saleImg }}" alt="{{ $product->translationValue('name') }}"
                                            class="max-h-full max-w-full mix-blend-multiply object-contain">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col p-3 mt-auto">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-primary font-bold text-[16px] leading-[1] tracking-tight">
                                            {{ $symbol }}{{ number_format((float) $salePricing['current_price'] * $rate, 2) }}
                                        </span>
                                        @if ($disc > 0)
                                            <span
                                                class="text-primary border border-[#fba8a8] bg-white text-[9.5px] font-bold px-[5px] py-[2px] rounded-[4px] leading-none tracking-tight">-{{ $disc }}%</span>
                                        @endif
                                    </div>

                                    {{-- Countdown bar --}}
                                    @if ($endTs > 0)
                                        <div class="flex items-center gap-2 mb-3 w-full">
                                            <div class="flex-1 relative h-[4px] bg-[#e5e5e5] rounded-full">
                                                <div class="absolute left-0 top-0 bottom-0 bg-[#3b3b3b] rounded-full"
                                                    style="width: 10%;"></div>
                                            </div>
                                            <span class="text-[10px] font-bold text-[#3b3b3b] tracking-wider js-countdown"
                                                data-countdown="{{ $endTs }}">--:--:--</span>
                                        </div>
                                    @endif

                                    {{-- Stars --}}
                                    @include('themes.ecommet.pages._stars', ['rating' => $product->average_rating])
                                </div>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </section>
        @endif
