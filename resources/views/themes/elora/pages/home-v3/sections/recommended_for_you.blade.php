    @php
      $PLACEHOLDER_IMG = asset('elora-3/assets/images/product-placeholder.svg');
      $recommendedProducts = [
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeText' => 'var(--color-white)', 'name' => 'Headphone', 'weight' => '250g', 'desc' => 'Premium sound quality', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Pants', 'weight' => '150g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Essential Shoes', 'weight' => '250g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+560)', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeText' => 'var(--color-white)', 'name' => 'Classic Pants', 'weight' => '300g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+430)', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Headphone', 'weight' => '230g', 'desc' => 'Premium sound quality', 'rating' => '4.5 (+980)', 'price' => '$88.00', 'oldPrice' => '$105.00', 'discount' => '16% Off', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Zip Hoodie', 'weight' => '215g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+640)', 'price' => '$97.00', 'oldPrice' => '$115.00', 'discount' => '15% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Street Sneakers', 'weight' => '265g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+505)', 'price' => '$105.00', 'oldPrice' => '$125.00', 'discount' => '16% Off', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Cargo Pants', 'weight' => '320g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+330)', 'price' => '$82.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeText' => 'var(--color-white)', 'name' => 'Pullover Hoodie', 'weight' => '225g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+890)', 'price' => '$91.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
      ];
    @endphp
    <section
      class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          Recommended For You
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[20px] tracking-[0.5px]"
          style="color: var(--color-text-primary)"
          >see all</a
        >
      </div>
      <div
        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[16px]"
      >
        @foreach ($recommendedProducts as $p)
          <div>
            @include('themes.elora.pages.home-v3.sections.partials.product_card', ['p' => $p])
          </div>
        @endforeach
      </div>
    </section>
