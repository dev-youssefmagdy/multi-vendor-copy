<!DOCTYPE html>
<html lang="{{ $currentLanguage?->code ?? app()->getLocale() }}"
    dir="{{ (($currentLanguage?->direction?->value ?? null) === 'rtl' || in_array($currentLanguage?->code ?? app()->getLocale(), ['ar', 'he', 'fa'])) ? 'rtl' : 'ltr' }}"
    data-locale-managed="server">

<head>
    <meta charset="UTF-8" />
    {{-- Logo fonts (text-logo builder) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&family=Amiri:wght@400;700&family=Bebas+Neue&family=Cairo:wght@400;700&family=Cormorant+Garamond:wght@400;700&family=DM+Sans:wght@400;700&family=IBM+Plex+Sans+Arabic:wght@400;700&family=Inter:wght@400;700&family=Lalezar&family=Lemonada:wght@400;700&family=Lora:wght@400;700&family=Montserrat:wght@400;700&family=Noto+Sans+Arabic:wght@400;700&family=Nunito:wght@400;700&family=Oswald:wght@400;700&family=Playfair+Display:wght@400;700&family=Poppins:wght@400;700&family=Raleway:wght@400;700&family=Readex+Pro:wght@400;700&family=Reem+Kufi:wght@400;700&family=Scheherazade+New:wght@400;700&family=Space+Grotesk:wght@400;700&family=Tajawal:wght@400;700&display=swap">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? $storeName ?? config('app.name') }}</title>
    <meta name="description" content="{{ $metaDescription ?? '' }}">
    @if(!empty($canonicalUrl))
    <link rel="canonical" href="{{ $canonicalUrl }}" />
    @endif
    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}" />
    <meta property="og:title" content="{{ $title ?? $storeName ?? config('app.name') }}" />
    <meta property="og:description" content="{{ $metaDescription ?? '' }}" />
    <meta property="og:url" content="{{ $canonicalUrl ?? request()->url() }}" />
    <meta property="og:site_name" content="{{ $storeName ?? config('app.name') }}" />
    @if(!empty($ogImage))
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta property="og:image:alt" content="{{ $title ?? $storeName ?? config('app.name') }}" />
    @endif
    @if(($ogType ?? '') === 'product')
    <meta property="og:availability" content="{{ $ogAvailability ?? 'in stock' }}" />
    @if(!empty($ogBrand))
    <meta property="og:brand" content="{{ $ogBrand }}" />
    @endif
    @if(!empty($ogPrice) && !empty($ogCurrencyCode))
    <meta property="product:price:amount" content="{{ $ogPrice }}" />
    <meta property="product:price:currency" content="{{ $ogCurrencyCode }}" />
    @endif
    @endif
    {{-- Twitter Card --}}
    <meta name="twitter:card" content="{{ !empty($ogImage) ? 'summary_large_image' : 'summary' }}" />
    <meta name="twitter:title" content="{{ $title ?? $storeName ?? config('app.name') }}" />
    <meta name="twitter:description" content="{{ $metaDescription ?? '' }}" />
    @if(!empty($ogImage))
    <meta name="twitter:image" content="{{ $ogImage }}" />
    <meta name="twitter:image:alt" content="{{ $title ?? $storeName ?? config('app.name') }}" />
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @livewireStyles
    @include('themes.nexo.layout.styles')
    @include('storefront.partials.tracking-scripts')
    @stack('head')
</head>


<body @stack('body-attrs') data-page="index" class="bg-gray-50">

    @if (\App\Services\Preview\PreviewOverrides::active())
        <x-preview-banner />
    @endif

    {{-- Page preloader: shown until all scripts/styles/fonts are loaded. Added aria-hidden so screen readers ignore it --}}
    <div id="page-preloader" aria-hidden="true"
        style="position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;display:flex;align-items:center;justify-content:center;z-index:99999;transition:opacity .3s ease;">
        <div
            style="width:40px;height:40px;border:3px solid #e5e7eb;border-top:3px solid #242424;border-radius:50%;animation:nexo-spin .75s linear infinite;">
        </div>
    </div>
    <style>
        @keyframes nexo-spin {
            to {
                transform: rotate(360deg)
            }
        }
    </style>

    @php
        $storefrontFlashMessages = [];

        foreach (['success', 'error', 'warning', 'info'] as $flashType) {
            if (session()->has($flashType)) {
                $storefrontFlashMessages[] = [
                    'message' => (string) session($flashType),
                    'type' => $flashType,
                ];
            }
        }

        if (session()->has('status')) {
            $storefrontFlashMessages[] = [
                'message' => (string) session('status'),
                'type' => (string) session('status_type', 'success'),
            ];
        }

        if (session('payment_success')) {
            $storefrontFlashMessages[] = [
                'message' => __('Payment completed successfully.'),
                'type' => 'success',
            ];
        }

        if (session('payment_cancelled')) {
            $storefrontFlashMessages[] = [
                'message' => __('Payment was cancelled.'),
                'type' => 'warning',
            ];
        }

        foreach ($errors->all() as $message) {
            $storefrontFlashMessages[] = [
                'message' => (string) $message,
                'type' => 'error',
            ];
        }
    @endphp

    @if ($storefrontFlashMessages !== [])
        <script id="storefront-flash-messages" type="application/json">
            @json($storefrontFlashMessages)
        </script>
    @endif

    @include('themes.nexo.partials.header', ['categories' => $categories, 'logoPath' => $logoPath, 'storeName' => $storeName, 'cartCount' => $cartCount, 'rootCategories' => $rootCategories, 'socialLinks' => $socialLinks])

    {{ $slot }}

    @include('themes.nexo.partials.footer', ['categories' => $categories, 'logoPath' => $logoPath, 'storeName' => $storeName, 'socialLinks' => $socialLinks, 'cartCount' => $cartCount])
    <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    {{-- Path-based tenancy: override Livewire's update URI so component updates
    are routed through InitializeTenancyBySlug on the central domain.
    The hidden element must appear before @livewireScripts in the DOM so
    Livewire's querySelector("[data-update-uri]") picks it up first. --}}
    @if(config('tenancy.path_tenant_slug'))
        <div data-update-uri="{{ url('/s/' . config('tenancy.path_tenant_slug') . '/livewire/update') }}"
            style="display:none" aria-hidden="true"></div>
    @endif
    @livewireScripts
    @include('themes.nexo.layout.scripts')
    @stack('scripts')

    {{-- Variant selection modal --}}
    <div id="variant-select-modal" class="fixed inset-0 z-[9998] items-center justify-center"
        style="display:none; background:rgba(0,0,0,0.5);">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                <h3 id="variant-modal-title" class="text-[15px] font-semibold text-[#242424] pr-4">
                    {{ __('Select a variant') }}
                </h3>
                <button onclick="closeVariantModal()" aria-label="{{ __('Close') }}"
                    class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100 shrink-0">
                    <svg class="w-4 h-4 text-[#555]" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="px-5 py-4">
                <div id="variant-modal-list" class="flex flex-col gap-2 max-h-60 overflow-y-auto"></div>
            </div>
            <div class="px-5 pb-5 flex gap-3">
                <button onclick="closeVariantModal()"
                    class="flex-1 py-2 rounded-lg border border-gray-200 text-[14px] text-[#555] hover:bg-gray-50 transition">
                    {{ __('Cancel') }}
                </button>
                <button id="variant-modal-confirm"
                    class="flex-1 py-2 rounded-lg bg-[#111827] text-white text-[14px] hover:opacity-90 transition">
                    {{ __('Add to Cart') }}
                </button>
            </div>
        </div>
    </div>

    <livewire:tenant.storefront.layout.cart-manager />

</body>

</html>
