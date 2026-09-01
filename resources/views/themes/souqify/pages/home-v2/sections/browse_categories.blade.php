@php
    $__categories = ($categories ?? $rootCategories ?? collect());
@endphp
<!-- ============ CATEGORIES ============ -->
<section class="px-[16px] lg:px-[56px] py-[16px] lg:py-[24px] flex flex-col gap-[16px] lg:gap-[24px]" style="background:var(--color-page-bg)">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[23px] lg:text-[32px]" style="color:var(--color-text-primary)">{{ __('Categories') }}</h2>
    <a href="{{ route('tenant.storefront.category') }}" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color:var(--color-accent-purple)">{{ __('see all') }}</a>
  </div>
  <div class="flex items-center gap-[24px] lg:gap-[34px] overflow-x-auto no-scrollbar pb-[4px]">
    @forelse ($__categories as $category)
      <a href="{{ route('tenant.storefront.category', $category->slug) }}" class="relative flex flex-col items-center gap-[9px] shrink-0 w-[75px] lg:w-[160px]">
        <img src="{{ asset('souqify-1/assets/images/category-pattern.png') }}" alt="" class="absolute bottom-0 right-0 w-[45px] lg:w-[95px] h-auto -z-10" />
        <div class="relative flex items-center justify-center size-[75px] lg:size-[160px] overflow-hidden">
          @if ($category->thumb_url ?? $category->image_url ?? null)
            <img src="{{ $category->thumb_url ?? $category->image_url }}" alt="{{ $category->translationValue('name') ?? $category->slug }}" class="relative w-full h-full object-contain" />
          @else
            <img src="{{ asset('souqify-1/assets/images/cat-women-bags.png') }}" alt="" class="relative w-[64px] lg:w-[135px] h-auto" />
          @endif
        </div>
        <p class="font-semibold text-[13px] lg:text-[25px] tracking-[0.5px] lg:tracking-[1px] text-center" style="color:var(--color-brand-purple)">{{ \Illuminate\Support\Str::limit($category->translationValue('name') ?? $category->slug, 15) }}</p>
      </a>
    @empty
      <p class="text-sm py-4" style="color:var(--color-text-muted)">{{ __('No categories yet.') }}</p>
    @endforelse
  </div>
</section>
