@php($routeInfo = $shell['routeInfo'])

<header id="nav">
    <button type="button" class="ham" data-action="handle-ham" aria-label="Toggle sidebar">
        <svg class="icon-t2" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <div class="bc xs-hide">
        <span class="text-t3">{{ $routeInfo['section'] }}</span>
        @if($routeInfo['group'])
            <svg class="icon-t3" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            <span class="text-t3">{{ $routeInfo['group'] }}</span>
        @endif
        <svg class="icon-t3" width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        <span class="text-strong">{{ $routeInfo['label'] }}</span>
    </div>

    <div class="nav-spacer"></div>

    <div class="date-pill xs-hide">
        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span id="dt"></span>
    </div>

    @if($shell['plan']['name'])
        <a href="{{ route('tenant.finance.wallet') }}" class="plan-pill xs-hide {{ $shell['plan']['expired'] ? 'plan-pill-expired' : '' }}" title="{{ $shell['plan']['expired'] ? 'Subscription expired' : 'View subscriptions' }}">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            <span class="plan-pill-name">{{ $shell['plan']['name'] }}</span>
            @if($shell['plan']['expiry'])
                <span class="plan-pill-sep">·</span>
                <span class="plan-pill-date">{{ $shell['plan']['expired'] ? 'Expired ' : 'Exp ' }}{{ $shell['plan']['expiry']->format('M d, Y') }}</span>
            @endif
        </a>
    @endif

    <x-tenant::notification-bell :unread="$shell['unreadNotifications']" />

    <div class="tp" data-action="toggle-theme" title="Toggle theme">
        <div class="to ta" id="dOpt">Moon</div>
        <div class="to" id="lOpt">Sun</div>
    </div>

    <div class="av">
        <x-tenant::avatar :name="$shell['user']?->name ?? 'Tenant Admin'" :seed="$shell['user']?->email ?? $shell['tenantId']" size="26" />
        <div class="profile-meta xs-hide">
            <div class="profile-name">{{ $shell['user']?->name ?? 'Tenant Admin' }}</div>
            <div class="profile-role">{{ $shell['role'] }}</div>
        </div>
        <x-tenant::btn variant="secondary" size="sm" class="xs-hide"
            data-action-url="{{ route('tenant.logout') }}" data-action-method="POST" data-success="redirect">
            Logout
        </x-tenant::btn>
        <noscript>
            <form method="POST" action="{{ route('tenant.logout') }}" class="xs-hide">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
            </form>
        </noscript>
    </div>
</header>
