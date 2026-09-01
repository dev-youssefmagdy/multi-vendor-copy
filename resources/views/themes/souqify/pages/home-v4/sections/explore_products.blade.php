@php
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(6)->values();
    $__tileImages = [
        asset('souqify-3/assets/images/shop-accessories.png'),
        asset('souqify-3/assets/images/shop-electronics.jpg'),
        asset('souqify-3/assets/images/shop-mens.png'),
    ];
@endphp
<!-- ============ SHOP BY CATEGORY ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[48px] flex flex-col gap-[16px] lg:gap-[28px]">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color:var(--color-text-primary)">{{ __('Shop by Category') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-brand-green)">{{ __('Explore all') }}</a>
  </div>
  <div class="flex items-center gap-[10px] lg:gap-[16px] overflow-x-auto no-scrollbar">
    @forelse ($__tiles as $index => $category)
      @php
        $__img = $category->thumb_url ?? $category->image_url ?? $__tileImages[$index % count($__tileImages)];
        $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
      @endphp
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="relative shrink-0 w-[105px] h-[148px] lg:w-[176px] lg:h-[245px] flex items-end justify-center overflow-hidden" style="border-radius:50% 50% 8px 8px">
        <img src="{{ $__img }}" alt="{{ $__name }}" class="absolute inset-0 h-full w-full object-cover" />
        <div class="absolute inset-0" style="background:linear-gradient(0deg, rgba(0,0,0,0.75) 0%, rgba(90,155,0,0.15) 100%)"></div>
        <span class="relative text-white font-medium text-[13px] lg:text-[20px] tracking-[0.5px] pb-[10px] lg:pb-[16px]">{{ $__name }}</span>
      </a>
    @empty
      <p class="text-sm py-6" style="color:var(--color-footer-text-muted)">{{ __('No categories yet.') }}</p>
    @endforelse
  </div>
  <button type="button" class="hidden lg:flex self-center border rounded-full px-[32px] py-[16px] text-[16px] font-medium cursor-pointer" style="border-color:var(--color-stroke); color:var(--color-text-primary)" onclick="window.location='{{ route('tenant.storefront.category') }}'">{{ __('Explore all') }}</button>
</section>
