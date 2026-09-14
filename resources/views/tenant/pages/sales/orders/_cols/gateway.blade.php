<div class="entity-title">{{ str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString() }}</div>
<div class="entity-subtitle">{{ $order->paid ? 'Paid' : 'Unpaid' }}</div>
