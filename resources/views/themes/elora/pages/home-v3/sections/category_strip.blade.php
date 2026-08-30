{{-- No direct counterpart in the elora-3 mockup; built consistent with its pink/dot-pattern visual language --}}
@php
  $__strip = [
    "Men's Clothing", "Women's Clothing", "Kid's Fashion", 'Electronics', 'Gaming', 'Cameras', 'Home Decor', 'Bags',
  ];
@endphp
<section class="pattern-flash-sale px-[16px] lg:px-[56px] py-[16px] lg:py-[20px] overflow-x-auto no-scrollbar">
  <div class="flex items-center gap-[10px] lg:gap-[16px] w-max lg:w-full lg:justify-between">
    @foreach ($__strip as $__label)
      <a
        href="#"
        class="shrink-0 rounded-full bg-white/95 hover:bg-white transition-colors px-[16px] lg:px-[24px] py-[8px] lg:py-[12px] font-medium text-[13px] lg:text-[16px] tracking-[0.5px] whitespace-nowrap"
        style="color: var(--color-brand-pink)"
      >{{ $__label }}</a>
    @endforeach
  </div>
</section>
