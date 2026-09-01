@php
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(6)->values();
    $__tileImages = [
        asset('souqify-2/assets/images/shop-cat-accessories.jpg'),
        asset('souqify-2/assets/images/shop-cat-fashion.jpg'),
        asset('souqify-2/assets/images/shop-cat-electronics.jpg'),
    ];
@endphp
<!-- ============ SHOP BY CATEGORY ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col items-center gap-[16px] lg:gap-[28px]">
  <h2 class="font-medium text-[18px] lg:text-[26px] text-center" style="color: var(--color-black)">{{ __('Shop by Category') }}</h2>
  <div class="relative w-full">
    <div class="swiper card-swiper">
      <div class="swiper-wrapper" id="shopByCategoryWrapper">
        @forelse ($__tiles as $index => $category)
          @php
            $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
            $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
          @endphp
          <div class="swiper-slide h-auto !w-[160px] lg:!w-[220px]">
            <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="category-tile relative rounded-[10px] overflow-hidden flex flex-col items-center justify-end pb-[14px] lg:pb-[22px] gap-[6px] lg:gap-[8px] h-[160px] w-[160px] lg:h-[220px] lg:w-[220px]">
              <img src="{{ $__img }}" alt="{{ $__name }}" class="absolute inset-0 h-full w-full object-cover" />
              <span class="relative text-white font-semibold text-[14px] lg:text-[19px] tracking-[0.5px]">{{ $__name }}</span>
            </a>
          </div>
        @empty
          <p class="text-sm py-6" style="color: var(--color-text-muted)">{{ __('No categories yet.') }}</p>
        @endforelse
      </div>
    </div>
  </div>
  <a href="{{ route('tenant.storefront.category') }}" class="border rounded-full px-[28px] py-[12px] lg:px-[32px] lg:py-[16px] text-[13px] lg:text-[16px] font-medium cursor-pointer" style="border-color: var(--color-black); color: var(--color-black)">{{ __('Explore all') }}</a>
</section>
