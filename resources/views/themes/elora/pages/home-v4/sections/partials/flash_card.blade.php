{{-- Expects $p: image, name, weight, price, oldPrice, discount, rating, url --}}
<div class="h-[148px]">
  <a href="{{ $p['url'] ?? '#' }}" class="flex gap-[5px] h-[148px] items-start rounded-[10px] shadow-[var(--shadow-card-lg)]" style="background:var(--color-bg-main); text-decoration:none">
    <div class="flex flex-col gap-[8px] h-full items-end justify-end px-[6px] py-[5px] relative shrink-0 w-[204px]">
      <div class="absolute flex gap-[8px] h-full items-start left-0 p-[6px] top-0 w-full">
        <div class="absolute flex flex-col gap-[8px] h-full items-end left-0 top-0 w-full">
          <div class="absolute left-1/2 -translate-x-1/2 h-full rounded-t-[8px] top-0 w-full overflow-hidden">
            <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
          </div>
          <div class="flex h-[25px] items-center justify-center p-[5px] relative rounded-bl-[7px] rounded-tr-[7px] shrink-0" style="background:var(--color-accent-yellow)">
            <p class="font-normal text-[13px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black)">70% Sold</p>
          </div>
        </div>
        <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
          data-fav='{{ $p['favData'] ?? '{}' }}'
          aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn bg-white cursor-pointer shadow flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[33px]">
          <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[21px]" />
        </button>
      </div>
      <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
        aria-label="{{ __('Add to cart') }}"
        class="flex items-center justify-center px-[12px] py-[4px] relative rounded-[17px] shrink-0 h-[47px] w-[59px] cursor-pointer" style="background:var(--color-text-primary)">
        <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" alt="" class="size-[25px] invert" />
      </button>
    </div>
    <div class="flex flex-1 flex-col gap-[7px] h-full items-start p-[7px] relative min-w-0">
      <div class="flex flex-col gap-[4px] items-start w-full">
        <div class="flex items-center justify-between w-full whitespace-nowrap">
          <p class="font-medium text-[20px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
          <p class="font-normal text-[16px]" style="color:var(--color-brand-orange-bright)">{{ $p['weight'] ?? '250g' }}</p>
        </div>
        <p class="font-normal text-[16px] w-full" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
      </div>
      <div class="flex flex-col gap-[4px] items-start">
        <div class="flex gap-[8px] items-center justify-center">
          <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[10px] w-[71px]" />
          <span class="text-[13px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
        </div>
        <div class="flex gap-[8px] items-end">
          <p class="font-medium text-[20px] whitespace-nowrap" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
          <p class="font-light text-[15px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
          <p class="font-normal text-[13px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        </div>
      </div>
      <div class="flex flex-col gap-[4px] items-start w-full">
        <div class="flex gap-[7px] items-center w-full">
          <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[20px]" />
          <p class="font-medium text-[13px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
        </div>
        <div class="flex gap-[8px] items-center">
          <img src="{{ asset('elora-4/assets/icons/cart-x.svg') }}" alt="" class="size-[17px]" />
          <p class="font-medium text-[12px] whitespace-nowrap" style="color:var(--color-success)">Only 5 left</p>
        </div>
      </div>
    </div>
  </a>
</div>
