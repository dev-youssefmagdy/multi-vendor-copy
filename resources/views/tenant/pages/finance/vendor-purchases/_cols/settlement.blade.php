@if (\App\Support\OrderProfitCalculator::vendorOwnPlatformForOrder($order) <= 0)
    <span class="badge badge-green">Settled</span>
@else
    <span class="badge badge-amber">Pending</span>
@endif
