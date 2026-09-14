@if (!$order->vendor_settled)
    <a href="{{ route('tenant.finance.vendor-purchase-settle', $order->id) }}" class="btn btn-primary btn-sm">Pay Central</a>
@endif
