{{-- PANELS-UPDATES-TABLE-SORTING: one sortable <th> for hand-written tables (host component uses SortsTable). --}}
<th @if($sortKey === $key) aria-sort="{{ $sortDir === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <button type="button" wire:click="sortByColumn('{{ $key }}')"
            style="all:unset;cursor:pointer;display:inline-flex;align-items:center;gap:4px;font:inherit;color:inherit;text-transform:inherit;letter-spacing:inherit">
        {{ $label }}
        <span aria-hidden="true" style="font-size:10px;opacity:{{ $sortKey === $key ? '1' : '.35' }}">{{ $sortKey === $key ? ($sortDir === 'asc' ? '▲' : '▼') : '↕' }}</span>
    </button>
</th>
