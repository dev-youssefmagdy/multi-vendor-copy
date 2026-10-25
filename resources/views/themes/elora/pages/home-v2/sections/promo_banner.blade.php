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
    <section class="px-4 mb-6" wire:ignore>
        <a href="{{ $promoBannerLink }}"
            class="relative block rounded-2xl overflow-hidden bg-black min-h-[200px] max-w-[1280px] mx-auto">
            @if ($promoBannerImageUrl || $promoBannerMobileImageUrl)
                <picture>
                    @if ($promoBannerMobileImageUrl)
                        <source media="(max-width: 767px)" srcset="{{ $promoBannerMobileImageUrl }}">
                    @endif
                    <img src="{{ $promoBannerImageUrl ?? $promoBannerMobileImageUrl }}" alt="{{ $promoBannerTitle }}"
                        class="w-full h-[260px] object-cover opacity-75" loading="lazy">
                </picture>
            @endif
            @if ($promoBannerTitle || $promoBannerSubtitle)
                <div class="absolute inset-0 flex flex-col justify-center px-8">
                    @if ($promoBannerTitle)
                        <h2 class="text-white text-2xl md:text-[28px] font-extrabold mb-2">{{ $promoBannerTitle }}</h2>
                    @endif
                    @if ($promoBannerSubtitle)
                        <p class="text-white/80 text-sm md:text-[15px] mb-5">{{ $promoBannerSubtitle }}</p>
                    @endif
                    <span class="inline-block w-fit font-bold text-sm px-6 py-2.5 rounded-full"
                          style="background:#8A38F5;color:#fff;">
                        {{ $promoBannerCta }}
                    </span>
                </div>
            @endif
        </a>
    </section>
@endif
