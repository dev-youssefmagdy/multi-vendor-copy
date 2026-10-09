@extends('tenant.layouts.app')

@section('title', $order['uuid'] ?? 'Order Details')

@section('content')
    <x-tenant::page-header :title="$order['uuid'] ?? 'Order Details'" badge="Sales" description="Full order detail — financials, line items, shipping address, and gateway payload.">
        <x-slot:actions>
            @if(!empty($afterSales['can_cancel']))
                <button type="button" class="btn" style="background:var(--red);color:#fff" data-modal-open="cancel-order-modal">{{ __('Cancel order') }}</button>
            @endif
            @if(!empty($settlement))
                <a class="btn btn-primary" href="#pay-central">Pay Central</a>
            @endif
            <a class="btn btn-secondary" href="{{ route('tenant.orders.index') }}">← Orders</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="page-stack section-gap">
        @include('tenant.pages.sales.orders._partials.details', ['order' => $order])

        @include('tenant.pages.sales.orders._partials.after-sales', ['afterSales' => $afterSales ?? [], 'orderId' => $orderId])

        @if(!empty($settlement))
            @include('tenant.pages.sales.orders._partials.pay-central', ['settlement' => $settlement, 'orderId' => $orderId])
        @endif

        @include('tenant.pages.sales.orders._partials.shipping-status', ['order' => $order, 'orderId' => $orderId, 'shippingStatuses' => $shippingStatuses ?? []])
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/order-show.js')
    @if(!empty($settlement))
        @vite('resources/js/tenant/pages/finance/vendor-settle.js')
    @endif
@endpush
