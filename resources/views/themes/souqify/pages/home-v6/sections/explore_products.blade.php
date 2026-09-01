@php
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(3)->values();
    $__tileImages = [
        asset('souqify-5/assets/images/shop-mens.png'),
        asset('souqify-5/assets/images/shop-electronics.png'),
        asset('souqify-5/assets/images/shop-accessories.png'),
    ];
@endphp
<!-- ============ SHOP BY CATEGORY ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col items-center gap-[16px] lg:gap-[32px]" style="background:var(--color-page-bg)">
    <h2 class="font-bold text-[26px] lg:text-[46px] text-center" style="color:transparent; -webkit-text-stroke:1.5px var(--color-brand-pink)">{{ __('Shop by Category') }}</h2>
    <div class="flex items-start justify-center gap-[24px] lg:gap-[80px]">
        @forelse ($__tiles as $index => $category)
            @php
                $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
                $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
            @endphp
            <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex flex-col items-center gap-[8px] lg:gap-[16px]">
                <div class="relative w-[90px] h-[92px] lg:w-[189px] lg:h-[192px]">
                    <div class="absolute right-[10px] top-0 w-[46px] h-[50px] lg:w-[96px] lg:h-[105px] rounded-t-full" style="background:var(--color-brand-pink)"></div>
                    <img src="{{ $__img }}" alt="{{ $__name }}" class="absolute inset-0 w-full h-[80%] object-contain" />
                </div>
                <span class="text-[13px] lg:text-[20px]" style="color:var(--color-text-primary)">{{ $__name }}</span>
            </a>
        @empty
            <p class="text-sm py-6" style="color:var(--color-text-muted)">{{ __('No categories yet.') }}</p>
        @endforelse
    </div>
    <a href="{{ route('tenant.storefront.category') }}" class="border rounded-full px-[28px] py-[8px] lg:px-[32px] lg:py-[16px] text-[14px] lg:text-[16px] font-medium cursor-pointer" style="border-color:var(--color-stroke); color:var(--color-text-primary)">{{ __('Explore all') }}</a>
</section>
