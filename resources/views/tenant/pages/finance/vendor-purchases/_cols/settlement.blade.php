@if (\App\Support\OrderProfitCalculator::vendorOwnPlatformForOrder($order) <= 0)
    <span class="badge badge-green">{{ __('Settled') }}</span>
@else
    <span class="badge badge-amber">{{ __('Pending') }}</span>
@endif
