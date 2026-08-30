{{-- Expects $p: image, name, weight, price, oldPrice, discount, rating --}}
<div class="swiper-slide h-auto !w-[193px]">
  <div class="flex flex-col items-start rounded-[9px] shadow-[var(--shadow-card-lg)]" style="background:var(--color-bg-main)">
    <div class="flex flex-col gap-[7px] h-[167px] items-end justify-end px-[5px] py-[5px] relative shrink-0 w-full">
      <div class="absolute flex gap-[7px] h-[167px] items-start left-0 p-[5px] top-0 w-full">
        <div class="absolute flex flex-col gap-[7px] h-[167px] items-end left-0 top-0 w-full">
          <div class="absolute left-1/2 -translate-x-1/2 h-[167px] rounded-t-[7px] top-0 w-full overflow-hidden">
            <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
          </div>
          <div class="flex h-[22px] items-center justify-center p-[5px] relative rounded-bl-[6px] rounded-tr-[6px] shrink-0" style="background:var(--color-accent-yellow)">
            <p class="font-normal text-[11px] tracking-[0.3px] whitespace-nowrap" style="color:var(--color-black)">70% Sold</p>
          </div>
        </div>
        <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer shadow flex items-center justify-center p-[7px] relative rounded-full shrink-0 size-[29px]">
          <img src="{{ asset('elora-4/assets/icons/heart.svg') }}" alt="" class="size-[18px]" />
        </button>
      </div>
      <div class="flex items-center justify-center px-[11px] py-[4px] relative rounded-[15px] shrink-0 h-[41px] w-[52px]" style="background:var(--color-text-primary)">
        <img src="{{ asset('elora-4/assets/icons/cart.svg') }}" alt="Add to cart" class="size-[22px] invert" />
      </div>
    </div>
    <div class="flex flex-col gap-[6px] items-start p-[6px] relative shrink-0 w-full">
      <div class="flex flex-col gap-[4px] items-start w-full">
        <div class="flex items-center justify-between w-full whitespace-nowrap">
          <p class="font-medium text-[17px]" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
          <p class="font-normal text-[15px]" style="color:var(--color-badge-pink)">{{ $p['weight'] }}</p>
        </div>
        <p class="font-normal text-[15px] w-full" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
      </div>
      <div class="flex flex-col gap-[1px] items-start w-full">
        <div class="h-[5px] w-full rounded-full" style="background:var(--color-stroke)">
          <div class="h-full rounded-full" style="background:var(--color-progress-red); width:{{ $p['progress'] ?? 83 }}%"></div>
        </div>
        <p class="text-[10px] tracking-[0.3px]" style="color:var(--color-progress-red)">{{ $p['ordered'] ?? '5 ordered last 30 min' }}</p>
      </div>
      <div class="flex flex-col gap-[4px] items-start">
        <div class="flex gap-[7px] items-center justify-center">
          <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[9px] w-[63px]" />
          <span class="text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
        </div>
        <div class="flex gap-[7px] items-end">
          <p class="font-medium text-[17px] whitespace-nowrap" style="color:var(--color-badge-pink)">{{ $p['price'] }}</p>
          <p class="font-light text-[12px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
          <p class="font-normal text-[12px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        </div>
      </div>
      <div class="flex flex-col gap-[4px] items-start w-full">
        <div class="flex gap-[6px] items-center w-full">
          <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="size-[17px]" />
          <p class="font-medium text-[12px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
        </div>
      </div>
    </div>
  </div>
</div>
