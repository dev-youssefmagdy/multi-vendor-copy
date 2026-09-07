{{--
Partial: _variant-row.blade.php (3-column layout: lead | pairs | meta)
Variables: $vIdx (int), $variant (array), $variations (Collection)
--}}
<div class="vrow" data-vcard data-position="{{ $vIdx }}">

    <input type="hidden" name="variants[{{ $vIdx }}][id]" value="{{ $variant['id'] ?? '' }}">
    <input type="hidden" name="variants[{{ $vIdx }}][position]" class="v-position-input" value="{{ $vIdx }}">
    <input type="hidden" name="variants[{{ $vIdx }}][remove_image]" value="0" class="v-remove-img-flag">

    {{-- ── Column 1: drag handle, number, thumbnail ────────────────────── --}}
    <div class="vrow-lead">
        <div class="vrow-handle" title="Drag to reorder">
            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/>
                <circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/>
                <circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/>
            </svg>
        </div>
        <span class="vrow-num">#<span class="v-num">{{ $vIdx + 1 }}</span></span>

        <div class="vrow-thumb-wrap" data-dropzone>
            @if (!empty($variant['thumbnail_url']))
                <img id="vthumb-{{ $vIdx }}" src="{{ $variant['thumbnail_url'] }}" alt="" class="vrow-thumb">
                <button type="button" class="vrow-img-remove-btn" onclick="removeVariantImage(this, {{ $vIdx }})" title="Remove image">&times;</button>
            @else
                <label class="vrow-thumb-placeholder">
                    <span>IMG</span>
                    <input type="file" name="variants[{{ $vIdx }}][image]" accept="image/*" class="dropzone-input sr-only">
                </label>
            @endif
        </div>
    </div>

    {{-- ── Column 2: variation pairs, stacked ───────────────────────────── --}}
    <div class="vrow-pairs-col">
        <span class="vrow-pairs-label">Options</span>

        <div class="v-pairs">
            @foreach ($variant['pairs'] as $pIdx => $pair)
                @php
                    $selectedVariation = filled($pair['variation_id'])
                        ? $variations->find((int) $pair['variation_id'])
                        : null;
                    $otherUsed = collect($variant['pairs'])
                        ->except($pIdx)
                        ->pluck('variation_id')
                        ->filter(fn($id) => filled($id))
                        ->map(fn($id) => (int) $id)
                        ->all();
                @endphp
                <div class="variant-pair-row">
                    <select class="field-control" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][variation_id]" onchange="onVariationChange(this)">
                        <option value="">Group…</option>
                        @foreach ($variations as $variation)
                            @php
                                $isCurrent = (int) ($pair['variation_id'] ?? 0) === $variation->id;
                                $isExcluded = in_array($variation->id, $otherUsed, true) && !$isCurrent;
                            @endphp
                            @if (!$isExcluded)
                                <option value="{{ $variation->id }}" {{ $isCurrent ? 'selected' : '' }}>
                                    {{ $variation->translationValue('name') ?? $variation->slug }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <div class="var-options">
                        <select class="field-control" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][option_id]">
                            <option value="">Option…</option>
                            @if ($selectedVariation)
                                @foreach ($selectedVariation->options as $option)
                                    <option value="{{ $option->id }}" {{ (int) ($pair['option_id'] ?? 0) === $option->id ? 'selected' : '' }}>
                                        {{ $option->translationValue('name') ?? $option->slug }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    @if ($pIdx > 0)
                        <button type="button" class="btn btn-secondary btn-sm btn-danger" onclick="removeOptionPair(this)" title="Remove this group">
                            <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    @else
                        <span></span>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($variations->count() > count($variant['pairs'] ?? []))
            <button type="button" class="btn btn-secondary btn-sm vrow-add-pair-btn" onclick="addOptionPair(this)">
                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Add another group
            </button>
        @endif
    </div>

    {{-- ── Column 3: name + price/stock/sku/weight ──────────────────────── --}}
    <div class="vrow-meta">
        <input type="text" name="variants[{{ $vIdx }}][title]" class="field-control vrow-meta-name"
            value="{{ $variant['title'] ?? '' }}" placeholder="Variant name (auto-generated if blank)">

        <input type="number" step="0.01" min="0" name="variants[{{ $vIdx }}][price]" value="{{ $variant['price'] ?? '' }}" class="field-control" placeholder="Price">
        <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][stock]" value="{{ $variant['stock'] ?? 0 }}" class="field-control" placeholder="Stock">
        <input type="text" name="variants[{{ $vIdx }}][sku]" value="{{ $variant['sku'] ?? '' }}" class="field-control" placeholder="SKU (auto)">
        <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][weight_grams]" value="{{ $variant['weight_grams'] ?? '' }}" class="field-control" placeholder="Weight (g)">
    </div>

    {{-- ── Column 4: remove variant ──────────────────────────────────────── --}}
    <button type="button" class="btn btn-secondary btn-sm btn-danger vrow-remove" onclick="removeVariant(this)" title="Remove variant">
        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
        </svg>
    </button>
</div>
