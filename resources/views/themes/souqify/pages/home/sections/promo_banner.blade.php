@php
    $__promoSettings = app(\App\Services\Tenant\HomeViewData::class)->settings([
        'promo_banner_image_url',
        'promo_banner_mobile_image_url',
        'promo_banner_link',
        'promo_banner_title',
        'promo_banner_subtitle',
        'promo_banner_cta_text',
    ]);
    $promoBannerImageUrl = $__promoSettings['promo_banner_image_url'] ?: null;
    $promoBannerMobileImageUrl = $__promoSettings['promo_banner_mobile_image_url'] ?: null;
    $promoBannerLink = $__promoSettings['promo_banner_link'] ?: '#';
    $promoBannerTitle = $__promoSettings['promo_banner_title'] ?: null;
    $promoBannerSubtitle = $__promoSettings['promo_banner_subtitle'] ?: null;
    $promoBannerCta = $__promoSettings['promo_banner_cta_text'] ?: __('Shop Now');
@endphp

@if ($promoBannerImageUrl || $promoBannerMobileImageUrl || $promoBannerTitle)
    <section class="px-5 mb-7">
        <a href="{{ $promoBannerLink }}"
            class="relative block rounded-[20px] overflow-hidden bg-[#0f172a] min-h-[220px] max-w-[1280px] mx-auto">
            @if ($promoBannerImageUrl || $promoBannerMobileImageUrl)
                <picture>
                    @if ($promoBannerMobileImageUrl)
                        <source media="(max-width: 767px)" srcset="{{ $promoBannerMobileImageUrl }}">
                    @endif
                    <img src="{{ $promoBannerImageUrl ?? $promoBannerMobileImageUrl }}" alt="{{ $promoBannerTitle }}"
                        class="w-full h-[280px] object-cover opacity-65" loading="lazy">
                </picture>
            @endif
            @if ($promoBannerTitle || $promoBannerSubtitle)
                <div class="absolute inset-0 flex flex-col justify-center px-9">
                    @if ($promoBannerTitle)
                        <h2 class="text-white text-[30px] font-extrabold mb-2.5">{{ $promoBannerTitle }}</h2>
                    @endif
                    @if ($promoBannerSubtitle)
                        <p class="text-white/75 text-[15px] mb-5">{{ $promoBannerSubtitle }}</p>
                    @endif
                    <span class="inline-block w-fit bg-[#004AC6] text-white font-bold text-sm px-7 py-3 rounded-xl">
                        {{ $promoBannerCta }}
                    </span>
                </div>
            @endif
        </a>
    </section>
@endif
