@props(['value' => null, 'label' => 'Copy'])

<button type="button" class="t-copy-btn" data-tenant-copy data-copy-value="{{ $value }}">
    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a1 1 0 01-1-1V4a1 1 0 011-1h10a1 1 0 011 1v1"/>
    </svg>
    <span>{{ $label }}</span>
</button>
