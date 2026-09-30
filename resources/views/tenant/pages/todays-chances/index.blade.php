@extends('tenant.layouts.app')

@section('title', "Today's chances")

{{--
    Today's chances — every winning opportunity, 12 per page.

    BACKEND TODO: everything below is mock data (109 sample opportunities,
    same keys as the dashboard cards). Replace $total / $items with a real
    paginated query. Values marked "dummy" come from the backend; the country
    select and card buttons are UI only for now.
--}}

@php
    $page     = $products->currentPage();
    $lastPage = max(1, $products->lastPage());
    $count    = $products->count();
    $total    = $products->total();
    $pages    = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
    $pageUrl  = fn (int $n) => $products->url($n);
@endphp

@section('content')
    <div class="tc-page">
        <div class="db-welcome fu d0">
            <div class="db-welcome-copy">
                <h1 class="db-welcome-title">Welcome back, {{ tenant('name') ?? 'your store' }}</h1>
                <p class="db-welcome-sub">These are your store's most important opportunities, actions, and results all in one place.</p>
            </div>
            {{-- Market selector — flag dropdown; UI only until the backend provides markets. --}}
            <x-tenant::country-picker trigger-class="tc-country">
                <span data-country-label>Select your country</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
            </x-tenant::country-picker>
        </div>

        @if($products->total() === 0)
            <div class="pm-empty fu d1">
                <h3>No opportunities yet</h3>
                <p>Add products with cost prices set to see opportunities here.</p>
            </div>
        @else
            <div class="tc-grid fu d1">
                @foreach($items as $opp)
                    @include('tenant.pages.dashboard._opportunity-card', ['opp' => $opp, 'cardClass' => 'is-wide'])
                @endforeach
            </div>

            <nav class="tc-pagination" aria-label="Opportunities pages">
                <p>Show <strong>{{ $count }}</strong> of {{ $total }} result</p>
                <div class="tc-pages">
                    @if($page > 1)
                        <a href="{{ $pageUrl($page - 1) }}" class="tc-page-btn is-text" rel="prev">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                            Previous
                        </a>
                    @else
                        <span class="tc-page-btn is-text is-disabled" aria-disabled="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                            Previous
                        </span>
                    @endif

                    @foreach($pages as $i => $n)
                        @if($i > 0 && $n - $pages[$i - 1] > 1)
                            <span class="tc-page-gap" aria-hidden="true">…</span>
                        @endif
                        @if($n === $page)
                            <span class="tc-page-btn is-current" aria-current="page">{{ $n }}</span>
                        @else
                            <a href="{{ $pageUrl($n) }}" class="tc-page-btn">{{ $n }}</a>
                        @endif
                    @endforeach

                    @if($page < $lastPage)
                        <a href="{{ $pageUrl($page + 1) }}" class="tc-page-btn is-next" rel="next">
                            Next
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                        </a>
                    @else
                        <span class="tc-page-btn is-next is-disabled" aria-disabled="true">
                            Next
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                        </span>
                    @endif
                </div>
            </nav>
        @endif
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/todays-chances.js')
@endpush
