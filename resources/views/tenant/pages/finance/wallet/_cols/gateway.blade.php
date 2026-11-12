<div class="entity-title">{{ __((string) str($subscription->payment_gateway_type->value)->headline()) }}</div>
<div class="entity-subtitle">{{ __('Gateway ID :value', ['value' => $subscription->payment_gateway_id ?: __('N/A')]) }}</div>
