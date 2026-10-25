@if($shell['setupBanner'])
    @php
        $done = $shell['onboardingProgress']['done'];
        $total = $shell['onboardingProgress']['total'];
        $pct = $total > 0 ? (int) round(($done / $total) * 100) : 100;
        $next = $shell['setupNext'];
    @endphp
    <div id="nav2" class="ob-setup-banner">
        <div class="ob-setup-banner-bar">
            <div class="ob-setup-banner-fill" style="width: {{ $pct }}%"></div>
        </div>
        <span class="ob-setup-banner-text">
            Setup {{ $done }}/{{ $total }} ({{ $pct }}%) — Next: {{ $next['label'] }}
        </span>
        <a href="{{ $next['action_url'] }}" class="ob-setup-banner-cta">
            {{ $next['action_label'] }}
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="9 18 15 12 9 6" />
            </svg>
        </a>
    </div>
@endif
