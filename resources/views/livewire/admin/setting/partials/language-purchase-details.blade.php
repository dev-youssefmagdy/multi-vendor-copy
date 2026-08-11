<div class="page-stack">
    <div>
        <div class="panel-title">Tenant</div>
        <p class="panel-copy">{{ $purchase['tenant_name'] ?? $purchase['tenant_id'] ?? '-' }}
            ({{ $purchase['tenant_email'] ?? '-' }})</p>
    </div>

    <div>
        <div class="panel-title">Language</div>
        <p class="panel-copy">{{ $purchase['language'] ?? '-' }} ({{ strtoupper($purchase['language_code'] ?? '-') }})
        </p>
    </div>

    <div>
        <div class="panel-title">Amount</div>
        <p class="panel-copy">${{ number_format($purchase['amount'] ?? 0, 2) }}</p>
    </div>

    <div>
        <div class="panel-title">Gateway</div>
        <p class="panel-copy">{{ strtoupper($purchase['gateway'] ?? '-') }}</p>
    </div>

    <div>
        <div class="panel-title">Transaction</div>
        <p class="panel-copy">{{ $purchase['transaction_uuid'] ?? '-' }}</p>
    </div>

    <div>
        <div class="panel-title">Purchased At</div>
        <p class="panel-copy">{{ optional($purchase['created_at'] ?? now())->format('M d, Y H:i') ?? '-' }}</p>
    </div>
</div>