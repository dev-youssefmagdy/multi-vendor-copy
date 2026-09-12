@extends('tenant.layouts.app')

@section('title', 'Sort Products')

@section('content')
    <x-tenant::page-header title="Product Order" badge="Catalog" description="Drag rows to reorder how products appear in the storefront listing.">
        <x-slot:actions>
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to Products</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card title="All Products">
        <form method="GET" action="{{ route('tenant.products.sort') }}" class="mb-4">
            <x-tenant::input type="text" name="search" placeholder="Search by name or slug" value="{{ $search }}" />
        </form>

        @if($products->isEmpty())
            <x-tenant::empty-state title="No products" copy="Add products before ordering them." />
        @else
            <x-tenant::sortable-list id="products-sort" :save-url="route('tenant.products.sort.save')" method="POST" payload-key="ids">
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
