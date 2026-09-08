    <!-- =========== BROWSE BY CATEGORIES =========== -->
    <section class="bg-white mb-6 lg:mb-16" wire:ignore>
        <div class="swiper categories-slide max-w-[1440px] mx-auto p-4 sm:p-6 lg:p-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">{{ __('Browse by Categories') }}</h3>
                <div class="flex items-center gap-2">
                    <button
                        class="swiper-navigation-prev w-8 h-8 rounded-full border border-gray-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        class="swiper-navigation-next w-8 h-8 rounded-full border border-gray-300 hover:border-blue-700 hover:text-blue-700 flex items-center justify-center transition">
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="swiper-wrapper">
                @forelse ($allCats->take(12) as $cat)
                @php
                $catImg = $cat->thumb_url ?? null;
                $catName = $cat?->translationValue('name') ?? $cat->name ?? $cat->slug;
                $catUrl = route('tenant.storefront.category', $cat->slug);
                @endphp
                <div class="swiper-slide w-fit">
                    <a href="{{ $catUrl }}" class="flex flex-col items-center gap-2 shrink-0 group">
                        <div
                            class="w-20 h-20 lg:w-32 lg:h-32 rounded-lg overflow-hidden border-transparent flex items-center justify-center">
                            @if ($catImg)
                            <img loading="lazy" src="{{ $catImg }}" alt="{{ $catName }}" class="w-full h-full object-cover" />
                            @else
                            <span class="text-2xl">🏷️</span>
                            @endif
                        </div>
                        <span
                            class="text-xs font-medium text-gray-700 group-hover:text-blue-700 transition text-center whitespace-nowrap max-w-[80px] truncate">{{ $catName }}</span>
                    </a>
                </div>
                @empty
                <p class="text-sm text-neutral-400 py-4">{{ __('No categories yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>
