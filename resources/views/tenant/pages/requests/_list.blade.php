{{--
    Shared layout for the Request product pages (Product, Manufacturing and
    Brand requests): header + 3 KPI cards + "Your Requests" table, same
    design as Orders / Customers (styles in resources/css/tenant/pages/orders.css).

    Expects: $title, $description, $stats, $tableId, $url, $tableColumns,
    $statusOptions (value => label), $createUrl.
    Optional: $exportUrl + $exportId (download report), $order, $search (bool,
    only when the data endpoint supports search), $emptyTitle, $emptyCopy.
--}}

@php
    $kpiIcons = [
        'Total Requests' => ['tenant-panel/requests/total.png', 30],
        'Pending' => ['tenant-panel/requests/pending.png', 72],
    ];
    $chevronLeft = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
    $chevronRight = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
@endphp

<div class="od-page rq-page">
    <div class="db-welcome fu d0">
        <div class="db-welcome-copy">
            <h1 class="db-welcome-title">{{ $title }}</h1>
            <p class="db-welcome-sub">{{ $description }}</p>
        </div>
        <div class="rq-actions">
            @if(!empty($exportUrl))
                <a id="{{ $exportId }}" href="{{ $exportUrl }}" class="btn btn-lg od-download">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 14.5V4.5M12 14.5c-.7 0-2-2-2.5-2.5M12 14.5c.7 0 2-2 2.5-2.5"/><path d="M20 16.5c0 2.48-.52 3-3 3H7c-2.48 0-3-.52-3-3"/></svg>
                    download report
                </a>
            @endif
            <a href="{{ $createUrl }}" class="btn btn-primary btn-lg rq-add">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v8M16 12H8"/><path d="M2.5 12c0-4.48 0-6.72 1.39-8.11C5.28 2.5 7.52 2.5 12 2.5s6.72 0 8.11 1.39C21.5 5.28 21.5 7.52 21.5 12s0 6.72-1.39 8.11C18.72 21.5 16.48 21.5 12 21.5s-6.72 0-8.11-1.39C2.5 18.72 2.5 16.48 2.5 12z"/></svg>
                Add Request
            </a>
        </div>
    </div>

    <div class="an-kpis od-kpis rq-kpis fu d1">
        @foreach($stats as $stat)
            <div class="an-kpi">
                <div class="an-kpi-body">
                    <span class="an-kpi-label">{{ $stat['label'] }}</span>
                    <strong class="an-kpi-value">{{ $stat['value'] }}</strong>
                    @if(!empty($stat['caption']))<p class="an-kpi-caption">{{ $stat['caption'] }}</p>@endif
                </div>
                @isset($kpiIcons[$stat['label']])
                    <img src="{{ asset($kpiIcons[$stat['label']][0]) }}" alt="" class="od-kpi-icon" style="--od-icon: {{ $kpiIcons[$stat['label']][1] }}px">
                @endisset
            </div>
        @endforeach
    </div>

    <div class="od-queue rq-queue fu d2">
        <x-tenant::datatable
            :id="$tableId"
            :url="$url"
            :columns="$tableColumns"
            :order="$order ?? []"
            :page-length="12"
            :length-change="false"
            title="Your Requests"
            description=":count requests found."
            info-template="Show :count of :total result"
            :language="['paginate' => ['previous' => $chevronLeft.' Previous', 'next' => 'Next '.$chevronRight]]"
            :empty-title="$emptyTitle ?? 'No records found'"
            :empty-copy="$emptyCopy ?? 'No requests match the current filters yet.'"
            search-placeholder="Search"
            :quick-search="$search ?? false"
        >
            <x-slot:toolbar>
                {{-- Filter button: opens the status filter (same filters as before, applied live). --}}
                <x-tenant::filters-card :target="$tableId" title="Filters" :export-link="!empty($exportId) ? '#'.$exportId : null">
                    <x-tenant::select2 name="status" label="Status" :options="$statusOptions" placeholder="All statuses" />
                </x-tenant::filters-card>
            </x-slot:toolbar>
        </x-tenant::datatable>
    </div>
</div>
