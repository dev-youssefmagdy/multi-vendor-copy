@php
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(6)->values();
    $__tileImages = [
        asset('souqify-4/assets/images/shop-footwear.png'),
        asset('souqify-4/assets/images/shop-electronics.png'),
        asset('souqify-4/assets/images/shop-accessories.png'),
    ];
@endphp
<!-- ============ SHOP BY CATEGORY ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[40px] flex flex-col items-center gap-[16px] lg:gap-[24px] bg-white">
    <h2 class="font-extrabold text-[22px] lg:text-[40px] text-center" style="color:var(--color-text-primary)">{{ __('Shop by Category') }}</h2>
    <div class="grid grid-cols-3 gap-[12px] lg:flex lg:items-end lg:justify-between w-full">
        @forelse ($__tiles as $index => $category)
            @php
                $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
                $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
            @endphp
            <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex flex-col items-center gap-[8px] lg:gap-[13px]">
                <div class="relative flex items-end justify-center h-[100px] w-[90px] lg:h-[149px] lg:w-[159px]">
                    <img src="{{ asset('souqify-4/assets/icons/shop-blob-' . (($index % 3) + 1) . '.svg') }}" class="absolute left-1/2 -translate-x-1/2 bottom-[10%] size-[70px] lg:size-[133px]" alt="" />
                    <img src="{{ $__img }}" class="relative w-full h-full object-contain" alt="{{ $__name }}" />
                </div>
                <p class="font-medium text-[14px] lg:text-[21px] tracking-[0.5px] lg:tracking-[0.67px]" style="color:var(--color-text-primary)">{{ $__name }}</p>
            </a>
        @empty
            <p class="col-span-3 text-sm py-6" style="color:var(--color-text-muted)">{{ __('No categories yet.') }}</p>
        @endforelse
    </div>
    <button type="button" class="border rounded-full px-[28px] py-[14px] lg:px-[32px] lg:py-[16px] text-[14px] lg:text-[20px] font-medium cursor-pointer" style="border-color:var(--color-text-primary); color:var(--color-text-primary)" onclick="window.location='{{ route('tenant.storefront.category') }}'">{{ __('Explore all') }}</button>
</section>
