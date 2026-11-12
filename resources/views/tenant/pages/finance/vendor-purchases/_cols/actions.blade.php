@if (\App\Support\OrderProfitCalculator::vendorOwnPlatformForOrder($order) > 0)
    <a href="{{ route('tenant.finance.vendor-purchase-settle', $order->id) }}" class="btn btn-primary btn-sm">{{ __('Pay platform') }}</a>
@endif
