@extends('tenant.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <script id="dashboard-chart-data" type="application/json">@json($chartPayload)</script>

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Vendor Dashboard</h1>
                <span class="page-badge">Tenant Workspace</span>
            </div>
            <p class="page-copy">Track store performance, tenant billing health, customer growth, shipping capture, and product profitability from the current tenant database.</p>
        </div>
    </div>

    <div class="g-stats section-gap">
        @foreach($cards as $card)
            <div class="card {{ $card['glow'] ?? '' }}">
                <div class="stat-head">
                    <div>
                        <div class="eyebrow">{{ $card['label'] }}</div>
                        <div class="D stat-value">{{ $card['value'] }}</div>
                    </div>
                    <div class="mini-stat-dot {{ $card['dot'] ?? 'dot-cyan' }}"></div>
                </div>
                <p class="panel-copy">{{ $card['caption'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="g-r2 section-gap">
        <section class="card fu d2">
            <div class="chart-head chart-head-wrap">
                <div>
                    <h3 class="D section-title">Sales vs Collection</h3>
                    <p class="section-copy">Gross sales and collected sales by month for the tenant.</p>
                </div>
            </div>
            <canvas id="revenueChart"></canvas>
        </section>

        <section class="card fu d3">
            <div class="section-stack-lg">
                <h3 class="D section-title">Order Status Mix</h3>
                <p class="section-copy">Current distribution of tenant order statuses.</p>
            </div>
            <div class="donut-wrap"><canvas id="donutChart"></canvas></div>
            <div class="legend-list">
                @foreach($statusRows as $index => $row)
                    <div class="legend-row">
                        <div class="legend-meta">
                            <span class="dot {{ ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'][$index % 4] }}"></span>
                            <span class="text-t2">{{ $row['label'] }}</span>
                        </div>
                        <span class="legend-value">{{ number_format($row['count']) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="g-r3 section-gap">
        <section class="card fu d2">
            <div class="panel-head">
                <div>
                    <h3 class="D panel-title">Shipping Capture</h3>
                    <p class="panel-copy">Monthly shipping revenue captured from tenant orders.</p>
                </div>
            </div>
            <canvas id="barChart"></canvas>
        </section>

        <section class="card fu d3">
            <div class="panel-head">
                <div>
                    <h3 class="D panel-title">Customer Momentum</h3>
                    <p class="panel-copy">New customers compared with repeat buying activity.</p>
                </div>
            </div>
            <canvas id="lineChart"></canvas>
        </section>

        <section class="card fu d4">
            <div class="panel-head">
                <div>
                    <h3 class="D panel-title">Performance Radar</h3>
                    <p class="panel-copy">Operational score snapshot across sales, catalog, and subscriptions.</p>
                </div>
            </div>
            <canvas id="radarChart"></canvas>
        </section>
    </div>

    <div class="g-r2 section-gap">
        <x-tenant::datatable id="dashboard-latest-orders" mode="client" :paging="false" :searching="false"
            :columns="$latestOrdersColumns" :rows="$latestOrdersRows"
            title="Latest Orders" description="Most recent order activity in the tenant storefront."
            empty-title="No order activity yet" empty-copy="Tenant order data will appear here as soon as storefront orders are created." />

        <x-tenant::datatable id="dashboard-top-customers" mode="client" :paging="false" :searching="false"
            :columns="$topCustomersColumns" :rows="$topCustomersRows"
            title="Top Customers" description="Highest lifetime spend customers in the current tenant."
            empty-title="No customer order history yet" empty-copy="Top customers appear once the tenant has customer orders." />
    </div>

    <x-tenant::datatable id="dashboard-top-products" mode="client" :paging="false" :searching="false"
        :columns="$topProductsColumns" :rows="$topProductsRows"
        title="Top Products" description="Most profitable tracked items based on realized revenue and cost."
        empty-title="No profitability data yet" empty-copy="Product profitability will populate as soon as tenant orders include tracked items." />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/dashboard/index.js')
@endpush
