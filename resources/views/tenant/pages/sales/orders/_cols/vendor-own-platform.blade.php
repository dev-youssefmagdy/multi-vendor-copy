@php
    $owed = \App\Support\OrderProfitCalculator::vendorOwnPlatformForOrder($order);
@endphp
${{ number_format($owed, 2) }}
@if($owed > 0)
    <div><a href="{{ route('tenant.finance.vendor-purchase-settle', $order->id) }}" class="btn btn-primary btn-sm">Pay platform</a></div>
@endif
