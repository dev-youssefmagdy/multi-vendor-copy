{{-- ── Category Swiper (2 rows: each slide is a column of 2 circle thumbnails) ── --}}
@if ($categories->isNotEmpty())
<section class="py-5 sm:py-8 bg-white" wire:ignore>
    <div class="max-w-screen-xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="swiper category-swiper-2row">
            <div class="swiper-wrapper">
                @foreach ($categories->chunk(2) as $column)
                <div class="swiper-slide !w-[76px] lg:!w-[85px] !h-auto">
                    <div class="flex flex-col gap-4 lg:gap-8">
                        @foreach ($column as $cat)
                        @php $catName = $cat->translationValue('name') ?? $cat->name; @endphp
                        <a href="{{ route('tenant.storefront.category', $cat->slug) }}" draggable="false"
                            class="group flex flex-col items-center gap-4 h-[129px]">
                            <div class="w-[76px] h-[76px] lg:w-[81px] lg:h-[81px] rounded-full bg-gray-100 flex items-center justify-center overflow-hidden group-hover:ring-2 group-hover:ring-main transition-all">
                                @if ($cat->thumb_url ?? null)
                                    <img loading="lazy" draggable="false" src="{{ $cat->thumb_url }}" alt="{{ $catName }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-2xl">🛍️</span>
                                @endif
                            </div>
                            <span class="w-full text-center text-[14px] leading-[18px] tracking-[0.5px] text-[#242424] line-clamp-2"
                                style="font-family:'Outfit',sans-serif;">{{ $catName }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
