@php
    $raw = $transaction->description;
    $decoded = is_string($raw) ? (json_decode($raw, true) ?? $raw) : $raw;
    $hasData = is_array($decoded) ? $decoded !== [] : filled($decoded);
@endphp

@if (!$hasData)
    <span class="panel-copy">{{ __('No detail payload stored') }}</span>
@else
    <details class="json-collapse">
        <summary class="json-collapse-summary">
            <div>
                <div class="details-inline-title">{{ __('Transaction details') }}</div>
                <div class="details-inline-copy">{{ __('Open stored transaction metadata') }}</div>
            </div>
            <span class="json-collapse-badge">{{ __('Expand') }}</span>
        </summary>
        <div class="json-collapse-body">
            <x-tenant::json-tree :nodes="\App\Support\Tenant\JsonTreeBuilder::nodes($decoded)" />
        </div>
    </details>
@endif
