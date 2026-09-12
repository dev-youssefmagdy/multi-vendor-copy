@php
    $transactionId = data_get($order->payment_details, 'transaction_id');
@endphp
<span class="badge {{ $order->paid ? 'badge-green' : 'badge-amber' }}">{{ $order->paid ? 'Paid' : 'Unpaid' }}</span>
<div class="entity-subtitle">{{ $transactionId ?: 'No transaction id' }}</div>
