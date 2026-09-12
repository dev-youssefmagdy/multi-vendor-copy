<div class="entity-subtitle">Last synced: {{ $gateway->last_synced_at?->diffForHumans() ?? '—' }}</div>
<div class="entity-subtitle">Last transaction: {{ $gateway->last_transaction_at?->diffForHumans() ?? '—' }}</div>
@if($gateway->last_error)
    <div class="entity-subtitle t-text-danger">{{ $gateway->last_error }}</div>
@endif
