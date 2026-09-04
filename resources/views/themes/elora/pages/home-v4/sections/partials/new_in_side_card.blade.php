{{-- New In composite slide's right-side mini card. Expects $p: image, name, weight, price, oldPrice, discount, rating, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="bg-[var(--color-bg-main)] flex items-stretch lg:gap-[6.44px] rounded-[6px] lg:rounded-[12.87px] h-full lg:h-[192.98px] lg:w-[611.81px] shadow-[var(--shadow-card-lg)] overflow-hidden" style="text-decoration:none">
  <div class="relative shrink-0 w-[36%] lg:w-[265.32px]">
    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 lg:inset-[6.74px_8.09px] h-full w-full lg:h-auto lg:w-auto object-cover" />
    <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
      data-fav='{{ $p['favData'] ?? '{}' }}'
      aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn absolute top-[5px] left-[5px] lg:top-[8px] lg:left-[8px] bg-white cursor-pointer shadow lg:shadow-[0px_5.39px_5.39px_rgba(0,0,0,0.15)] flex items-center justify-center p-[4px] lg:p-[10.79px] rounded-full size-[18px] lg:size-[43.15px]">
      <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[10px] lg:size-[26.97px]" />
    </button>
    <span class="absolute top-0 right-0 flex items-center justify-center text-[8px] lg:text-[16.55px] lg:leading-[21px] font-normal px-[5px] py-[3px] lg:px-[7.09px] lg:py-[7.09px] rounded-bl-[5px] lg:rounded-bl-[9.46px] lg:rounded-tr-[9.46px] tracking-normal lg:tracking-[0.59px] lg:w-[84.18px] whitespace-nowrap" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
    <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
      aria-label="{{ __('Add to cart') }}"
      class="absolute bottom-[5px] right-[5px] lg:bottom-[8px] lg:right-[8px] flex items-center justify-center rounded-[8px] lg:rounded-[21.57px] h-[20px] lg:h-[60.67px] lg:w-[76.85px] px-[5px] lg:px-[16.18px] lg:py-[5.39px] cursor-pointer bg-[var(--color-text-primary)] lg:bg-[var(--color-bg-main)]">
      <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" alt="" class="lg:hidden size-[11px] invert" />
      <img src="{{ asset('elora-4/assets/icons/add-to-cart.svg') }}" alt="" class="hidden lg:block lg:size-[32.36px]" />
    </button>
  </div>
  <div class="flex-1 flex flex-col gap-[2px] lg:gap-[8.58px] p-[6px] lg:p-[8.58px] min-w-0 justify-center overflow-hidden">

    {{-- Name + weight + subtitle --}}
    <div class="flex flex-col gap-[2px] lg:gap-[5.39px] w-full">
      <div class="flex items-center justify-between gap-[5px] lg:gap-[2.7px] w-full">
        <p class="font-medium text-[12px] lg:text-[25.74px] lg:leading-[32px] lg:tracking-[0.67px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="font-normal text-[10px] lg:text-[21.45px] lg:leading-[34px] lg:tracking-[0.67px] lg:text-center shrink-0" style="color:#F87601">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[10px] lg:text-[21.45px] lg:leading-[27px] lg:tracking-[0.67px] truncate" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
    </div>

    {{-- Rating + price --}}
    <div class="flex flex-col gap-[2px] lg:gap-[5.39px]">
      <div class="flex gap-[4px] lg:gap-[10.79px] items-center">
        <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[6px] lg:h-[13.48px] w-[43px] lg:w-[92.35px]" />
        <span class="text-[8px] lg:text-[17.16px] lg:leading-[22px] tracking-[0.3px] lg:tracking-[0.67px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[5px] lg:gap-[10.79px] items-end">
        <p class="font-medium text-[13px] lg:text-[25.74px] lg:leading-[32px] whitespace-nowrap" style="color:#F67501">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[8px] lg:text-[18.88px] lg:leading-[24px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[8px] lg:text-[17.16px] lg:leading-[22px] tracking-[0.3px] lg:tracking-[0.67px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>

    {{-- Delivery estimate --}}
    <div class="flex gap-[4px] lg:gap-[8.58px] items-center w-full min-w-0">
      <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[12px] lg:size-[25.74px] shrink-0" />
      <p class="font-medium text-[8px] lg:text-[17.16px] lg:leading-[22px] truncate" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>

  </div>
</a>
