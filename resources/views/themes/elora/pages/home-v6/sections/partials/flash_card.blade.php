{{-- Expects $p: name, price, oldPrice, discount, image, url --}}
<a href="{{ $p['url'] ?? '#' }}" class="flex gap-[5px] rounded-[10px] overflow-hidden h-full" style="background:var(--color-bg-main)">
  <div class="relative w-[45%] max-h-[148px] shrink-0">
    <img src="{{ $p['image'] ?? asset('elora-2/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="h-full max-h-[148px] w-full object-cover rounded-l-[8px]" />
    <span class="absolute top-0 left-0 text-[12px] font-normal px-[6px] py-[4px] rounded-bl-[8px] rounded-tr-[8px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
  </div>
  <div class="flex-1 p-[8px] flex flex-col gap-[4px] min-w-0">
    <div class="flex items-center justify-between">
      <p class="font-medium text-[16px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
      <p class="text-[14px] shrink-0" style="color:var(--color-accent-green)">250g</p>
    </div>
    <p class="text-[13px]" style="color:var(--color-text-subtitle)">Premium cotton blend</p>
    <div class="flex items-center gap-[6px]">
      <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[56px]" />
      <span class="text-[12px]" style="color:var(--color-text-subtitle)">4.2 (+850)</span>
    </div>
    <div class="flex items-end gap-[6px]">
      <p class="font-medium text-[16px]" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
      <p class="text-[11px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
      <p class="text-[12px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
    </div>
    <p class="text-[11px] font-medium" style="color:var(--color-success)">Delivered by 24 March · Only 5 left</p>
  </div>
</a>
