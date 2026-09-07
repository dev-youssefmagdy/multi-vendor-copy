<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Affiliate Panel') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        .aff-shell { display: flex; min-height: 100vh; }
        .aff-sidebar {
            width: 232px;
            flex-shrink: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 22px 14px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .aff-brand { font-family: var(--font-display); font-weight: 700; font-size: 16px; color: var(--t1); margin-bottom: 4px; padding: 0 10px; }
        .aff-brand small { display: block; font-family: var(--font-sans); font-weight: 500; font-size: 11px; color: var(--t3); margin-top: 2px; }
        .aff-nav { display: flex; flex-direction: column; gap: 2px; margin-top: 18px; }
        .aff-ni {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px;
            font-size: 13px; font-weight: 500;
            color: var(--t2); text-decoration: none;
            transition: background .15s, color .15s;
        }
        .aff-ni:hover { background: var(--elevated); color: var(--t1); }
        .aff-ni.act { background: var(--elevated); color: var(--cyan); }
        .aff-logout { margin-top: auto; padding-top: 14px; border-top: 1px solid var(--border); }
        .aff-logout button {
            width: 100%; text-align: left; padding: 9px 12px; border-radius: 8px;
            border: none; background: none; font-size: 13px; color: var(--t3); cursor: pointer;
        }
        .aff-logout button:hover { color: var(--red); }
        .aff-main { flex: 1; min-width: 0; padding: 32px; }
    </style>
</head>
<body>

@if(session('success'))
    <div id="flash-status" data-message="{{ session('success') }}" data-type="success" hidden></div>
@endif
@if(session('error'))
    <div id="flash-status" data-message="{{ session('error') }}" data-type="error" hidden></div>
@endif

<div class="aff-shell">
    @if (auth('affiliate')->check())
        <aside class="aff-sidebar">
            <div class="aff-brand">
                {{ config('app.name') }}
                <small>{{ __('Affiliate Panel') }}</small>
            </div>
            <nav class="aff-nav">
                <a href="{{ route('affiliate.dashboard') }}" class="aff-ni {{ request()->routeIs('affiliate.dashboard') ? 'act' : '' }}">◈ {{ __('Dashboard') }}</a>
                <a href="{{ route('affiliate.links') }}" class="aff-ni {{ request()->routeIs('affiliate.links') ? 'act' : '' }}">⧉ {{ __('My Links') }}</a>
                <a href="{{ route('affiliate.conversions') }}" class="aff-ni {{ request()->routeIs('affiliate.conversions') ? 'act' : '' }}">↗ {{ __('Conversions') }}</a>
                <a href="{{ route('affiliate.payouts') }}" class="aff-ni {{ request()->routeIs('affiliate.payouts') ? 'act' : '' }}">◫ {{ __('Payouts') }}</a>
                <a href="{{ route('affiliate.profile') }}" class="aff-ni {{ request()->routeIs('affiliate.profile') ? 'act' : '' }}">⊙ {{ __('Profile') }}</a>
            </nav>
            <div class="aff-logout">
                <form method="POST" action="{{ route('affiliate.logout') }}">
                    @csrf
                    <button type="submit">⎋ {{ __('Logout') }}</button>
                </form>
            </div>
        </aside>
    @endif

    <main class="aff-main">
        {{ $slot }}
    </main>
</div>

@livewireScripts
</body>
</html>
