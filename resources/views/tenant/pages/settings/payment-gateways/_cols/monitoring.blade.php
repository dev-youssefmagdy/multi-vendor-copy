<div class="entity-subtitle">{{ __('Last synced: :diffForHumans', ['diffForHumans' => $gateway->last_synced_at?->diffForHumans() ?? '—']) }}</div>
<div class="entity-subtitle">{{ __('Last transaction: :diffForHumans', ['diffForHumans' => $gateway->last_transaction_at?->diffForHumans() ?? '—']) }}</div>
@if($gateway->last_error)
    <div class="entity-subtitle t-text-danger">{{ $gateway->last_error }}</div>
@endif
