@extends('tenant.layouts.app')

@section('title', 'Sort Badge Products')

@section('content')
    <x-tenant::page-header title="Sort — {{ ucwords(str_replace('-', ' ', $badge->text)) }}" badge="Catalog" description="Drag rows to reorder how these products appear for this badge on the storefront.">
        <x-slot:actions>
            <a href="{{ route('tenant.badges.show', $badge) }}" class="btn btn-secondary">Back to Assignment</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card title="Assigned Products">
        @if($products->isEmpty())
            <x-tenant::empty-state title="No products assigned" copy="Assign products to this badge before ordering them." />
        @else
            <x-tenant::sortable-list id="badge-products-sort" :save-url="route('tenant.badges.sort', ['badge' => $badge, 'country_id' => $activeCountryId])" method="POST" payload-key="ids">
                @foreach($products as $product)
                    @include('tenant.pages.catalog.sort._row', ['item' => $product])
                @endforeach
            </x-tenant::sortable-list>
        @endif
    </x-tenant::card>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/sortable.js')
@endpush
