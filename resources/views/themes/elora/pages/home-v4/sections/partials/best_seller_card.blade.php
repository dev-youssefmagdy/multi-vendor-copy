{{-- Expects $p: image, name, weight, price, oldPrice, discount, rating, url --}}
<div class="swiper-slide h-auto !w-[192.65px]">
  <a href="{{ $p['url'] ?? '#' }}" class="flex flex-col items-start rounded-[8.73px] shadow-[var(--shadow-card-lg)]" style="background:var(--color-bg-main); text-decoration:none">
    <div class="flex flex-col gap-[7.31px] h-[167.33px] items-end justify-end px-[5.49px] py-[4.57px] relative shrink-0 w-full">
      <div class="absolute flex gap-[7.31px] h-[167.33px] items-start left-0 p-[5.49px] top-0 w-full">
        <div class="absolute flex flex-col gap-[7.31px] h-[167.33px] items-end left-0 top-0 w-full">
          <div class="absolute left-1/2 -translate-x-1/2 h-[167.33px] rounded-t-[7.31px] top-0 w-full overflow-hidden">
            <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
          </div>
          <div class="flex h-[22.44px] items-center justify-center p-[4.81px] relative rounded-bl-[6.41px] rounded-tr-[6.41px] shrink-0" style="background:var(--color-accent-yellow)">
            <p class="font-normal text-[11.22px] leading-[14px] tracking-[0.4px] whitespace-nowrap" style="color:var(--color-black)">70% Sold</p>
          </div>
        </div>
        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
          data-fav='{{ $p['favData'] ?? '{}' }}'
          aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn bg-white cursor-pointer shadow-[0px_3.66px_3.66px_rgba(0,0,0,0.15)] flex items-center justify-center p-[7.31px] relative rounded-full shrink-0 size-[29.26px]">
          <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[18.29px]" />
        </button>
      </div>
      <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
        aria-label="{{ __('Add to cart') }}"
        class="flex items-center justify-center px-[10.97px] py-[3.66px] relative rounded-[14.63px] shrink-0 h-[41.15px] w-[52.12px] cursor-pointer" style="background:var(--color-bg-main)">
        <img src="{{ asset('elora-4/assets/icons/add-to-cart-dark.svg') }}" alt="" class="size-[21.94px]" />
      </button>
    </div>
    <div class="flex flex-col gap-[5.82px] items-start p-[5.82px] relative shrink-0 w-full">
      <div class="flex flex-col gap-[3.66px] items-start w-full">
        <div class="flex items-center justify-between gap-[1.83px] w-full">
          <p class="font-medium text-[17.46px] leading-[22px] tracking-[0.46px] truncate min-w-0" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
          <p class="font-normal text-[14.55px] leading-[23px] tracking-[0.46px] text-center shrink-0 whitespace-nowrap" style="color:var(--color-badge-pink)">{{ $p['weight'] }}</p>
        </div>
        <p class="font-normal text-[14.55px] leading-[18px] tracking-[0.46px] w-full truncate" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
      </div>
      <div class="flex flex-col gap-[1px] items-start w-full">
        <div class="h-[5.02px] w-full rounded-full" style="background:var(--color-stroke)">
          <div class="h-full rounded-full" style="background:var(--color-progress-red); width:{{ $p['progress'] ?? 83 }}%"></div>
        </div>
        <p class="text-[10.03px] leading-[13px] tracking-[0.32px] w-full truncate" style="color:var(--color-progress-red)">{{ $p['ordered'] ?? '5 ordered last 30 min' }}</p>
      </div>
      <div class="flex flex-col gap-[3.66px] items-start">
        <div class="flex gap-[7.31px] items-center justify-center">
          <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[9.14px] w-[62.63px]" />
          <span class="text-[11.64px] leading-[15px] tracking-[0.46px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
        </div>
        <div class="flex gap-[7.31px] items-end">
          <p class="font-medium text-[17.46px] leading-[22px] whitespace-nowrap" style="color:var(--color-badge-pink)">{{ $p['price'] }}</p>
          @if (!empty($p['oldPrice']))
          <p class="font-light text-[12.8px] leading-[16px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
          @endif
          @if (!empty($p['discount']))
          <p class="font-normal text-[11.64px] leading-[15px] tracking-[0.46px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
          @endif
        </div>
      </div>
      <div class="flex gap-[5.82px] items-center w-full min-w-0">
        <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[17.46px] shrink-0" />
        <p class="font-medium text-[11.64px] leading-[15px] truncate" style="color:var(--color-success)">Delivered by 24 March</p>
      </div>
    </div>
  </a>
</div>
