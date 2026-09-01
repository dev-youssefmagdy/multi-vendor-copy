@php
    $__categories = ($categories ?? $rootCategories ?? collect())->take(4)->values();
@endphp
<!-- ============ CATEGORIES ============ -->
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[24px]" style="background: var(--color-page-bg)">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[18px] lg:text-[24px]" style="color: var(--color-black)">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="font-normal text-[12px] lg:text-[16px] tracking-[0.5px]" style="color: var(--color-souqify-teal)">{{ __('see all') }}</a>
  </div>
  <div class="flex items-center justify-between lg:justify-start lg:gap-[64px] overflow-x-auto no-scrollbar">
    @forelse ($__categories as $category)
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="flex flex-col items-center gap-[8px] lg:gap-[12px] w-[72px] lg:w-[110px] shrink-0">
        @if ($category->thumb_url ?? $category->image_url ?? null)
          <img src="{{ $category->thumb_url ?? $category->image_url }}" alt="{{ $category->translationValue('name') ?? $category->slug }}" class="w-[39px] lg:w-[60px] h-auto object-contain" />
        @else
          <img src="{{ asset('souqify-2/assets/images/cat-bag.png') }}" alt="" class="w-[39px] lg:w-[60px] h-auto" />
        @endif
        <p class="font-semibold text-[12px] lg:text-[16px] tracking-[0.5px] text-center" style="color: var(--color-black)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15) }}</p>
      </a>
    @empty
      <p class="text-sm py-4" style="color: var(--color-text-muted)">{{ __('No categories yet.') }}</p>
    @endforelse
  </div>
</section>
