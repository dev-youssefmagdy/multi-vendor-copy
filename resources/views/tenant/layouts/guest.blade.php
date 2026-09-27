<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Helpers\TenantNavigation::direction() }}"
      data-theme="{{ in_array(request()->cookie('tenant_theme'), ['light', 'dark'], true) ? request()->cookie('tenant_theme') : 'light' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in')</title>
    @vite(['resources/css/tenant/app.css', 'resources/js/tenant/app.js'])
    @stack('tenant-vite')
</head>
<body class="t-scope ta-shell">
    {{-- Background circles in the brand-gradient colours --}}
    <div class="ta-backdrop ta-backdrop-a" aria-hidden="true"></div>
    <div class="ta-backdrop ta-backdrop-b" aria-hidden="true"></div>
    @yield('content')
</body>
</html>
