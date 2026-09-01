@php
    $__categories = ($categories ?? $rootCategories ?? collect())->take(4)->values();
    $__catFallbackImages = [
        asset('souqify-5/assets/images/category-bag.png'),
        asset('souqify-5/assets/images/category-gaming.png'),
        asset('souqify-5/assets/images/category-electronics.png'),
        asset('souqify-5/assets/images/category-accessories.png'),
    ];
@endphp
<!-- ============ CATEGORIES ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]" style="background:var(--color-page-bg)">
    <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px] text-black">{{ __('Categories') }}</h2>
        <a href="{{ route('tenant.storefront.category') }}" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-brand-pink)">{{ __('see all') }}</a>
    </div>
    <div class="grid grid-cols-4 lg:flex lg:items-start lg:justify-between gap-[10px] lg:gap-[32px]">
        @forelse ($__categories as $index => $category)
            <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex flex-col items-center gap-[10px] lg:gap-[16px]">
                <div class="relative w-[75px] h-[76px] lg:w-[151px] lg:h-[153px]">
                    <div class="absolute left-[16px] top-[7px] w-[70px] h-[85px] lg:left-[33px] lg:top-[10px] lg:w-[96px] lg:h-[105px] rounded-t-full" style="background:var(--color-brand-pink)"></div>
                    <img src="{{ $category->thumb_url ?? $category->image_url ?? $__catFallbackImages[$index % count($__catFallbackImages)] }}" alt="{{ $category->translationValue('name') ?? $category->slug }}" class="absolute left-0 top-[8px] w-[75px] h-[62px] lg:top-[16px] lg:w-[151px] lg:h-[125px] object-contain" />
                </div>
                <span class="text-[11px] lg:text-[18px] tracking-[0.3px] text-center" style="color:var(--color-text-primary)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15) }}</span>
            </a>
        @empty
            <p class="col-span-4 text-sm py-4" style="color:var(--color-text-muted)">{{ __('No categories yet.') }}</p>
        @endforelse
    </div>
</section>
