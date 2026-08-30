@php
  $__trustItems = [
    ['icon' => 'feature-truck.svg', 'label' => 'Free Shipping'],
    ['icon' => 'feature-headphones.svg', 'label' => 'Customer Support 24/7'],
    ['icon' => 'feature-package.svg', 'label' => 'Money-Back Guarantee'],
    ['icon' => 'feature-bag.svg', 'label' => '100% Secure Payment'],
  ];
@endphp
<section class="overflow-x-auto no-scrollbar" style="background: var(--color-brand-pink)">
  <div class="flex items-center gap-[24px] lg:gap-0 px-[16px] lg:px-[56px] py-[12px] lg:py-[13px] w-max lg:w-full lg:justify-between">
    @foreach ($__trustItems as $__i => $__item)
      <div
        class="flex items-center gap-[12px] lg:gap-[12px] shrink-0
          @if ($__i === 0) lg:pr-[26px] lg:border-r
          @elseif ($__i === count($__trustItems) - 1) lg:pl-[26px]
          @else lg:px-[26px] lg:border-r
          @endif"
        @if ($__i !== count($__trustItems) - 1) style="border-color: rgba(255, 255, 255, 0.5)" @endif
      >
        <img src="{{ asset('elora-3/assets/icons/' . $__item['icon']) }}" alt="" class="size-[24px] lg:size-[36px]" />
        <span class="text-[13px] lg:text-[18px] tracking-[0.5px] lg:tracking-[0.75px] whitespace-nowrap font-normal text-white">{{ $__item['label'] }}</span>
      </div>
    @endforeach
  </div>
</section>
