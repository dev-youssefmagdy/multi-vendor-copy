@props([
    'title' => null,
    'badge' => null,
    'description' => null,
    'back' => null,
])

<div class="page-head fu d0">
    <div>
        <div class="page-title-row">
            @if($back)
                <a href="{{ $back }}" class="t-back-link" aria-label="Back">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
            @endif
            <h1 class="D page-title">{{ $title }}</h1>
            @if($badge)<span class="page-badge">{{ $badge }}</span>@endif
        </div>
        @if($description)<p class="page-copy">{{ $description }}</p>@endif
        @isset($meta){{ $meta }}@endisset
    </div>
    @isset($actions)
        <div class="page-actions">{{ $actions }}</div>
    @endisset
</div>
