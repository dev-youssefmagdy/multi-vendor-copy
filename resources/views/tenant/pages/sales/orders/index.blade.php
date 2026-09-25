@extends('tenant.layouts.app')

@section('title', 'Orders')

@php
    // KPI icons (design); the Paid card has none in the design.
    $kpiIcons = [
        'Orders' => ['tenant-panel/orders/orders.png', 72],
        'Processing' => ['tenant-panel/orders/processing.png', 72],
        'Collected' => ['tenant-panel/orders/collected.png', 42],
    ];
    // Table columns as in the design: no row-number column.
    $tableColumns = collect($columns)
        ->reject(fn ($col) => (($col instanceof \App\Support\Tenant\TableColumn ? $col->toArray() : $col)['data'] ?? null) === 'DT_RowIndex')
        ->values()
        ->all();
    $placedIndex = collect($tableColumns)->search(fn ($col) => (($col instanceof \App\Support\Tenant\TableColumn ? $col->toArray() : $col)['data'] ?? null) === 'placed_at');

    // FOR DESIGN PURPOSE
    // No real orders yet → 109 sample rows (dummy data) rendered in the page.
    // When removing: delete this block and the :order/:mode/:rows/:searching $isMock switches below.
    // BACKEND TODO: remove once the store has orders; real rows load from tenant.orders.data.
    $ordersStat = collect($stats)->firstWhere('label', 'Orders');
    $isMock = ($ordersStat['value'] ?? '0') === '0';
    $mockRows = [];
    if ($isMock) {
        $names = ['Winter Wonderland Party Decorations', 'Summer Beach Party Essentials', 'Halloween Costume Accessories', "Valentine's Day Gift Baskets", 'Back to School Stationery Packs', 'Super Bowl Viewing Party Gear', 'Fourth of July BBQ Essentials', "New Year's Eve Celebration Kit", 'Easter Egg Hunt Supplies', 'Graduation Party Supplies', 'Earth Day Eco-Friendly Kit', "St. Patrick's Day Party Kit"];
        $photos = ['photo-1542291026-7eec264c27ff', 'photo-1505740420928-5e560c06d30e', 'photo-1523275335684-37898b6baf30', 'photo-1583394838336-acd977736f90', 'photo-1553062407-98eeb64c6a62', 'photo-1602143407151-7111542de6e8', 'photo-1608571423902-eed4a5ad8108', 'photo-1546868871-7041f2a55e12', 'photo-1541643600914-78b084683601', 'photo-1526170375885-4d8ecf77b99f', 'photo-1556228578-8c89e6adf883', 'photo-1572635196237-14b3f281503f'];
        $statuses = ['pending', 'shipped', 'processing', 'delivered', 'processing', 'pending', 'shipped', 'delivered', 'pending', 'processing', 'delivered', 'processing'];
        $customers = ['Ali mohamed', 'Abdullah Magdy', 'Ziad mohamed', 'Abdullah Magdy'];
        $gateways = ['Paypal', 'Cash', 'Credit card', 'Credit card', 'Paypal', 'Paypal', 'Paypal', 'Party Items'];
        $values = [80, 27, 14.5, 30, 12.5, 23, 24, 35, 22, 32, 17, 18.5];
        $dots = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5.5v.01M12 12v.01M12 18.5v.01"/></svg>';
        $actions = \Illuminate\Support\Facades\Blade::render('<x-tenant::dropdown align="end" :icon="$dots"><x-tenant::dropdown-item href="#">View order</x-tenant::dropdown-item></x-tenant::dropdown>', ['dots' => $dots]);
        $day = now()->startOfDay();
        foreach (range(0, 108) as $i) {
            $k = $i % 12;
            $date = $day->copy()->subDays($i * 3);
            $mockRows[] = [
                '<div class="od-order"><img src="https://images.unsplash.com/'.$photos[$k].'?w=80&q=60&auto=format&fit=crop" alt="" class="od-thumb" loading="lazy"><span class="od-order-name">'.e($names[$k]).'</span></div>',
                e($customers[$i % 4]),
                '$ '.number_format($values[$k], 2),
                '$ 2.66',
                \Illuminate\Support\Facades\Blade::render('<x-tenant::status-badge :status="$s" />', ['s' => $statuses[$k]]),
                $gateways[$i % 8],
                strtoupper($date->format('D')).$date->format(', d M,Y'),
                $actions,
            ];
        }
    }
    $chevronLeft = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
    $chevronRight = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
@endphp

@section('content')
    <div class="od-page">
        <div class="db-welcome fu d0">
            <div class="db-welcome-copy">
                <h1 class="db-welcome-title">Orders</h1>
                <p class="db-welcome-sub">Track tenant orders, payment collection, fulfillment progress, and full order payload details from one queue.</p>
            </div>
            <a id="orders-export-link" href="{{ route('tenant.orders.export') }}" class="btn btn-lg od-download">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 14.5V4.5M12 14.5c-.7 0-2-2-2.5-2.5M12 14.5c.7 0 2-2 2.5-2.5"/><path d="M20 16.5c0 2.48-.52 3-3 3H7c-2.48 0-3-.52-3-3"/></svg>
                download report
            </a>
        </div>

        <div class="an-kpis od-kpis fu d1">
            @foreach($stats as $stat)
                <div class="an-kpi">
                    <div class="an-kpi-body">
                        <span class="an-kpi-label">{{ $stat['label'] }}</span>
                        <strong class="an-kpi-value">{{ $stat['value'] }}</strong>
                        @if(!empty($stat['caption']))<p class="an-kpi-caption">{{ rtrim($stat['caption'], '.') }}</p>@endif
                    </div>
                    @isset($kpiIcons[$stat['label']])
                        <img src="{{ asset($kpiIcons[$stat['label']][0]) }}" alt="" class="od-kpi-icon" style="--od-icon: {{ $kpiIcons[$stat['label']][1] }}px">
                    @endisset
                </div>
            @endforeach
        </div>

        <div class="od-queue is-orders fu d2">
            <x-tenant::datatable
                id="orders-table"
                :url="route('tenant.orders.data')"
                :columns="$tableColumns"
                :order="$isMock ? [] : [[$placedIndex === false ? 0 : $placedIndex, 'desc']]"
                :mode="$isMock ? 'client' : 'server'"
                :rows="$isMock ? $mockRows : null"
                :searching="$isMock"
                :page-length="12"
                :length-change="false"
                :responsive="false"
                title="Order Queue"
                description=":count orders matched the current queue filters."
                info-template="Show :count of :total result"
                :language="['paginate' => ['previous' => $chevronLeft.' Previous', 'next' => 'Next '.$chevronRight]]"
                search-placeholder="Search"
                quick-search
            >
                <x-slot:toolbar>
                    {{-- Filter button: opens the status filter (same filters as before, applied live). --}}
                    <x-tenant::filters-card target="orders-table" title="Filters" export-link="#orders-export-link">
                        <x-tenant::select2 name="status" label="Status" :options="$statusOptions" placeholder="All" />
                    </x-tenant::filters-card>
                </x-slot:toolbar>
            </x-tenant::datatable>
        </div>
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/orders-index.js')
@endpush
