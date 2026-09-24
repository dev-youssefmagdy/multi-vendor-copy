@extends('tenant.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <script id="dashboard-chart-data" type="application/json">@json($chartPayload)</script>

    @include('tenant.pages.dashboard._hero')

    @include('tenant.pages.dashboard._performance')

    @include('tenant.pages.dashboard._pay-alert')

    @include('tenant.pages.dashboard._opportunities')

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
