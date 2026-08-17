        {{-- ── Flash Sale banner strip ──────────────────────────────────────────── --}}
        @php $firstSale = $flashSales->first(); @endphp
        @if ($firstSale)
            <section
                class="max-w-7xl mx-auto w-full bg-red-50 overflow-hidden py-4 px-4 md:py-9 md:px-12 flex flex-row items-center justify-between text-gray-dark mt-4 mb-4 md:mt-12 md:mb-12 border-y md:border md:rounded-lg border-[#ffd7d0]">
                <div class="flex flex-col items-start gap-[6px] md:w-1/3">
                    <h2 class="text-[12px] md:text-[23px] font-medium text-[#222222] whitespace-nowrap">
                        {{ __('Flash Sale now on!') }}
                    </h2>
                    @if ($firstSale->end_date)
                        <div class="flex md:hidden items-center text-[16px] font-black gap-[3px] text-primary tracking-widest js-countdown"
                            data-countdown="{{ $firstSale->end_date->timestamp }}">--:--:--</div>
                    @endif
                </div>

                @if ($firstSale->end_date)
                    <div class="hidden md:flex flex-1 justify-center">
                        <div class="flex items-center text-[28px] lg:text-[34px] font-black gap-[3px] md:gap-2 text-[#222222] tracking-widest js-countdown"
                            data-countdown="{{ $firstSale->end_date->timestamp }}">--:--:--</div>
                    </div>
                @endif

                <div
                    class="flex flex-col items-start md:items-center md:flex-row gap-2 md:gap-4 lg:gap-6 ml-0 md:w-1/3 md:justify-end">
                    <div
                        class="text-[9.5px] md:text-[11px] lg:text-[13px] leading-[1.3] text-left text-[#242424] whitespace-nowrap">
                        <p>{{ __('Save on everything.') }}</p>
                        <p>{{ __('Best seller + more') }}</p>
                    </div>
                    <a href="{{ route('tenant.storefront.best-selling') }}"
                        class="bg-gray-darkest text-white px-[12px] py-[6px] md:px-6 md:py-3 lg:px-8 lg:py-3.5 rounded-full font-bold hover:bg-black transition text-[9px] md:text-xs lg:text-sm tracking-wide shadow w-fit whitespace-nowrap">
                        {{ __('Shop flash deals') }}
                    </a>
                </div>
            </section>
        @endif
