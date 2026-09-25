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
    // FOR DESIGN PURPOSE
    $photos = [
        'photo-1590874103328-eac38a683ce7', 'photo-1505740420928-5e560c06d30e', 'photo-1523275335684-37898b6baf30',
        'photo-1525966222134-fcfa99b8ae77', 'photo-1583394838336-acd977736f90', 'photo-1572635196237-14b3f281503f',
        'photo-1521572163474-6864f9cf17ab', 'photo-1511499767150-a48a237f0083', 'photo-1527864550417-7fd91fc51a46',
        'photo-1546868871-7041f2a55e12',
    ];
    $markets = ['sa' => 'KSA', 'gb' => 'UK', 'eg' => 'Egy', 'us' => 'USA', 'ae' => 'UAE', 'fr' => 'FRA', 'ma' => 'Mor', 'iq' => 'IRQ', 'qa' => 'QTR'];

    $total = 109;
    $perPage = 12;
    $lastPage = (int) ceil($total / $perPage);
    $page = min(max(1, (int) request('page', 1)), $lastPage);
    $from = ($page - 1) * $perPage;
    $count = min($perPage, $total - $from);

    $items = collect(range($from, $from + $count - 1))->map(fn (int $i) => [
        'image' => 'https://images.unsplash.com/'.$photos[$i % count($photos)].'?w=1000&q=70&auto=format&fit=crop',
        'added' => $i === 0,
        'category' => 'electronics',
        'trend' => 'rising',
        'competition' => 'Low competition',
        'title' => 'Sports smart watch',
        'description' => 'Rapid demand, easy advertising content, and a margin that leaves you with a strong competitive advantage.',
        'markets' => $markets,
        'below_market' => 'dummy',
        'score' => 'dummy', 'score_pct' => 60,
        'profit' => 'dummy', 'profit_pct' => 70,
        'competition_level' => 'dummy', 'competition_pct' => 30,
        'status' => 'Hot', 'status_pct' => 75,
        'cost' => 'dummy',
        'markup' => 'dummy',
        'suggested_price' => 'dummy',
    ]);

    // Page numbers: first, last, and the current page ± 1, with gaps shown as "…"
    $pages = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
    $pageUrl = fn (int $n) => request()->fullUrlWithQuery(['page' => $n]);
@endphp

@section('content')
    <div class="tc-page">
        <div class="db-welcome fu d0">
            <div class="db-welcome-copy">
                <h1 class="db-welcome-title">Welcome back, {{ tenant('name') ?? 'your store' }}</h1>
                <p class="db-welcome-sub">These are your store's most important opportunities, actions, and results all in one place.</p>
            </div>
            {{-- Market selector — UI only until the backend provides markets. --}}
            <button type="button" class="tc-country">
                Select your country
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
            </button>
        </div>

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
    </div>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/todays-chances.js')
@endpush
