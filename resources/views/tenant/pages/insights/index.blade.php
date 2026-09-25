@extends('tenant.layouts.app')

@section('title', 'Analytics')

{{--
    Single Analytics page (AnalyticsController). The tab pills switch between
    the analytics modules; each module supplies the same data shape it used
    to pass to insights/_layout (title, cards, chartPayload, chartSections,
    tables, …). Charts are drawn by resources/js/tenant/pages/insights/analytics.js.
--}}

@php
    // KPI illustrations from the design, matched by card label ('dot' = green status dot)
    $kpiIcons = [
        'Gross Sales' => 'gross', 'Collected' => 'collected',
        'Outstanding' => 'outstanding', 'Average Order' => 'average',
        'Customers' => 'customers', 'Buyers' => 'dot',
        'Repeat Buyers' => 'repeat', 'Average Lifetime' => 'collected',
    ];

    $statusPalette = [
        'completed' => '#10B981', 'delivered' => '#10B981', 'paid' => '#10B981', 'active' => '#10B981',
        'pending' => '#F59E0B', 'processing' => '#3B82F6', 'in delivery' => '#3B82F6', 'shipped' => '#8B5CF6',
        'cancelled' => '#EF4444', 'rejected' => '#EF4444', 'inactive' => '#EF4444',
        'one-time' => '#F59E0B', 'repeat' => '#3B82F6',
    ];
    $fallbackPalette = ['#10B981', '#F59E0B', '#3B82F6', '#EF4444', '#8B5CF6', '#14B8A6'];
    $colorFor = fn (string $label, int $i) => $statusPalette[strtolower($label)] ?? $fallbackPalette[$i % count($fallbackPalette)];

    $donutLabels = $chartPayload['donutLabels'] ?? [];
    $donutData = $chartPayload['donutData'] ?? [];
    $donutTotal = array_sum($donutData);
    $donutRows = collect($donutLabels)->values()->map(fn ($label, $i) => [
        'label' => $label,
        'count' => $donutData[$i] ?? 0,
        'percent' => $donutTotal > 0 ? round(($donutData[$i] ?? 0) / $donutTotal * 100, 1) : 0,
        'color' => $colorFor((string) $label, $i),
    ]);

    // Status breakdown badge tones (Orders tab)
    $badgeTone = fn (string $label) => match (strtolower($label)) {
        'completed', 'delivered', 'paid' => 'green',
        'pending' => 'amber',
        'processing', 'shipped', 'in delivery' => 'blue',
        'cancelled', 'rejected' => 'orange',
        default => 'gray',
    };
    $barColor = ['green' => '#03AA00', 'amber' => '#F0C800', 'blue' => '#424AFF', 'orange' => '#FD6F04', 'gray' => '#A3A3A3'];
    $statusRows = collect($statusRows ?? []);
    $statusTotal = $statusRows->sum('count');
@endphp

