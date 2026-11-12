@if($central)
    <div class="entity-title">${{ number_format((float) ($central['current_price'] ?? 0), 2) }}</div>
    <div class="entity-subtitle">{{ __('Base $:currentprice', ['currentprice' => number_format((float) ($central['base_price'] ?? $central['current_price'] ?? 0), 2)]) }}</div>
@else
    <span class="entity-subtitle">{{ __('Not linked') }}</span>
@endif
