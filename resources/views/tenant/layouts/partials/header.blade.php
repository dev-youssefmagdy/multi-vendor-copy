<header id="nav">
    <button type="button" class="ham" data-action="handle-ham" aria-label="Toggle sidebar">
        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <path d="M3.25 4.25h12.5M3.25 12h17.5M3.25 19.75h17.5"/>
        </svg>
    </button>

    {{-- Logo — mobile only (the sidebar carries it on larger screens). --}}
    <a href="{{ route('tenant.dashboard') }}" class="hd-logo" aria-label="Dashboard">
        <img src="{{ asset('tenant-panel/logo.svg') }}" alt="NOGRGR" width="153" height="40" class="hd-logo-dark">
        {{-- Mobile logo (light wordmark) for dark backgrounds: dashboard hero, dark theme --}}
        <img src="{{ asset('tenant-panel/mobile-logo.svg') }}" alt="" width="153" height="40" class="hd-logo-light" aria-hidden="true">
    </a>

    {{-- Country selector — UI only for now; not wired to any data yet. --}}
    <button type="button" class="hd-field hd-country xs-hide" aria-label="Select your country">
        <span class="hd-field-main">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.75"/><path d="M8 12c0 5.25 1.8 9.75 4 9.75s4-4.5 4-9.75S14.2 2.25 12 2.25 8 6.75 8 12z"/><path d="M3 9h18M3 15h18"/></svg>
            <span>Select your country</span>
        </span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
    </button>

    {{-- Search — UI only for now; not wired to any endpoint yet. --}}
    <label class="hd-field hd-search xs-hide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8.75"/><path d="M17.5 17.5l4.25 4.25"/></svg>
        <input type="search" placeholder="Search" aria-label="Search">
    </label>

    <div class="hd-end">
        <div class="hd-actions">
            <x-tenant::notification-bell :unread="$shell['unreadNotifications']" />

            <button type="button" class="hd-icon-btn hd-theme" data-action="toggle-theme" aria-label="Toggle theme" title="Toggle theme">
                <svg class="hd-theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.5 14.08A9.75 9.75 0 0 1 9.92 2.5 9.75 9.75 0 1 0 21.5 14.08z"/></svg>
                <svg class="hd-theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
            </button>
        </div>

        <div class="hd-profile" data-tenant-dropdown data-align="end">
            <button type="button" class="hd-profile-trigger" data-dropdown-trigger aria-haspopup="true" aria-expanded="false">
                <x-tenant::avatar :name="$shell['tenantName'] ?? 'Tenant Owner'" :seed="$shell['tenantId']" size="34" />
                <span class="hd-profile-meta xs-hide">
                    <span class="hd-profile-name">{{ $shell['tenantName'] ?? 'Tenant Owner' }}</span>
                    <span class="hd-profile-role">Vendor Workspace</span>
                </span>
                <svg class="hd-profile-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
            </button>

            <div class="t-dropdown-menu hd-profile-menu" data-dropdown-menu role="menu" hidden>
                <div class="hd-menu-user">
                    <span class="hd-menu-user-name">{{ $shell['user']?->name ?? 'Tenant Admin' }}</span>
                    <span class="hd-menu-user-role">{{ $shell['role'] }}</span>
                </div>

                @if($shell['plan']['name'])
                    <a href="{{ route('tenant.finance.wallet') }}" role="menuitem" class="t-dropdown-item hd-menu-plan {{ $shell['plan']['expired'] ? 'is-expired' : '' }}" title="{{ $shell['plan']['expired'] ? 'Subscription expired' : 'View subscriptions' }}">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span>
                            {{ $shell['plan']['name'] }}
                            @if($shell['plan']['expiry'])
                                <small>{{ $shell['plan']['expired'] ? 'Expired ' : 'Exp ' }}{{ $shell['plan']['expiry']->format('M d, Y') }}</small>
                            @endif
                        </span>
                    </a>
                @endif

                <button type="button" role="menuitem" class="t-dropdown-item is-danger"
                    data-action-url="{{ route('tenant.logout') }}" data-action-method="POST" data-success="redirect">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3.09A9.75 9.75 0 1 0 14 20.9"/><path d="M21 12H11m10 0c0-.7-2-2.01-2.5-2.5M21 12c0 .7-2 2.01-2.5 2.5"/></svg>
                    Logout
                </button>
                <noscript>
                    <form method="POST" action="{{ route('tenant.logout') }}">
                        @csrf
                        <button type="submit" class="t-dropdown-item is-danger">Logout</button>
                    </form>
                </noscript>
            </div>
        </div>
    </div>
</header>
