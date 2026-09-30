{{-- No products yet: guide the user to add some. --}}
<div class="pm-empty fu d1" style="grid-column: 1 / -1; padding: 3rem 1rem; text-align: center;">
    <h3>No products yet</h3>
    <p>Add your first product from the central catalog to see it here.</p>
    <a href="{{ route('tenant.products.create') }}" class="btn btn-primary" style="margin-top:1rem;">Add product</a>
</div>

@if(isset($total) && $total > 0)
<nav class="tc-pagination" aria-label="{{ $pagerLabel ?? 'Products pages' }}">
    <p>Show <strong>{{ $count }}</strong> of {{ $total }} result</p>
    <div class="tc-pages">
        @if($page > 1)
            <a href="{{ $pageUrl($page - 1) }}" class="tc-page-btn is-text" rel="prev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</a>
        @else
            <span class="tc-page-btn is-text is-disabled" aria-disabled="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg> Previous</span>
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
