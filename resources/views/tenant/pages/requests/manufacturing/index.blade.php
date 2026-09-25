@extends('tenant.layouts.app')

@section('title', 'Manufacturing Requests')

@php
    // Columns in the design order: Product, Qty, Admin Notes, Status, Submitted, Actions.
    $byKey = collect($columns)->keyBy(fn ($col) => ($col instanceof \App\Support\Tenant\TableColumn ? $col->toArray() : $col)['data'] ?? '');
    $tableColumns = collect(['product', 'quantity', 'admin_notes', 'status', 'submitted', 'actions'])
        ->map(fn ($key) => $byKey->get($key))->filter()->values()->all();
    $submittedIndex = array_search('submitted', ['product', 'quantity', 'admin_notes', 'status', 'submitted', 'actions'], true);
@endphp

@section('content')
    @include('tenant.pages.requests._list', [
        'title' => $title,
        'description' => $description,
        'stats' => $stats,
        'tableId' => 'manufacturing-table',
        'url' => route('tenant.manufacturing.data'),
        'tableColumns' => $tableColumns,
        'order' => [[$submittedIndex, 'desc']],
        'statusOptions' => $statusOptions,
        'queueClass' => 'is-mfg',
        'createUrl' => route('tenant.manufacturing.create'),
        'exportUrl' => route('tenant.manufacturing.export'),
        'exportId' => 'manufacturing-export-link',
        'search' => true,
        'emptyTitle' => 'No manufacturing requests',
        'emptyCopy' => 'Submit your first manufacturing request using the Add Request button.',
    ])
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/manufacturing-index.js')
@endpush
