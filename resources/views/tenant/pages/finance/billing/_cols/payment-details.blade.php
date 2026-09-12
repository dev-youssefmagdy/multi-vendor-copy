@php
    $transactionId = data_get($order->payment_details, 'transaction_id');
    $hasData = !empty($order->payment_details);
@endphp
@if (!$hasData)
    <div class="json-empty">No structured data available.</div>
@else
    <details class="json-collapse">
        <summary class="json-collapse-summary">
            <div>
                <div class="details-inline-title">Gateway payload</div>
                <div class="details-inline-copy">{{ $transactionId ? ('Transaction ' . $transactionId) : 'Open nested gateway response' }}</div>
            </div>
            <span class="json-collapse-badge">Expand</span>
        </summary>

        <div class="json-collapse-body">
            <x-tenant::json-tree :nodes="\App\Support\Tenant\JsonTreeBuilder::nodes($order->payment_details)" />
        </div>
    </details>
@endif
