@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    <x-tenant::page-header :title="$title" :badge="$badge" :description="$description">
        <x-slot:actions>
            <a href="{{ route('tenant.finance.vendor-purchases') }}" class="btn btn-secondary btn-sm">{{ __('Back to Purchases') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="vso-layout">
        <div class="page-stack">
            <section class="card form-card section-gap">
                <div class="locale-badge" style="margin-bottom:10px;">{{ __('Order Summary') }}</div>
                <div class="form-grid form-grid-2" style="gap:8px 16px;">
                    <div>
                        <div class="entity-subtitle">{{ __('Order UUID') }}</div>
                        <div class="entity-title vso-mono">{{ $order->uuid }}</div>
                    </div>
                    <div>
                        <div class="entity-subtitle">{{ __('Order Date') }}</div>
                        <div class="entity-title">{{ $order->created_at?->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div class="entity-subtitle">{{ __('Customer') }}</div>
                        <div class="entity-title">{{ $order->customer?->full_name ?? __('Guest') }}</div>
                        @if($order->customer?->email)
                            <div class="entity-subtitle">{{ $order->customer->email }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="entity-subtitle">{{ __('Items') }}</div>
                        <div class="entity-title">{{ $order->items_count }}</div>
                    </div>
                </div>
            </section>

            <section class="card form-card section-gap">
                <div class="locale-badge" style="margin-bottom:10px;">{{ __('Settlement Breakdown') }}</div>
                <div data-breakdown-body>
                    @include('tenant.pages.finance.vendor-settle._breakdown', ['breakdown' => $breakdown, 'selected' => $presented['selected']])
                </div>
            </section>
        </div>

        <div class="page-stack">
            <section class="card form-card section-gap">
                <div class="locale-badge" style="margin-bottom:10px;">{{ __('Payment Gateway') }}</div>

                <div data-vendor-settle data-breakdown-url="{{ route('tenant.finance.vendor-purchase-settle.breakdown', ['orderId' => $order->id]) }}">
                    <x-tenant::payment.gateway-modal
                        id="vendor-settle-{{ $order->id }}"
                        :title="__('Pay Central')"
                        :action="route('tenant.finance.vendor-purchase-settle.pay', ['orderId' => $order->id])"
                        :validate="route('tenant.finance.vendor-purchase-settle.pay.validate', ['orderId' => $order->id])"
                        :presented="$presented"
                        :submit-label="__('Proceed to Payment')"
                        :inline="true"
                    />
                </div>
            </section>
        </div>
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/finance/vendor-settle.js')
@endpush
