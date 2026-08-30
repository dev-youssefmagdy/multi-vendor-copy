<!DOCTYPE html>
<html lang="{{ $currentLanguage?->code ?? app()->getLocale() }}"
    dir="{{ (($currentLanguage?->direction?->value ?? null) === 'rtl' || in_array($currentLanguage?->code ?? app()->getLocale(), ['ar', 'he', 'fa'])) ? 'rtl' : 'ltr' }}"
    data-locale-managed="server">

<head>
    <meta charset="UTF-8" />
    {{-- Logo fonts (text-logo builder) --}}
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
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @switch($storefrontThemeVariant?->key)
        @case('v2') <!-- Purple Edition -->
            @php
                $bodyClass= 'bg-[var(--color-bg-main)]';
                $headerKey = 'header-v2';
                $footerKey = 'footer-v2';
                $stylesKey = 'styles-v2';
                $scriptsKey = 'scripts-v2';
            @endphp
            @break
        @case('v3') <!-- Fresh Edition -->
            @php
                $bodyClass= 'bg-[var(--color-bg-main)]';
                $headerKey = 'header-v3';
                $footerKey = 'footer-v3';
                $stylesKey = 'styles-v3';
                $scriptsKey = 'scripts-v3';
            @endphp
            @break
        @case('v4') <!-- Bold Edition -->
            @php
                $bodyClass= 'bg-[var(--color-bg-main)]';
                $headerKey = 'header-v4';
                $footerKey = 'footer-v4';
                $stylesKey = 'styles-v4';
                $scriptsKey = 'scripts-v4';
            @endphp
            @break
        @case('v5') <!-- Minimal Edition -->
            @php
                $bodyClass= 'bg-[var(--color-bg-main)]';
                $headerKey = 'header-v5';
                $footerKey = 'footer-v5';
                $stylesKey = 'styles-v5';
                $scriptsKey = 'scripts-v5';
            @endphp
            @break
        @case('v6') <!-- New In Edition -->
            @php
                $bodyClass= 'bg-[var(--color-bg-main)] pb-[80px] lg:pb-0';
                $headerKey = 'header-v6';
                $footerKey = 'footer-v6';
                $stylesKey = 'styles-v6';
                $scriptsKey = 'scripts-v6';
            @endphp
            @break
        @default
            @php
                $bodyClass= 'bg-gray-50';
                $headerKey = 'header';
                $footerKey = 'footer';
                $stylesKey = 'styles';
                $scriptsKey = 'scripts';
            @endphp
    @endswitch

    @livewireStyles
    @include('themes.elora.layout.' . $stylesKey)
    @include('storefront.partials.tracking-scripts')
    @stack('head')
</head>

<body @stack('body-attrs') data-page="index" class="{{ $bodyClass }}">

    @if (\App\Services\Preview\PreviewOverrides::active())
        <x-preview-banner />
    @endif

    {{-- Page preloader: shown until all scripts/styles/fonts are loaded. Added aria-hidden so screen readers ignore it --}}
    <div id="page-preloader" aria-hidden="true"
        style="position:fixed;top:0;left:0;width:100%;height:100%;background:#fff;display:flex;align-items:center;justify-content:center;z-index:99999;transition:opacity .3s ease;">
        <div
            style="width:40px;height:40px;border:3px solid #e5e7eb;border-top:3px solid #242424;border-radius:50%;animation:elora-spin .75s linear infinite;">
        </div>
    </div>
    <style>
        @keyframes elora-spin {
            to {
                transform: rotate(360deg)
            }
        }
    </style>
    <script>
        (function () {
            function revealPage() {
                var el = document.getElementById('page-preloader');
                if (el) { el.style.opacity = '0'; setTimeout(function () { el.style.display = 'none'; }, 320); }
            }
            if (document.readyState === 'complete') {
                revealPage();
            } else {
                window.addEventListener('load', revealPage);
                setTimeout(revealPage, 6000);
            }
        })();
    </script>

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


    @include('themes.elora.partials.' . $headerKey, ['categories' => $categories, 'logoPath' => $logoPath, 'storeName' => $storeName, 'cartCount' => $cartCount, 'rootCategories' => $rootCategories, 'socialLinks' => $socialLinks])

    {{ $slot }}

    @include('themes.elora.partials.' . $footerKey, ['categories' => $categories, 'logoPath' => $logoPath, 'storeName' => $storeName, 'socialLinks' => $socialLinks, 'cartCount' => $cartCount])
    {{-- Path-based tenancy: override Livewire's update URI so component updates
    are routed through InitializeTenancyBySlug on the central domain.
    The hidden element must appear before @livewireScripts in the DOM so
    Livewire's querySelector("[data-update-uri]") picks it up first. --}}
    @if(config('tenancy.path_tenant_slug'))
        <div data-update-uri="{{ url('/s/' . config('tenancy.path_tenant_slug') . '/livewire/update') }}"
            style="display:none" aria-hidden="true"></div>
    @endif
    @livewireScripts
    @include('themes.elora.layout.' . $scriptsKey)
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
