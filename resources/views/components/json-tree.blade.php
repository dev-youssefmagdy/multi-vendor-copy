@props(['data' => null, 'label' => null, 'startOpen' => false])

@php
    $value = $data;

    if ($value instanceof \Illuminate\Support\Collection) {
        $value = $value->all();
    }

    if ($value instanceof \BackedEnum) {
        $value = $value->value;
    }

    if (is_string($value)) {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $value = $decoded;
        }
    }

    $isBranch = is_array($value) || is_object($value);
    $items = $isBranch ? (array) $value : [];
    $branchType = is_array($value) && array_is_list($value) ? 'list' : 'object';
    $displayValue = match (true) {
        $value === null => 'null',
        is_bool($value) => $value ? 'true' : 'false',
        is_scalar($value) => (string) $value,
        default => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '',
    };
@endphp

@if ($isBranch)
    <details class="json-tree-node" @if ($startOpen || $label === null) open @endif>
        <summary class="json-tree-summary">
            <span class="json-tree-key">{{ $label ?? 'payload' }}</span>
            <span class="json-tree-meta">{{ $branchType }} · {{ count($items) }}</span>
        </summary>

        <div class="json-tree-children">
            @forelse ($items as $childKey => $childValue)
                <x-json-tree :data="$childValue" :label="(string) $childKey" />
            @empty
                <div class="json-tree-empty">empty</div>
            @endforelse
        </div>
    </details>
@else
    <div class="json-tree-leaf">
        @if ($label !== null)
            <div class="json-tree-key">{{ $label }}</div>
        @endif

        <div class="json-tree-value">{{ $displayValue }}</div>
    </div>
@endif