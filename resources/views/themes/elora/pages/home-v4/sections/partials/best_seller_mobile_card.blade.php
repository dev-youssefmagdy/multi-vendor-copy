{{-- Expects $p: image, name, weight, price, oldPrice, discount, rating, url.
     Sizing (width/height) and the scattered tilt are applied by the parent
     .swiper-slide (Swiper's grid module sets slide width/height inline;
     rotation comes from the nth-child rules in elora-v4.css) — this card
     just fills 100% of that slide. --}}
<a
  href="{{ $p['url'] ?? '#' }}"
  class="relative flex flex-col items-start w-full h-full rounded-[7.41px] shadow-[0_0_22.65px_rgba(0,0,0,0.16)]"
  style="background: var(--color-bg-main); text-decoration:none"
>
  <div class="relative w-full h-[142.03px] shrink-0 overflow-hidden rounded-t-[6.21px]">
    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />

    <span
      class="absolute top-0 right-0 flex items-center justify-center px-[4.08px] h-[19.05px] rounded-tr-[5.44px] rounded-bl-[5.44px]"
      style="background: var(--color-accent-yellow)"
    >
      <p class="font-normal text-[9.52px] leading-[12px] tracking-[0.34px] whitespace-nowrap" style="color: var(--color-black)">{{ __('70% Sold') }}</p>
    </span>

    <button
      type="button"
      onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
      data-fav='{{ $p['favData'] ?? '{}' }}'
      aria-label="{{ __('Add to favorites') }}"
      class="elora-v4-heart-btn absolute top-[5px] left-[5px] flex items-center justify-center size-[24.83px] rounded-full bg-white shadow-[0_3.1px_3.1px_rgba(0,0,0,0.15)]"
    >
      <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[15.52px]" />
    </button>

    <button
      type="button"
      wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})"
      onclick="event.stopPropagation()"
      aria-label="{{ __('Add to cart') }}"
      class="absolute bottom-[5px] right-[5px] flex items-center justify-center w-[44.24px] h-[34.92px] rounded-[12.42px]"
      style="background: var(--color-bg-main)"
    >
      <img src="{{ asset('elora-4/assets/icons/add-to-cart-dark.svg') }}" alt="" class="size-[18.63px]" />
    </button>
  </div>

  <div class="flex flex-col gap-[4.94px] items-start p-[4.94px] w-full flex-1 min-h-0 box-border">
    <div class="flex flex-col gap-[3.1px] items-start w-full">
      <div class="flex items-center justify-between gap-[1.83px] w-full">
        <p class="font-medium text-[14.82px] leading-[19px] tracking-[0.39px] truncate min-w-0" style="color: var(--color-text-primary)">{{ $p['name'] }}</p>
        @if (!empty($p['weight']))
          <p class="font-normal text-[12.35px] leading-[19px] tracking-[0.39px] text-center shrink-0 whitespace-nowrap" style="color: var(--color-badge-pink)">{{ $p['weight'] }}</p>
        @endif
      </div>
      <p class="font-normal text-[12.35px] leading-[16px] tracking-[0.39px] w-full truncate" style="color: var(--color-text-subtitle)">{{ __('Premium cotton blend') }}</p>
    </div>

    <div class="flex flex-col gap-[1px] items-start w-full">
      <div class="h-[4.26px] w-full rounded-full" style="background: var(--color-stroke)">
        <div class="h-full rounded-full" style="background: var(--color-progress-red); width: {{ $p['progress'] ?? 83 }}%"></div>
      </div>
      <p class="text-[8.52px] leading-[11px] tracking-[0.27px] w-full truncate" style="color: var(--color-progress-red)">{{ $p['ordered'] ?? __('5 ordered last 30 min') }}</p>
    </div>

    <div class="flex flex-col gap-[3.1px] items-start">
      <div class="flex gap-[6.21px] items-center justify-center">
        <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[7.76px] w-[53.16px]" />
        <span class="text-[9.88px] leading-[12px] tracking-[0.39px] whitespace-nowrap" style="color: var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[6.21px] items-end">
        <p class="font-medium text-[14.82px] leading-[19px] whitespace-nowrap" style="color: var(--color-badge-pink)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[10.87px] leading-[14px] line-through whitespace-nowrap" style="color: var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[9.88px] leading-[12px] tracking-[0.39px] whitespace-nowrap" style="color: var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>

    <div class="flex gap-[4.94px] items-center w-full min-w-0">
      <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[14.82px] shrink-0" />
      <p class="font-medium text-[9.88px] leading-[12px] truncate" style="color: var(--color-success)">{{ __('Delivered by 24 March') }}</p>
    </div>
  </div>
</a>
