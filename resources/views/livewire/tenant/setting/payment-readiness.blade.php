<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Payment Readiness</h1>
                <span class="page-badge">Settings</span>
            </div>
            <p class="page-copy">A real-time checklist of your payment gateway setup — see exactly what's ready and what needs attention before you sell globally.</p>
        </div>
        <a href="{{ route('tenant.settings.payment-gateways') }}" class="btn btn-secondary">Manage Gateways</a>
    </div>

    @php
        $pct = $report['max_score'] > 0 ? round(($report['score'] / $report['max_score']) * 100) : 0;
        $scoreColor = $pct >= 80 ? '#16a34a' : ($pct >= 50 ? '#d97706' : '#dc2626');
        $scoreLabel = $pct >= 80 ? 'Ready' : ($pct >= 50 ? 'Partial' : 'Not Ready');
    @endphp

    <section class="card fu d1" style="padding:28px 32px;">
        <div style="display:flex;align-items:center;gap:28px;flex-wrap:wrap;">
            <div style="position:relative;width:100px;height:100px;flex-shrink:0;">
                <svg viewBox="0 0 36 36" style="width:100%;height:100%;transform:rotate(-90deg);">
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f0f0f0" stroke-width="3.2"/>
                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $scoreColor }}" stroke-width="3.2"
                            stroke-dasharray="{{ $pct }} {{ 100 - $pct }}" stroke-linecap="round"/>
                </svg>
                <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <span style="font-size:20px;font-weight:800;color:{{ $scoreColor }};line-height:1;">{{ $pct }}%</span>
                    <span style="font-size:10px;font-weight:600;color:#888;margin-top:2px;">{{ $scoreLabel }}</span>
                </div>
            </div>

            <div style="flex:1;min-width:200px;">
                <div style="font-size:17px;font-weight:700;color:var(--t1);margin-bottom:6px;">
                    {{ $report['score'] }} of {{ $report['max_score'] }} checks passed
                </div>
                <p class="panel-copy" style="margin-bottom:12px;">
                    {{ $report['active_gateways'] }} gateway(s) active,
                    {{ $report['connected_gateways'] }} verified.
                    @if($report['score'] === $report['max_score'])
                        Your store is fully ready to accept payments.
                    @else
                        Complete the checks below to ensure customers can pay.
                    @endif
                </p>

                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach([
                        ['label' => 'Card', 'ok' => $report['supports_card']],
                        ['label' => 'Apple Pay', 'ok' => $report['supports_apple_pay']],
                        ['label' => 'Google Pay', 'ok' => $report['supports_google_pay']],
                        ['label' => 'International', 'ok' => $report['supports_international']],
                        ['label' => 'Wallets', 'ok' => $report['supports_wallets']],
                    ] as $cap)
                        <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:600;
                            background:{{ $cap['ok'] ? '#f0fdf4' : '#fff7ed' }};
                            color:{{ $cap['ok'] ? '#15803d' : '#b45309' }};
                            border:1px solid {{ $cap['ok'] ? '#bbf7d0' : '#fed7aa' }};">
                            @if($cap['ok'])
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            @else
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            @endif
                            {{ $cap['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="card fu d2" style="padding:24px 28px;">
        <h3 class="panel-title" style="margin-bottom:16px;">Readiness Checklist</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($report['checks'] as $check)
                <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;border-radius:12px;background:{{ $check['passed'] ? '#f0fdf4' : '#fffbeb' }};border:1px solid {{ $check['passed'] ? '#d1fae5' : '#fde68a' }};">
                    <div style="width:28px;height:28px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                        background:{{ $check['passed'] ? '#16a34a' : '#d97706' }};">
                        @if($check['passed'])
                            <svg width="13" height="13" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                            <svg width="13" height="13" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01"/></svg>
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:700;color:var(--t1);margin-bottom:2px;">{{ $check['label'] }}</div>
                        <div class="panel-copy">{{ $check['detail'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="g-stats2 section-gap">
        <section class="card fu d3" style="padding:20px 24px;">
            <h3 class="panel-title" style="margin-bottom:12px;">Supported Currencies</h3>
            @if(!empty($report['supported_currencies']))
                <div style="display:flex;flex-wrap:wrap;gap:6px;">
                    @foreach($report['supported_currencies'] as $cur)
                        <span style="padding:2px 9px;border-radius:6px;border:1px solid var(--border2);background:var(--card2);font-size:12px;font-weight:600;color:var(--t2);">{{ $cur }}</span>
                    @endforeach
                </div>
            @else
                <p class="panel-copy">No gateways active yet.</p>
            @endif
        </section>

        <section class="card fu d4" style="padding:20px 24px;">
            <h3 class="panel-title" style="margin-bottom:12px;">Target Country Coverage</h3>
            @if(empty($report['target_countries']))
                <p class="panel-copy">
                    You haven't set target countries yet.
                    <a href="{{ route('tenant.store.target-countries') }}" class="link">Configure countries →</a>
                </p>
            @else
                @if(!empty($report['covered_countries']))
                    <p class="panel-copy" style="margin-bottom:8px;color:#15803d;font-weight:600;">✓ Covered ({{ count($report['covered_countries']) }})</p>
                    <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px;">
                        @foreach($report['covered_countries'] as $iso)
                            <span style="padding:2px 8px;border-radius:6px;background:#f0fdf4;border:1px solid #bbf7d0;font-size:12px;font-weight:600;color:#15803d;">{{ $iso }}</span>
                        @endforeach
                    </div>
                @endif
                @if(!empty($report['uncovered_countries']))
                    <p class="panel-copy" style="margin-bottom:8px;color:#b45309;font-weight:600;">✗ Not covered ({{ count($report['uncovered_countries']) }})</p>
                    <div style="display:flex;flex-wrap:wrap;gap:5px;">
                        @foreach($report['uncovered_countries'] as $iso)
                            <span style="padding:2px 8px;border-radius:6px;background:#fff7ed;border:1px solid #fed7aa;font-size:12px;font-weight:600;color:#b45309;">{{ $iso }}</span>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    </div>

    @if(!empty($report['recommendations']))
    <section class="card fu d5" style="padding:20px 24px;">
        <h3 class="panel-title" style="margin-bottom:4px;">Recommended Gateways for Your Target Markets</h3>
        <p class="panel-copy" style="margin-bottom:14px;">Based on your target countries, these gateways would maximize your customer coverage.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;">
            @foreach($report['recommendations'] as $rec)
                <div style="padding:16px;border-radius:14px;border:1px solid var(--border2);background:var(--card2);">
                    <div style="font-size:14px;font-weight:700;color:var(--t1);margin-bottom:4px;">{{ $rec['name'] }}</div>
                    <div class="panel-copy" style="margin-bottom:8px;">
                        Covers {{ $rec['score'] }} of your target countries
                    </div>
                    @if(!empty($rec['meta']['payment_methods']))
                        <x-payment-method-badges :methods="$rec['meta']['payment_methods']" size="xs" />
                    @endif
                </div>
            @endforeach
        </div>
    </section>
    @endif
</main>
