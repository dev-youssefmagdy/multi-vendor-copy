@php
  $__categories = [
    ['name' => 'Women bags', 'image' => 'cat-bag.png', 'clip' => '78% 100%', 'width' => '70%', 'imgH' => 'h-[45px] lg:h-[67px]', 'imgRight' => 'right-[4px] lg:right-[8px]', 'w' => 'w-[108px] lg:w-[162px]'],
    ['name' => 'Gaming', 'image' => 'cat-gaming.png', 'clip' => '74% 100%', 'width' => '68%', 'imgH' => 'h-[48px] lg:h-[78px] -rotate-[15deg]', 'imgRight' => 'right-[2px] lg:right-[4px]', 'w' => 'w-[108px] lg:w-[162px]'],
    ['name' => 'Electronics', 'image' => 'cat-electronics.png', 'clip' => '76% 100%', 'width' => '68%', 'imgH' => 'h-[42px] lg:h-[68px]', 'imgRight' => 'right-[4px] lg:right-[6px]', 'w' => 'w-[128px] lg:w-[196px]'],
    ['name' => 'Accessories', 'image' => 'cat-accessories.png', 'clip' => '76% 100%', 'width' => '68%', 'imgH' => 'h-[40px] lg:h-[62px]', 'imgRight' => 'right-[6px] lg:right-[10px]', 'w' => 'w-[122px] lg:w-[186px]'],
  ];
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[34px] flex flex-col gap-[16px] lg:gap-[34px]">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">Categories</h2>
    <a href="#" class="font-normal text-[14px] lg:text-[24px] tracking-[0.5px]" style="color: var(--color-brand-pink)">see all</a>
  </div>
  <div class="categories-row no-scrollbar flex items-center gap-[10px] lg:gap-[14px]">
    @foreach ($__categories as $__cat)
      <div class="relative shrink-0 h-[62px] lg:h-[93px] {{ $__cat['w'] }} rounded-[18px] lg:rounded-[28px] bg-white overflow-hidden flex items-center p-[5px] lg:p-[7px]">
        <div
          class="absolute inset-y-0 left-0 flex items-center pl-[10px] lg:pl-[16px]"
          style="width: {{ $__cat['width'] }}; background: var(--color-brand-pink); clip-path: polygon(0 0, 100% 0, {{ $__cat['clip'] }}, 0 100%);"
        >
          <p class="font-semibold text-white text-[13px] lg:text-[20.7px] tracking-[0.5px] lg:tracking-[0.86px]">{{ $__cat['name'] }}</p>
        </div>
        <img
          src="{{ asset('elora-3/assets/images/' . $__cat['image']) }}"
          alt="{{ $__cat['name'] }}"
          class="absolute {{ $__cat['imgRight'] }} top-1/2 -translate-y-1/2 {{ $__cat['imgH'] }} w-auto"
        />
      </div>
    @endforeach
  </div>
</section>
