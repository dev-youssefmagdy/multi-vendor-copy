@extends('tenant.layouts.app')

@section('title', __('Sort Categories'))

@section('content')
    <x-tenant::page-header :title="__('Category Order')" :badge="__('Catalog')" :description="__('Drag rows to reorder how categories appear in the storefront.')">
        <x-slot:actions>
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">{{ __('Back to Categories') }}</a>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card :title="__('Active Categories')">
        @if($categories->isEmpty())
            <x-tenant::empty-state :title="__('No categories')" :copy="__('Add categories before ordering them.')" />
        @else
            <x-tenant::sortable-list id="categories-sort" :save-url="route('tenant.categories.sort.save')" method="POST" payload-key="ids">
                @foreach($categories as $category)
                    @include('tenant.pages.catalog.sort._row', ['item' => $category])
                @endforeach
            </x-tenant::sortable-list>
        @endif
    </x-tenant::card>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/catalog/sortable.js')
@endpush
