@extends('tenant.layouts.app')

@section('title', $title)

@section('content')
    @include('tenant.pages.requests._list', [
        'title' => $title,
        'description' => $description,
        'stats' => $stats,
        'tableId' => 'brand-requests-table',
        'url' => route('tenant.brand-requests.data'),
        'tableColumns' => $columns,
        'order' => [[2, 'desc']],
        'statusOptions' => collect($statusOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(),
        'createUrl' => route('tenant.brand-requests.create'),
        'emptyTitle' => 'No brand requests yet',
        'emptyCopy' => "Submit a request when you'd like to carry a new brand in your store.",
    ])
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/requests/brand-index.js')
@endpush
