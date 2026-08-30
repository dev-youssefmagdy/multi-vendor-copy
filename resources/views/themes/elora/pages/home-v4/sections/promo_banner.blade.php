@php
    use App\Models\Tenant\Setting;
    $promoBannerImageUrl = Setting::query()->where('name', 'promo_banner_image_url')->value('value') ?: null;
    $promoBannerLink = Setting::query()->where('name', 'promo_banner_link')->value('value') ?: '#';
    $promoBannerTitle = Setting::query()->where('name', 'promo_banner_title')->value('value') ?: 'New Season Arrivals';
    $promoBannerSubtitle = Setting::query()->where('name', 'promo_banner_subtitle')->value('value') ?: 'Discover what\'s trending this week';
    $promoBannerCta = Setting::query()->where('name', 'promo_banner_cta_text')->value('value') ?: __('Shop Now');
@endphp

<section class="px-[16px] lg:px-[56px] py-[24px] lg:py-[32px]">
    <a
        href="{{ $promoBannerLink }}"
        class="relative block rounded-[16px] lg:rounded-[20px] overflow-hidden min-h-[200px] lg:min-h-[280px] max-w-[1328px] mx-auto"
        style="background: var(--color-brand-orange-bright)"
    >
        <img
            src="{{ $promoBannerImageUrl ?: asset('elora-4/assets/images/hero-woman.png') }}"
            alt="{{ $promoBannerTitle }}"
            class="absolute inset-0 h-full w-full object-cover opacity-85"
            loading="lazy"
        >
        <div
            class="absolute inset-0"
            style="background: linear-gradient(90deg, var(--color-brand-orange-bright) 30%, rgba(0,0,0,0) 80%);"
        ></div>
        <div class="relative flex flex-col justify-center h-full px-[24px] lg:px-[56px] py-[32px] gap-[14px] lg:gap-[20px] max-w-[420px]">
            <h2 class="text-white text-[24px] lg:text-[40px] font-black leading-[1.1]">{{ $promoBannerTitle }}</h2>
            <p class="text-white/85 text-[14px] lg:text-[20px]">{{ $promoBannerSubtitle }}</p>
            <span
                class="inline-flex items-center justify-center w-fit font-bold text-[14px] lg:text-[18px] px-[24px] lg:px-[32px] h-[42px] lg:h-[56px] rounded-full bg-white"
                style="color: var(--color-brand-orange)"
            >
                {{ $promoBannerCta }}
            </span>
        </div>
    </a>
</section>
