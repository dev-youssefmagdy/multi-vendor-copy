{{-- No direct counterpart in the elora-3 mockup; built from the same product-card language with a pill-tab switcher --}}
@php
  $__ph = asset('elora-3/assets/images/product-placeholder.svg');
  $__tabs = ['All', 'Fashion', 'Electronics', 'Accessories'];
  $__base = [
    ['badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Everyday Hoodie', 'weight' => '210g', 'desc' => 'Premium cotton blend', 'rating' => '4.5 (+620)', 'price' => '$95.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
    ['badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Runner Sneakers', 'weight' => '260g', 'desc' => 'Premium cotton blend', 'rating' => '4.6 (+1.2k)', 'price' => '$110.00', 'oldPrice' => '$130.00', 'discount' => '15% Off', 'urgency' => 'var(--color-error)'],
    ['badge' => '30% OFF', 'badgeBg' => 'var(--color-primary)', 'badgeText' => 'var(--color-white)', 'name' => 'Headphone', 'weight' => '250g', 'desc' => 'Premium sound quality', 'rating' => '4.2 (+850)', 'price' => '$89.00', 'oldPrice' => '$89.00', 'discount' => '20% Off', 'urgency' => 'var(--color-error)'],
    ['badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Tailored Pants', 'weight' => '310g', 'desc' => 'Premium cotton blend', 'rating' => '4.1 (+430)', 'price' => '$84.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
    ['badge' => '70% Sold', 'badgeBg' => 'var(--color-accent-yellow)', 'badgeText' => 'var(--color-text-primary)', 'name' => 'Street Sneakers', 'weight' => '265g', 'desc' => 'Premium cotton blend', 'rating' => '4.2 (+505)', 'price' => '$105.00', 'oldPrice' => '$125.00', 'discount' => '16% Off', 'urgency' => 'var(--color-error)'],
    ['badge' => 'New In', 'badgeBg' => 'var(--color-accent-purple)', 'badgeText' => 'var(--color-white)', 'name' => 'Zip Hoodie', 'weight' => '215g', 'desc' => 'Premium cotton blend', 'rating' => '4.4 (+640)', 'price' => '$97.00', 'oldPrice' => null, 'discount' => null, 'urgency' => 'var(--color-success)'],
  ];
  $__products = array_map(fn ($p) => array_merge($p, ['image' => $__ph]), $__base);
@endphp
<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px] flex flex-col gap-[16px] lg:gap-[24px]">
  <h2 class="font-medium text-[22px] lg:text-[32px] text-center" style="color: var(--color-text-primary)">Shop the Look</h2>
  <div class="flex items-center justify-center gap-[8px] lg:gap-[12px] overflow-x-auto no-scrollbar">
    @foreach ($__tabs as $__i => $__tab)
      <button
        type="button"
        class="tabbed-pill shrink-0 rounded-full px-[16px] lg:px-[24px] py-[8px] lg:py-[12px] font-medium text-[13px] lg:text-[16px] tracking-[0.5px] border transition-colors {{ $__i === 0 ? 'active' : '' }}"
        style="{{ $__i === 0 ? 'background: var(--color-brand-pink); border-color: var(--color-brand-pink); color: #fff;' : 'border-color: var(--color-brand-pink); color: var(--color-brand-pink);' }}"
      >{{ $__tab }}</button>
    @endforeach
  </div>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-[12px] lg:gap-[16px]">
    @foreach ($__products as $p)
      @include('themes.elora.pages.home-v3.sections.partials.product_card', ['p' => $p])
    @endforeach
  </div>
</section>
