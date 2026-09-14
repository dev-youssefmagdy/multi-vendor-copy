@if($shell['setupBanner'])
    @php
        $pct = $shell['onboardingProgress']['total'] > 0
            ? (int) round(($shell['onboardingProgress']['done'] / $shell['onboardingProgress']['total']) * 100)
            : 100;
        $remaining = $shell['onboardingProgress']['total'] - $shell['onboardingProgress']['done'];
    @endphp
    <a href="{{ route('tenant.onboarding', ['tab' => 'setup']) }}" id="nav2" class="ob-setup-banner">
        <div class="ob-setup-banner-bar">
            <div class="ob-setup-banner-fill" style="width: {{ $pct }}%"></div>
        </div>
        <span class="ob-setup-banner-text">
            Setup {{ $pct }}% complete — {{ $remaining }} {{ \Illuminate\Support\Str::plural('step', $remaining) }} remaining
        </span>
        <span class="ob-setup-banner-cta">
            Finish setup
            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="9 18 15 12 9 6" />
            </svg>
        </span>
    </a>
@endif
