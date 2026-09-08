@extends('layout.app')
@section('title', 'Track Order — ' . ($storeName ?? ''))

@section('content')
<div class="wrap" style="padding:32px 0">
    <h1>Tracking Order #{{ $order->uuid }}</h1>
    <p>Current status: <strong>{{ $order->status?->value ?? $order->status }}</strong></p>
</div>
@endsection
