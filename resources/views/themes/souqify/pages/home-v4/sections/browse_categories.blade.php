@php
    $__categories = ($categories ?? $rootCategories ?? collect())->take(4)->values();
    $__catFallbackImages = [
        asset('souqify-3/assets/images/cat-women-bags.png'),
        asset('souqify-3/assets/images/cat-accessories.png'),
        asset('souqify-3/assets/images/cat-gaming.png'),
        asset('souqify-3/assets/images/cat-electronics.png'),
    ];
@endphp
<!-- ============ CATEGORIES ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[30px]" style="background:var(--color-page-bg)">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color:var(--color-text-primary)">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-brand-green)">{{ __('see all') }}</a>
  </div>
  <div class="flex items-start gap-[20px] lg:gap-0 lg:justify-between overflow-x-auto no-scrollbar">
    @forelse ($__categories as $index => $category)
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex flex-col items-center gap-[10px] lg:gap-[15px] shrink-0 w-[75px] lg:w-[140px]">
        <div class="relative w-[67px] h-[69px] lg:w-[125px] lg:h-[129px] flex items-end justify-center" style="background:var(--color-brand-green); border-radius:50% 50% 10px 0">
          <img src="{{ $category->thumb_url ?? $category->image_url ?? $__catFallbackImages[$index % count($__catFallbackImages)] }}" alt="{{ $category->translationValue('name') ?? $category->slug }}" class="w-[55px] lg:w-[100px] h-auto -mb-[6px] lg:-mb-[10px] object-contain" />
        </div>
        <p class="font-semibold text-[13px] lg:text-[22px] tracking-[0.5px] text-center whitespace-nowrap" style="color:var(--color-text-primary)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15) }}</p>
      </a>
    @empty
      <p class="text-sm py-4" style="color:var(--color-footer-text-muted)">{{ __('No categories yet.') }}</p>
    @endforelse
  </div>
</section>
