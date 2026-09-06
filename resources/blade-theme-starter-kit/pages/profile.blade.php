@extends('layout.app')
@section('title', 'My Account — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0">
    <h1>My Account</h1>
    <p>Welcome back, {{ $customer->full_name ?? $customer->email }}</p>

    <h2 style="margin-top:32px">Order History</h2>
    @forelse ($orders as $order)
        <div style="border:1px solid #eee;border-radius:8px;padding:16px;margin-bottom:12px">
            <p>Order #{{ $order->uuid }} — {{ $order->status?->value ?? $order->status }}</p>
            <a href="{{ route('tenant.storefront.order-status', $order->uuid) }}">View Order</a>
        </div>
    @empty
        <p>No orders yet.</p>
    @endforelse

    <h2 style="margin-top:32px">Addresses</h2>
    @forelse ($addresses as $addr)
        <div style="border:1px solid #eee;border-radius:8px;padding:16px;margin-bottom:12px">
            <p>{{ $addr->address_line_1 }}, {{ $addr->city }}</p>
        </div>
    @empty
        <p>No saved addresses.</p>
    @endforelse
</div>
@endsection
