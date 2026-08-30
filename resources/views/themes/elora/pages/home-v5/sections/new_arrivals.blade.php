    @php
      $newInProducts = [
        ['name' => 'Essential Shoes', 'weight' => '250g', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'rating' => '4.2 (+850)', 'ordered' => '5 ordered last 30 min', 'progress' => 83, 'stock' => 'Only 5 left'],
        ['name' => 'Court Sneakers', 'weight' => '255g', 'price' => '$92.00', 'oldPrice' => '$110.00', 'discount' => '16% Off', 'rating' => '4.3 (+390)', 'ordered' => '8 ordered last 30 min', 'progress' => 64, 'stock' => 'Only 4 left'],
        ['name' => 'Wireless Earbuds', 'weight' => '60g', 'price' => '$59.00', 'oldPrice' => '$79.00', 'discount' => '25% Off', 'rating' => '4.5 (+920)', 'ordered' => '12 ordered last 30 min', 'progress' => 72, 'stock' => 'Only 7 left'],
        ['name' => 'Classic Watch', 'weight' => '90g', 'price' => '$149.00', 'oldPrice' => '$179.00', 'discount' => '17% Off', 'rating' => '4.6 (+1.2k)', 'ordered' => '3 ordered last 30 min', 'progress' => 40, 'stock' => 'Only 9 left'],
      ];
    @endphp
    <section class="newin-bg ps-[16px] lg:ps-[56px] py-[24px] lg:py-[38px] flex flex-col gap-[16px] lg:gap-[24px] mt-12">
      <img src="{{ asset('elora-5/assets/images/new-in-texture.png') }}" alt="" class="newin-texture" />
      <div class="relative flex items-center justify-between">
        <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-black)">New In</h2>
        <a href="#" class="font-normal text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-primary)">see all</a>
      </div>
      <div class="relative">
        <div class="swiper card-swiper newin-swiper">
          <div class="swiper-wrapper" id="newInWrapper">
            @foreach ($newInProducts as $product)
              <div class="swiper-slide h-auto !w-[280px] lg:!w-[430px]">
                @include('themes.elora.pages.home-v5.sections.partials.wide_card', ['p' => $product])
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
