    @php
      $PLACEHOLDER_IMG = asset('elora-3/assets/images/product-placeholder.svg');
      $newInBadge = ['bg' => 'var(--color-accent-yellow)', 'text' => 'var(--color-text-primary)'];
      $newInBadge2 = ['bg' => 'var(--color-accent-purple)', 'text' => 'var(--color-white)'];
      $newInProducts = [
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => $newInBadge['bg'], 'badgeText' => $newInBadge['text'], 'name' => 'Essential Shoes', 'weight' => '250g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => $newInBadge2['bg'], 'badgeText' => $newInBadge2['text'], 'name' => 'Everyday Hoodie', 'weight' => '210g', 'desc' => 'Premium cotton blend', 'rating' => '4.5 (+620)', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => $newInBadge['bg'], 'badgeText' => $newInBadge['text'], 'name' => 'Runner Sneakers', 'weight' => '260g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+1.2k)', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => $newInBadge2['bg'], 'badgeText' => $newInBadge2['text'], 'name' => 'Tailored Pants', 'weight' => '310g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+430)', 'price' => '$84.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => $newInBadge['bg'], 'badgeText' => $newInBadge['text'], 'name' => 'Fleece Hoodie', 'weight' => '220g', 'desc' => 'Premium cotton blend', 'rating' => '4.3 (+710)', 'price' => '$99.00', 'oldPrice' => '$120.00', 'discount' => '18% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => $newInBadge2['bg'], 'badgeText' => $newInBadge2['text'], 'name' => 'Trail Sneakers', 'weight' => '270g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+560)', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => $newInBadge['bg'], 'badgeText' => $newInBadge['text'], 'name' => 'Cargo Pants', 'weight' => '320g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+330)', 'price' => '$82.00', 'oldPrice' => '$98.00', 'discount' => '16% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => $newInBadge2['bg'], 'badgeText' => $newInBadge2['text'], 'name' => 'Zip Hoodie', 'weight' => '215g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+640)', 'price' => '$97.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => $newInBadge['bg'], 'badgeText' => $newInBadge['text'], 'name' => 'Street Sneakers', 'weight' => '265g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+505)', 'price' => '$105.00', 'oldPrice' => '$125.00', 'discount' => '16% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => $newInBadge['bg'], 'badgeText' => $newInBadge['text'], 'name' => 'Essential Shoes', 'weight' => '250g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => $newInBadge2['bg'], 'badgeText' => $newInBadge2['text'], 'name' => 'Everyday Hoodie', 'weight' => '210g', 'desc' => 'Premium cotton blend', 'rating' => '4.5 (+620)', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => '70% Sold', 'badgeBg' => $newInBadge['bg'], 'badgeText' => $newInBadge['text'], 'name' => 'Runner Sneakers', 'weight' => '260g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+1.2k)', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'urgency' => 'var(--color-error)'],
        ['image' => $PLACEHOLDER_IMG, 'badge' => 'New In', 'badgeBg' => $newInBadge2['bg'], 'badgeText' => $newInBadge2['text'], 'name' => 'Tailored Pants', 'weight' => '310g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+430)', 'price' => '$84.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
      ];
    @endphp
    <section
      class="pattern-newin px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px] mt-12"
    >
      <div class="flex items-center justify-between">
        <h2
          class="font-medium text-[22px] lg:text-[32px]"
          style="color: var(--color-text-primary)"
        >
          New In
        </h2>
        <a
          href="#"
          class="text-[14px] lg:text-[24px] tracking-[0.5px]"
          style="color: var(--color-text-primary)"
          >see all</a
        >
      </div>
      <div class="relative">
        <div class="swiper card-swiper newin-swiper" id="newInSwiper">
          <div class="swiper-wrapper">
            @foreach ($newInProducts as $p)
              @include('themes.elora.pages.home-v3.sections.partials.new_in_card', ['p' => $p])
            @endforeach
          </div>
        </div>
      </div>
    </section>
