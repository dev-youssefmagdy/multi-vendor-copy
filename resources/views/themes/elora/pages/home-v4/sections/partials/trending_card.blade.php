{{-- Expects $p: image, name, price, oldPrice, discount, rating, url; ordered/progress optional --}}
<div class="swiper-slide h-auto">
  <a href="{{ $p['url'] ?? '#' }}" class="flex bg-[var(--color-bg-main)] rounded-[10px] shadow-sm h-full overflow-hidden" style="text-decoration:none">
    <div class="relative w-[45%] shrink-0">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
      <span class="absolute top-0 right-0 text-[12px] lg:text-[16px] font-normal px-[8px] py-[4px] rounded-bl-[8px]" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
      <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
        data-fav='{{ $p['favData'] ?? '{}' }}'
        aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow"><img src="{{ asset('elora-4/assets/icons/heart.svg') }}" class="size-[16px] lg:size-[22px]" alt="" /></button>
      <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
        aria-label="{{ __('Add to cart') }}"
        class="absolute bottom-[8px] right-[8px] rounded-[8px] p-[6px] shadow cursor-pointer" style="background:var(--color-text-primary)"><img src="{{ asset('elora-4/assets/icons/cart.svg') }}" class="size-[20px] lg:size-[26px] invert" alt="" /></button>
    </div>
    <div class="flex-1 min-w-0 p-[12px] lg:p-[14px] flex flex-col gap-[8px]">
      <div class="flex items-center justify-between gap-[6px]">
        <p class="font-medium text-[16px] lg:text-[19px] truncate min-w-0" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="text-[14px] lg:text-[16px] shrink-0 whitespace-nowrap" style="color:var(--color-brand-orange-bright)">{{ $p['weight'] ?? '250g' }}</p>
      </div>
      <p class="text-[13px] lg:text-[16px] truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] ?? __('Premium quality') }}</p>
      <div class="h-[6px] lg:h-[8px] w-full rounded-full" style="background:var(--color-stroke)">
        <div class="h-full rounded-full" style="background:var(--color-error); width:{{ $p['progress'] ?? 83 }}%"></div>
      </div>
      <p class="text-[11px] lg:text-[13px] truncate" style="color:var(--color-error)">{{ $p['ordered'] ?? __('Trending now') }}</p>
      <div class="flex items-center gap-[6px]">
        <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] lg:h-[10px] w-[56px] lg:w-[70px]" />
        <span class="text-[12px] lg:text-[14px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex items-end gap-[6px]">
        <p class="font-medium text-[16px] lg:text-[19px]" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
        <p class="text-[11px] lg:text-[13px] line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
        <p class="text-[12px] lg:text-[14px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
  </a>
</div>
