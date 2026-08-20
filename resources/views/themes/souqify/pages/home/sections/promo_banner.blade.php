@php
    use App\Models\Tenant\Setting;
    $promoBannerImageUrl = Setting::query()->where('name', 'promo_banner_image_url')->value('value') ?: null;
    $promoBannerLink = Setting::query()->where('name', 'promo_banner_link')->value('value') ?: '#';
    $promoBannerTitle = Setting::query()->where('name', 'promo_banner_title')->value('value') ?: null;
    $promoBannerSubtitle = Setting::query()->where('name', 'promo_banner_subtitle')->value('value') ?: null;
    $promoBannerCta = Setting::query()->where('name', 'promo_banner_cta_text')->value('value') ?: __('Shop Now');
@endphp

@if ($promoBannerImageUrl || $promoBannerTitle)
    <section class="px-5 mb-7">
        <a href="{{ $promoBannerLink }}"
            class="relative block rounded-[20px] overflow-hidden bg-[#0f172a] min-h-[220px] max-w-[1280px] mx-auto">
            @if ($promoBannerImageUrl)
                <img src="{{ $promoBannerImageUrl }}" alt="{{ $promoBannerTitle }}"
                    class="w-full h-[280px] object-cover opacity-65" loading="lazy">
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
