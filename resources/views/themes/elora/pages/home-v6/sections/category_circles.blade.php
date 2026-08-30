@php
  $categories6 = [
    ['icon' => 'cat-bag.png', 'label' => 'Women bags', 'outline' => true, 'w' => '39px', 'wLg' => '73px'],
    ['icon' => 'cat-lamp.png', 'label' => 'Accessories', 'outline' => true, 'w' => '28px', 'wLg' => '52px'],
    ['icon' => 'cat-controller.png', 'label' => 'Gaming', 'outline' => false, 'w' => '47px', 'wLg' => '88px', 'rotate' => true],
    ['icon' => 'cat-laptop.png', 'label' => 'Laptops', 'outline' => true, 'w' => '53px', 'wLg' => '98px'],
    ['icon' => 'cat-bag.png', 'label' => 'Bags', 'outline' => true, 'w' => '39px', 'wLg' => '73px'],
    ['icon' => 'cat-lamp.png', 'label' => 'Home Decor', 'outline' => true, 'w' => '28px', 'wLg' => '52px'],
    ['icon' => 'cat-laptop.png', 'label' => 'Electronics', 'outline' => true, 'w' => '53px', 'wLg' => '98px'],
    ['icon' => 'cat-bag.png', 'label' => 'Sports', 'outline' => false, 'w' => '39px', 'wLg' => '73px'],
  ];
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]" style="background: var(--color-page-bg)">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">Categories</h2>
    <a href="#" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-accent-green)">see all</a>
  </div>
  <div class="flex items-stretch gap-[12px] lg:gap-[22px] overflow-x-auto no-scrollbar pb-[4px]">
    @foreach ($categories6 as $cat)
      <div
        class="flex flex-col items-center justify-between px-[12px] py-[8px] lg:px-[22px] lg:py-[15px] rounded-[12px] lg:rounded-[22px] shrink-0 {{ $cat['outline'] ? 'border-2' : '' }}"
        style="{{ $cat['outline'] ? 'border-color: var(--color-accent-green)' : 'background: var(--color-accent-green)' }}"
      >
        <img src="{{ asset('elora-2/assets/icons/' . $cat['icon']) }}" alt="{{ $cat['label'] }}" class="h-auto {{ !empty($cat['rotate']) ? '-rotate-[15deg]' : '' }}" style="width: {{ $cat['w'] }}" />
        <p
          class="font-semibold text-[12px] lg:text-[22px] tracking-[0.5px] lg:tracking-[0.9px] whitespace-nowrap {{ $cat['outline'] ? '' : 'text-white' }}"
          style="{{ $cat['outline'] ? 'color: var(--color-accent-green)' : '' }}"
        >{{ $cat['label'] }}</p>
      </div>
    @endforeach
  </div>
</section>
