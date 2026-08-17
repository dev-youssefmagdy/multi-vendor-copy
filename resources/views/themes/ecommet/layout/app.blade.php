<!DOCTYPE html>
<html lang="{{ $currentLanguage?->code ?? app()->getLocale() }}"
    dir="{{ (($currentLanguage?->direction?->value ?? null) === 'rtl' || in_array($currentLanguage?->code ?? app()->getLocale(), ['ar', 'he', 'fa'])) ? 'rtl' : 'ltr' }}"
    data-locale-managed="server">

<head>
    {{-- Logo fonts (text-logo builder) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&family=Amiri:wght@400;700&family=Cairo:wght@400;700&family=Montserrat:wght@400;700&family=Oswald:wght@400;700&family=Playfair+Display:wght@400;700&family=Poppins:wght@400;700&family=Tajawal:wght@400;700&display=swap">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @livewireStyles
    @include('themes.ecommet.layout.styles')
    @include('storefront.partials.tracking-scripts')
    @stack('head')
</head>

<body
    class="bg-gray-lightest text-gray-dark font-main antialiased min-h-screen flex flex-col overflow-x-hidden w-full pb-14 lg:pb-0">

    @if (\App\Services\Preview\PreviewOverrides::active())
        <x-preview-banner />
    @endif

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
        <script id="storefront-flash-messages" type="application/json">@json($storefrontFlashMessages)</script>
    @endif

    {{-- PERFORMANCE FIX: Replaced external resource-heavy .gif preloader with inline CSS spinner --}}
    <div class="request-loader fixed inset-0 z-[9999] bg-white flex items-center justify-center transition-opacity duration-300" id="preloader">
        <div class="w-10 h-10 border-4 border-gray-200 border-t-black rounded-full animate-spin"></div>
    </div>

    @include('themes.ecommet.partials.header', ['categories' => $categories, 'rootCategories' => $rootCategories, 'logoPath' => $logoPath, 'storeName' => $storeName])

    {{ $slot }}

    @include('themes.ecommet.partials.footer', ['categories' => $categories, 'rootCategories' => $rootCategories, 'logoPath' => $logoPath, 'socialLinks' => $socialLinks, 'storeName' => $storeName, 'footerText' => $footerText, 'footerCopyright' => $footerCopyright])

    @if(config('tenancy.path_tenant_slug'))
        <div data-update-uri="{{ url('/s/' . config('tenancy.path_tenant_slug') . '/livewire/update') }}"
            style="display:none" aria-hidden="true"></div>
    @endif
    @livewireScripts
    @include('themes.ecommet.layout.scripts')
    @stack('scripts')

    {{-- Variant selection modal --}}
    <div id="variant-select-modal" class="fixed inset-0 z-[9998] items-center justify-center"
        style="display:none; background:rgba(0,0,0,0.5);">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#e8e8e8]">
                <h3 id="variant-modal-title" class="text-[15px] font-semibold text-[#242424] pr-4">
                    {{ __('Select a variant') }}
                </h3>
                <button onclick="closeVariantModal()"
                    class="w-7 h-7 flex items-center justify-center rounded-full hover:bg-gray-100 shrink-0">
                    <svg class="w-4 h-4 text-[#555]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="flex-1 py-2 rounded-lg border border-[#e5e5e5] text-[14px] text-[#555] hover:bg-gray-50 transition">
                    {{ __('Cancel') }}
                </button>
                <button id="variant-modal-confirm"
                    class="flex-1 py-2 rounded-lg bg-[#de1709] text-white text-[14px] hover:opacity-90 transition">
                    {{ __('Add to Cart') }}
                </button>
            </div>
        </div>
    </div>

    <livewire:tenant.storefront.layout.cart-manager />

</body>
</html>
