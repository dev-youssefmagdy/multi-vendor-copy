@php
    $__tiles = collect($categories ?? $rootCategories ?? [])->take(4)->values();
@endphp
<!-- ============ SHOP BY CATEGORY ============ -->
@if ($__tiles->isNotEmpty())
<section class="bg-white px-[16px] lg:px-[56px] py-[24px] flex flex-col items-center gap-[16px] lg:gap-[24px]">
  <h2 class="font-extrabold text-[24px] lg:text-[40px] text-center" style="color:var(--color-text-primary)">{{ __('Shop by Category') }}</h2>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-[10px] lg:gap-[17px] w-full">
    @foreach ($__tiles as $index => $category)
      @php
        $__img = $category->thumb_url ?? $category->image_url ?? asset('souqify-1/assets/images/shopcat-accessories.png');
        $__name = \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 20);
      @endphp
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="relative rounded-[11px] overflow-hidden flex items-end justify-center {{ $index === 0 ? 'h-[237px] lg:h-[328px] lg:row-span-1' : 'h-[113px] lg:h-[155px]' }}">
        <img src="{{ $__img }}" alt="{{ $__name }}" class="absolute inset-0 h-full w-full object-cover" />
        <span class="relative w-full text-white font-medium text-[16px] lg:text-[22px] tracking-[0.5px] rounded-tr-[36px] px-[16px] py-[12px]" style="background:var(--color-brand-purple)">{{ $__name }}</span>
      </a>
    @endforeach
  </div>
  <a href="{{ route('tenant.storefront.category') }}" class="border rounded-[34px] px-[32px] py-[16px] text-[14px] lg:text-[20px] font-medium cursor-pointer" style="border-color:var(--color-text-primary); color:var(--color-text-primary)">{{ __('Explore all') }}</a>
</section>
@endif
