@props([
    'target' => null,
    'title' => 'Filters',
    'description' => null,
    'open' => false,
    'syncUrl' => true,
    'exportLink' => null,
])

<details class="card filters-card fu d1 section-gap" data-tenant-filters-card
    data-target="{{ $target }}"
    data-sync-url="{{ $syncUrl ? '1' : '0' }}"
    @if($exportLink) data-export-link="{{ $exportLink }}" @endif
    @if($open) open @endif
>
    <summary class="filters-summary">
        <div>
            <div class="panel-title">{{ $title }}</div>
            @if($description)<p class="panel-copy">{{ $description }}</p>@endif
        </div>
        <div class="filters-summary-meta">
            <span class="filter-pill" data-filters-count hidden>0 active</span>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </div>
    </summary>

    <div class="filters-grid">
        {{ $slot }}
    </div>

    <div class="filters-actions">
        <p class="filters-note">Filters apply automatically as you type or choose an option.</p>
        <div class="page-actions compact-actions">
            <button type="button" class="btn btn-secondary" data-filters-reset>Reset</button>
        </div>
    </div>
</details>
