{{-- PANELS-UPDATES-TABLE-SORTING: pass `sortable` (label => key), `sortKey`, `sortDir` and a header becomes a
     click-to-sort button (calls sortByColumn on the host Livewire component). Without them it renders as before. --}}
@props(['headers', 'sortable' => [], 'sortKey' => '', 'sortDir' => 'desc'])

<div class="tw">
    <table {{ $attributes->merge(['class' => 'tb']) }}>
        <thead>
            <tr>
                @foreach($headers as $header)
                    @php $key = $sortable[$header] ?? null; @endphp
                    <th @if($key && $sortKey === $key) aria-sort="{{ $sortDir === 'asc' ? 'ascending' : 'descending' }}" @endif>
                        @if($key)
                            <button type="button" wire:click="sortByColumn('{{ $key }}')"
                                    style="all:unset;cursor:pointer;display:inline-flex;align-items:center;gap:4px;font:inherit;color:inherit;text-transform:inherit;letter-spacing:inherit">
                                {{ $header }}
                                <span aria-hidden="true" style="font-size:10px;opacity:{{ $sortKey === $key ? '1' : '.35' }}">{{ $sortKey === $key ? ($sortDir === 'asc' ? '▲' : '▼') : '↕' }}</span>
                            </button>
                        @else
                            {{ $header }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
