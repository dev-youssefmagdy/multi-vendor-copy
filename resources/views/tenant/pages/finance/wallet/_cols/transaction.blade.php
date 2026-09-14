@if ($subscription->transaction)
    <div class="entity-title">{{ $subscription->transaction->uuid }}</div>
    <div class="entity-subtitle">${{ number_format((float) $subscription->transaction->amount, 2) }}</div>
@else
    <span class="panel-copy">No linked transaction</span>
@endif
