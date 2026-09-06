{{-- Expects $p: image, badge, badgeBg, badgeColor, name, weight, weightColor, desc, rating, price, priceColor, oldPrice, discount, stockColor, url --}}
<a
  href="{{ $p['url'] ?? '#' }}"
  class="relative flex flex-col items-start w-[177.51px] h-[269px] lg:w-[304.54px] lg:h-[461.49px] bg-[var(--color-bg-main)] overflow-hidden no-underline transition-[width,height,border-radius,box-shadow] duration-200 ease-in-out {{ $p['cardClass'] ?? 'rounded-[6px] shadow-[var(--shadow-card-lg)]' }}"
>
  <div class="relative w-full h-[154.69px] lg:h-[265.39px] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <div class="absolute inset-0 flex flex-col justify-between items-end p-[5.07185px] lg:p-[8.70123px] gap-[6.76px] lg:gap-[11.6px]">
      <div class="flex w-full justify-between items-start">
            <button
          type="button"
          aria-label="Add to favorites"
          class="flex items-center justify-center w-[27.05px] h-[27.05px] lg:w-[46.41px] lg:h-[46.41px] p-[6.76px] lg:p-[11.6px] bg-white rounded-full shadow-[0px_3.38123px_3.38123px_rgba(0,0,0,0.15)] lg:shadow-[0px_5.80082px_5.80082px_rgba(0,0,0,0.15)] transition-transform duration-150 ease active:scale-95"
        >
          <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" alt="" class="size-[16.91px] lg:size-[29px]" />
        </button> 
      <span
          class="flex items-center justify-center h-[21.89px] px-[4.45px] lg:h-[37.25px] lg:px-[7.63px] text-[10.3739px] lg:text-[17.7974px] font-normal leading-[13px] lg:leading-[22px] tracking-[0.370497px] lg:tracking-[0.635622px] text-white rounded-tr-[5.928px] rounded-bl-[5.928px] lg:rounded-tr-[10.17px] lg:rounded-bl-[10.17px]"
          style="background:{{ $p['badgeBg'] ?? 'var(--color-primary)' }}; color:{{ $p['badgeColor'] ?? '#fff' }}"
          >{{ $p['badge'] ?? '70% Sold' }}</span
        >
  
      </div>
      <button
        type="button"
        aria-label="Add to cart"
        class="flex items-center justify-center w-[48.18px] h-[38.04px] px-[10.14px] py-[3.38px] lg:w-[82.66px] lg:h-[65.26px] lg:px-[17.4px] lg:py-[5.8px] rounded-[13.5249px] lg:rounded-[23.2033px] transition-transform duration-150 ease active:scale-95"
        style="background: var(--color-bg-main)"
      >
        <img src="{{ asset('elora-2/assets/icons/cart-add.svg') }}" alt="Add to cart" class="size-[20.29px] lg:size-[34.8px]" />
      </button>
    </div>
  </div>
  <div class="flex-1 flex flex-col gap-[5.38px] lg:gap-[9.23px] items-start p-[5.38px] lg:p-[9.23px] w-full min-h-0">
    <div class="flex flex-col gap-[3.38px] lg:gap-[5.8px] items-start w-full">
      <div class="flex items-center justify-between gap-[1.69px] lg:gap-[2.9px] w-full whitespace-nowrap">
        <p class="font-medium text-[16.1377px] lg:text-[27.6857px] leading-[20px] lg:leading-[35px] tracking-[0.422654px] lg:tracking-[0.725102px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="font-normal text-[13.4481px] lg:text-[23.0714px] leading-[21px] lg:leading-[36px] tracking-[0.422654px] lg:tracking-[0.725102px] text-center shrink-0" style="color:{{ $p['weightColor'] ?? 'var(--color-primary)' }}">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[13.4481px] lg:text-[23.0714px] leading-[17px] lg:leading-[29px] tracking-[0.422654px] lg:tracking-[0.725102px] w-full truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] ?? 'Premium cotton blend' }}</p>
    </div>
    <div class="flex flex-col gap-[3.38px] lg:gap-[5.8px] items-start">
      <div class="flex gap-[6.76px] lg:gap-[11.6px] items-center justify-center">
        <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[8.45px] w-[57.9px] lg:h-[14.5px] lg:w-[99.33px]" />
        <p class="font-normal text-[10.7585px] lg:text-[18.4571px] leading-[14px] lg:leading-[23px] tracking-[0.422654px] lg:tracking-[0.725102px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</p>
      </div>
      <div class="flex gap-[6.76px] lg:gap-[11.6px] items-end">
        <p class="font-medium text-[16.1377px] lg:text-[27.6857px] leading-[20px] lg:leading-[35px] whitespace-nowrap" style="color:{{ $p['priceColor'] ?? 'var(--color-text-primary)' }}">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[11.8343px] lg:text-[20.3029px] leading-[15px] lg:leading-[26px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[10.7585px] lg:text-[18.4571px] leading-[14px] lg:leading-[23px] tracking-[0.422654px] lg:tracking-[0.725102px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="mt-auto flex gap-[5.38px] lg:gap-[9.23px] items-center w-full">
      <img src="{{ asset('elora-2/assets/icons/cart-x-red.svg') }}" alt="" class="size-[16.14px] lg:size-[27.69px]" />
      <p class="font-medium text-[10.7585px] lg:text-[18.4571px] leading-[14px] lg:leading-[23px] whitespace-nowrap" style="color:{{ $p['stockColor'] ?? 'var(--color-error)' }}">Only 5 left</p>
    </div>
  </div>
</a>
