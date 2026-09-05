{{-- Best Seller composite slide's bottom wide card. Expects $p: image, name, rating, price, priceColor, oldPrice, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex-1 flex gap-[3px] lg:gap-[5px] bg-[var(--color-bg-main)] rounded-[6px] lg:rounded-[10.3px] overflow-hidden shadow-[var(--shadow-card)]">
  <div class="relative w-[38%] shrink-0">
    <img src="{{ !empty($p['image']) ? $p['image'] : asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
  </div>
  <div class="flex-1 p-[8px] flex flex-col justify-center gap-[4px] min-w-0">
    <p class="font-medium text-[14px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
    <div class="flex items-center gap-[4px]">
      <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[7px] w-[48px]" />
      <span class="text-[10px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
    </div>
    <div class="flex items-end gap-[5px]">
      <p class="font-medium text-[14px]" style="color:{{ $p['priceColor'] ?? 'var(--color-primary)' }}">{{ $p['price'] }}</p>
      @if (!empty($p['oldPrice']))
        <p class="text-[10px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      @endif
    </div>
  </div>
</a>
