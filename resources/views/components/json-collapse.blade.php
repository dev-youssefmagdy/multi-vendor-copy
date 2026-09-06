@props(['title' => 'JSON Payload', 'summary' => null, 'data' => null, 'startOpen' => false])

@php
    $hasData = is_array($data) || is_object($data) || (is_string($data) && trim($data) !== '') || is_numeric($data) || is_bool($data);
@endphp

@if (!$hasData)
    <div class="json-empty">No structured data available.</div>
@else
    <details class="json-collapse" @if ($startOpen) open @endif>
        <summary class="json-collapse-summary">
            <div>
                <div class="details-inline-title">{{ $title }}</div>
                @if ($summary)
                    <div class="details-inline-copy">{{ $summary }}</div>
                @endif
            </div>
            <span class="json-collapse-badge">Expand</span>
        </summary>

        <div class="json-collapse-body">
            <x-json-tree :data="$data" :startOpen="true" />
        </div>
    </details>
@endif