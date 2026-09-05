{{-- Best Seller composite slide's small card (top-left/top-right of the right column). Expects $p: image, name, badge, badgeBg, badgeColor, weight, weightColor, rating, price, priceColor, oldPrice, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex-1 bg-[var(--color-bg-main)] flex flex-col items-start rounded-[5.1px] lg:rounded-[8.7px] overflow-hidden shadow-[var(--shadow-card)]">
  <div class="relative h-[120px] lg:h-[166px] w-full shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    <span class="absolute top-0 right-0 text-[11px] font-medium px-[6px] py-[3px] rounded-bl-[8px]" style="background:{{ $p['badgeBg'] ?? 'var(--color-accent-yellow)' }}; color:{{ $p['badgeColor'] ?? 'var(--color-black)' }}">{{ $p['badge'] ?? '70% Sold' }}</span>
    <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[14px]" alt="" /></button>
    <div class="absolute bottom-[6px] right-[6px] bg-white rounded-[8px] p-[5px] shadow"><img src="{{ asset('elora-2/assets/icons/cart-add.svg') }}" class="size-[16px]" alt="" /></div>
  </div>
  <div class="flex flex-col gap-[4px] items-start p-[8px] w-full min-w-0">
    <div class="flex items-center justify-between w-full">
      <p class="font-medium text-[14px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
      <p class="text-[12px] shrink-0" style="color:{{ $p['weightColor'] ?? 'var(--color-accent-green)' }}">{{ $p['weight'] }}</p>
    </div>
    <div class="flex items-center gap-[4px]">
      <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[7px] w-[48px]" />
      <span class="text-[10px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
    </div>
    <div class="flex items-end gap-[5px]">
      <p class="font-medium text-[14px]" style="color:{{ $p['priceColor'] ?? 'var(--color-text-primary)' }}">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="text-[10px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
    </div>
    <div class="flex gap-[4px] items-center w-full">
      <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[16px]" />
      <p class="font-medium text-[10px] whitespace-nowrap" style="color:var(--color-success)">Delivered by 24 March</p>
    </div>
  </div>
</a>
