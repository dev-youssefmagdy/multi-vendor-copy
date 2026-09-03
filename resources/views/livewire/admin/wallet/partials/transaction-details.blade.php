@php
    $transaction = $transaction ?? [];
    $subscription = $transaction['subscription'] ?? null;
@endphp

<div class="details-stack">
    <div class="details-grid">
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Transaction Snapshot</h4>
                <p class="panel-copy">Tenant ledger movement aggregated into the central wallet management module.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Reference</span><span
                        class="details-value">{{ $transaction['reference'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Store</span><span
                        class="details-value">{{ $transaction['store_name'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Tenant Email</span><span
                        class="details-value">{{ $transaction['tenant_email'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Direction</span><span
                        class="details-value">{{ $transaction['direction'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Model Type</span><span
                        class="details-value">{{ $transaction['model_type'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Model Label</span><span
                        class="details-value">{{ $transaction['model_label'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Amount</span><span
                        class="details-value">${{ number_format((float) ($transaction['amount'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Admin Profit</span><span
                        class="details-value">${{ number_format((float) ($transaction['admin_profit'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Created At</span><span
                        class="details-value">{{ optional($transaction['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </section>

        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Description</h4>
                <p class="panel-copy">Free-form transaction details captured inside the tenant ledger.</p>
            </div>
            <div class="content-note">{{ $transaction['description'] ?? 'No description provided.' }}</div>

            @if ($subscription)
                <div class="metric-list mt-4">
                    <div class="metric-row"><span class="metric-label">Subscription ID</span><span
                            class="metric-value">{{ $subscription['id'] ?? '-' }}</span></div>
                    <div class="metric-row"><span class="metric-label">Status</span><span
                            class="metric-value">{{ $subscription['status'] ?? '-' }}</span></div>
                    <div class="metric-row"><span class="metric-label">Term</span><span
                            class="metric-value">{{ $subscription['term'] ?? '-' }}</span></div>
                    <div class="metric-row"><span class="metric-label">Price</span><span
                            class="metric-value">${{ number_format((float) ($subscription['price'] ?? 0), 2) }}</span></div>
                </div>
            @endif
        </section>
    </div>
</div>
