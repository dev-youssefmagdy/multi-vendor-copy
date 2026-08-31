{{-- ═══════════════════════════════════════════════════════════════════
     HERO BANNER — Elora v4
     Desktop: orange left panel (w=548px) + full-bleed photo (flex-1)
     Mobile:  orange left panel (flex-1) + cropped photo (w=142px, h=210px)
     Figma desktop node: 16:10508 | mobile node: 16:7409
═══════════════════════════════════════════════════════════════════ --}}
<section class="relative">

  <div class="swiper hero-swiper">
    <div class="swiper-wrapper">

      {{-- ── DYNAMIC BANNERS ────────────────────────────────────────── --}}
      @forelse ($banners as $banner)
        @php
          $img = $banner->image_path ?? null;
          $img = $img
            ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
            : asset('elora-4/assets/images/hero-woman.png');
          $bTag        = null;
          $bTitle      = $banner->translationValue('title')       ?? $storeName;
          $bSubtitle   = $banner->translationValue('subtitle');
          $bButtonText = $banner->translationValue('button_text') ?? __('Shop Now');
          $bUrl        = $banner->url ?? route('tenant.home');
        @endphp
        <div class="swiper-slide">
          @include('themes.elora.pages.home-v4.sections.partials.hero_slide', [
            'img'         => $img,
            'tag'         => $bTag,
            'title'       => $bTitle,
            'subtitle'    => $bSubtitle,
            'buttonText'  => $bButtonText,
            'buttonUrl'   => $bUrl,
          ])
        </div>
      @empty

        {{-- ── FALLBACK DEFAULT SLIDE ──────────────────────────────── --}}
        <div class="swiper-slide">
          @include('themes.elora.pages.home-v4.sections.partials.hero_slide', [
            'img'        => asset('elora-4/assets/images/hero-woman.png'),
            'tag'        => __('NEW USER'),
            'title'      => __('Explore New') . "\n" . __('Products'),
            'subtitle'   => __('New user exclusive deal'),
            'buttonText' => __('Shop Now'),
            'buttonUrl'  => route('tenant.home'),
          ])
        </div>

      @endforelse
    </div>{{-- /.swiper-wrapper --}}

    {{-- Pagination dots --}}
    <div class="hero-pagination"></div>
  </div>{{-- /.swiper --}}

</section>
