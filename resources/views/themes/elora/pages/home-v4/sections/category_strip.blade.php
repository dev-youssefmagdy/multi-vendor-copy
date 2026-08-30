{{-- No direct counterpart in elora-4/index.html; a horizontal category pill
     strip built from the same category art + orange palette used elsewhere
     in the elora-4 design (feature strip / category circles). --}}
@php
  $stripCategories = [
    ['image' => 'cat-gaming.png', 'name' => 'Gaming'],
    ['image' => 'cat-bag.png', 'name' => 'Women Bags'],
    ['image' => 'cat-electronics.png', 'name' => 'Electronics'],
    ['image' => 'cat-accessories.png', 'name' => 'Accessories'],
  ];
@endphp
<section
  class="overflow-x-auto no-scrollbar"
  style="background: var(--color-brand-orange)"
>
  <div class="flex items-center gap-[12px] lg:gap-[20px] px-[16px] lg:px-[56px] py-[14px] lg:py-[18px] w-max lg:w-full lg:justify-center">
    @foreach ($stripCategories as $cat)
      <a
        href="#"
        class="flex items-center gap-[10px] shrink-0 bg-white rounded-full pl-[6px] pr-[18px] lg:pr-[24px] py-[6px]"
      >
        <span class="flex items-center justify-center rounded-full size-[32px] lg:size-[40px] overflow-hidden" style="background: var(--color-brand-orange)">
          <img src="{{ asset('elora-4/assets/images/' . $cat['image']) }}" alt="{{ $cat['name'] }}" class="h-[26px] lg:h-[32px] w-auto" />
        </span>
        <span class="font-medium text-[13px] lg:text-[16px] tracking-[0.3px] whitespace-nowrap" style="color: var(--color-text-primary)">{{ $cat['name'] }}</span>
      </a>
    @endforeach
  </div>
</section>
