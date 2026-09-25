@extends('tenant.layouts.app')

@section('title', 'Product Requests')

@section('content')
    @include('tenant.pages.requests._list', [
        'title' => 'Product Requests',
        'description' => 'Track the status of your product requests to the Neozena team.',
        'stats' => $stats,
        'tableId' => 'product-requests-table',
        'url' => route('tenant.product-requests.data'),
        'tableColumns' => $columns,
        'order' => [[2, 'desc']],
        'statusOptions' => $statusOptions,
        'createUrl' => route('tenant.product-requests.create'),
        'emptyTitle' => 'No product requests yet',
        'emptyCopy' => "Submit a request when you'd like a new product added to the catalog.",
    ])
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/product-requests-index.js')
@endpush
