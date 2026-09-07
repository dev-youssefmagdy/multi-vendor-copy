{{-- New In carousel card. Expects $p: image, badge, badgeBg, badgeColor, name, weight, desc, rating, price, oldPrice, discount, url --}}
<a
  href="{{ $p['url'] ?? '#' }}"
  class="relative flex flex-col items-start w-[192.01px] h-[290px] lg:w-[282.43px] lg:h-[426.58px] bg-[var(--color-bg-main)] overflow-hidden no-underline rounded-[8.69876px] lg:rounded-[12.7955px] shadow-[0px_20.5907px_68.555px_rgba(0,0,0,0.16)] lg:shadow-[0px_30.2881px_100.842px_rgba(0,0,0,0.16)] transition-[width,height,border-radius,box-shadow] duration-200 ease-in-out"
>
  <div class="relative w-full h-[166.77px] lg:h-[245.31px] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <div class="absolute inset-0 flex flex-col justify-between items-end p-[5.47px] lg:p-[8.04px] gap-[7.29px] lg:gap-[10.72px]">
      <div class="flex w-full justify-between items-start">
        <button
          type="button"
          aria-label="Add to favorites"
          class="flex items-center justify-center w-[29.16px] h-[29.16px] lg:w-[42.9px] lg:h-[42.9px] p-[7.29px] lg:p-[10.72px] bg-white rounded-full shadow-[0px_3.64519px_3.64519px_rgba(0,0,0,0.15)] lg:shadow-[0px_5.36193px_5.36193px_rgba(0,0,0,0.15)] transition-transform duration-150 ease active:scale-95"
        >
          <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" alt="" class="size-[18.23px] lg:size-[26.81px]" />
        </button>
        <span
          class="flex items-center justify-center h-[22.37px] px-[4.79px] lg:h-[32.9px] lg:px-[7.05px] text-[11.1838px] lg:text-[16.4509px] font-normal leading-[14px] lg:leading-[21px] tracking-[0.399421px] lg:tracking-[0.587531px] rounded-tr-[6.39px] rounded-bl-[6.39px] lg:rounded-tr-[9.4px] lg:rounded-bl-[9.4px]"
          style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}"
          >{{ $p['badge'] ?? '70% Sold' }}</span
        >
      </div>
      <button
        type="button"
        aria-label="Add to cart"
        class="flex items-center justify-center w-[51.94px] h-[41.01px] px-[10.94px] py-[3.65px] lg:w-[76.41px] lg:h-[60.32px] lg:px-[16.09px] lg:py-[5.36px] rounded-[14.5808px] lg:rounded-[21.4477px] transition-transform duration-150 ease active:scale-95"
        style="background: var(--color-bg-main)"
      >
        <img src="{{ asset('elora-2/assets/icons/cart-add.svg') }}" alt="Add to cart" class="size-[25px] lg:size-[36px]" />
      </button>
    </div>
  </div>
  <div class="flex-1 flex flex-col gap-[5.8px] lg:gap-[8.53px] items-start p-[5.8px] lg:p-[8.53px] w-full min-h-0">
    <div class="flex flex-col gap-[3.65px] lg:gap-[5.36px] items-start w-full">
      <div class="flex items-center justify-between gap-[1.82px] lg:gap-[2.68px] w-full">
        <p class="font-medium text-[17.3975px] lg:text-[25.591px] leading-[22px] lg:leading-[32px] tracking-[0.455649px] lg:tracking-[0.670241px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="font-normal text-[14.4979px] lg:text-[21.3259px] leading-[23px] lg:leading-[34px] tracking-[0.455649px] lg:tracking-[0.670241px] text-center shrink-0" style="color:var(--color-accent-green)">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[14.4979px] lg:text-[21.3259px] leading-[18px] lg:leading-[27px] tracking-[0.455649px] lg:tracking-[0.670241px] w-full truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] ?? 'Premium cotton blend' }}</p>
    </div>
    <div class="flex flex-col gap-[3.65px] lg:gap-[5.36px] items-start">
      <div class="flex gap-[7.29px] lg:gap-[10.72px] items-center justify-center">
        <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[9.11px] w-[62.42px] lg:h-[13.4px] lg:w-[91.81px]" />
        <p class="font-normal text-[11.5983px] lg:text-[17.0607px] leading-[15px] lg:leading-[21px] tracking-[0.455649px] lg:tracking-[0.670241px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</p>
      </div>
      <div class="flex gap-[7.29px] lg:gap-[10.72px] items-end">
        <p class="font-medium text-[17.3975px] lg:text-[25.591px] leading-[22px] lg:leading-[32px] whitespace-nowrap" style="color:var(--color-accent-green)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[12.7582px] lg:text-[18.7668px] leading-[16px] lg:leading-[24px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[11.5983px] lg:text-[17.0607px] leading-[15px] lg:leading-[21px] tracking-[0.455649px] lg:tracking-[0.670241px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="mt-auto flex gap-[5.8px] lg:gap-[8.53px] items-center w-full">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[17.4px] lg:size-[25.59px]" />
      <p class="font-medium text-[11.5983px] lg:text-[17.0607px] leading-[15px] lg:leading-[21px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>
  </div>
</a>
