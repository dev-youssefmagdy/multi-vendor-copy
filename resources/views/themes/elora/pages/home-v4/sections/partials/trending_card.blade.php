{{-- Expects $p: image, name, price, oldPrice, discount, rating, url; ordered/progress optional --}}
<div class="swiper-slide h-auto w-full lg:w-auto">
  <a href="{{ $p['url'] ?? '#' }}" class="flex lg:items-center bg-[var(--color-bg-main)] rounded-[6px] lg:rounded-[10.11px] shadow-sm h-full lg:h-[236px] overflow-hidden" style="text-decoration:none">
    <div class="relative w-[123.69px] lg:w-[222.51px] shrink-0 h-full lg:h-[236px] lg:px-[6.36px] lg:py-[5.3px]">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="h-full w-full object-cover" />
      <span class="absolute top-0 right-0 flex items-center justify-center text-[7.71px] leading-[10px] lg:text-[16px] font-normal px-[3.31px] lg:px-[8px] py-[3.31px] lg:py-[4px] rounded-bl-[4.41px] lg:rounded-bl-[8px] tracking-[0.28px] lg:tracking-normal w-[39.61px] lg:w-auto whitespace-nowrap" style="background:var(--color-accent-yellow); color:var(--color-black)">70% Sold</span>
      <button type="button" onclick="event.preventDefault(); event.stopPropagation(); eloraV4ToggleFavorite(this)"
        data-fav='{{ $p['favData'] ?? '{}' }}'
        aria-label="{{ __('Add to favorites') }}" class="elora-v4-heart-btn absolute top-[5px] left-[5px] lg:top-[6px] lg:left-[6px] bg-white rounded-full p-[5.03px] lg:p-[5px] shadow-[0px_2.51px_2.51px_rgba(0,0,0,0.15)] lg:shadow"><img src="{{ asset('elora-4/assets/icons/heart.svg') }}" class="size-[12.57px] lg:size-[22px]" alt="" /></button>
      <button type="button" wire:click.prevent="addToCart({{ $p['id'] ?? 0 }})" onclick="event.stopPropagation()"
        aria-label="{{ __('Add to cart') }}"
        class="absolute bottom-[5px] right-[5px] lg:bottom-[8px] lg:right-[8px] flex items-center justify-center bg-white rounded-[10.06px] lg:rounded-[14px] w-[35.83px] h-[28.29px] lg:size-[44px] shadow cursor-pointer"><img src="{{ asset('elora-4/assets/icons/add-to-cart-dark.svg') }}" class="size-[15.09px] lg:size-[20px]" alt="" /></button>
    </div>
    <div class="flex-1 min-w-0 p-[4px] lg:px-[13.49px] lg:py-[6.74px] flex flex-col gap-[4px] lg:gap-[13.49px] lg:h-[193.86px] lg:justify-center">

      {{-- Name + weight + subtitle --}}
      <div class="flex flex-col gap-[2.51px] lg:gap-[4.24px] w-full">
        <div class="flex items-center justify-between gap-[1.26px] lg:gap-[2.12px] w-full">
          <p class="font-medium text-[12px] leading-[15px] lg:text-[23.6px] lg:leading-[30px] tracking-[0.31px] lg:tracking-[0.53px] truncate min-w-0" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
          <p class="text-[10px] leading-[16px] lg:text-[25.29px] lg:leading-[26px] tracking-[0.31px] lg:tracking-[0.53px] lg:text-center shrink-0 whitespace-nowrap" style="color:#CD0032">{{ $p['weight'] ?? '250g' }}</p>
        </div>
        <p class="text-[10px] leading-[13px] lg:text-[20.23px] lg:leading-[25px] tracking-[0.31px] lg:tracking-[0.53px] truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] ?? __('Premium quality') }}</p>
      </div>

      {{-- Progress bar --}}
      <div class="flex flex-col gap-[2px] lg:gap-[1.69px]">
        <div class="h-[6px] lg:h-[8.43px] w-full rounded-full" style="background:#D9D9D9">
          <div class="h-full rounded-full" style="background:var(--color-brand-orange-bright); width:{{ $p['progress'] ?? 83 }}%"></div>
        </div>
        <p class="text-[11px] lg:text-[16.86px] lg:leading-[21px] lg:tracking-[0.53px] truncate" style="color:var(--color-brand-orange-bright)">{{ $p['ordered'] ?? __('Trending now') }}</p>
      </div>

      {{-- Rating + price --}}
      <div class="flex flex-col gap-[2.51px] lg:gap-[4.24px]">
        <div class="flex items-center gap-[5.03px] lg:gap-[8.48px]">
          <img src="{{ asset('elora-4/assets/icons/star-rating.svg') }}" alt="" class="h-[6.28px] lg:h-[10.59px] w-[43.05px] lg:w-[72.57px] shrink-0" />
          <span class="text-[8px] leading-[10px] lg:text-[20.23px] lg:leading-[25px] tracking-[0.31px] lg:tracking-[0.53px]" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
        </div>
        <div class="flex items-end gap-[5.03px] lg:gap-[8.48px]">
          <p class="font-medium text-[12px] leading-[15px] lg:text-[26.97px] lg:leading-[34px]" style="color:var(--color-brand-orange)">{{ $p['price'] }}</p>
          @if (!empty($p['oldPrice']))
          <p class="text-[8.8px] leading-[11px] lg:text-[14.83px] lg:leading-[19px] lg:font-light line-through" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
          @endif
          @if (!empty($p['discount']))
          <p class="text-[8px] leading-[10px] lg:text-[20.23px] lg:leading-[25px] tracking-[0.31px] lg:tracking-[0.53px]" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
          @endif
        </div>
      </div>

      {{-- Delivery estimate --}}
      <div class="flex items-center gap-[4px] lg:gap-[6.74px]">
        <img src="{{ asset('elora-4/assets/icons/truck-delivery.svg') }}" alt="" class="w-[12px] h-[12px] lg:w-[20.23px] lg:h-[20.23px] shrink-0" />
        <span class="font-medium text-[8px] leading-[10px] lg:text-[13.49px] lg:leading-[17px] whitespace-nowrap" style="color:var(--color-success)">{{ __('Delivered by 24 March') }}</span>
      </div>

    </div>
  </a>
</div>
