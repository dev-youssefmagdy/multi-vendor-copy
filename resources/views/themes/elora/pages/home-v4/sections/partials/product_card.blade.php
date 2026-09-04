{{-- Expects $p: image, name, weight, desc, rating, price, oldPrice, discount, url, sold (optional badge text) --}}
<a href="{{ $p['url'] ?? '#' }}" class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[5.71px] lg:rounded-[12.25px] h-full lg:h-[408.48px] lg:w-[270.45px] shadow-[0px_13.52px_45.02px_rgba(0,0,0,0.16)] lg:shadow-[0px_29px_96.56px_rgba(0,0,0,0.16)]" style="text-decoration:none">
  <div class="flex flex-col gap-[4.79px] lg:gap-[10.27px] h-[109.51px] lg:h-[234.9px] items-end justify-end px-[3.59px] lg:px-[7.7px] py-[2.99px] lg:py-[6.42px] relative shrink-0 w-full">
    <div class="absolute flex gap-[4.79px] lg:gap-[10.27px] h-[109.51px] lg:h-[234.9px] items-start left-0 p-[3.59px] lg:p-[7.7px] top-0 w-full">
      <div class="absolute flex flex-col gap-[4.79px] lg:gap-[10.27px] h-[109.51px] lg:h-[234.9px] items-end left-0 top-0 w-full">
        <div class="absolute left-1/2 -translate-x-1/2 h-[109.51px] lg:h-[234.9px] rounded-t-[4.79px] lg:rounded-t-[10.27px] top-0 w-full overflow-hidden">
          <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        </div>
        <div class="flex h-[14.69px] lg:h-[31.51px] items-center justify-center p-[3.15px] lg:p-[6.75px] relative rounded-bl-[4.2px] lg:rounded-bl-[9px] rounded-tr-[4.2px] lg:rounded-tr-[9px] shrink-0 w-[37.29px] lg:w-[80.5px]" style="background:var(--color-accent-yellow)">
          <p class="font-normal text-[7.34px] leading-[9px] lg:text-[15.75px] lg:leading-[20px] tracking-[0.26px] lg:tracking-[0.56px] whitespace-nowrap" style="color:var(--color-black)">{{ $p['sold'] ?? '70% Sold' }}</p>
        </div>
      </div>
      <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
        data-fav='{{ $p['favData'] ?? '{}' }}'
        aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn bg-white cursor-pointer shadow-[0px_2.39px_2.39px_rgba(0,0,0,0.15)] lg:shadow-[0px_5.13px_5.13px_rgba(0,0,0,0.15)] flex items-center justify-center p-[4.79px] lg:p-[10.27px] relative rounded-full shrink-0 size-[19.15px] lg:size-[41.08px]">
        <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[11.97px] lg:size-[25.67px]" />
      </button>
    </div>
    <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
      aria-label="{{ __('Add to cart') }}"
      class="flex items-center justify-center px-[7.18px] lg:px-[15.4px] py-[2.39px] lg:py-[5.13px] relative rounded-[9.57px] lg:rounded-[20.54px] shrink-0 h-[26.93px] lg:h-[57.76px] w-[34.11px] lg:w-[73.17px] cursor-pointer bg-[var(--color-bg-main)]">
      <img src="{{ asset('elora-4/assets/icons/add-to-cart-dark.svg') }}" alt="" class="size-[14.36px] lg:size-[30.81px]" />
    </button>
  </div>
  <div class="flex flex-col gap-[3.81px] lg:gap-[8.17px] items-start p-[3.81px] lg:p-[8.17px] relative shrink-0 w-full">

    {{-- Name + weight + subtitle --}}
    <div class="flex flex-col gap-[2.39px] lg:gap-[5.13px] items-start w-full">
      <div class="flex items-center justify-between gap-[1.2px] lg:gap-[2.57px] w-full">
        <p class="font-medium text-[11.42px] leading-[14px] lg:text-[24.51px] lg:leading-[31px] tracking-[0.3px] lg:tracking-[0.64px] truncate min-w-0" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="font-normal text-[9.52px] leading-[15px] lg:text-[20.42px] lg:leading-[32px] tracking-[0.3px] lg:tracking-[0.64px] lg:text-center shrink-0 whitespace-nowrap text-[#0018E8] lg:text-[#F67501]">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[9.52px] leading-[12px] lg:text-[20.42px] lg:leading-[26px] tracking-[0.3px] lg:tracking-[0.64px] w-full truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] ?? 'Premium cotton blend' }}</p>
    </div>

    {{-- Rating + price --}}
    <div class="flex flex-col gap-[2.39px] lg:gap-[5.13px] items-start">
      <div class="flex gap-[4.79px] lg:gap-[10.27px] items-center justify-center">
        <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[5.98px] lg:h-[12.83px] w-[40.99px] lg:w-[87.92px]" />
        <span class="text-[7.62px] leading-[10px] lg:text-[16.34px] lg:leading-[21px] tracking-[0.3px] lg:tracking-[0.64px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[4.79px] lg:gap-[10.27px] items-end">
        <p class="font-medium text-[11.42px] leading-[14px] lg:text-[24.51px] lg:leading-[31px] whitespace-nowrap text-[#0018E8] lg:text-[#F67501]">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[8.38px] leading-[11px] lg:text-[17.97px] lg:leading-[23px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[7.62px] leading-[10px] lg:text-[16.34px] lg:leading-[21px] tracking-[0.3px] lg:tracking-[0.64px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>

    {{-- Delivery estimate --}}
    <div class="flex gap-[3.81px] lg:gap-[8.17px] items-center w-full min-w-0">
      <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[11.42px] lg:size-[24.51px] shrink-0" />
      <p class="font-medium text-[7.62px] leading-[10px] lg:text-[16.34px] lg:leading-[21px] truncate" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>

  </div>
</a>
