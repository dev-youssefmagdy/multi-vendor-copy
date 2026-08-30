@php
  $__shopCategories = [
    ['name' => "Women's Fashion", 'items' => '2M items', 'image' => 'shop-womens-fashion.jpg'],
    ['name' => "Men's Fashion", 'items' => '0.7M items', 'image' => 'shop-mens-fashion.jpg'],
    ['name' => 'Coastal Chic', 'items' => '3.1k items', 'image' => 'shop-coastal-chic.jpg'],
  ];
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col items-center gap-[16px] lg:gap-[24px]">
  <h2 class="font-semibold text-[22px] lg:text-[32px] tracking-[0.5px] text-center" style="color: var(--color-text-primary)">Shop by Category</h2>
  <div class="flex items-center justify-center gap-[10px] lg:gap-[13px] overflow-x-auto no-scrollbar w-full">
    @foreach ($__shopCategories as $__cat)
      <a href="#" class="relative shrink-0 rounded-[16px] lg:rounded-[25px] overflow-hidden flex items-end justify-start p-[16px] lg:p-[19px] h-[220px] lg:h-[382px] w-[145px] lg:w-[240px]">
        <img src="{{ asset('elora-3/assets/images/' . $__cat['image']) }}" alt="{{ $__cat['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        <div class="absolute inset-0" style="background: linear-gradient(0deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0) 55%);"></div>
        <div class="relative flex flex-col items-start gap-[4px]">
          <span class="text-white font-bold text-[14px] lg:text-[22px]">{{ $__cat['name'] }}</span>
          <span class="text-[10px] lg:text-[16px]" style="color: rgba(255, 255, 255, 0.75)">{{ $__cat['items'] }}</span>
          <span class="mt-[6px] lg:mt-[13px] rounded-full text-white font-bold text-[11px] lg:text-[19px] px-[14px] lg:px-[22px] py-[6px] lg:py-[9px]" style="background: var(--color-brand-pink)">Shop &rarr;</span>
        </div>
      </a>
    @endforeach
  </div>
  <button type="button" class="border rounded-full px-[32px] py-[16px] text-[14px] lg:text-[16px] font-medium cursor-pointer" style="border-color: var(--color-text-primary); color: var(--color-text-primary);">
    Explore all
  </button>
</section>
