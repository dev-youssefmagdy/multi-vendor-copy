@php
  $bestSellerFull6 = ['image' => 'images/product-placeholder.svg', 'name' => 'Pants', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeColor' => 'var(--color-white)', 'weightColor' => 'var(--color-primary)', 'deliveredColor' => 'var(--color-error)'];
  $bestSellerSmall6 = [
    ['image' => 'images/product-placeholder.svg', 'name' => 'Weekend Backpack', 'weight' => '480g', 'price' => '$89.00', 'oldPrice' => null, 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)', 'priceColor' => 'var(--color-primary)'],
    ['image' => 'images/product-placeholder.svg', 'name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'rating' => '4.2 (+850)', 'weightColor' => 'var(--color-primary)', 'priceColor' => 'var(--color-primary)'],
  ];
  $bestSellerWide6 = ['image' => 'images/product-placeholder.svg', 'name' => 'Smart Watch', 'price' => '$129.00', 'oldPrice' => '$159.00', 'rating' => '4.6 (+1.1k)', 'priceColor' => 'var(--color-primary)'];
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]" style="background: var(--color-accent-yellow)">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">Best Seller</h2>
    <a href="#" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-text-secondary-dark)">see all</a>
  </div>
  <div class="relative">
    <div class="swiper card-swiper w-full">
      <div class="swiper-wrapper" id="bestSellerWrapper">
        <div class="swiper-slide h-auto">
          <div class="flex gap-[12px] h-full">
            <div class="flex-1">
              @include('themes.elora.pages.home-v6.sections.partials.product_card', ['p' => $bestSellerFull6])
            </div>
            <div class="flex-1 flex flex-col gap-[12px]">
              <div class="flex gap-[12px] flex-1">
                @foreach ($bestSellerSmall6 as $small)
                  <div class="flex-1 bg-[var(--color-bg-main)] flex flex-col items-start rounded-[6px] overflow-hidden shadow-[var(--shadow-card)]">
                    <div class="relative h-[120px] lg:h-[166px] w-full shrink-0">
                      <img src="{{ asset('elora-2/assets/' . $small['image']) }}" alt="{{ $small['name'] }}" class="absolute inset-0 h-full w-full object-cover" />
                      <span class="absolute top-0 right-0 text-[11px] font-medium px-[6px] py-[3px] rounded-bl-[8px]" style="background: var(--color-accent-yellow); color: var(--color-black)">70% Sold</span>
                      <button type="button" aria-label="Add to favorites" class="absolute top-[6px] left-[6px] bg-white rounded-full p-[5px] shadow">
                        <img src="{{ asset('elora-2/assets/icons/heart.svg') }}" class="size-[14px]" alt="" />
                      </button>
                      <div class="absolute bottom-[6px] right-[6px] bg-white rounded-[8px] p-[5px] shadow">
                        <img src="{{ asset('elora-2/assets/icons/cart.svg') }}" class="size-[16px]" alt="" />
                      </div>
                    </div>
                    <div class="flex flex-col gap-[4px] items-start p-[8px] w-full min-w-0">
                      <div class="flex items-center justify-between w-full">
                        <p class="font-medium text-[14px] truncate" style="color: var(--color-text-primary)">{{ $small['name'] }}</p>
                        <p class="text-[12px] shrink-0" style="color: {{ $small['weightColor'] }}">{{ $small['weight'] }}</p>
                      </div>
                      <div class="flex items-center gap-[4px]">
                        <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[7px] w-[48px]" />
                        <span class="text-[10px]" style="color: var(--color-text-subtitle)">{{ $small['rating'] }}</span>
                      </div>
                      <div class="flex items-end gap-[5px]">
                        <p class="font-medium text-[14px]" style="color: {{ $small['priceColor'] }}">{{ $small['price'] }}</p>
                        @if (!empty($small['oldPrice']))
                          <p class="text-[10px] line-through" style="color: var(--color-text-subtitle)">{{ $small['oldPrice'] }}</p>
                        @endif
                      </div>
                      <div class="flex gap-[4px] items-center w-full">
                        <img src="{{ asset('elora-2/assets/icons/truck-delivery.svg') }}" alt="" class="size-[16px]" />
                        <p class="font-medium text-[10px] whitespace-nowrap" style="color: var(--color-success)">Delivered by 24 March</p>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
              <div class="flex-1 flex bg-[var(--color-bg-main)] rounded-[6px] overflow-hidden shadow-[var(--shadow-card)]">
                <div class="relative w-[38%] shrink-0">
                  <img src="{{ asset('elora-2/assets/' . $bestSellerWide6['image']) }}" alt="{{ $bestSellerWide6['name'] }}" class="h-full w-full object-cover" />
                </div>
                <div class="flex-1 p-[8px] flex flex-col justify-center gap-[4px] min-w-0">
                  <p class="font-medium text-[14px] truncate" style="color: var(--color-text-primary)">{{ $bestSellerWide6['name'] }}</p>
                  <div class="flex items-center gap-[4px]">
                    <img src="{{ asset('elora-2/assets/icons/star-rating.svg') }}" alt="" class="h-[7px] w-[48px]" />
                    <span class="text-[10px]" style="color: var(--color-text-subtitle)">{{ $bestSellerWide6['rating'] }}</span>
                  </div>
                  <div class="flex items-end gap-[5px]">
                    <p class="font-medium text-[14px]" style="color: {{ $bestSellerWide6['priceColor'] }}">{{ $bestSellerWide6['price'] }}</p>
                    @if (!empty($bestSellerWide6['oldPrice']))
                      <p class="text-[10px] line-through" style="color: var(--color-text-subtitle)">{{ $bestSellerWide6['oldPrice'] }}</p>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <button id="bestSellerPrev" type="button" aria-label="Previous" class="swiper-nav-btn swiper-nav-prev">
      <img src="{{ asset('elora-2/assets/icons/arrow-down.svg') }}" class="size-[14px] rotate-90" alt="" />
    </button>
    <button id="bestSellerNext" type="button" aria-label="Next" class="swiper-nav-btn swiper-nav-next">
      <img src="{{ asset('elora-2/assets/icons/arrow-down.svg') }}" class="size-[14px] -rotate-90" alt="" />
    </button>
  </div>
</section>