@section('content')
    @if($chartPayload ?? null)
        <script id="dashboard-chart-data" type="application/json">@json($chartPayload)</script>
    @endif

    <div class="an-page" data-analytics-page data-tab="{{ $activeTab }}" data-report-name="{{ \Illuminate\Support\Str::slug($title) }}">
        {{-- Page header --}}
        <div class="an-head fu d0">
            <h1 class="an-title">Analytics</h1>
            <button type="button" class="btn btn-secondary btn-lg an-download" data-analytics-download>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 14.5V3.75M12 14.5c-.7 0-2.01-2-2.5-2.5M12 14.5c.7 0 2.01-2 2.5-2.5"/><path d="M20.25 16.5c0 2.48-.52 3.75-4 3.75h-8.5c-3.48 0-4-1.27-4-3.75"/></svg>
                download report
            </button>
        </div>

        {{-- Module tabs --}}
        <nav class="an-tabs fu d0" aria-label="Analytics modules">
            @foreach($tabs as $slug => $tab)
                <a href="{{ $tab['url'] }}" class="an-tab {{ $tab['active'] ? 'is-active' : '' }}" @if($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
            @endforeach
        </nav>

        {{-- Module heading --}}
        <div class="an-module-head fu d1">
            <h2>{{ $title }}</h2>
            @if(!empty($description))<p>{{ $description }}</p>@endif
        </div>

        {{-- KPI cards --}}
        @if($cards ?? [])
            <div class="an-kpis fu d1" data-analytics-kpis>
                @foreach($cards as $card)
                    <div class="an-kpi">
                        <div class="an-kpi-body">
                            <span class="an-kpi-label" data-kpi-label>{{ $card['label'] }}</span>
                            <strong class="an-kpi-value" data-kpi-value>{{ $card['value'] }}</strong>
                            @if(!empty($card['caption']))<p class="an-kpi-caption">{{ $card['caption'] }}</p>@endif
                        </div>
                        @if(($kpiIcons[$card['label']] ?? null) === 'dot')
                            <span class="an-kpi-dot" aria-hidden="true"></span>
                        @elseif(isset($kpiIcons[$card['label']]))
                            <img class="an-kpi-icon" src="{{ asset('tenant-panel/analytics/'.$kpiIcons[$card['label']].'.png') }}" alt="" width="44" height="44">
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($activeTab === 'customer-lifetime-value')
            @include('tenant.pages.insights._clv')
        @else
        {{-- Chart rows --}}
        @foreach($chartSections ?? [] as $section)
            <div class="an-chart-row fu d2">
                @foreach($section['cards'] as $chartCard)
                    @php $canvas = $chartCard['canvas'] ?? null; @endphp

                    @if($canvas === 'donutChart')
                        <section class="an-card an-donut-card">
                            <div>
                                <h3 class="an-card-title is-lg">{{ $chartCard['title'] }}</h3>
                                @if(!empty($chartCard['description']))<p class="an-card-sub">{{ $chartCard['description'] }}</p>@endif
                            </div>
                            <div class="db-status-body">
                                <div class="db-donut">
                                    <canvas id="donutChart" width="151" height="151" role="img" aria-label="{{ $chartCard['title'] }}"
                                        data-colors='@json($donutRows->pluck('color'))'></canvas>
                                    <div class="db-donut-center">
                                        <span class="db-donut-total">{{ number_format($donutTotal) }}</span>
                                        <span class="db-donut-caption">{{ $donutCaption }}</span>
                                    </div>
                                </div>
                                <ul class="db-status-list">
                                    @forelse($donutRows as $row)
                                        <li>
                                            <span class="db-status-name"><i style="background:{{ $row['color'] }}"></i>{{ $row['label'] }}</span>
                                            <span class="db-status-count">{{ number_format($row['count']) }}</span>
                                            <span class="db-status-pct">{{ $row['percent'] }}%</span>
                                        </li>
                                    @empty
                                        <li class="db-status-empty">No data yet.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </section>
                    @elseif($canvas)
                        <section class="an-card an-chart-card">
                            <div class="an-card-head">
                                <div>
                                    <h3 class="an-card-title">{{ $chartCard['title'] }}</h3>
                                    @if(!empty($chartCard['description']))<p class="an-card-sub">{{ $chartCard['description'] }}</p>@endif
                                </div>
                                <div class="db-legend-inline" data-legend-for="{{ $canvas }}"></div>
                            </div>
                            <div class="an-canvas"><canvas id="{{ $canvas }}" role="img" aria-label="{{ $chartCard['title'] }}"></canvas></div>
                        </section>
                    @elseif(!empty($chartCard['metrics']))
                        <section class="an-card an-metrics-card">
                            <div>
                                <h3 class="an-card-title is-lg">{{ $chartCard['title'] }}</h3>
                                @if(!empty($chartCard['description']))<p class="an-card-sub">{{ $chartCard['description'] }}</p>@endif
                            </div>
                            <dl class="an-metrics">
                                @foreach($chartCard['metrics'] as $metric)
                                    <div><dt>{{ $metric['label'] }}</dt><dd>{{ $metric['value'] }}</dd></div>
                                @endforeach
                            </dl>
                        </section>
                    @endif
                @endforeach
            </div>
        @endforeach

        {{-- Tables --}}
        @foreach($tables ?? [] as $table)
            @if($table['id'] === 'order-analytics-monthly')
                {{-- BACKEND TODO: mock rows — the live monthly data table only showed its loading state.
                     Replace "dummy" with real values (the same rows come from OrderAnalyticsController::dataMonthly). --}}
                <section class="an-card an-table-card fu d3">
                    <div class="an-table-head">
                        <h3>{{ $table['title'] }}</h3>
                        @if(!empty($table['description']))<p>{{ $table['description'] }}</p>@endif
                    </div>
                    <div class="an-table-wrap">
                        <table class="an-table" data-report-table="{{ $table['title'] }}">
                            <thead><tr><th>Month</th><th>Orders</th><th>Paid</th><th>Gross</th><th>Collected</th><th>AVG Order</th></tr></thead>
                            <tbody>
                                @foreach(['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $month)
                                    <tr>
                                        <td>{{ $month }}</td>
                                        <td>dummy</td>
                                        <td>dummy</td>
                                        <td>dummy</td>
                                        <td>dummy</td>
                                        <td>dummy</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @elseif($table['id'] === 'order-analytics-status')
                {{-- Status breakdown with proportion bars, rendered from the same rows --}}
                <section class="an-card an-table-card fu d3">
                    <div class="an-table-head">
                        <h3>{{ $table['title'] }}</h3>
                        @if(!empty($table['description']))<p>{{ $table['description'] }}</p>@endif
                    </div>
                    <div class="an-table-wrap">
                        <table class="an-table an-status-table" data-report-table="{{ $table['title'] }}">
                            <thead><tr><th>Status</th><th>Orders</th><th class="is-wide">Proportion</th><th>Share</th></tr></thead>
                            <tbody>
                                @forelse($statusRows as $row)
                                    @php
                                        $tone = $badgeTone($row['label']);
                                        $share = $statusTotal > 0 ? round($row['count'] / $statusTotal * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td><span class="badge badge-{{ $tone }}"><span class="badge-dot"></span>{{ $row['label'] }}</span></td>
                                        <td>{{ number_format($row['count']) }}</td>
                                        <td class="is-wide"><span class="an-bar"><i style="width:{{ $share }}%;background:{{ $barColor[$tone] }}"></i></span></td>
                                        <td>{{ $share }}%</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="an-empty">No orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="an-table-foot"><strong>{{ number_format($statusTotal) }}</strong> total orders across {{ $statusRows->count() }} statuses</p>
                </section>
            @else
                <div class="an-dt fu d3" data-report-source data-report-title="{{ $table['title'] }}" data-report-url="{{ $table['url'] }}" data-report-columns='@json(collect($table['columns'])->map(fn ($c) => ['data' => $c['data'], 'title' => $c['title']])->values())'>
                    <x-tenant::datatable
                        :id="$table['id']"
                        :url="$table['url']"
                        :columns="$table['columns']"
                        :order="$table['order'] ?? []"
                        :page-length="$table['pageLength'] ?? 12"
                        :title="$table['title']"
                        :description="$table['description'] ?? null"
                        :paging="$table['paging'] ?? true"
                    />
                </div>
            @endif
        @endforeach
        @endif
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/insights/analytics.js')
@endpush
