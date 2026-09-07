{{-- New In composite slide's right-side mini card. Expects $p: image, name, weight, price, oldPrice, discount, rating, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="bg-[var(--color-bg-main)] flex items-stretch gap-[3px] lg:gap-[6.44px] rounded-[6px] lg:rounded-[12.87px] h-full lg:h-[192.98px] w-[285.22px] lg:w-[611.81px] shadow-[var(--shadow-card-lg)] overflow-hidden" style="text-decoration:none">
  <div class="relative shrink-0 w-[123.69px] lg:w-[265.32px]">
    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 lg:inset-[6.74px_8.09px] h-full w-full lg:h-auto lg:w-auto object-cover" />
    <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
      data-fav='{{ $p['favData'] ?? '{}' }}'
      aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn absolute top-[5px] left-[5px] lg:top-[8px] lg:left-[8px] bg-white cursor-pointer shadow-[0px_2.51px_2.51px_rgba(0,0,0,0.15)] lg:shadow-[0px_5.39px_5.39px_rgba(0,0,0,0.15)] flex items-center justify-center p-[5.03px] lg:p-[10.79px] rounded-full size-[20.11px] lg:size-[43.15px]">
      <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[12.57px] lg:size-[26.97px]" />
    </button>
    <span class="absolute top-0 right-0 flex items-center justify-center text-[7.71px] leading-[10px] lg:text-[16.55px] lg:leading-[21px] font-normal px-[3.31px] lg:px-[7.09px] py-[3.31px] lg:py-[7.09px] rounded-bl-[4.41px] lg:rounded-bl-[9.46px] rounded-tr-[4.41px] lg:rounded-tr-[9.46px] tracking-[0.28px] lg:tracking-[0.59px] w-[39.61px] lg:w-[84.18px] whitespace-nowrap" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
    <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
      aria-label="{{ __('Add to cart') }}"
      class="absolute bottom-[5px] right-[5px] lg:bottom-[8px] lg:right-[8px] flex items-center justify-center rounded-[10.06px] lg:rounded-[21.57px] h-[28.29px] lg:h-[60.67px] w-[35.83px] lg:w-[76.85px] px-[7.54px] lg:px-[16.18px] py-[2.51px] lg:py-[5.39px] cursor-pointer bg-[var(--color-bg-main)]">
      <img src="{{ asset('elora-4/assets/icons/add-to-cart-dark.svg') }}" alt="" class="size-[15.09px] lg:size-[32.36px]" />
    </button>
  </div>
  <div class="flex-1 flex flex-col gap-[4px] lg:gap-[8.58px] p-[4px] lg:p-[8.58px] min-w-0 justify-center overflow-hidden">

    {{-- Name + weight + subtitle --}}
    <div class="flex flex-col gap-[2.51px] lg:gap-[5.39px] w-full">
      <div class="flex items-center justify-between gap-[1.26px] lg:gap-[2.7px] w-full">
        <p class="font-medium text-[12px] leading-[15px] lg:text-[25.74px] lg:leading-[32px] tracking-[0.31px] lg:tracking-[0.67px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="font-normal text-[10px] leading-[16px] lg:text-[21.45px] lg:leading-[34px] tracking-[0.31px] lg:tracking-[0.67px] lg:text-center shrink-0 text-[#132092] lg:text-[#F87601]">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[10px] leading-[13px] lg:text-[21.45px] lg:leading-[27px] tracking-[0.31px] lg:tracking-[0.67px] truncate" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
    </div>

    {{-- Rating + price --}}
    <div class="flex flex-col gap-[2.51px] lg:gap-[5.39px]">
      <div class="flex gap-[5.03px] lg:gap-[10.79px] items-center">
        <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[6.28px] lg:h-[13.48px] w-[43.05px] lg:w-[92.35px]" />
        <span class="text-[8px] leading-[10px] lg:text-[17.16px] lg:leading-[22px] tracking-[0.31px] lg:tracking-[0.67px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[5.03px] lg:gap-[10.79px] items-end">
        <p class="font-medium text-[12px] leading-[15px] lg:text-[25.74px] lg:leading-[32px] whitespace-nowrap text-[#0018E8] lg:text-[#F67501]">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[8.8px] leading-[11px] lg:text-[18.88px] lg:leading-[24px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[8px] leading-[10px] lg:text-[17.16px] lg:leading-[22px] tracking-[0.31px] lg:tracking-[0.67px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>

    {{-- Delivery estimate --}}
    <div class="flex gap-[4px] lg:gap-[8.58px] items-center w-full min-w-0">
      <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[12px] lg:size-[25.74px] shrink-0" />
      <p class="font-medium text-[8px] leading-[10px] lg:text-[17.16px] lg:leading-[22px] truncate" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>

  </div>
</a>
