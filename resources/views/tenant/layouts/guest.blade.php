<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in')</title>
    @vite(['resources/css/tenant/app.css', 'resources/js/tenant/app.js'])
    @stack('tenant-vite')
</head>
<body class="auth-shell">
<div class="auth-stage">
    <div class="auth-backdrop auth-backdrop-a"></div>
    <div class="auth-backdrop auth-backdrop-b"></div>
    @yield('content')
</div>
</body>
</html>
