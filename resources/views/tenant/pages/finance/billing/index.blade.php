@extends('tenant.layouts.app')

@section('title', 'Billing')

@section('content')
    <x-tenant::page-header title="Billing" badge="Finance" description="Track what you have billed, what has been collected, what is still outstanding, and how much of your plan you are using.">
    </x-tenant::page-header>

    <section class="bill-section">
        <h3 class="bill-section-title">Billing summary</h3>
        <x-tenant::stats-grid :stats="$stats" :columns="5" />
    </section>

    <section class="bill-section">
        <div class="bill-section-head">
            <h3 class="bill-section-title">Plan usage</h3>
            <span class="bill-section-copy">What you have used against your current plan limits.</span>
        </div>
        <div class="bill-usage">
            @foreach ($planUsage as $row)
                <div class="bill-usage-item">
                    <div class="bill-usage-top">
                        <span class="bill-usage-label">{{ $row['label'] }}</span>
                        <span class="bill-usage-value">{{ number_format($row['used']) }} / {{ $row['limit'] === null ? 'Unlimited' : number_format($row['limit']) }}</span>
                    </div>
                    @if ($row['percent'] !== null)
                        <div class="bill-usage-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $row['percent'] }}" aria-label="{{ $row['label'] }}">
                            <span class="{{ $row['percent'] >= 90 ? 'is-high' : '' }}" style="width: {{ $row['percent'] }}%"></span>
                        </div>
                    @else
                        <div class="bill-usage-note">No limit on your plan</div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="bill-section">
        <x-tenant::datatable id="billing-table" :url="route('tenant.finance.billing.data')" :columns="$columns" title="Order billing ledger" quick-search search-placeholder="Order UUID, customer, or gateway">
            <x-slot:toolbar>
                <x-tenant::filters-card target="billing-table" title="Filters">
                    <x-tenant::select2 name="paid" label="Payment State" :options="['paid' => 'Paid', 'unpaid' => 'Unpaid']" placeholder="All" />
                    <x-tenant::select2 name="gateway" label="Gateway" :options="$gatewayOptions" placeholder="All" />
                </x-tenant::filters-card>
            </x-slot:toolbar>
        </x-tenant::datatable>
    </section>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/billing-index.js')
@endpush
