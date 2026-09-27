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
    'language' => null,
    'infoTemplate' => null,
    'lengthChange' => null,
    'searchPlaceholder' => null,
    'responsive' => null,
    'design' => true,
    'mobileCards' => null,
])

{{--
    design       the "Order Queue" table design (components/ds-table.css): grey
                 search box + orange filter button (put the filters card in the
                 toolbar slot), no row-number column, "Show :count of :total
                 result" + Previous/Next pager.
    mobileCards  rows become cards on mobile (defaults to `design`).
    Pages with their own table styles (Orders, Customers, Requests) pass
    :design="false" :mobile-cards="false".
--}}

@php
    $mobileCards ??= $design;
    $responsive ??= ! $mobileCards; // card rows need every column in the DOM
    $lengthChange ??= ! $design;
    $searchPlaceholder ??= $design ? 'Search' : 'Quick search';

    if ($design) {
        $chevronLeft = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
        $chevronRight = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
        $infoTemplate ??= 'Show :count of :total result';
        $language ??= ['paginate' => ['previous' => $chevronLeft.' Previous', 'next' => 'Next '.$chevronRight]];
    }

    $columnsArray = array_map(fn ($col) => $col instanceof \App\Support\Tenant\TableColumn ? $col->toArray() : $col, $columns);

    // The design has no row-number column: drop it and shift the sort indexes.
    if ($design && $rows === null) {
        $removed = array_keys(array_filter($columnsArray, fn ($col) => ($col['data'] ?? null) === 'DT_RowIndex'));
        if ($removed) {
            $columnsArray = array_values(array_diff_key($columnsArray, array_flip($removed)));
            $order = collect($order)
                ->reject(fn ($rule) => in_array($rule[0], $removed, true))
                ->map(fn ($rule) => [$rule[0] - count(array_filter($removed, fn ($i) => $i < $rule[0])), $rule[1]])
                ->values()
                ->all();
        }
    }

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
        'language' => $language,
        'infoTemplate' => $infoTemplate,
        'lengthChange' => $lengthChange,
        'responsive' => $responsive,
    ];
@endphp

<div @class(['card fu d4 table-card-shell', 'ds-table' => $design, 'ds-mcards' => $mobileCards]) data-tenant-datatable-card>
    <div class="table-header-shell">
        <div>
            @if($title)<h3 class="panel-title">{{ $title }}</h3>@endif
            @if($description)<p class="panel-copy" data-table-description>{{ $description }}</p>@endif
        </div>
        <div class="table-header-actions">
            @if($quickSearch)
                <input type="text" class="field-control table-search" placeholder="{{ $searchPlaceholder }}" data-table-quick-search>
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
