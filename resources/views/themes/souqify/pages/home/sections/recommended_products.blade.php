    <!-- =========== RECOMMENDED PRODUCTS (infinite scroll) =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12 lg:pb-16" id="sqRecommendedSection"
        data-api-url="{{ route('tenant.storefront.products.json') }}">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-slate-900">{{ __('Recommended Products') }}
            </h2>
            <a href="{{ route('tenant.storefront.category') }}?section=recommended"
                class="flex items-center gap-1 text-slate-900 hover:text-blue-700 transition text-sm sm:text-base">
                {{ __('View all products') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </a>
        </div>

        <!-- Product grid populated by JS -->
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 lg:gap-6"
            id="sqRecommendedGrid"></div>

        <!-- Scroll sentinel — hidden once all pages are loaded -->
        <div id="sqRecommendedSentinel" class="w-full h-4 mt-4"></div>

        <!-- Spinner -->
        <div id="sqRecommendedLoader" class="hidden w-full justify-center py-8">
            <div class="w-8 h-8 border-4 border-blue-700 border-t-transparent rounded-full animate-spin"></div>
        </div>
    </section>
