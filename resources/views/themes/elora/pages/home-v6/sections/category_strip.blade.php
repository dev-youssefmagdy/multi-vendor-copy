{{-- invented section, no direct elora-2 counterpart --}}
{{-- elora-2's own palette defines --color-primary/--color-secondary as an orange/red-orange
     pair that's otherwise unused on the "New In" home mockup (only referenced by carousels.js
     badges) -- reusing them here gives this strip an "Orange Category Strip" identity that still
     stays true to elora-2's real design tokens rather than inventing new colors. --}}
@php
  $stripCategories6 = [
    ['label' => "Men's Clothing"],
    ['label' => 'Featured'],
    ['label' => 'Gaming'],
    ['label' => 'Electronics'],
    ['label' => "Kid's Fashion"],
    ['label' => 'Sports'],
  ];
@endphp
<section class="px-[16px] lg:px-[56px] py-[16px] lg:py-[20px]" style="background: var(--color-primary)">
  <div class="flex items-center gap-[10px] lg:gap-[16px] overflow-x-auto no-scrollbar">
    @foreach ($stripCategories6 as $cat)
      <a
        href="#"
        class="shrink-0 whitespace-nowrap rounded-full px-[18px] py-[10px] lg:px-[24px] lg:py-[12px] text-[13px] lg:text-[16px] font-medium tracking-[0.5px] text-white border border-white/40 hover:bg-white/10 transition-colors"
      >{{ $cat['label'] }}</a>
    @endforeach
  </div>
</section>
