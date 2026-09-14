@php
    $tenantProfit = \App\Support\OrderProfitCalculator::effectiveTenantProfitForOrder($order);
    $ownerProfit = \App\Support\OrderProfitCalculator::effectiveOwnerProfitForOrder($order);
@endphp
<div class="entity-title">{{ number_format($tenantProfit, 2) }}</div>
<div class="entity-subtitle">platform {{ number_format($ownerProfit, 2) }}</div>
