<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Helpers\TenantNavigation::direction() }}"
      data-theme="{{ in_array(request()->cookie('tenant_theme'), ['light', 'dark'], true) ? request()->cookie('tenant_theme') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="tenant-broadcast-auth" content="{{ route('tenant.broadcasting.auth') }}">
    <meta name="tenant-login-url" content="{{ route('tenant.login') }}">
    <meta name="tenant-id" content="{{ tenant('id') }}">
    @php($pageTitle = html_entity_decode(trim($__env->yieldContent('title', 'Dashboard')), ENT_QUOTES | ENT_HTML5))
    <title>{{ __($pageTitle) }} · {{ tenant('name') ? tenant('name').' '.__('Vendor Panel') : __('Vendor Panel') }}</title>
    {{-- Restore the collapsed (icon rail) sidebar before first paint, so it doesn't flash open --}}
    <script>
        try {
            if (localStorage.getItem('tenant-sb-collapsed') === '1') document.documentElement.classList.add('sb-rail');
        } catch (e) {}
    </script>
    @include('tenant.layouts.partials.js-i18n')
    @vite(['resources/css/tenant/app.css', 'resources/js/tenant/app.js'])
    @stack('tenant-vite')
</head>
<body class="t-scope">
    @include('tenant.layouts.partials.flash')
    <div id="ov" data-action="close-mobile"></div>
    @include('tenant.layouts.partials.sidebar')
    @include('tenant.layouts.partials.header')
    @include('tenant.layouts.partials.compliance-modal')
    <main id="mn">
        @include('tenant.layouts.partials.setup-banner')
        @yield('content')
    </main>
    @include('tenant.layouts.partials.mobile-nav')
</body>
</html>
