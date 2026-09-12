@props(['item'])

<div class="t-sortable-row" data-id="{{ $item->id }}">
    <span class="t-drag" style="cursor:grab">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <line x1="4" y1="8" x2="20" y2="8" />
            <line x1="4" y1="16" x2="20" y2="16" />
        </svg>
    </span>
    <div>
        <div class="entity-title">{{ $item->translationValue('name') ?? $item->slug ?? 'Item #' . $item->id }}</div>
        <div class="entity-subtitle">/{{ $item->slug }}</div>
    </div>
</div>
