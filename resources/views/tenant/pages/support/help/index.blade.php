@extends('tenant.layouts.app')
@section('title', 'Help & Documentation')
@section('content')
    <x-tenant::page-header title="Help & Documentation" badge="Help" description="Guides for setting up and managing your store." />

    <div class="docs-layout">
        {{-- Sidebar nav --}}
        <nav class="docs-sidebar card" data-help-articles-url="{{ route('tenant.help.article', ['slug' => '__slug__']) }}">
            @php $categories = collect($articles)->map(fn($q, $key) => ['id' => $key, ...$q])->groupBy('category'); @endphp
            @foreach ($categories as $cat => $items)
                <div class="docs-nav-group">
                    <div class="docs-nav-label">{{ $cat }}</div>
                    @foreach ($items as $slug => $article)
                        <a href="{{ route('tenant.help.index', ['article' => $article['id']]) }}"
                           data-help-article-link="{{ $article['id'] }}"
                           class="docs-nav-link {{ $currentSlug === $article['id'] ? 'docs-nav-link--active' : '' }}">
                            {{ $article['title'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Article content --}}
        <article class="docs-content card" id="docs-content" data-docs-content>
            @if ($currentArticle)
                @include($currentArticle['view'])
            @else
                <p class="panel-copy">Article not found.</p>
            @endif
        </article>
    </div>
@endsection
@push('tenant-vite') @vite('resources/js/tenant/pages/support/help.js') @endpush
