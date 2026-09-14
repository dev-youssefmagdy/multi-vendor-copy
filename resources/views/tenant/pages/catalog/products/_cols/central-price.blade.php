@if($central)
    <div class="entity-title">${{ number_format((float) ($central['current_price'] ?? 0), 2) }}</div>
    <div class="entity-subtitle">Base ${{ number_format((float) ($central['base_price'] ?? $central['current_price'] ?? 0), 2) }}</div>
@else
    <span class="entity-subtitle">Not linked</span>
@endif
