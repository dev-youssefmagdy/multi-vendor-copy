{{--
    Dashboard "NO GRGR Partner Program" banner.

    BACKEND TODO: $partner is sample data — replace it with real data from the
    controller (same keys). "dummy" marks every value the backend provides.
    Copy uses the shared data-tenant-copy component; Invite shares the link
    (resources/js/tenant/pages/dashboard/partner.js).
--}}

@php
    $partner = $partner ?? [
        'invite_link' => 'dummy',
        'visits' => 'dummy',
        'visits_trend' => 'dummy this month',
        'traders' => 'dummy',
        'active_traders' => 'dummy active traders',
        'reward' => 'dummy',
        'reward_status' => 'Ready to use',
    ];

    $trend = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 7.25l-7 7-4-4-6 6"/><path d="M16 7.25h4.5v4.5"/></svg>';
    $gift = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 11.25v6c0 2.12 0 3.18.66 3.84.66.66 1.72.66 3.84.66h6c2.12 0 3.18 0 3.84-.66.66-.66.66-1.72.66-3.84v-6"/><path d="M3 9.25c0-.94 0-1.41.3-1.7.29-.3.76-.3 1.7-.3h14c.94 0 1.41 0 1.7.3.3.29.3.76.3 1.7v.5c0 .94 0 1.41-.3 1.7-.29.3-.76.3-1.7.3H5c-.94 0-1.41 0-1.7-.3-.3-.29-.3-.76-.3-1.7z"/><path d="M12 7.25v14.5"/><path d="M12 7.25c-.63-1.94-1.9-4.5-4-4.5a2 2 0 0 0 0 4 M12 7.25c.63-1.94 1.9-4.5 4-4.5a2 2 0 0 1 0 4"/></svg>';
@endphp

<section class="db-section db-partner fu d2">
    <div class="db-partner-copy">
        <span class="db-partner-badge">{!! $gift !!} NO GRGR Partner Program</span>

        <div>
            <h2 class="db-brand-title">Participate in NO GRGR and win with us</h2>
            <p class="db-brand-text">Introduce traders to the platform, and track your referrals and rewards from a single dashboard.</p>
        </div>

        <div class="db-partner-actions">
            <div class="db-partner-link">
                <span class="db-partner-url">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 12.5v-4a3.5 3.5 0 0 1 7 0v7a5.5 5.5 0 0 1-11 0v-7"/><path d="M12 8.5v7a1.5 1.5 0 0 1-1.5 1.5"/></svg>
                    <span data-partner-link>{{ $partner['invite_link'] }}</span>
                </span>
                <button type="button" class="btn btn-primary db-partner-copy-btn" data-tenant-copy data-copy-value="{{ $partner['invite_link'] }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 15c0-2.83 0-4.24.88-5.12C10.76 9 12.17 9 15 9h1c2.83 0 4.24 0 5.12.88.88.88.88 2.3.88 5.12v1c0 2.83 0 4.24-.88 5.12-.88.88-2.3.88-5.12.88h-1c-2.83 0-4.24 0-5.12-.88C9 20.24 9 18.83 9 16z"/><path d="M17 9c0-2.96-.04-4.49-.9-5.53a3.5 3.5 0 0 0-.57-.57C14.39 2 12.73 2 9.4 2H9c-3.3 0-4.95 0-5.97 1.03C2 4.05 2 5.7 2 9v.4c0 3.33 0 4.99.9 6.13.17.21.36.4.57.57C4.51 16.96 6.04 17 9 17"/></svg>
                    Copy
                </button>
            </div>

            <button type="button" class="btn btn-secondary btn-lg db-partner-invite" data-partner-invite data-invite-link="{{ $partner['invite_link'] }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5.5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="18.5" r="3"/><path d="M8.6 10.6l6.8-3.7M8.6 13.4l6.8 3.7"/></svg>
                Invite a merchant
            </button>
        </div>
    </div>

    <div class="db-partner-stats">
        <div class="db-partner-card">
            <div class="db-partner-card-head">
                <span class="db-partner-icon" style="--c:#8026FF"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.54 10.95c.3.43.46.64.46 1.05s-.15.62-.46 1.05C20.19 14.95 16.72 19 12 19s-8.19-4.05-9.54-5.95C2.15 12.62 2 12.4 2 12s.15-.62.46-1.05C3.81 9.05 7.28 5 12 5s8.19 4.05 9.54 5.95z"/><circle cx="12" cy="12" r="3"/></svg></span>
                Link visits
            </div>
            <strong>{{ $partner['visits'] }}</strong>
            <span class="t-trend t-trend-up">{!! $trend !!} {{ $partner['visits_trend'] }}</span>
        </div>

        <div class="db-partner-card">
            <div class="db-partner-card-head">
                <span class="db-partner-icon" style="--c:#FF6A26"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.77 18c.75 0 1.34-.47 1.88-1.13 1.1-1.35-.7-2.44-1.4-2.97-.7-.54-1.49-.85-2.29-.92m-1-2a2.5 2.5 0 0 0 0-5M3.23 18c-.75 0-1.34-.47-1.88-1.13-1.1-1.35.7-2.44 1.4-2.97.7-.54 1.49-.85 2.29-.92m.5-2a2.5 2.5 0 0 1 0-5"/><path d="M8.08 15.11c-1.02.63-3.7 1.92-2.07 3.53.8.79 1.69 1.36 2.81 1.36h6.36c1.12 0 2.01-.57 2.81-1.36 1.63-1.61-1.05-2.9-2.07-3.53a7.7 7.7 0 0 0-7.84 0z"/><path d="M15.5 7.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0z"/></svg></span>
                Registered traders
            </div>
            <strong>{{ $partner['traders'] }}</strong>
            <span class="t-trend t-trend-up">{!! $trend !!} {{ $partner['active_traders'] }}</span>
        </div>

        <div class="db-partner-card is-wide">
            <div class="db-partner-card-head">
                <span class="db-partner-icon" style="--c:#05942D">{!! $gift !!}</span>
                Available reward
            </div>
            <strong>{{ $partner['reward'] }}</strong>
            <div class="db-partner-card-foot">
                <span class="t-trend t-trend-up">{!! $trend !!} {{ $partner['reward_status'] }}</span>
                <a href="{{ route('tenant.finance.wallet') }}" class="db-partner-withdraw">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.94 13c.04-.43.06-.9.06-1.4 0-4.5 0-6.74-1.4-8.14C18.2 2.06 15.94 2.06 11.45 2.06c-4.5 0-6.75 0-8.15 1.4C1.9 4.86 1.9 7.1 1.9 11.6c0 4.5 0 6.75 1.4 8.15 1.4 1.4 3.65 1.4 8.15 1.4.52 0 1-.01 1.44-.04"/><path d="M14.13 7.5c-.5-.63-1.46-1-2.68-1-1.63 0-2.95.95-2.95 2.13 0 1.17 1.32 2.12 2.95 2.12 1.63 0 2.95.95 2.95 2.12 0 1.18-1.32 2.13-2.95 2.13-1.22 0-2.18-.37-2.68-1M11.45 5v1.5m0 10V18"/><path d="M22 16l-5 5m0-4v4h4"/></svg>
                    Withdraw
                </a>
            </div>
        </div>
    </div>
</section>
