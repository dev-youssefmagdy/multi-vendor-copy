{{-- Expects $p: image, badge, badgeBg, badgeColor, name, weight, weightColor, desc, rating, price, priceColor, oldPrice, discount --}}
<div class="bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] h-full shadow-[var(--shadow-card-lg)]">
  <div class="flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end justify-end px-[6px] py-[5px] relative shrink-0 w-full">
    <div class="absolute flex gap-[8px] h-[183px] lg:h-[227px] items-start left-0 p-[6px] top-0 w-full">
      <div class="absolute flex flex-col gap-[8px] h-[183px] lg:h-[227px] items-end left-0 top-0 w-full">
        <div class="absolute left-1/2 -translate-x-1/2 h-[183px] lg:h-[227px] rounded-t-[6px] top-0 w-full overflow-hidden">
          <img src="{{ asset('elora-2/assets/' . $p['image']) }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
        </div>
        <div class="content-stretch flex h-[28px] items-center justify-center p-[6px] relative rounded-bl-[8px] rounded-tr-[8px] shrink-0" style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}">
          <p class="font-medium text-[14px] tracking-[0.5px] whitespace-nowrap" style="color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}">{{ $p['badge'] ?? '70% Sold' }}</p>
        </div>
      </div>
      <button type="button" aria-label="Add to favorites" class="bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[8px] relative rounded-full shrink-0 size-[32px]">
        <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" alt="" class="size-[20px]" />
      </button>
    </div>
    <div class="bg-[var(--color-bg-main)] flex h-[45px] items-center justify-center px-[12px] py-[4px] relative rounded-[16px] shrink-0 w-[57px]">
      <img src="{{ asset('elora-2/assets/icons/cart.svg') }}" alt="Add to cart" class="size-[24px]" />
    </div>
  </div>
  <div class="flex flex-col gap-[8px] items-start p-[8px] relative shrink-0 w-full">
    <div class="flex flex-col gap-[4px] items-start tracking-[0.5px] w-full">
      <div class="flex items-center justify-between w-full whitespace-nowrap">
        <p class="font-medium text-[var(--color-text-primary)] text-[16px]">{{ $p['name'] }}</p>
        <p class="font-normal text-[14px] text-center" style="color:{{ $p['weightColor'] ?? 'var(--color-accent-green)' }}">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[var(--color-text-subtitle)] text-[14px] w-full">{{ $p['desc'] ?? 'Premium cotton blend' }}</p>
    </div>
    <div class="flex flex-col gap-[4px] items-start">
      <div class="flex gap-[8px] items-center justify-center">
        <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[10px] w-[69px]" />
        <p class="font-normal text-[var(--color-text-subtitle)] text-[12px] tracking-[0.5px] whitespace-nowrap">{{ $p['rating'] }}</p>
      </div>
      <div class="flex gap-[8px] items-end">
        <p class="font-medium text-[18px] whitespace-nowrap" style="color:{{ $p['priceColor'] ?? 'var(--color-text-primary)' }}">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[var(--color-text-subtitle)] text-[14px] line-through whitespace-nowrap">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[var(--color-secondary)] text-[12px] tracking-[0.5px] whitespace-nowrap">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex flex-col gap-[4px] items-start w-full">
      <div class="flex gap-[4px] items-center w-full">
        <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[18px]" />
        <p class="font-medium text-[12px] whitespace-nowrap" style="color:{{ $p['deliveredColor'] ?? 'var(--color-success)' }}">Delivered by 24 March</p>
      </div>
      <div class="flex gap-[6px] items-center w-full">
        <img src="{{ asset('elora-2/assets/icons/cart-x.svg') }}" alt="" class="size-[14px]" />
        <p class="font-medium text-[12px] whitespace-nowrap" style="color:{{ $p['deliveredColor'] ?? 'var(--color-success)' }}">Only 5 left</p>
      </div>
    </div>
  </div>
</div>
