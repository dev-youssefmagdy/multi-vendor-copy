@php
    use App\Models\Tenant\Setting;
    $promoBannerImageUrl = Setting::query()->where('name', 'promo_banner_image_url')->value('value') ?: null;
    $promoBannerLink = Setting::query()->where('name', 'promo_banner_link')->value('value') ?: '#';
    $promoBannerTitle = Setting::query()->where('name', 'promo_banner_title')->value('value') ?: null;
    $promoBannerSubtitle = Setting::query()->where('name', 'promo_banner_subtitle')->value('value') ?: null;
    $promoBannerCta = Setting::query()->where('name', 'promo_banner_cta_text')->value('value') ?: __('Shop Now');
@endphp

@if ($promoBannerImageUrl || $promoBannerTitle)
    <section class="max-w-[1280px] mx-auto px-4 mb-8">
        <a href="{{ $promoBannerLink }}"
            class="relative block rounded-2xl overflow-hidden bg-gray-900 min-h-[200px]">
            @if ($promoBannerImageUrl)
                <img src="{{ $promoBannerImageUrl }}" alt="{{ $promoBannerTitle }}"
                    class="w-full h-[220px] md:h-[280px] object-cover opacity-75" loading="lazy">
            @endif
            @if ($promoBannerTitle || $promoBannerSubtitle)
                <div class="absolute inset-0 flex flex-col justify-center px-6 md:px-10">
                    @if ($promoBannerTitle)
                        <h2 class="text-white text-2xl md:text-3xl font-extrabold mb-2">{{ $promoBannerTitle }}</h2>
                    @endif
                    @if ($promoBannerSubtitle)
                        <p class="text-white/80 text-sm md:text-base mb-5">{{ $promoBannerSubtitle }}</p>
                    @endif
                    <span class="inline-block w-fit bg-white text-gray-900 font-bold text-sm px-6 py-2.5 rounded-full">
                        {{ $promoBannerCta }}
                    </span>
                </div>
            @endif
        </a>
    </section>
@endif
