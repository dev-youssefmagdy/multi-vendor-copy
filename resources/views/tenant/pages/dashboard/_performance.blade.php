{{--
    Dashboard "Your store's performance": 4 KPI cards + Gross vs Collected
    line chart + Status Mix donut. Charts are drawn by
    resources/js/tenant/pages/dashboard/performance.js from $chartPayload.
    "dummy" marks values the backend still has to provide.
--}}

@php
    $kpis = collect(['Orders', 'Average Order', 'Outstanding', 'Customers'])
        ->map(fn ($label) => collect($cards)->firstWhere('label', $label))
        ->filter()
        ->values();

    $statusPalette = [
        'completed' => '#10B981', 'delivered' => '#10B981', 'paid' => '#10B981',
        'pending' => '#F59E0B',
        'processing' => '#3B82F6', 'shipped' => '#8B5CF6',
        'cancelled' => '#EF4444', 'rejected' => '#EF4444',
    ];
    $fallbackPalette = ['#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6', '#14B8A6'];

    $statusTotal = collect($statusRows)->sum('count');
    $statusMix = collect($statusRows)->values()->map(fn ($row, $i) => [
        'label' => $row['label'],
        'count' => $row['count'],
        'percent' => $statusTotal > 0 ? round($row['count'] / $statusTotal * 100, 1) : 0,
        'color' => $statusPalette[strtolower($row['label'])] ?? $fallbackPalette[$i % count($fallbackPalette)],
    ]);

    $trendUp = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 7.25l-7 7-4-4-6 6"/><path d="M16 7.25h4.5v4.5"/></svg>';
@endphp

<section class="db-section fu d1">
    <div class="db-section-head">
        <div class="db-welcome-copy">
            <h2 class="db-welcome-title">Your store's performance</h2>
            <p class="db-welcome-sub">The numbers that really matter to you, in a clear size and without clutter.</p>
        </div>

        {{-- Period filter — UI only; the data below is always the last 12 months until the backend supports other ranges. --}}
        <button type="button" class="db-period" aria-label="Period">
            12 Months
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
        </button>
    </div>

    {{-- KPI cards: grid on desktop, Swiper carousel (1.25 per view) on mobile — performance.js --}}
    <div class="db-kpis-slider" data-db-kpis>
    <div class="db-kpis">
        @foreach($kpis as $kpi)
            <div class="t-kpi db-kpi">
                <div class="t-kpi-head">
                    <span class="t-kpi-label">{{ $kpi['label'] }}</span>
                    {{-- FOR DESIGN PURPOSE --}}
                    <span class="t-trend t-trend-up">{!! $trendUp !!} dummy</span>
                </div>
                <div class="t-kpi-value">{{ $kpi['value'] }}</div>
                <p class="t-kpi-caption">{{ $kpi['caption'] }}</p>
            </div>
        @endforeach
    </div>
    </div>

    <div class="db-charts">
        <div class="db-chart-card db-chart-line">
            <div class="db-chart-head">
                <div>
                    <h3 class="db-chart-title">Gross vs Collected</h3>
                    <p class="db-chart-sub">Monthly gross order value compared with paid collections.</p>
                </div>
                <div class="db-legend-inline">
                    <span><i style="background:#3B82F6"></i>Gross sales</span>
                    <span><i style="background:#10B981"></i>Collected</span>
                </div>
            </div>
            {{-- On mobile the plot is wider than the card and scrolls sideways --}}
            <div class="db-chart-scroll">
                <div class="db-chart-canvas">
                    <canvas id="dbGrossChart" aria-label="Gross vs collected sales by month" role="img"></canvas>
                </div>
            </div>
        </div>

        <div class="db-chart-card db-status">
            <div>
                <h3 class="db-status-title">Status Mix</h3>
                <p class="db-chart-sub">Live order pipeline distribution across tenant statuses.</p>
            </div>

            <div class="db-status-body">
                <div class="db-donut">
                    <canvas id="dbStatusChart" width="151" height="151" aria-label="Order status mix" role="img"
                        data-values='@json($statusMix->pluck('count'))'
                        data-labels='@json($statusMix->pluck('label'))'
                        data-colors='@json($statusMix->pluck('color'))'></canvas>
                    <div class="db-donut-center">
                        <span class="db-donut-total">{{ number_format($statusTotal) }}</span>
                        <span class="db-donut-caption">ORDERS</span>
                    </div>
                </div>

                <ul class="db-status-list">
                    @forelse($statusMix as $row)
                        <li>
                            <span class="db-status-name"><i style="background:{{ $row['color'] }}"></i>{{ $row['label'] }}</span>
                            <span class="db-status-count">{{ number_format($row['count']) }}</span>
                            <span class="db-status-pct">{{ $row['percent'] }}%</span>
                        </li>
                    @empty
                        <li class="db-status-empty">No orders yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</section>
