{{-- Expects $p: name, weight, progress, ordered, rating, price, oldPrice, discount, image, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex h-auto bg-[var(--color-bg-main)] rounded-[6px] lg:rounded-[10.1143px] shadow-sm overflow-hidden no-underline">
  <div class="relative w-[41.77%] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
    <span
      class="absolute top-0 right-0 flex items-center justify-center whitespace-nowrap text-[7.71px] lg:text-[13.0037px] leading-[10px] lg:leading-[16px] tracking-[0.2755px] lg:tracking-[0.464px] font-normal p-[3.31px] lg:p-[5.573px] rounded-tr-[4.41px] lg:rounded-tr-[7.43px] rounded-bl-[4.41px] lg:rounded-bl-[7.43px]"
      style="background:var(--color-accent-yellow); color:var(--color-black)"
      >70% Sold</span
    >
    <button type="button" aria-label="Add to favorites" class="absolute top-[3.77px] lg:top-[6.358px] left-[3.77px] lg:left-[6.358px] flex items-center justify-center size-[20.11px] lg:size-[33.91px] bg-white rounded-full p-[5.03px] lg:p-[8.477px] shadow-[0px_2.51429px_2.51429px_rgba(0,0,0,0.15)] lg:shadow-[0px_4.238px_4.238px_rgba(0,0,0,0.15)]">
      <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[12.57px] lg:size-[21.19px]" alt="" />
    </button>
    <div class="absolute bottom-[3.77px] lg:bottom-[5.298px] right-[3.77px] lg:right-[6.358px] flex items-center justify-center w-[35.83px] h-[28.29px] lg:w-[60.4px] lg:h-[47.68px] bg-[var(--color-bg-main)] rounded-[10.057px] lg:rounded-[16.954px] px-[7.543px] lg:px-[12.715px] py-[2.514px] lg:py-[4.238px] shadow">
      <img src="{{ asset('elora-2/assets/icons/cart-add.svg') }}" class="size-[15.09px] lg:size-[25.43px]" alt="" />
    </div>
  </div>
  <div class="flex-1 px-2 py-1 lg:px-[13.486px] lg:py-[6.743px] flex flex-col gap-2 lg:gap-[13.49px] min-w-0">
    <div class="flex flex-col gap-[2.51px] lg:gap-[13.49px]">
      <div class="flex items-center justify-between gap-[1.26px] lg:gap-[2.12px]">
        <p class="min-w-0 flex-1 line-clamp-1 font-medium text-[14px] lg:text-[23.6px] leading-[18px] lg:leading-[30px] tracking-[0.314px] lg:tracking-[0.53px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="shrink-0 text-[15px] lg:text-[25.29px] leading-[16px] lg:leading-[26px] tracking-[0.314px] lg:tracking-[0.53px]" style="color:var(--color-accent-green)">{{ $p['weight'] }}</p>
      </div>
      <p class="text-[12px] lg:text-[20.23px] leading-[15px] lg:leading-[25px] tracking-[0.314px] lg:tracking-[0.53px] truncate" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
    </div>
    <div class="flex flex-col gap-[1px] lg:gap-[13.49px]">
      <div class="h-[5px] lg:h-[8.43px] w-full rounded-[24px] lg:rounded-[40.457px]" style="background:var(--color-progress-track)">
        <div class="h-full rounded-[24px] lg:rounded-[40.457px]" style="background:var(--color-accent-green); width:{{ $p['progress'] }}%"></div>
      </div>
      <p class="text-[10px] lg:text-[16.86px] leading-[13px] lg:leading-[21px] tracking-[0.314px] lg:tracking-[0.53px] text-center" style="color:var(--color-accent-green)">{{ $p['ordered'] }}</p>
    </div>
    <div class="flex flex-col gap-[2.51px] lg:gap-[13.49px]">
      <div class="flex items-center justify-start gap-[5.03px] lg:gap-[8.48px] w-full">
        <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[6.28px] w-[43.05px] lg:h-[10.59px] lg:w-[72.57px]" />
        <span class="text-[12px] lg:text-[20.23px] leading-[15px] lg:leading-[25px] tracking-[0.314px] lg:tracking-[0.53px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex items-end gap-[5.03px] lg:gap-[8.48px]">
        <p class="font-medium text-[16px] lg:text-[26.97px] leading-[20px] lg:leading-[34px] text-center" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[8.8px] lg:text-[14.83px] leading-[11px] lg:leading-[19px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="text-[12px] lg:text-[20.23px] leading-[15px] lg:leading-[25px] tracking-[0.314px] lg:tracking-[0.53px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex gap-1 lg:gap-[4.24px] items-center w-full">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[12px] lg:size-[20.23px]" />
      <p class="font-medium text-[8px] lg:text-[13.49px] leading-[10px] lg:leading-[17px] text-right whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>
  </div>
</a>
