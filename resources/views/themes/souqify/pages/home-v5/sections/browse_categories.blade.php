@php
    $__categories = ($categories ?? $rootCategories ?? collect())->take(4)->values();
    $__catFallbackImages = [
        asset('souqify-4/assets/images/cat-women-bags.png'),
        asset('souqify-4/assets/images/cat-accessories.png'),
        asset('souqify-4/assets/images/cat-gaming.png'),
        asset('souqify-4/assets/images/cat-electronics.png'),
    ];
@endphp
<!-- ============ CATEGORIES ============ -->
<section class="px-[16px] lg:px-[56px] py-[20px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px] bg-white">
    <div class="flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px]" style="color:var(--color-text-primary)">{{ __('Categories') }}</h2>
        <a href="{{ route('tenant.storefront.category') }}" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-primary)">{{ __('see all') }}</a>
    </div>
    <div class="grid grid-cols-4 gap-[8px] lg:flex lg:flex-wrap lg:justify-between lg:gap-[16px]">
        @forelse ($__categories as $index => $category)
            <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex flex-col items-center gap-[8px] lg:gap-[15px]">
                <div class="relative flex items-center justify-center size-[64px] lg:size-[83px]">
                    <img src="{{ asset('souqify-4/assets/icons/category-blob-' . (($index % 4) + 1) . '.svg') }}" class="absolute inset-0 size-full" alt="" />
                    <img src="{{ $category->thumb_url ?? $category->image_url ?? $__catFallbackImages[$index % count($__catFallbackImages)] }}" class="relative w-[70%] h-auto" alt="{{ $category->translationValue('name') ?? $category->slug }}" />
                </div>
                <p class="font-semibold text-[11px] lg:text-[22px] text-center tracking-[0.5px] lg:tracking-[0.9px]" style="color:var(--color-text-primary)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15) }}</p>
            </a>
        @empty
            <p class="col-span-4 text-sm py-4" style="color:var(--color-text-muted)">{{ __('No categories yet.') }}</p>
        @endforelse
    </div>
</section>
