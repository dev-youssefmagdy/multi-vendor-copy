{{--
Partial: _variant-row.blade.php (compact row layout)
Variables: $vIdx (int), $variant (array), $variations (Collection)
--}}
<div class="vrow" data-vcard data-position="{{ $vIdx }}">
    <div class="vrow-handle" title="Drag to reorder">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <circle cx="9" cy="6" r="1.2"/><circle cx="15" cy="6" r="1.2"/>
            <circle cx="9" cy="12" r="1.2"/><circle cx="15" cy="12" r="1.2"/>
            <circle cx="9" cy="18" r="1.2"/><circle cx="15" cy="18" r="1.2"/>
        </svg>
    </div>

    <span class="vrow-num">#<span class="v-num">{{ $vIdx + 1 }}</span></span>

    <input type="hidden" name="variants[{{ $vIdx }}][id]" value="{{ $variant['id'] ?? '' }}">
    <input type="hidden" name="variants[{{ $vIdx }}][position]" class="v-position-input" value="{{ $vIdx }}">
    <input type="hidden" name="variants[{{ $vIdx }}][remove_image]" value="0" class="v-remove-img-flag">

    {{-- Thumbnail + upload trigger --}}
    <div style="width:44px;flex-shrink:0" data-dropzone>
        @if (!empty($variant['thumbnail_url']))
            <div style="position:relative">
                <img id="vthumb-{{ $vIdx }}" src="{{ $variant['thumbnail_url'] }}" alt="" class="vrow-thumb">
                <button type="button" class="v-img-remove-btn" onclick="removeVariantImage(this, {{ $vIdx }})"
                    title="Remove image"
                    style="position:absolute;top:-6px;right:-6px;width:16px;height:16px;border-radius:50%;background:var(--danger,#e11d48);color:#fff;border:none;font-size:10px;line-height:1;cursor:pointer">
                    &times;
                </button>
            </div>
        @else
            <label class="vrow-thumb-placeholder" style="cursor:pointer">
                <span>IMG</span>
                <input type="file" name="variants[{{ $vIdx }}][image]" accept="image/*" class="dropzone-input sr-only">
            </label>
        @endif
    </div>

    {{-- Compact inline fields --}}
    <div class="vrow-fields">
        <input type="text" name="variants[{{ $vIdx }}][title]" class="field-control" style="max-width:140px"
            value="{{ $variant['title'] ?? '' }}" placeholder="Name (auto)">

        <div class="v-pairs" style="display:flex;gap:6px;flex-wrap:wrap">
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
                <div class="variant-pair-row" style="display:flex;gap:4px;align-items:center">
                    <select class="field-control" style="max-width:110px" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][variation_id]" onchange="onVariationChange(this)">
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
                    <div class="var-options" style="display:flex;gap:4px;align-items:center">
                        <select class="field-control" style="max-width:110px" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][option_id]">
                            <option value="">Option…</option>
                            @if ($selectedVariation)
                                @foreach ($selectedVariation->options as $option)
                                    <option value="{{ $option->id }}" {{ (int) ($pair['option_id'] ?? 0) === $option->id ? 'selected' : '' }}>
                                        {{ $option->translationValue('name') ?? $option->slug }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @if ($pIdx > 0)
                            <button type="button" class="btn btn-secondary btn-sm btn-danger" onclick="removeOptionPair(this)"
                                title="Remove this group" style="padding:4px 6px">
                                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($variations->count() > count($variant['pairs'] ?? []))
            <button type="button" class="btn btn-secondary btn-sm" onclick="addOptionPair(this)" title="Add another group">
                <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </button>
        @endif

        <input type="number" step="0.01" min="0" name="variants[{{ $vIdx }}][price]" value="{{ $variant['price'] ?? '' }}" class="field-control" style="max-width:90px" placeholder="Price">
        <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][stock]" value="{{ $variant['stock'] ?? 0 }}" class="field-control" style="max-width:80px" placeholder="Stock">
        <input type="text" name="variants[{{ $vIdx }}][sku]" value="{{ $variant['sku'] ?? '' }}" class="field-control" style="max-width:110px" placeholder="SKU">
        <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][weight_grams]" value="{{ $variant['weight_grams'] ?? '' }}" class="field-control" style="max-width:90px" placeholder="Weight (g)">
    </div>

    <button type="button" class="btn btn-secondary btn-sm btn-danger" style="flex-shrink:0" onclick="removeVariant(this)">
        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>
        </svg>
    </button>
</div>
