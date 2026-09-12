<div class="entity-title">{{ str($subscription->payment_gateway_type->value)->headline()->toString() }}</div>
<div class="entity-subtitle">Gateway ID {{ $subscription->payment_gateway_id ?: 'N/A' }}</div>
