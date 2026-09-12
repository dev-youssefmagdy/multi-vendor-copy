@extends('tenant.layouts.app')

@section('title', 'Sort Category Products')

@section('content')
    <x-tenant::page-header title="Product Order — {{ $category->translationValue('name') ?? $category->slug }}" badge="Catalog" description="Drag rows to reorder how products appear within this category.">
        <x-slot:actions>
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card title="Category Products">
        @if($products->isEmpty())
            <x-tenant::empty-state title="No products" copy="Assign products to this category before ordering them." />
        @else
            <x-tenant::sortable-list id="category-products-sort" :save-url="route('tenant.categories.products.sort', $category)" method="POST" payload-key="ids">
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
