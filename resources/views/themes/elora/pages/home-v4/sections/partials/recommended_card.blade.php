{{-- Expects $p: image, name, weight, desc, rating, price, oldPrice, discount, url, sold (optional badge text) --}}
<a href="{{ $p['url'] ?? '#' }}" class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] lg:rounded-[8px] h-full shadow-[var(--shadow-card-lg)]" style="text-decoration:none">
  <div class="flex flex-col gap-[5px] lg:gap-[8px] h-[115px] lg:h-[183px] items-end justify-end px-[4px] lg:px-[6px] py-[3px] lg:py-[5px] relative shrink-0 w-full">
    <div class="absolute flex gap-[5px] lg:gap-[8px] h-[115px] lg:h-[183px] items-start left-0 p-[4px] lg:p-[6px] top-0 w-full">
      <div class="absolute flex flex-col gap-[5px] lg:gap-[8px] h-[115px] lg:h-[183px] items-end left-0 top-0 w-full">
        <div class="absolute left-1/2 -translate-x-1/2 h-[115px] lg:h-[183px] rounded-t-[5px] lg:rounded-t-[8px] top-0 w-full overflow-hidden">
          <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        </div>
        <div class="flex h-[15px] lg:h-[28px] items-center justify-center p-[3px] lg:p-[6px] relative rounded-bl-[4px] lg:rounded-bl-[8px] rounded-tr-[4px] lg:rounded-tr-[8px] shrink-0 lg:w-[71px]" style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}">
          <p class="font-normal text-[8px] lg:text-[14px] lg:leading-[18px] tracking-[0.3px] lg:tracking-[0.5px] whitespace-nowrap" style="color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}">{{ $p['badgeText'] ?? ($p['sold'] ?? '70% Sold') }}</p>
        </div>
      </div>
      <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
        data-fav='{{ $p['favData'] ?? '{}' }}'
        aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn bg-white cursor-pointer shadow lg:shadow-[0px_4px_4px_rgba(0,0,0,0.15)] flex items-center justify-center p-[5px] lg:p-[8px] relative rounded-full shrink-0 size-[20px] lg:size-[32px]">
        <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[13px] lg:size-[20px]" />
      </button>
    </div>
    <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
      aria-label="{{ __('Add to cart') }}"
      class="flex items-center justify-center px-[8px] lg:px-[12px] py-[3px] lg:py-[4px] relative rounded-[10px] lg:rounded-[16px] shrink-0 h-[28px] lg:h-[45px] w-[36px] lg:w-[57px] cursor-pointer bg-[var(--color-text-primary)] lg:bg-[var(--color-bg-main)]">
      <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" alt="" class="lg:hidden size-[15px] invert" />
      <img src="{{ asset('elora-4/assets/icons/add-to-cart-dark.svg') }}" alt="" class="hidden lg:block lg:size-[24px]" />
    </button>
  </div>
  <div class="flex flex-col gap-[4px] lg:gap-[8px] items-start p-[4px] lg:p-[8px] relative shrink-0 w-full">

    {{-- Name + weight + subtitle --}}
    <div class="flex flex-col gap-[2px] lg:gap-[4px] items-start w-full">
      <div class="flex items-center justify-between gap-[5px] lg:gap-[2px] w-full">
        <p class="font-medium text-[12px] lg:text-[16px] lg:leading-[20px] tracking-normal lg:tracking-[0.5px] truncate min-w-0" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="font-normal text-[10px] lg:text-[14px] lg:leading-[25px] lg:tracking-[0.5px] lg:text-center shrink-0 whitespace-nowrap" style="color:var(--color-primary)">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[10px] lg:text-[14px] lg:leading-[18px] lg:tracking-[0.5px] w-full truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] ?? 'Premium cotton blend' }}</p>
    </div>

    {{-- Rating + price --}}
    <div class="flex flex-col gap-[2px] lg:gap-[4px] items-start">
      <div class="flex gap-[5px] lg:gap-[8px] items-center justify-center">
        <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[6px] lg:h-[10px] w-[43px] lg:w-[68.49px]" />
        <span class="text-[8px] lg:text-[12px] lg:leading-[15px] tracking-[0.3px] lg:tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[5px] lg:gap-[8px] items-end">
        <p class="font-medium text-[12px] lg:text-[18px] lg:leading-[23px] whitespace-nowrap" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[6px] lg:text-[14px] lg:leading-[18px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[8px] lg:text-[12px] lg:leading-[15px] tracking-[0.3px] lg:tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>

    {{-- Delivery estimate --}}
    <div class="flex gap-[4px] items-center w-full min-w-0">
      <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[12px] lg:size-[18px] shrink-0" />
      <p class="font-medium text-[8px] lg:text-[12px] lg:leading-[15px] truncate" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>

  </div>
</a>
