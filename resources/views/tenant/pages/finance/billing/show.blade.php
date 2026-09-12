@extends('tenant.layouts.app')

@section('title', $order['uuid'] ?? 'Billing Details')

@section('content')
    <x-tenant::page-header :title="$order['uuid'] ?? 'Billing Details'" badge="Finance" description="Full billing detail — payment state, gateway payload, financials, and order line items.">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.finance.billing') }}">← Billing</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="page-stack section-gap">
        {{-- Reuses the Sales order-details partial (resources/views/tenant/pages/sales/orders/_partials/details.blade.php)
             instead of duplicating the snapshot/financials/line-items/tracking/address/payment markup. --}}
        @include('tenant.pages.sales.orders._partials.details', ['order' => $order])

        @include('tenant.pages.sales.orders._partials.shipping-status', ['order' => $order, 'orderId' => $orderId, 'shippingStatuses' => $shippingStatuses ?? []])
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/billing-show.js')
@endpush
