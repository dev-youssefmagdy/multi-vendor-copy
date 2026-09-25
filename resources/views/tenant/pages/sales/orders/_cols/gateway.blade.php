<span title="{{ $order->paid ? 'Paid' : 'Unpaid' }}">{{ str((string) $order->payment_method)->replace(['_', '-'], ' ')->lower()->ucfirst()->toString() ?: '—' }}</span>
