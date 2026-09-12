{{--
Partial: fields for one variant row (content only — the wrapping .t-repeater-row
div is provided by the caller, either the repeater JS for new rows or the
server-rendered markup below for existing ones).
Variables: $vIdx (string|int — real index, or the __INDEX__ token for the
           blank template), $variant (array|null), $variations (Collection)
--}}
<div class="vrow" data-vcard>
    <input type="hidden" name="variants[{{ $vIdx }}][id]" value="{{ $variant['id'] ?? '' }}">
    <input type="hidden" name="variants[{{ $vIdx }}][remove_image]" value="0" data-vrow-remove-flag>

    <div class="vrow-lead">
        <div class="vrow-handle t-drag" title="Drag to reorder">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/>
                <circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/>
                <circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/>
            </svg>
        </div>
        <span class="vrow-num" data-vrow-num>#</span>

        <div class="vrow-thumb-wrap" data-vrow-thumb>
            @if(!empty($variant['thumbnail_url']))
                <img src="{{ $variant['thumbnail_url'] }}" alt="" class="vrow-thumb" data-vrow-thumb-img>
                <button type="button" class="vrow-img-remove-btn" data-vrow-remove-image title="Remove image">&times;</button>
            @else
                <label class="vrow-thumb-placeholder">
                    <span>IMG</span>
                    <input type="file" name="variants[{{ $vIdx }}][image]" accept="image/*" data-vrow-thumb-input>
                </label>
            @endif
        </div>
    </div>

    <div class="vrow-pairs-col">
        <span class="vrow-pairs-label">Options</span>

        <x-tenant::repeater name="variants.{{ $vIdx }}.pairs" :items="$variant['pairs'] ?? []" :min="1" index-token="__PINDEX__" add-label="+ Add another group">
            @include('tenant.pages.catalog.own-products._pair-row', ['vIdx' => $vIdx, 'pIdx' => '__PINDEX__', 'variations' => $variations])
        </x-tenant::repeater>
    </div>

    <div class="vrow-meta">
        <input type="text" name="variants[{{ $vIdx }}][title]" class="field-control vrow-meta-name" value="{{ $variant['title'] ?? '' }}" placeholder="Variant name (auto-generated if blank)">
        <input type="number" step="0.01" min="0" name="variants[{{ $vIdx }}][price]" value="{{ $variant['price'] ?? '' }}" class="field-control" placeholder="Price">
        <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][stock]" value="{{ $variant['stock'] ?? 0 }}" class="field-control" placeholder="Stock">
        <input type="text" name="variants[{{ $vIdx }}][sku]" value="{{ $variant['sku'] ?? '' }}" class="field-control" placeholder="SKU (auto)">
        <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][weight_grams]" value="{{ $variant['weight_grams'] ?? '' }}" class="field-control" placeholder="Weight (g)">
        <label class="toggle-field vrow-meta-name">
            <input type="hidden" name="variants[{{ $vIdx }}][active]" value="0">
            <input type="checkbox" name="variants[{{ $vIdx }}][active]" value="1" {{ ($variant['active'] ?? true) ? 'checked' : '' }}>
            <span>Active</span>
        </label>
    </div>

    <button type="button" class="btn btn-secondary btn-sm btn-danger vrow-remove" data-repeater-remove title="Remove variant">
        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
        </svg>
    </button>
</div>
