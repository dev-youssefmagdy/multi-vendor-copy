<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Helpers\TenantNavigation::direction() }}"
      data-theme="{{ in_array(request()->cookie('tenant_theme'), ['light', 'dark'], true) ? request()->cookie('tenant_theme') : 'dark' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="tenant-broadcast-auth" content="{{ route('tenant.broadcasting.auth') }}">
    <meta name="tenant-login-url" content="{{ route('tenant.login') }}">
    <meta name="tenant-id" content="{{ tenant('id') }}">
    <title>@yield('title', 'Dashboard') · {{ tenant('name') ? tenant('name').' Vendor Panel' : 'Vendor Panel' }}</title>
    @vite(['resources/css/tenant/app.css', 'resources/js/tenant/app.js'])
    @stack('tenant-vite')
</head>
<body class="t-scope">
    @include('tenant.layouts.partials.flash')
    <div id="ov" data-action="close-mobile"></div>
    @include('tenant.layouts.partials.sidebar')
    @include('tenant.layouts.partials.header')
    @include('tenant.layouts.partials.compliance-modal')
    @include('tenant.layouts.partials.setup-banner')
    <main id="mn">@yield('content')</main>
</body>
</html>
