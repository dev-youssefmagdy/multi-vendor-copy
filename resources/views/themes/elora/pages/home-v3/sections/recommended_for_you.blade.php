@php
  $__ph = asset('elora-3/assets/images/product-placeholder.svg');
  $__base = [
    ['badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-error)'],
    ['badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeText' => 'var(--color-white)', 'name' => 'Headphone', 'weight' => '250g', 'desc' => 'Premium sound quality', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'urgency' => 'var(--color-error)'],
    ['badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Essential Hoodie', 'weight' => '200g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
    ['badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Pants', 'weight' => '150g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'urgency' => 'var(--color-success)'],
    ['badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Essential Shoes', 'weight' => '250g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+560)', 'price' => '$115.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
    ['badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeText' => 'var(--color-white)', 'name' => 'Classic Pants', 'weight' => '300g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+430)', 'price' => '$79.00', 'oldPrice' => '$99.00', 'discount' => '20% Off', 'urgency' => 'var(--color-error)'],
  ];
  $__recommended = array_map(fn ($p) => array_merge($p, ['image' => $__ph]), $__base);
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]">
  <div class="flex items-center justify-between">
    <h2 class="font-medium text-[22px] lg:text-[32px]" style="color: var(--color-text-primary)">Recommended For You</h2>
    <a href="#" class="text-[14px] lg:text-[20px] tracking-[0.5px]" style="color: var(--color-text-primary)">see all</a>
  </div>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[16px]">
    @foreach ($__recommended as $p)
      @include('themes.elora.pages.home-v3.sections.partials.product_card', ['p' => $p])
    @endforeach
  </div>
</section>
