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
                :design="false"
                :mobile-cards="false"
                :url="route('tenant.orders.data')"
                :columns="$tableColumns"
                :order="[[$placedIndex === false ? 0 : $placedIndex, 'desc']]"
                mode="server"
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
