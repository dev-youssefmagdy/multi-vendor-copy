@php
  $flashProducts6 = array_fill(0, 18, ['name' => 'Essential Shoes', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off']);
@endphp
<section class="flash-sale-stripes py-[24px] lg:py-[32px] flex flex-col gap-[24px]">
  <div class="flex items-center justify-between px-[16px] lg:px-[56px]">
    <div class="flex items-center gap-[13px]">
      <h2 class="font-semibold text-[24px] lg:text-[40px] text-white whitespace-nowrap">Flash Sale</h2>
      <img src="{{ asset('elora-2/assets/icons/oi-flash.svg') }}" alt="" class="size-[26px] lg:size-[41px]" />
    </div>
    <a href="#" class="font-normal text-[14px] lg:text-[24px] tracking-[0.5px] lg:tracking-[0.8px] text-white whitespace-nowrap">see all</a>
  </div>

  <div class="swiper flash-swiper w-full ps-[16px]! lg:ps-[56px]!">
    <div class="swiper-wrapper" id="flashGrid">
      @foreach ($flashProducts6 as $product)
        <div class="swiper-slide">
          @include('themes.elora.pages.home-v6.sections.partials.flash_card', ['p' => $product])
        </div>
      @endforeach
    </div>
  </div>

  <div class="flex items-center justify-end gap-[20px] w-full px-[16px] lg:px-[56px]">
    <div class="flex items-center gap-[5px]">
      <span class="font-semibold text-[16px] lg:text-[20px] tracking-[0.5px] lg:tracking-[0.8px] whitespace-nowrap" style="color: var(--color-accent-yellow); transform: rotate(-90deg) translateY(20px);">Ends in</span>
      <div class="border-2 border-white flex flex-col h-[52px] w-[54px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[8px] lg:rounded-[12px]" style="background: var(--color-accent-green)">
        <span id="flashTimerH" class="font-semibold text-[24px] lg:text-[40px] text-white tracking-[0.5px] lg:tracking-[0.8px]">03</span>
      </div>
      <div class="border-2 border-white flex flex-col h-[52px] w-[54px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[8px] lg:rounded-[12px]" style="background: var(--color-accent-green)">
        <span id="flashTimerM" class="font-semibold text-[24px] lg:text-[40px] text-white tracking-[0.5px] lg:tracking-[0.8px]">06</span>
      </div>
      <div class="border-2 border-white flex flex-col h-[52px] w-[54px] lg:h-[73px] lg:w-[76px] items-center justify-center rounded-[8px] lg:rounded-[12px]" style="background: var(--color-accent-green)">
        <span id="flashTimerS" class="font-semibold text-[24px] lg:text-[40px] text-white tracking-[0.5px] lg:tracking-[0.8px]">25</span>
      </div>
    </div>
    <button type="button" class="flex-1 bg-white h-[46px] lg:h-[63px] rounded-full flex items-center justify-center px-[16px]">
      <span class="font-medium text-[16px] lg:text-[23px] tracking-[0.5px] lg:tracking-[0.8px]" style="color: var(--color-accent-green)">Shop now</span>
    </button>
  </div>
</section>
