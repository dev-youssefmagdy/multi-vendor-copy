{{-- Expects $p: name, weight, price, oldPrice, discount, rating, stock, alt (bool) — image band sized to 57.5% of card height, for use inside the Best Seller fan cascade / Flash Sale big cards.
     Best Seller's mapper always sets 'alt' (true/false); Flash Sale's mapper never does — that presence, not alt's value, is what
     tells us weight/price should use Best Seller's fixed orange/black scheme instead of Flash Sale's alt-driven blue/orange one.
     The Tailwind classes below are cascade-position-0 (active/front card) sizing. Best Seller's fan cascade is draggable, so which
     product occupies position 1, 2, etc. changes at runtime — elora-v5-carousels.js tags each slide with data-cascade-pos to match,
     and per-position size/spacing overrides for positions 1+ live in elora-v5.css keyed off [data-cascade-pos="N"], targeting the
     fan-* class hooks below, rather than being baked into this template. --}}
@php
    $isBestSeller = array_key_exists('alt', $p);
    $weightColor = $isBestSeller ? 'var(--color-badge-orange)' : (!empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-price-blue)');
    $priceColor = $isBestSeller ? 'var(--color-black)' : (!empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-price-blue)');
    $deliveredColor = !empty($p['alt']) ? 'var(--color-error)' : 'var(--color-success)';
@endphp
<a href="{{ $p['url'] ?? '#' }}" class="fan-card flex flex-col items-start rounded-[8px] lg:rounded-[12.9px] h-full shadow-[var(--shadow-card-lg)] lg:shadow-[0px_6.38px_34.11px_rgba(0,0,0,0.25)]" style="background:var(--color-bg-main)">
  <div class="relative shrink-0 w-full" style="height:57.5%">
    <div class="fan-image rounded-t-[8px] lg:rounded-t-[10.81px] absolute inset-0 overflow-hidden">
      <img src="{{ $p['image'] ?? asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    </div>
    <button type="button" aria-label="Add to favorites" class="fan-heart-btn absolute top-[5px] left-[5px] lg:top-[8.11px] lg:left-[8.11px] bg-white cursor-pointer shadow lg:shadow-[0_5.40px_5.40px_rgba(0,0,0,0.15)] flex items-center justify-center p-[5px] lg:p-[10.81px] rounded-full shrink-0 size-[22px] lg:size-[43.23px]">
      <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="fan-heart-icon size-[13px] lg:size-[27.02px]" />
    </button>
    <div class="fan-badge absolute top-0 right-0 flex items-center justify-center px-[10px] py-[5px] lg:p-[7.11px] rounded-tl-[8px] rounded-br-[8px] lg:rounded-tl-[9.47px] lg:rounded-br-[9.47px] shrink-0" style="background:{{ !empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-yellow)' }}">
      <p class="fan-badge-text font-normal text-[11px] lg:text-[16.58px] lg:leading-[21px] tracking-[0.3px] lg:tracking-[0.59px] whitespace-nowrap" style="color:{{ !empty($p['alt']) ? '#fff' : 'var(--color-black-alt)' }}">{{ !empty($p['alt']) ? '30% OFF' : '70% Sold' }}</p>
    </div>
    <div class="fan-cart-btn absolute bottom-[5px] right-[5px] lg:bottom-[8.11px] lg:right-[8.11px] flex items-center justify-center p-[7px] lg:px-[16.21px] lg:py-[5.40px] rounded-full lg:rounded-[21.62px] shrink-0 size-[34px] lg:w-[77px] lg:h-[60.79px] bg-white shadow">
      <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="Add to cart" class="fan-cart-icon size-[18px] lg:size-[32.42px]" />
    </div>
  </div>
  <div class="fan-content flex flex-col gap-[4px] lg:gap-[8.60px] items-start p-[6px] lg:p-[8.60px] relative shrink-0 w-full">
    <div class="fan-info-block flex flex-col gap-[2px] lg:gap-[5.40px] items-start w-full">
      <div class="fan-title-row flex items-center justify-between gap-[6px] lg:gap-[2.70px] w-full whitespace-nowrap">
        <p class="fan-name font-medium text-[13px] lg:text-[25.79px] lg:leading-[32px] lg:tracking-[0.68px] truncate" style="color:var(--color-black)">{{ $p['name'] }}</p>
        <p class="fan-weight font-normal text-[11px] lg:text-[21.49px] lg:leading-[34px] lg:tracking-[0.68px] shrink-0" style="color:{{ $weightColor }}">{{ $p['weight'] }}</p>
      </div>
      <p class="fan-desc font-normal text-[10px] lg:text-[21.49px] lg:leading-[27px] lg:tracking-[0.68px] w-full truncate" style="color:var(--color-subtitle)">Premium cotton blend</p>
    </div>
    <div class="fan-rating-price flex flex-col gap-[2px] lg:gap-[5.40px] items-start">
      <div class="fan-stars-row flex gap-[5px] lg:gap-[10.81px] items-center justify-center">
        <img src="{{ asset('elora-5/assets/icons/star-rating.svg') }}" alt="" class="fan-star-icon h-[7px] lg:h-[13.50px] w-[48px] lg:w-[92.53px]" />
        <span class="fan-rating-text text-[9px] lg:text-[17.19px] lg:leading-[22px] tracking-[0.3px] lg:tracking-[0.68px] whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="fan-price-row flex gap-[5px] lg:gap-[10.81px] items-end flex-wrap">
        <p class="fan-price font-medium text-[13px] lg:text-[25.79px] lg:leading-[32px]" style="color:{{ $priceColor }}">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="fan-oldprice font-light text-[9px] lg:text-[18.91px] lg:leading-[24px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="fan-discount font-normal text-[9px] lg:text-[17.19px] lg:leading-[22px] tracking-[0.3px] lg:tracking-[0.68px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="fan-delivery-block flex flex-col gap-[2px] lg:gap-[5.40px] items-start w-full">
      @if (!empty($p['left']))
        {{-- Low-stock urgency replaces the routine delivery line rather than stacking below it —
             every card-size spec's delivery/stock wrapper is only ever tall enough for one row. --}}
        <div class="fan-stock-row flex gap-[5px] lg:gap-[8.60px] items-center w-full">
          <img src="{{ asset('elora-5/assets/icons/cart-x.svg') }}" alt="" class="fan-stock-icon size-[12px] lg:size-[25.79px] shrink-0" />
          <p class="fan-stock-text font-medium text-[9px] lg:text-[17.19px] lg:leading-[22px] whitespace-nowrap" style="color:{{ $deliveredColor }}">{{ $p['left'] }}</p>
        </div>
      @else
        <div class="fan-delivery-row flex gap-[5px] lg:gap-[5.40px] items-center w-full">
          <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="fan-delivery-icon size-[12px] lg:size-[24.32px] shrink-0" />
          <p class="fan-delivery-text font-medium text-[9px] lg:text-[16.21px] lg:leading-[20px] whitespace-nowrap" style="color:{{ $deliveredColor }}">{{ $p['stock'] ?: 'Delivered by 24 March' }}</p>
        </div>
      @endif
    </div>
  </div>
</a>
