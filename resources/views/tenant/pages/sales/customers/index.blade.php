@extends('tenant.layouts.app')

@section('title', 'Customers')

@php
    use App\Support\Tenant\TableColumn;

    // KPI icons (design): the Active card shows a green status dot instead.
    $kpiIcons = [
        'Customers' => ['tenant-panel/customers/customers.png', 48],
        'Buyers' => ['tenant-panel/customers/buyers.png', 49],
        'Avg Lifetime' => ['tenant-panel/customers/lifetime.png', 42],
    ];
    $kpiCaptions = ['Customers' => 'All Tenant customers records'];

    // Columns in the design order: ID, Customer, Contact, Orders, Status, Lifetime Value, Last Order, Actions.
    // "ID" reads the customer id already present in each data row.
    $byKey = collect($columns)->keyBy(fn ($col) => ($col instanceof TableColumn ? $col->toArray() : $col)['data'] ?? '');
    $tableColumns = collect(['customer', 'contact', 'orders', 'status', 'lifetime_value', 'last_order', 'actions'])
        ->map(fn ($key) => $byKey->get($key))
        ->filter()
        ->prepend(TableColumn::make('id', 'ID')->orderable(false))
        ->values()
        ->all();

    // FOR DESIGN PURPOSE
    // No customers yet → 109 sample rows (dummy data) rendered in the page.
    // When removing: delete this block and the :mode/:rows/:searching $isMock switches below.
    $customersStat = collect($stats)->firstWhere('label', 'Customers');
    $isMock = ($customersStat['value'] ?? '0') === '0';
    $mockRows = [];
    if ($isMock) {
        $names = ['Ali mohamed', 'Abdullah Magdy', 'Ziad mohamed', 'Abdullah Magdy'];
        $orders = [6, 7, 2, 4, 8, 2, 6, 10, 22, 34, 7, 7];
        $values = [620, 410, 210, 620, 620, 620, 620, 620, 620, 620, 620, 620];
        $dots = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5.5v.01M12 12v.01M12 18.5v.01"/></svg>';
        $actions = \Illuminate\Support\Facades\Blade::render('<x-tenant::dropdown align="end" :icon="$dots"><x-tenant::dropdown-item href="#">Edit</x-tenant::dropdown-item><x-tenant::dropdown-item danger>Delete</x-tenant::dropdown-item></x-tenant::dropdown>', ['dots' => $dots]);
        $active = \Illuminate\Support\Facades\Blade::render('<x-tenant::status-badge status="active" />');
        $day = now()->startOfDay();
        foreach (range(0, 108) as $i) {
            $name = $names[$i % 4];
            $avatar = \Illuminate\Support\Facades\Blade::render('<x-tenant::avatar :name="$name" size="39" />', ['name' => $name]);
            $date = $day->copy()->subDays($i * 3);
            $mockRows[] = [
                (string) (48487218415848 + $i * 7919),
                '<span class="cu-customer">'.$avatar.'<span>'.e($name).'</span></span>',
                '+201234567890',
                (string) $orders[$i % 12],
                $active,
                '$ '.number_format($values[$i % 12]),
                strtoupper($date->format('D')).$date->format(', d M,Y'),
                $actions,
            ];
        }
    }

    $chevronLeft = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
    $chevronRight = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
@endphp

@section('content')
    <div class="od-page cu-page">
        <div class="db-welcome fu d0">
            <div class="db-welcome-copy">
                <h1 class="db-welcome-title">Customers</h1>
                <p class="db-welcome-sub">Manage tenant customers with live order counts, lifetime value, and repeat-buyer context alongside the edit workflow.</p>
            </div>
            <a id="customers-export-link" href="{{ route('tenant.customers.export') }}" class="btn btn-lg od-download">
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
                        @php($caption = $kpiCaptions[$stat['label']] ?? rtrim($stat['caption'] ?? '', '.'))
                        @if($caption !== '')<p class="an-kpi-caption">{{ $caption }}</p>@endif
                    </div>
                    @if(isset($kpiIcons[$stat['label']]))
                        <img src="{{ asset($kpiIcons[$stat['label']][0]) }}" alt="" class="od-kpi-icon" style="--od-icon: {{ $kpiIcons[$stat['label']][1] }}px">
                    @elseif($stat['label'] === 'Active')
                        <span class="an-kpi-dot cu-kpi-dot" aria-hidden="true"></span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="od-queue cu-queue is-customers fu d2">
            <x-tenant::datatable
                id="customers-table"
                :url="route('tenant.customers.data')"
                :columns="$tableColumns"
                :order="[]"
                :mode="$isMock ? 'client' : 'server'"
                :rows="$isMock ? $mockRows : null"
                :searching="$isMock"
                :page-length="12"
                :length-change="false"
                :responsive="false"
                title="Customer List"
                description=":count customers matched the current CRM filters."
                info-template="Show :count of :total result"
                :language="['paginate' => ['previous' => $chevronLeft.' Previous', 'next' => 'Next '.$chevronRight]]"
                search-placeholder="Search"
                quick-search
            >
                <x-slot:toolbar>
                    {{-- Filter button: opens the status filter (same filters as before, applied live). --}}
                    <x-tenant::filters-card target="customers-table" title="Filters" export-link="#customers-export-link">
                        <x-tenant::select2 name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All" />
                    </x-tenant::filters-card>
                </x-slot:toolbar>
            </x-tenant::datatable>
        </div>
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/customers-index.js')
@endpush
