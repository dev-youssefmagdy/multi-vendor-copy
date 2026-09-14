@props(['banner'])

<div class="t-sortable-row" data-id="{{ $banner->id }}">
    <span class="t-drag" style="cursor:grab">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <line x1="4" y1="8" x2="20" y2="8" />
            <line x1="4" y1="16" x2="20" y2="16" />
        </svg>
    </span>

    @if($banner->image_path)
        <img src="{{ $banner->image_path }}" alt="" style="width:64px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0" />
    @endif

    <div style="flex:1;min-width:0">
        <div class="entity-title">{{ $banner->translationValue('title') ?? '—' }}</div>
        <div class="entity-subtitle">
            @if($banner->url)
                <a href="{{ $banner->url }}" target="_blank" rel="noopener">{{ Str::limit($banner->url, 50) }}</a>
            @else
                No link
            @endif
        </div>
    </div>

    <span class="badge">#{{ $banner->serial_number }}</span>

    <div class="flex gap-2">
        <button type="button" class="btn btn-secondary btn-sm"
            data-modal-open="banner-modal"
            data-modal-fill-url="{{ route('tenant.store.banners.show', $banner) }}"
            data-modal-action="{{ route('tenant.store.banners.update', $banner) }}"
            data-modal-method="PUT">Edit</button>
        <button type="button" class="btn btn-danger btn-sm"
            data-action-url="{{ route('tenant.store.banners.destroy', $banner) }}"
            data-action-method="DELETE"
            data-confirm="Delete banner?"
            data-success="reload-page">Delete</button>
    </div>
</div>
