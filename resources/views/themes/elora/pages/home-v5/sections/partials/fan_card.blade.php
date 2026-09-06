{{-- Card used by both Best Seller's fan cascade AND Flash Sale's big slide
     (flash_sale_strip.blade.php) — this partial itself stays size-agnostic
     (w-full h-full, fills whatever box its caller gives it); Flash Sale
     forces its own !w-[]/!h-[] on the .swiper-slide, and Best Seller's
     .fan-slide-base class (elora-v5.css) fixes ONE base size per breakpoint
     matching the Figma card exactly (206.23x312.52 mobile, 256.37x388.49
     desktop). Every cascade depth (bigger in front, smaller behind) is then
     produced by elora-v5-carousels.js applying `transform: scale()` to that
     base slide — never by resizing the slide's raw width/height directly.
     Scaling the whole card as a unit guarantees every child (text, icons,
     padding) shrinks/grows in lockstep — the previous approach set each
     slide's raw width/height per depth and relied on a matching CSS
     override per depth to rescale the inner content, but that override
     only ever existed for one of five desktop depths (and none of
     mobile's three), so the rest rendered full-size text crammed into a
     shrunk box.
     Expects $p: name, weight, price, oldPrice, discount, rating, stock, left, alt (bool) --}}
@php
    $isBestSeller = array_key_exists('alt', $p);
    $weightColor = $isBestSeller ? 'var(--color-badge-orange)' : (!empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-price-blue)');
    $priceColor = $isBestSeller ? 'var(--color-black)' : (!empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-price-blue)');
    $deliveredColor = !empty($p['alt']) ? 'var(--color-error)' : 'var(--color-success)';
@endphp
<a href="{{ $p['url'] ?? '#' }}" class="fan-card flex flex-col items-start w-full h-full rounded-[9.37px] lg:rounded-[11.65px] shadow-[0_4.63px_24.79px_rgba(0,0,0,0.25)] lg:shadow-[0_6.7px_35.86px_rgba(0,0,0,0.25)]" style="background:var(--color-bg-main)">
  <div class="relative shrink-0 w-full" style="height:57.5%">
    <div class="fan-image rounded-t-[7.86px] lg:rounded-t-[9.77px] absolute inset-0 overflow-hidden">
      <img src="{{ $p['image'] ?? asset('elora-5/assets/images/product-placeholder.svg') }}" alt="{{ $p['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
    </div>
    <button type="button" aria-label="Add to favorites" class="fan-heart-btn absolute top-[4.91px] left-[5.89px] lg:top-[7.32px] lg:left-[7.32px] bg-white cursor-pointer shadow-[0_3.93px_3.93px_rgba(0,0,0,0.15)] lg:shadow-[0_4.88px_4.88px_rgba(0,0,0,0.15)] flex items-center justify-center p-[7.86px] lg:p-[9.77px] rounded-full shrink-0 size-[31.43px] lg:size-[39.07px]">
      <img src="{{ asset('elora-5/assets/icons/heart.svg') }}" alt="" class="fan-heart-icon size-[19.64px] lg:size-[24.42px]" />
    </button>
    <div class="fan-badge absolute top-0 right-0 flex items-center justify-center px-[5.17px] py-[5.17px] lg:px-[6.42px] lg:py-[6.42px] rounded-tl-[6.89px] rounded-br-[6.89px] lg:rounded-tl-[8.56px] lg:rounded-br-[8.56px] shrink-0" style="background:{{ !empty($p['alt']) ? 'var(--color-badge-orange)' : 'var(--color-yellow)' }}">
      <p class="fan-badge-text font-normal text-[12.05px] leading-[15px] lg:text-[14.98px] lg:leading-[19px] tracking-[0.43px] lg:tracking-[0.54px] whitespace-nowrap" style="color:{{ !empty($p['alt']) ? '#fff' : 'var(--color-black-alt)' }}">{{ !empty($p['alt']) ? '30% OFF' : '70% Sold' }}</p>
    </div>
    <div class="fan-cart-btn absolute bottom-[4.91px] right-[5.89px] lg:bottom-[7.32px] lg:right-[7.32px] flex items-center justify-center px-[11.78px] py-[3.93px] lg:px-[14.65px] lg:py-[4.88px] rounded-[15.71px] lg:rounded-[19.53px] shrink-0 w-[55.98px] h-[44.19px] lg:w-[69.59px] lg:h-[54.94px] bg-white shadow">
      <img src="{{ asset('elora-5/assets/icons/icon-cart-card.svg') }}" alt="Add to cart" class="fan-cart-icon size-[23.57px] lg:size-[29.3px]" />
    </div>
  </div>
  <div class="fan-content flex flex-col gap-[6.25px] lg:gap-[7.77px] items-start p-[6.25px] lg:p-[7.77px] relative shrink-0 w-full flex-1 min-h-0">
    <div class="fan-info-block flex flex-col gap-[3.93px] lg:gap-[4.88px] items-start w-full">
      <div class="fan-title-row flex items-center justify-between gap-[1.96px] lg:gap-[2.44px] w-full whitespace-nowrap">
        <p class="fan-name font-medium text-[18.75px] leading-[24px] lg:text-[23.31px] lg:leading-[29px] tracking-[0.49px] lg:tracking-[0.61px] truncate" style="color:var(--color-black)">{{ $p['name'] }}</p>
        <p class="fan-weight font-normal text-[15.62px] leading-[25px] lg:text-[19.42px] lg:leading-[31px] tracking-[0.49px] lg:tracking-[0.61px] shrink-0" style="color:{{ $weightColor }}">{{ $p['weight'] }}</p>
      </div>
      <p class="fan-desc font-normal text-[15.62px] leading-[20px] lg:text-[19.42px] lg:leading-[24px] tracking-[0.49px] lg:tracking-[0.61px] w-full truncate" style="color:var(--color-subtitle)">{{ __('Premium cotton blend') }}</p>
    </div>
    <div class="fan-rating-price flex flex-col gap-[3.93px] lg:gap-[4.88px] items-start">
      <div class="fan-stars-row flex gap-[7.86px] lg:gap-[9.77px] items-center justify-center">
        <img src="{{ asset('elora-5/assets/icons/star-rating.svg') }}" alt="" class="fan-star-icon h-[9.82px] w-[67.26px] lg:h-[12.2px] lg:w-[83.62px]" />
        <span class="fan-rating-text text-[12.5px] leading-[16px] lg:text-[15.54px] lg:leading-[20px] tracking-[0.49px] lg:tracking-[0.61px] whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['rating'] }}</span>
      </div>
      <div class="fan-price-row flex gap-[7.86px] lg:gap-[9.77px] items-end flex-wrap">
        <p class="fan-price font-medium text-[18.75px] leading-[24px] lg:text-[23.31px] lg:leading-[29px]" style="color:{{ $priceColor }}">{{ $p['price'] }}</p>
        @if (!empty($p['oldPrice']))
          <p class="fan-oldprice font-light text-[13.75px] leading-[17px] lg:text-[17.09px] lg:leading-[22px] line-through whitespace-nowrap" style="color:var(--color-subtitle)">{{ $p['oldPrice'] }}</p>
        @endif
        @if (!empty($p['discount']))
          <p class="fan-discount font-normal text-[12.5px] leading-[16px] lg:text-[15.54px] lg:leading-[20px] tracking-[0.49px] lg:tracking-[0.61px] whitespace-nowrap" style="color:var(--color-secondary)">{{ $p['discount'] }}</p>
        @endif
      </div>
    </div>
    <div class="fan-delivery-block flex flex-col gap-[3.93px] lg:gap-[4.88px] items-start w-full">
      @if (!empty($p['left']))
        {{-- Low-stock urgency replaces the routine delivery line rather than stacking below it —
             every card-size spec's delivery/stock wrapper is only ever tall enough for one row. --}}
        <div class="fan-stock-row flex gap-[6.25px] lg:gap-[9.77px] items-center w-full">
          <img src="{{ asset('elora-5/assets/icons/cart-x.svg') }}" alt="" class="fan-stock-icon size-[18.75px] lg:size-[19.53px] shrink-0" />
          <p class="fan-stock-text font-medium text-[12.5px] leading-[16px] lg:text-[14.65px] lg:leading-[18px] whitespace-nowrap" style="color:{{ $deliveredColor }}">{{ $p['left'] }}</p>
        </div>
      @else
        <div class="fan-delivery-row flex gap-[3.93px] lg:gap-[7.77px] items-center w-full">
          <img src="{{ asset('elora-5/assets/icons/icon-truck-small.svg') }}" alt="" class="fan-delivery-icon size-[17.68px] lg:size-[23.31px] shrink-0" />
          <p class="fan-delivery-text font-medium text-[11.78px] leading-[15px] lg:text-[15.54px] lg:leading-[20px] whitespace-nowrap" style="color:{{ $deliveredColor }}">{{ $p['stock'] ?: __('Delivered by 24 March') }}</p>
        </div>
      @endif
    </div>
  </div>
</a>
