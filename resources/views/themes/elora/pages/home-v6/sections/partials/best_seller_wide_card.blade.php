{{-- Best Seller composite slide's bottom wide card. Expects $p: image, badge, badgeBg, badgeColor, name, weight, desc, rating, price, oldPrice, discount, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="relative flex-1 flex gap-[3px] lg:gap-[5px] bg-[var(--color-bg-main)] rounded-[6px] lg:rounded-[10px] overflow-hidden shadow-[var(--shadow-card)] no-underline">
  <div class="relative w-[40%] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <div class="absolute inset-0 flex flex-col justify-between items-end p-[3px_4px] lg:p-[5px_6.5px]">
      <div class="flex w-full justify-between items-start">
        <span
          class="flex items-center h-[15px] px-[3px] lg:h-[26px] lg:px-[6px] text-[8px] lg:text-[13px] font-normal tracking-[0.28px] lg:tracking-normal rounded-br-[4px] lg:rounded-br-[8px]"
          style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}"
          >{{ $p['badge'] ?? '70% Sold' }}</span
        >
        <button type="button" aria-label="Add to favorites" class="flex items-center justify-center w-[20px] h-[20px] lg:w-[35px] lg:h-[35px] p-[5px] bg-white rounded-full shadow">
          <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[12px] lg:size-[21px]" alt="" />
        </button>
      </div>
      <button type="button" aria-label="Add to cart" class="flex items-center justify-center w-[36px] h-[28px] lg:w-auto lg:h-[48px] lg:px-[12px] rounded-[10px] lg:rounded-[17px]" style="background: var(--color-bg-main)">
        <img src="{{ asset('elora-2/assets/icons/cart-add-blue.svg') }}" class="size-[15px] lg:size-[26px]" alt="" />
      </button>
    </div>
  </div>
  <div class="flex-1 min-w-0 flex flex-col justify-between p-[4px] lg:p-[7px]">
    <div class="flex flex-col gap-[2px] lg:gap-[4px]">
      <div class="flex items-center justify-between gap-[1px] lg:gap-[4px]">
        <p class="font-medium text-[12px] lg:text-[20px] tracking-[0.31px] lg:tracking-normal truncate" style="color: var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="text-[10px] lg:text-[17px] shrink-0" style="color: #132092">{{ $p['weight'] }}</p>
      </div>
      <p class="text-[10px] lg:text-[17px] tracking-[0.31px] lg:tracking-normal truncate" style="color: var(--color-text-subtitle)">{{ $p['desc'] ?? 'Premium cotton blend' }}</p>
    </div>
    <div class="flex flex-col gap-[2px] lg:gap-[4px]">
      <div class="flex items-center gap-[5px] lg:gap-[8px]">
        <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[6px] w-[44px] lg:h-[11px] lg:w-[73px]" />
        <span class="text-[8px] lg:text-[13.7px]" style="color: var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex items-baseline gap-[5px] lg:gap-[8px]">
        <p class="font-medium text-[12px] lg:text-[20.5px]" style="color: #0018e8">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[9px] lg:text-[15px] line-through" style="color: var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="text-[8px] lg:text-[13.7px] tracking-[0.31px] lg:tracking-normal" style="color: var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="flex items-center gap-[4px] lg:gap-[7px]">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[12px] lg:size-[20px]" />
      <p class="font-medium text-[8px] lg:text-[13.7px] whitespace-nowrap" style="color: var(--color-success)">Delivered by 24 March</p>
    </div>
  </div>
</a>
