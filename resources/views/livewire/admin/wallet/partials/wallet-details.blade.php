@php
    $wallet = $wallet ?? [];
@endphp

<div class="details-stack">
    <div class="details-grid">
        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Wallet Summary</h4>
                <p class="panel-copy">Tenant settlement position combined from orders, subscriptions, and wallet ledger
                    records.</p>
            </div>
            <div class="details-list">
                <div class="details-kv"><span class="details-label">Store</span><span
                        class="details-value">{{ $wallet['store_name'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Tenant Email</span><span
                        class="details-value">{{ $wallet['tenant_email'] ?? '-' }}</span></div>
                <div class="details-kv"><span class="details-label">Currency</span><span
                        class="details-value">{{ $wallet['currency'] ?? 'USD' }}</span></div>
                <div class="details-kv"><span class="details-label">Balance</span><span
                        class="details-value">${{ number_format((float) ($wallet['balance'] ?? 0), 2) }}</span></div>
                <div class="details-kv"><span class="details-label">Pending</span><span
                        class="details-value">${{ number_format((float) ($wallet['pending_balance'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Gross Sales</span><span
                        class="details-value">${{ number_format((float) ($wallet['gross_sales'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Collected Sales</span><span
                        class="details-value">${{ number_format((float) ($wallet['collected_sales'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Owner Profit</span><span
                        class="details-value">${{ number_format((float) ($wallet['owner_profit'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Subscriptions</span><span
                        class="details-value">{{ number_format((int) ($wallet['active_subscriptions'] ?? 0)) }}
                        active</span></div>
                <div class="details-kv"><span class="details-label">Subscription Revenue</span><span
                        class="details-value">${{ number_format((float) ($wallet['subscription_revenue'] ?? 0), 2) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Transactions</span><span
                        class="details-value">{{ number_format((int) ($wallet['transactions_count'] ?? 0)) }}</span>
                </div>
                <div class="details-kv"><span class="details-label">Updated</span><span
                        class="details-value">{{ optional($wallet['updated_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</span>
                </div>
            </div>
        </section>

        <section class="details-panel">
            <div class="details-header">
                <h4 class="panel-title">Recent Orders</h4>
                <p class="panel-copy">Latest order rows contributing to the current wallet state.</p>
            </div>
            <div class="details-list">
                @forelse ($wallet['recent_orders'] ?? [] as $order)
                    <div class="activity-row">
                        <div class="activity-row-title">{{ $order['order_number'] ?? '-' }} ·
                            {{ $order['customer_name'] ?? 'Guest' }}</div>
                        <div class="activity-row-copy">${{ number_format((float) ($order['total_amount'] ?? 0), 2) }} ·
                            {{ $order['status'] ?? '-' }} · {{ !empty($order['paid']) ? 'Paid' : 'Unpaid' }}</div>
                        <div class="activity-row-date">
                            {{ optional($order['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</div>
                    </div>
                @empty
                    <div class="json-empty">No recent orders were found for this tenant.</div>
                @endforelse
            </div>
        </section>
    </div>

    <x-card-collapse title="Subscriptions" subtitle="Tenant subscription records and their gateway routing."
        :start-open="true">
        <div class="tw">
            <table class="details-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Term</th>
                        <th>Gateway</th>
                        <th>Price</th>
                        <th>Transaction</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($wallet['subscriptions'] ?? [] as $subscription)
                        <tr>
                            <td>{{ $subscription['id'] ?? '-' }}</td>
                            <td>{{ $subscription['status'] ?? '-' }}</td>
                            <td>{{ $subscription['term'] ?? '-' }}</td>
                            <td>{{ $subscription['gateway'] ?? '-' }}</td>
                            <td>${{ number_format((float) ($subscription['price'] ?? 0), 2) }}</td>
                            <td>{{ $subscription['transaction_reference'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="json-empty">No subscriptions were found for this tenant.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card-collapse>

    <x-card-collapse title="Recent Transactions"
        subtitle="Latest credit and debit movements recorded in the tenant ledger." :start-open="false">
        <div class="details-list">
            @forelse ($wallet['recent_transactions'] ?? [] as $transaction)
                <div class="activity-row">
                    <div class="activity-row-title">{{ $transaction['reference'] ?? '-' }} ·
                        {{ $transaction['direction'] ?? '-' }}</div>
                    <div class="activity-row-copy">${{ number_format((float) ($transaction['amount'] ?? 0), 2) }} ·
                        ${{ number_format((float) ($transaction['admin_profit'] ?? 0), 2) }} admin profit</div>
                    <div class="activity-row-date">
                        {{ optional($transaction['created_at'] ?? null)->format('M d, Y H:i') ?? '-' }}</div>
                </div>
            @empty
                <div class="json-empty">No recent transactions were found for this tenant.</div>
            @endforelse
        </div>
    </x-card-collapse>
</div>
