<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? __('My Account') }} — {{ config('app.name') }}</title>
    <style>[x-cloak]{display:none!important}</style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* ── Owner-panel sidebar nav items ─────────────────────── */
        .owner-ni {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8.5px 14px;
            border-radius: 8px;
            margin: 1px 7px;
            width: calc(100% - 14px);
            color: var(--t2);
            font-size: 13px;
            font-weight: 500;
            transition: background .18s, color .18s;
            position: relative;
            white-space: nowrap;
            user-select: none;
            text-decoration: none;
        }
        .owner-ni:hover { background: var(--elevated); color: var(--t1); }
        .owner-ni.act {
            background: linear-gradient(135deg, rgba(0,229,255,.1), rgba(162,89,255,.07));
            color: var(--cyan);
            border: 1px solid rgba(0,229,255,.16);
        }
        [data-theme="light"] .owner-ni.act {
            background: linear-gradient(135deg, rgba(0,151,196,.09), rgba(124,58,237,.07));
            border-color: rgba(0,151,196,.2);
            color: var(--cyan);
        }
        .owner-ni.act .act-bar { display: block; }
    </style>
</head>
<body>

{{-- Flash messages (hidden div for JS pickup) --}}
@if(session('success'))
    <div id="flash-status" data-message="{{ session('success') }}" data-type="success" hidden></div>
@endif
@if(session('error'))
    <div id="flash-status" data-message="{{ session('error') }}" data-type="error" hidden></div>
@endif

<div id="ov" data-action="close-mobile"></div>

{{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
<aside id="sb">
    <div class="logo-row">
        <div class="logo-icon">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
                      stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div>
            <div class="brand">{{ strtoupper(config('app.name')) }}</div>
            <div class="brand-subtitle">MY ACCOUNT</div>
        </div>
        <button type="button" class="close-sb" data-action="close-mobile" aria-label="Close sidebar">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <div class="sb-scroll">
        <div class="sb-lbl">{{ __('Store') }}</div>

        <a href="{{ route('owner.domains') }}"
           class="owner-ni {{ request()->routeIs('owner.domains') ? 'act' : '' }}">
            @if(request()->routeIs('owner.domains'))
                <div class="act-bar"></div>
            @endif
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <circle cx="12" cy="12" r="10"/>
                <line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            {{ __('Domains') }}
        </a>

        <a href="{{ route('owner.subscriptions') }}"
           class="owner-ni {{ request()->routeIs('owner.subscriptions') ? 'act' : '' }}">
            @if(request()->routeIs('owner.subscriptions'))
                <div class="act-bar"></div>
            @endif
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            {{ __('Subscriptions') }}
        </a>

        <div class="sb-sep"></div>
        <div class="sb-lbl">{{ __('Account') }}</div>

        <form method="POST" action="{{ route('owner.logout') }}">
            @csrf
            <button type="submit"
                class="owner-ni w-full text-left"
                style="background:transparent;border:none;cursor:pointer;">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                {{ __('Logout') }}
            </button>
        </form>
    </div>

    <div class="sb-user">
        <div class="su-inner">
            <div class="user-avatar-wrap">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode(auth('tenant_owner')->user()?->email ?? 'owner') }}&backgroundColor=b6e3f4"
                     class="avatar avatar-30" alt="">
                <span class="status-indicator"></span>
            </div>
            <div class="user-meta">
                <div class="user-name">{{ auth('tenant_owner')->user()?->name ?? __('Store Owner') }}</div>
                <div class="user-role">{{ __('Store Owner') }}</div>
            </div>
        </div>
    </div>
</aside>

{{-- ── Top header ──────────────────────────────────────────────────── --}}
<header id="nav">
    <button type="button" class="ham" data-action="handle-ham" aria-label="Toggle sidebar">
        <svg class="icon-t2" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <div class="bc xs-hide">
        <span class="text-t3">{{ __('My Account') }}</span>
        <svg class="icon-t3" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-strong">
            @if(request()->routeIs('owner.domains')) {{ __('Domains') }}
            @elseif(request()->routeIs('owner.subscriptions')) {{ __('Subscriptions') }}
            @else {{ __('Dashboard') }}
            @endif
        </span>
    </div>

    <div class="nav-spacer"></div>

    <div class="date-pill xs-hide">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <span id="dt"></span>
    </div>

    <div class="tp" data-action="toggle-theme" title="Toggle theme">
        <div class="to ta" id="dOpt">Moon</div>
        <div class="to" id="lOpt">Sun</div>
    </div>

    @livewire('tenant-owner.tenant-switcher')

    <div class="av">
        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ urlencode(auth('tenant_owner')->user()?->email ?? 'owner') }}&backgroundColor=b6e3f4"
             class="avatar avatar-26" alt="">
        <div class="profile-meta xs-hide">
            <div class="profile-name">{{ auth('tenant_owner')->user()?->name ?? __('Store Owner') }}</div>
            <div class="profile-role">{{ __('Store Owner') }}</div>
        </div>
        <form method="POST" action="{{ route('owner.logout') }}" class="xs-hide">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm">{{ __('Logout') }}</button>
        </form>
    </div>
</header>

{{-- ── Main content ────────────────────────────────────────────────── --}}
<main id="mn">
    {{ $slot }}
</main>

@livewireScripts
</body>
</html>
