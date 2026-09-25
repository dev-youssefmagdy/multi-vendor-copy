@extends('tenant.layouts.app')

@section('title', 'Own products')

@php
    // No own products yet → sample cards (dummy data) with a mock 109-item pager, like Today's chances.
    $isMock = $products->total() === 0;
    $total = $isMock ? 109 : $products->total();
    $lastPage = $isMock ? (int) ceil($total / 12) : max(1, $products->lastPage());
    $page = $isMock ? min(max(1, (int) request('page', 1)), $lastPage) : $products->currentPage();
    $count = $isMock ? min(12, $total - ($page - 1) * 12) : $products->count();
    $pageUrl = fn (int $n) => $isMock ? request()->fullUrlWithQuery(['page' => $n]) : $products->url($n);
    $pages = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
@endphp

@section('content')
    <div class="pm-page">
        @include('tenant.pages.catalog._module-nav', ['activeTab' => 'own-products'])

        @if($isMock)
            {{-- No own products yet: show sample cards (dummy data) for now. --}}
            @include('tenant.pages.catalog.own-products._mock-cards')
        @else
            <div class="pm-grid fu d1">
                @foreach($products as $product)
                    @include('tenant.pages.catalog.products._card', ['product' => $product, 'central' => null])
                @endforeach
            </div>
        @endif

        @if($total > 0)
            <nav class="tc-pagination" aria-label="Own products pages">
                <p>Show <strong>{{ $count }}</strong> of {{ $total }} result</p>
                <div class="tc-pages">
                    @if($page <= 1)
                        <span class="tc-page-btn is-text is-disabled" aria-disabled="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</span>
                    @else
                        <a href="{{ $pageUrl($page - 1) }}" class="tc-page-btn is-text" rel="prev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</a>
                    @endif

                    @foreach($pages as $i => $n)
                        @if($i > 0 && $n - $pages[$i - 1] > 1)<span class="tc-page-gap" aria-hidden="true">…</span>@endif
                        @if($n === $page)
                            <span class="tc-page-btn is-current" aria-current="page">{{ $n }}</span>
                        @else
                            <a href="{{ $pageUrl($n) }}" class="tc-page-btn">{{ $n }}</a>
                        @endif
                    @endforeach

                    @if($page < $lastPage)
                        <a href="{{ $pageUrl($page + 1) }}" class="tc-page-btn is-next" rel="next">Next <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a>
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
