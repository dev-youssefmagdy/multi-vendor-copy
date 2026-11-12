@extends('tenant.layouts.app')

{{-- PANELS-UPDATES-NEW-PRODUCTS-SYNC: central products added after registration are NOT synced automatically;
     the tenant reviews them here and adds the ones (or all) they want. Updates to products they already have stay automatic. --}}
@section('title', __('New products'))

@php
    $page = $products->currentPage();
    $lastPage = max(1, $products->lastPage());
    $pages = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
@endphp

@section('content')
    <div class="pm-page">
        @include('tenant.pages.catalog._module-nav', ['activeTab' => 'products'])

        <div class="pm-newbar fu d1" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin:8px 0 18px">
            <div>
                <h2 class="db-welcome-title" style="margin:0">{{ __('New products from the platform') }}</h2>
                <p class="db-welcome-sub" style="margin:4px 0 0">
                    {{ $pendingCount > 0
                        ? trans_choice(':count new product not added to your store yet. Products you already have keep updating automatically.|:count new products not added to your store yet. Products you already have keep updating automatically.', $pendingCount, ['count' => $pendingCount])
                        : __('You are up to date. New products added by the platform will appear here.') }}
                </p>
            </div>
            @if($pendingCount > 0)
                <form method="POST" action="{{ route('tenant.products.sync-new') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg">{{ __('Sync all new products (:pendingCount)', ['pendingCount' => $pendingCount]) }}</button>
                </form>
            @endif
        </div>

        @if(empty($cards))
            <div class="pm-empty fu d2">
                <h3>{{ __('No new products') }}</h3>
                <p>{{ __('Everything the platform published is already in your store.') }}</p>
                <a href="{{ route('tenant.products.index') }}" class="btn btn-primary">{{ __('Back to products') }}</a>
            </div>
        @else
            <div class="pm-grid pm-grid-3 fu d2">
                @foreach($cards as $card)
                    <article class="db-opp pm-card">
                        <a href="{{ $card['url'] }}" class="db-opp-media" @if($card['image']) style="background-image:url('{{ $card['image'] }}')" @endif aria-label="{{ __($card['title']) }}">
                            <span class="db-opp-added" style="background:#7c3aed;color:#fff">{{ __('New') }}</span>
                        </a>
                        <div class="db-opp-body">
                            <div>
                                <h3 class="db-opp-title pm-card-title" title="{{ __($card['title']) }}"><a href="{{ $card['url'] }}" style="color:inherit;text-decoration:none">{{ __($card['title']) }}</a></h3>
                                <p class="db-opp-desc pm-card-desc">{{ $card['description'] !== '' ? $card['description'] : __('No description yet.') }}</p>
                            </div>
                            <div class="db-opp-stats pm-card-stats">
                                <div class="db-opp-stat is-dark is-half">
                                    <span class="db-opp-stat-label">{{ __('Platform price') }}</span>
                                    <strong>{{ $card['cost'] }}</strong>
                                    <small>{{ __('Before your profit margin') }}</small>
                                </div>
                            </div>
                            <a href="{{ $card['url'] }}" class="btn btn-secondary btn-lg" style="width:100%;margin-bottom:8px;text-align:center">{{ __('View details') }}</a>
                            <form method="POST" action="{{ route('tenant.products.sync-new') }}">
                                @csrf
                                <input type="hidden" name="product_ids[]" value="{{ $card['id'] }}">
                                <button type="submit" class="btn btn-primary btn-lg db-opp-cta" style="width:100%">{{ __('Add to my store') }}</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if($products->total() > 0)
            <nav class="tc-pagination" aria-label="{{ __('New products pages') }}">
                <p>{{ __('Show') }} <strong>{{ $products->count() }}</strong> {{ __('of :total result', ['total' => $products->total()]) }}</p>
                <div class="tc-pages">
                    @foreach($pages as $i => $n)
                        @if($i > 0 && $n - $pages[$i - 1] > 1)<span class="tc-page-gap" aria-hidden="true">…</span>@endif
                        @if($n === $page)
                            <span class="tc-page-btn is-current" aria-current="page">{{ $n }}</span>
                        @else
                            <a href="{{ $products->url($n) }}" class="tc-page-btn">{{ $n }}</a>
                        @endif
                    @endforeach
                </div>
            </nav>
        @endif
    </div>
@endsection
