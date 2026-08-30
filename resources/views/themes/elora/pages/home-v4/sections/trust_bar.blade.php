@php
  $trustItems = [
    ['icon' => 'icon-package.svg', 'title' => 'Money-Back Guarantee'],
    ['icon' => 'truck-delivery.svg', 'title' => 'Fast, Free Shipping'],
    ['icon' => 'icon-bell-outline.svg', 'title' => '24/7 Customer Support'],
    ['icon' => 'cart.svg', 'title' => '100% Secure Checkout'],
  ];
@endphp
<section
  class="overflow-x-auto no-scrollbar flex items-center justify-center px-[16px] py-[12px] lg:py-[8px]"
  style="background: var(--color-brand-orange)"
>
  <div class="flex items-center gap-[24px] lg:gap-[48px] w-max">
    @foreach ($trustItems as $item)
      <div class="flex items-center gap-[12px] lg:gap-[19px] shrink-0">
        <img
          src="{{ asset('elora-4/assets/icons/' . $item['icon']) }}"
          alt=""
          class="size-[24px] lg:size-[35px] invert"
        />
        <p class="font-normal text-[14px] lg:text-[18px] tracking-[0.4px] lg:tracking-[0.7px] text-white whitespace-nowrap">
          {{ $item['title'] }}
        </p>
      </div>
    @endforeach
  </div>
</section>
