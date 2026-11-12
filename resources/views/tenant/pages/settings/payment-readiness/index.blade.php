@extends('tenant.layouts.app')

@section('title', __('Payment Readiness'))

@section('content')
    <x-tenant::page-header :title="__('Payment Readiness')" :badge="__('Settings')" :description="__('A real-time checklist of your payment gateway setup — see exactly what\'s ready and what needs attention before you sell globally.')">
        <x-slot:actions>
            <a href="{{ route('tenant.settings.payment-gateways') }}" class="btn btn-secondary">{{ __('Manage Gateways') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    @php
        $scoreColor = $percent >= 80 ? '#16a34a' : ($percent >= 50 ? '#d97706' : '#dc2626');
    @endphp

    <section class="card fu d1 section-gap t-readiness-score-card">
        <div class="t-readiness-gauge">
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="15.9155" class="t-readiness-gauge-track" />
                <circle cx="18" cy="18" r="15.9155" class="t-readiness-gauge-value"
                    style="stroke: {{ $scoreColor }}; stroke-dasharray: {{ $percent }} {{ 100 - $percent }}" />
            </svg>
            <div class="t-readiness-gauge-label">
                <span class="t-readiness-gauge-pct" style="color: {{ $scoreColor }}">{{ $percent }}%</span>
                <span class="t-readiness-gauge-status">{{ $scoreLabel }}</span>
            </div>
        </div>

        <div style="flex:1;min-width:200px;">
            <div class="entity-title" style="font-size:17px;margin-bottom:6px;">{{ __(':score of :maxscore checks passed', ['score' => $report['score'], 'maxscore' => $report['max_score']]) }}</div>
            <p class="panel-copy" style="margin-bottom:12px;">
                {{ __(':activegateways gateway(s) active, :connectedgateways verified.', ['activegateways' => $report['active_gateways'], 'connectedgateways' => $report['connected_gateways']]) }}
                @if($report['score'] === $report['max_score'])
                    {{ __('Your store is fully ready to accept payments.') }}
                @else
                    {{ __('Complete the checks below to ensure customers can pay.') }}
                @endif
            </p>

            <div class="t-readiness-chips">
                @foreach([
                    ['label' => __('Card'), 'ok' => $report['supports_card']],
                    ['label' => __('Apple Pay'), 'ok' => $report['supports_apple_pay']],
                    ['label' => __('Google Pay'), 'ok' => $report['supports_google_pay']],
                    ['label' => __('International'), 'ok' => $report['supports_international']],
                    ['label' => __('Wallets'), 'ok' => $report['supports_wallets']],
                ] as $cap)
                    <span class="t-readiness-chip {{ $cap['ok'] ? 'is-ok' : 'is-pending' }}">
                        @if($cap['ok'])
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        @endif
                        {{ __($cap['label']) }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    <section class="card fu d2 section-gap" style="padding:24px 28px;">
        <h3 class="panel-title" style="margin-bottom:16px;">{{ __('Readiness Checklist') }}</h3>
        <div class="t-readiness-checklist">
            @foreach($report['checks'] as $check)
                <div class="t-readiness-check {{ $check['passed'] ? 'is-passed' : 'is-pending' }}">
                    <div class="t-readiness-check-icon">
                        @if($check['passed'])
                            <svg width="13" height="13" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                        @else
                            <svg width="13" height="13" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01"/></svg>
                        @endif
                    </div>
                    <div class="t-readiness-check-body">
                        <div class="t-readiness-check-title">{{ __($check['label']) }}</div>
                        <div class="panel-copy">{{ __($check['detail']) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="g-stats2 section-gap">
        <section class="card fu d3" style="padding:20px 24px;">
            <h3 class="panel-title" style="margin-bottom:12px;">{{ __('Supported Currencies') }}</h3>
            @if(!empty($report['supported_currencies']))
                <div class="t-readiness-tag-group">
                    @foreach($report['supported_currencies'] as $currency)
                        <span class="t-readiness-tag">{{ $currency }}</span>
                    @endforeach
                </div>
            @else
                <p class="panel-copy">{{ __('No gateways active yet.') }}</p>
            @endif
        </section>

        <section class="card fu d4" style="padding:20px 24px;">
            <h3 class="panel-title" style="margin-bottom:12px;">{{ __('Target Country Coverage') }}</h3>
            @if(empty($report['target_countries']))
                <p class="panel-copy">
                    {{ __('You haven\'t set target countries yet.') }}
                    <a href="{{ route('tenant.settings.general') }}" class="link">{{ __('Request target countries →') }}</a>
                </p>
            @else
                @if(!empty($report['covered_countries']))
                    <p class="panel-copy" style="margin-bottom:8px;color:#15803d;font-weight:600;">{{ __('✓ Covered (:coveredcountriesCount)', ['coveredcountriesCount' => count($report['covered_countries'])]) }}</p>
                    <div class="t-readiness-tag-group" style="margin-bottom:12px;">
                        @foreach($report['covered_countries'] as $iso)
                            <span class="t-readiness-tag is-covered">{{ $iso }}</span>
                        @endforeach
                    </div>
                @endif
                @if(!empty($report['uncovered_countries']))
                    <p class="panel-copy" style="margin-bottom:8px;color:#b45309;font-weight:600;">{{ __('✗ Not covered (:uncoveredcountriesCount)', ['uncoveredcountriesCount' => count($report['uncovered_countries'])]) }}</p>
                    <div class="t-readiness-tag-group">
                        @foreach($report['uncovered_countries'] as $iso)
                            <span class="t-readiness-tag is-uncovered">{{ $iso }}</span>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    </div>

    @if(!empty($report['recommendations']))
        <section class="card fu d5 section-gap" style="padding:20px 24px;">
            <h3 class="panel-title" style="margin-bottom:4px;">{{ __('Recommended Gateways for Your Target Markets') }}</h3>
            <p class="panel-copy" style="margin-bottom:14px;">{{ __('Based on your target countries, these gateways would maximize your customer coverage.') }}</p>
            <div class="t-recommendation-grid">
                @foreach($report['recommendations'] as $rec)
                    <div class="t-recommendation-card">
                        <div class="entity-title">{{ $rec['name'] }}</div>
                        <div class="entity-subtitle" style="margin-bottom:8px;">{{ __('Covers :score of your target countries', ['score' => $rec['score']]) }}</div>
                        @if(!empty($rec['meta']['payment_methods']))
                            <x-payment-method-badges :methods="$rec['meta']['payment_methods']" size="xs" />
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
