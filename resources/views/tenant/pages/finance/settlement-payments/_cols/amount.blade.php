<strong>${{ number_format((float) $record->total, 2) }}</strong>
<div class="entity-subtitle">{{ __('Fee: $:gatewayfee', ['gatewayfee' => number_format((float) $record->gateway_fee, 2)]) }}</div>
