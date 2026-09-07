@php
    $fmt = fn($v) => '$' . number_format((float) ($v ?? 0), 2);
    $tabs = [
        'overview' => 'Overview',
        'tenants_owe' => 'Tenants → Central',
        'central_owes' => 'Central → Tenants',
        'settlements' => 'Settlements',
        'payouts' => 'Payouts',
    ];
@endphp

<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)<span class="page-badge">{{ $badge }}</span>@endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary"  href="{{ route('admin.wallets.index') }}">Tenants Ledger</a>
            <a class="btn btn-secondary"  href="{{ route('admin.vendor-settlements.index') }}">Vendor Settlements</a>
        </div>
    </div>

    <div class="card section-gap fu d1">
        <div class="flex gap-2 flex-wrap">
            @foreach ($tabs as $key => $label)
                <button type="button"
                    class="btn {{ $tab === $key ? 'btn-primary' : 'btn-secondary' }} btn-sm"
                    wire:click="setTab('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    @if ($tab === 'overview')
        <div class="g-stats4 section-gap">
            <div class="card card-glow-cyan"><div class="eyebrow">Gross Sales</div><div class="D stat-value">{{ $fmt($totals['gross_sales']) }}</div><p class="panel-copy">Σ grand_total of all orders</p></div>
            <div class="card card-glow-green"><div class="eyebrow">Collected Sales</div><div class="D stat-value">{{ $fmt($totals['collected_sales']) }}</div><p class="panel-copy">Paid orders only</p></div>
            <div class="card card-glow-cyan"><div class="eyebrow">Vendor Profit Total</div><div class="D stat-value">{{ $fmt($totals['vendor_profit_total'] ?? 0) }}</div><p class="panel-copy">Σ (grand_total − owner_profit) on central-gateway paid orders</p></div>
            <div class="card card-glow-amber"><div class="eyebrow">Central Profit Total</div><div class="D stat-value">{{ $fmt($totals['central_profit_total'] ?? 0) }}</div><p class="panel-copy">Σ vendor_cost on vendor-gateway paid orders</p></div>
        </div>
        <div class="g-stats4 section-gap">
            <div class="card"><div class="eyebrow">Discounts</div><div class="D stat-value">{{ $fmt($totals['discount_total']) }}</div><p class="panel-copy">Items + order discounts</p></div>
            <div class="card"><div class="eyebrow">Taxes</div><div class="D stat-value">{{ $fmt($totals['tax_total']) }}</div><p class="panel-copy">Items + order taxes</p></div>
            <div class="card"><div class="eyebrow">Shipping</div><div class="D stat-value">{{ $fmt($totals['shipping_total']) }}</div><p class="panel-copy">Σ resolved_shipping_charge</p></div>
            <div class="card"><div class="eyebrow">Vendor Gateway Fees</div><div class="D stat-value">{{ $fmt($totals['vendor_gateway_fee_total']) }}</div><p class="panel-copy">Tenant's gateway processing fee</p></div>
        </div>
        <div class="g-stats4 section-gap">
            <div class="card card-glow-amber"><div class="eyebrow">Tenants Owe Central</div><div class="D stat-value">{{ $fmt($totals['tenants_owe_central']) }}</div><p class="panel-copy">Unsettled vendor_cost on vendor-gateway paid orders</p></div>
            <div class="card card-glow-amber"><div class="eyebrow">Central Owes Tenants</div><div class="D stat-value">{{ $fmt($totals['central_owes_tenants']) }}</div><p class="panel-copy">Vendor profit on central-gateway orders minus payouts</p></div>
            <div class="card card-glow-green"><div class="eyebrow">Settlements Received</div><div class="D stat-value">{{ $fmt($totals['settlements_received']) }}</div><p class="panel-copy">Σ vendor_settlements.total</p></div>
            <div class="card card-glow-green"><div class="eyebrow">Payouts Sent</div><div class="D stat-value">{{ $fmt($totals['payouts_sent']) }}</div><p class="panel-copy">Σ tenant_payouts.amount</p></div>
        </div>
    @endif

    @if ($tab === 'tenants_owe')
        <section class="card table-card-shell section-gap fu d2">
            <div class="table-header-shell">
                <div>
                    <h3 class="panel-title">Tenants who owe Central</h3>
                    <p class="panel-copy">Vendors who used their own gateway and have unsettled <strong>vendor_cost</strong> not yet paid to central. Settled when <code>vendor_settled_at</code> is set.</p>
                </div>
            </div>
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead><tr><th>Tenant</th><th>Outstanding</th><th>Unsettled Orders</th><th>Already Settled</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($tenantsOwe as $r)
                            <tr>
                                <td><div class="entity-title">{{ $r->store_name }}</div><div class="entity-subtitle">{{ $r->tenant_email ?? $r->tenant_id }}</div></td>
                                <td><strong>{{ $fmt($r->tenant_owes_central) }}</strong></td>
                                <td>{{ count($r->unsettled_orders) }}</td>
                                <td>{{ $fmt($r->settlements_received) }}</td>
                                <td><div class="table-actions-inline"><a class="btn btn-secondary btn-sm" href="{{ route('admin.vendor-settlements.index') }}?search={{ urlencode($r->tenant_email ?? '') }}">View Settlements</a></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No outstanding amounts</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'central_owes')
        <section class="card table-card-shell section-gap fu d2">
            <div class="table-header-shell">
                <div>
                    <h3 class="panel-title">Tenants the Central platform owes</h3>
                    <p class="panel-copy">Central-gateway orders: central collected the payment. Vendor earns <strong>(grand_total − owner_profit)</strong>. Outstanding = vendor profit total minus prior payouts.</p>
                </div>
            </div>
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead><tr><th>Tenant</th><th>Outstanding</th><th>Vendor Profit Total</th><th>Already Paid Out</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($centralOwes as $r)
                            <tr>
                                <td><div class="entity-title">{{ $r->store_name }}</div><div class="entity-subtitle">{{ $r->tenant_email ?? $r->tenant_id }}</div></td>
                                <td><strong>{{ $fmt($r->central_owes_tenant) }}</strong></td>
                                <td>{{ $fmt($r->vendor_profit_total) }}</td>
                                <td>{{ $fmt($r->payouts_sent) }}</td>
                                <td><div class="table-actions-inline"><a class="btn btn-primary btn-sm" href="{{ route('admin.finance.payouts.create', $r->tenant_id) }}">Pay Out</a></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No outstanding payouts</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'settlements')
        <section class="card table-card-shell section-gap fu d2">
            <div class="table-header-shell">
                <h3 class="panel-title">Recent Vendor Settlements (Tenant → Central)</h3>
            </div>
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead><tr><th>Invoice</th><th>Tenant</th><th>Amount</th><th>Gateway</th><th>Settled At</th></tr></thead>
                    <tbody>
                        @forelse ($recentSettlements as $s)
                            <tr>
                                <td style="font-family:monospace;font-size:11px;">{{ $s->invoice_number }}</td>
                                <td>{{ $s->tenant_name ?? $s->tenant_id }}</td>
                                <td>{{ $fmt($s->total) }}</td>
                                <td>{{ ucwords(str_replace('_',' ', (string) $s->gateway_code)) }}</td>
                                <td>{{ $s->settled_at?->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No settlements yet</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'payouts')
        <section class="card table-card-shell section-gap fu d2">
            <div class="table-header-shell">
                <h3 class="panel-title">Recent Payouts (Central → Tenant)</h3>
            </div>
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead><tr><th>Invoice</th><th>Tenant</th><th>Amount</th><th>Method</th><th>Reference</th><th>Paid At</th></tr></thead>
                    <tbody>
                        @forelse ($recentPayouts as $p)
                            <tr>
                                <td style="font-family:monospace;font-size:11px;">{{ $p->invoice_number }}</td>
                                <td>{{ $p->tenant_name ?? $p->tenant_id }}</td>
                                <td>{{ $fmt($p->amount) }}</td>
                                <td>{{ $p->method === 'bank' ? ('Bank · ' . ($p->bank_name ?? '—')) : ('Gateway · ' . ($p->gateway_name ?? '—')) }}</td>
                                <td style="font-family:monospace;font-size:11px;">{{ $p->transaction_reference ?? '—' }}</td>
                                <td>{{ $p->paid_at?->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No payouts yet</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</main>
