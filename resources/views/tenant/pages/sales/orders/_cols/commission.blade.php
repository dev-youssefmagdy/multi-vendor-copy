@php
    $tenantProfit = \App\Support\OrderProfitCalculator::effectiveTenantProfitForOrder($order);
@endphp
$ {{ number_format($tenantProfit, 2) }}
