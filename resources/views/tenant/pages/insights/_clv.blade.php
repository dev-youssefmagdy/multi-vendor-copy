{{--
    Customer Lifetime Value tab layout (Analytics page):
    Top Customer Spend (table with spend bars) + Buyer Mix (large donut),
    full-width Customer Momentum, and the Customer Ranking table.
    Tables render the real top customers server-side ($rankingRows).
--}}

@php
    $ranking = collect($rankingRows ?? []);
    $topSpend = $ranking->take(8);
    $maxSpend = max(1, (float) $topSpend->max('total'));
    $barTones = ['#03AA00', '#F0C800', '#424AFF', '#FD6F04'];
    $money = fn ($v) => '$'.number_format((float) $v, (float) $v == floor((float) $v) ? 0 : 2);
    $donutCenter = $cards[0]['value'] ?? number_format($donutTotal);
    // Legend in the design's order; the donut itself keeps the data order (colours travel with each row)
    $legendOrder = ['active' => 0, 'one-time' => 1, 'repeat' => 2, 'inactive' => 3];
    $legendRows = $donutRows->sortBy(fn ($row) => $legendOrder[strtolower($row['label'])] ?? 99)->values();
@endphp

<div class="an-chart-row an-row-clv fu d2">
    {{-- Top Customer Spend --}}
    <section class="an-card an-spend-card">
        <div>
            <h3 class="an-card-title is-xl">Top Customer Spend</h3>
            <p class="an-card-sub">Highest lifetime value customers ranked by total spend.</p>
        </div>
        <div class="an-table-wrap">
            <table class="an-table an-spend-table" data-report-table="Top Customer Spend">
                <thead><tr><th>Customer</th><th>Orders</th><th class="is-wide">Spend</th><th></th></tr></thead>
                <tbody>
                    @forelse($topSpend as $i => $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ number_format($row['orders']) }}</td>
                            <td class="is-wide"><span class="an-bar"><i style="width:{{ round($row['total'] / $maxSpend * 100, 1) }}%;background:{{ $barTones[$i % count($barTones)] }}"></i></span></td>
                            <td class="an-spend-value">{{ $money($row['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="an-empty">No customer orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Buyer Mix: large donut, legend underneath --}}
    <section class="an-card an-donut-card is-large">
        <div>
            <h3 class="an-card-title is-lg">Buyer Mix</h3>
            <p class="an-card-sub">Active, inactive, one-time, and repeat buyer distribution.</p>
        </div>
        <div class="an-donut-lg">
            <canvas id="donutChart" width="295" height="295" role="img" aria-label="Buyer mix"
                data-colors='@json($donutRows->pluck('color'))'></canvas>
            <div class="db-donut-center">
                <span class="an-donut-total">{{ $donutCenter }}</span>
                <span class="an-donut-caption">{{ $donutCaption }}</span>
            </div>
        </div>
        <ul class="db-status-list">
            @foreach($legendRows as $row)
                <li>
                    <span class="db-status-name"><i style="background:{{ $row['color'] }}"></i>{{ $row['label'] }}</span>
                    <span class="db-status-count">{{ number_format($row['count']) }}</span>
                    <span class="db-status-pct">{{ $row['percent'] }}%</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>

{{-- Customer Momentum (full width) --}}
<section class="an-card an-chart-card fu d2">
    <div class="an-card-head">
        <div>
            <h3 class="an-card-title">Customer Momentum</h3>
            <p class="an-card-sub">New customer growth compared with repeat buyer activity.</p>
        </div>
        <div class="db-legend-inline" data-legend-for="lineChart"></div>
    </div>
    <div class="an-canvas-scroll">
        <div class="an-canvas"><canvas id="lineChart" role="img" aria-label="Customer momentum"></canvas></div>
    </div>
</section>

{{-- Customer Ranking --}}
<section class="an-card an-table-card fu d3">
    <div class="an-table-head">
        <h3>Customer Ranking</h3>
        <p>Lifetime value ranked by total spend and repeat order activity.</p>
    </div>
    <div class="an-table-wrap">
        <table class="an-table is-brand" data-report-table="Customer Ranking">
            <thead><tr><th>Customer</th><th>Orders</th><th>Paid</th><th>Total spend</th><th>Collected</th><th>AVG Order</th><th>Last order</th></tr></thead>
            <tbody>
                @forelse($ranking as $row)
                    <tr>
                        <td class="an-cell-name">{{ $row['name'] }}</td>
                        <td>{{ number_format($row['orders']) }}</td>
                        <td>{{ number_format($row['paid_orders'] ?? 0) }}</td>
                        <td>{{ $money($row['total']) }}</td>
                        <td>{{ $money($row['paid_total'] ?? 0) }}</td>
                        <td>{{ $money($row['average'] ?? 0) }}</td>
                        <td>{{ !empty($row['last_order']) ? \Illuminate\Support\Carbon::parse($row['last_order'])->format('D, d M, Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="an-empty">No customer orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="an-table-foot">Showing <strong>{{ $ranking->count() }}</strong> top customers by lifetime value</p>
</section>
