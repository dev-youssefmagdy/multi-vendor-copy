{{-- Expects $p: image, badge, badgeBg, badgeText, name, weight, desc, rating, price, oldPrice, discount, urgency --}}
<div class="swiper-slide">
  <div class="bg-[var(--color-bg-main)] flex items-stretch rounded-[8px] h-[129px] max-h-[129px] shadow-sm overflow-hidden">
    <div class="relative shrink-0 w-[70px] lg:w-[140px]">
      <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover bg-[var(--color-page-bg)]" />
      <button type="button" aria-label="Add to favorites" class="absolute top-[4px] left-[4px] lg:top-[6px] lg:left-[6px] bg-white cursor-pointer drop-shadow-[0px_4px_2px_rgba(0,0,0,0.15)] flex items-center justify-center p-[3px] lg:p-[5px] rounded-full size-[16px] lg:size-[24px]">
        <img src="{{ asset('elora-3/assets/icons/heart.svg') }}" alt="" class="size-[9px] lg:size-[14px]" />
      </button>
      <span class="absolute top-0 right-0 text-[8px] lg:text-[11px] font-normal px-[4px] py-[2px] lg:px-[8px] lg:py-[4px] rounded-bl-[6px] lg:rounded-bl-[8px] whitespace-nowrap" style="background:{{ $p['badgeBg'] }}; color:{{ $p['badgeText'] }}">{{ $p['badge'] }}</span>
      <div class="absolute bottom-[4px] right-[4px] lg:bottom-[6px] lg:right-[6px] flex h-[18px] lg:h-[28px] items-center justify-center px-[5px] lg:px-[8px] rounded-[9px] lg:rounded-[12px]" style="background:var(--color-text-primary)">
        <img src="{{ asset('elora-3/assets/icons/cart-add.svg') }}" alt="Add to cart" class="size-[9px] lg:size-[14px]" />
      </div>
    </div>
    <div class="flex-1 flex flex-col gap-[2px] lg:gap-[3px] p-[6px] lg:p-[10px] min-w-0 justify-center overflow-hidden">
      <div class="flex items-center justify-between gap-[4px] lg:gap-[8px] w-full">
        <p class="font-medium text-[11px] lg:text-[16px] truncate" style="color:var(--color-text-primary)">{{ $p['name'] }}</p>
        <p class="font-normal text-[9px] lg:text-[12px] shrink-0" style="color:var(--color-brand-pink)">{{ $p['weight'] }}</p>
      </div>
      <p class="font-normal text-[9px] lg:text-[12px] truncate" style="color:var(--color-text-subtitle)">{{ $p['desc'] }}</p>
      <div class="flex gap-[4px] lg:gap-[8px] items-center">
        <img src="{{ asset('elora-3/assets/icons/star-rating.svg') }}" alt="" class="h-[8px] w-[55px] lg:h-[10px] lg:w-[69px]" />
        <span class="font-normal text-[9px] lg:text-[11px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="flex gap-[4px] lg:gap-[6px] items-end">
        <p class="font-medium text-[11px] lg:text-[16px] whitespace-nowrap" style="color:var(--color-text-primary)">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="font-light text-[9px] lg:text-[12px] line-through whitespace-nowrap" style="color:var(--color-text-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="font-normal text-[9px] lg:text-[11px] tracking-[0.5px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
      <div class="flex gap-[4px] items-center w-full">
        <img src="{{ asset('elora-3/assets/icons/truck-delivery.svg') }}" alt="" class="size-[10px] lg:size-[16px] shrink-0" />
        <p class="font-medium text-[9px] lg:text-[12px] truncate" style="color:{{ $p['urgency'] ?? 'var(--color-success)' }}">Delivered by 24 March</p>
      </div>
    </div>
  </div>
</div>
