@extends('layout.app')

@section('content')
    @include('pages.home.sections.hero')
    @include('pages.home.sections.trust_bar')
    @if ($flash_sales->isNotEmpty())
        @include('pages.home.sections.flash_sale')
    @endif
    @if ($rootCategories->isNotEmpty())
        @include('pages.home.sections.browse_categories')
    @endif
    @if ($new_arrivals->isNotEmpty())
        @include('pages.home.sections.new_arrivals')
    @endif
    @if ($recommended_products->isNotEmpty())
        @include('pages.home.sections.recommended_products')
    @endif
    @if ($best_sellers->isNotEmpty())
        @include('pages.home.sections.featured_products')
    @endif
@endsection
