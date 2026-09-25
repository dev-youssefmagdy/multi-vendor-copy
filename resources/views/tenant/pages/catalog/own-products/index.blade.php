@extends('tenant.layouts.app')

@section('title', 'Own products')

@php
    $page = $products->currentPage();
    $lastPage = max(1, $products->lastPage());
    $pages = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
@endphp

@section('content')
    <div class="pm-page">
        @include('tenant.pages.catalog._module-nav', [
            'activeTab' => 'own-products',
            'heading' => [
                'title' => 'Own Products',
                'sub' => 'Manage tenant-scoped product details, pricing, availability, and featured status.',
                'add' => route('tenant.own-products.create'),
            ],
        ])

        @if($products->total() === 0)
            {{-- No own products yet: sample cards (dummy data) for now. --}}
            @include('tenant.pages.catalog._mock-cards', ['added' => false, 'pagerLabel' => 'Own products pages'])
        @else
            <div class="pm-grid fu d1">
                @foreach($products as $product)
                    @include('tenant.pages.catalog.products._card', ['product' => $product, 'central' => null, 'added' => false])
                @endforeach
            </div>

            <nav class="tc-pagination" aria-label="Own products pages">
                <p>Show <strong>{{ $products->count() }}</strong> of {{ $products->total() }} result</p>
                <div class="tc-pages">
                    @if($products->onFirstPage())
                        <span class="tc-page-btn is-text is-disabled" aria-disabled="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</span>
                    @else
                        <a href="{{ $products->previousPageUrl() }}" class="tc-page-btn is-text" rel="prev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</a>
                    @endif

                    @foreach($pages as $i => $n)
                        @if($i > 0 && $n - $pages[$i - 1] > 1)<span class="tc-page-gap" aria-hidden="true">…</span>@endif
                        @if($n === $page)
                            <span class="tc-page-btn is-current" aria-current="page">{{ $n }}</span>
                        @else
                            <a href="{{ $products->url($n) }}" class="tc-page-btn">{{ $n }}</a>
                        @endif
                    @endforeach

                    @if($products->hasMorePages())
                        <a href="{{ $products->nextPageUrl() }}" class="tc-page-btn is-next" rel="next">Next <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a>
                    @else
                        <span class="tc-page-btn is-next is-disabled" aria-disabled="true">Next <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></span>
                    @endif
                </div>
            </nav>
        @endif
    </div>

    @include('tenant.pages.catalog.products._modals.video-ad')
@endsection

@push('tenant-vite')
    @vite(['resources/js/tenant/pages/catalog/products-index.js', 'resources/js/tenant/pages/catalog/products-grid.js'])
@endpush
