@props([
    'id' => null,
    'url' => null,
    'columns' => [],
    'order' => [[0, 'asc']],
    'pageLength' => 10,
    'title' => null,
    'description' => null,
    'emptyTitle' => 'No records found',
    'emptyCopy' => 'No tenant records match the current filters yet.',
    'quickSearch' => false,
    'filters' => null,
    'selectable' => false,
    'bulkUrl' => null,
    'bulkMethod' => 'DELETE',
    'bulkConfirm' => 'Delete the selected records?',
    'rows' => null,
    'mode' => 'server',
    'paging' => true,
    'searching' => false,
])

@php
    $columnsArray = array_map(fn ($col) => $col instanceof \App\Support\Tenant\TableColumn ? $col->toArray() : $col, $columns);
    $config = [
        'url' => $url,
        'columns' => $columnsArray,
        'order' => $order,
        'pageLength' => $pageLength,
        'filters' => $filters,
        'selectable' => $selectable,
        'bulkUrl' => $bulkUrl,
        'bulkMethod' => $bulkMethod,
        'bulkConfirm' => $bulkConfirm,
        'mode' => $mode,
        'paging' => $paging,
        'searching' => $searching,
        'emptyTitle' => $emptyTitle,
        'emptyCopy' => $emptyCopy,
        'descriptionTemplate' => $description,
    ];
@endphp

<div class="card fu d4 table-card-shell" data-tenant-datatable-card>
    <div class="table-header-shell">
        <div>
            @if($title)<h3 class="panel-title">{{ $title }}</h3>@endif
            @if($description)<p class="panel-copy" data-table-description>{{ $description }}</p>@endif
        </div>
        <div class="table-header-actions">
            @if($quickSearch)
                <input type="text" class="field-control table-search" placeholder="Quick search" data-table-quick-search>
            @endif
            @isset($toolbar){{ $toolbar }}@endisset
        </div>
    </div>

    @if($selectable)
        <div class="t-bulk-bar" data-bulk-bar hidden>
            <span data-bulk-count>0 selected</span>
            <button type="button" class="btn btn-danger btn-sm" data-bulk-action>Delete selected</button>
        </div>
    @endif

    <div class="tw">
        <table id="{{ $id }}" data-tenant-datatable data-config='@json($config)' class="tb">
            <thead>
                <tr>
                    @if($selectable)
                        <th class="t-select-col"><input type="checkbox" data-select-all></th>
                    @endif
                    @foreach($columnsArray as $col)
                        <th>{{ $col['title'] ?? '' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if($mode === 'client' && $rows)
                    @foreach($rows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{!! $cell !!}</td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    <div class="table-footer-shell">
        <div class="panel-copy" data-table-info></div>
        <div class="pagination-shell" data-table-pagination></div>
    </div>
</div>
