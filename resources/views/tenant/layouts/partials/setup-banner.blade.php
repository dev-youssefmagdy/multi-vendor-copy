@if($shell['setupBanner'])
    @php
        $done = $shell['onboardingProgress']['done'] ?? 0;
        $total = $shell['onboardingProgress']['total'] ?? 0;
        $pct = $total > 0 ? (int) round(($done / $total) * 100) : 100;
        $next = $shell['setupNext'] ?? ['label' => 'Setup Complete', 'action_label' => 'View Store', 'action_url' => '#'];
    @endphp
    <section id="nav2" class="ob-setup-banner" role="status" aria-label="Store setup progress">
        <div class="ob-sb-info">
            <span class="ob-sb-title">Setup {{ $done }}/{{ $total }} <em>{{ $pct }}%</em></span>
            <span class="ob-setup-banner-text">Next: {{ $next['label'] }}</span>
        </div>
        <div class="ob-setup-banner-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
            <div class="ob-setup-banner-fill" style="width: {{ $pct }}%"></div>
        </div>
        <a href="{{ $next['action_url'] }}" class="ob-setup-banner-cta">
            {{ $next['action_label'] }}
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="9 18 15 12 9 6" />
            </svg>
        </a>
    </section>
@endif
