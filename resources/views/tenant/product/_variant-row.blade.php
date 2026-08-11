{{--
Partial: tenant/product/_variant-row.blade.php
Variables: $vIdx (int), $variant (array), $variations (Collection)
--}}
<div class="vgroup-card" data-vcard>
    <input type="hidden" name="variants[{{ $vIdx }}][id]" value="{{ $variant['id'] ?? '' }}">
    <input type="hidden" name="variants[{{ $vIdx }}][remove_image]" value="0" class="v-remove-img-flag">

    <div class="vgroup-header">
        <span class="vgroup-title">Variant #<span class="v-num">{{ $vIdx + 1 }}</span></span>
        <button type="button" class="btn btn-secondary btn-sm btn-danger" onclick="removeVariant(this)">
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14H6L5 6"/>
                <path d="M10 11v6M14 11v6"/>
                <path d="M9 6V4h6v2"/>
            </svg>
            Remove
        </button>
    </div>

    <div class="vgroup-body">

        {{-- Variant Image --}}
        <div class="form-grid form-grid-2" style="margin-bottom:12px">
            <div>
                <label class="field-label">Variant Image</label>
                @if (!empty($variant['thumbnail_url']))
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                        <img id="vthumb-{{ $vIdx }}" src="{{ $variant['thumbnail_url'] }}" alt=""
                            style="width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #ddd">
                        <button type="button" class="btn btn-secondary btn-sm btn-danger v-img-remove-btn"
                            onclick="removeVariantImage(this, {{ $vIdx }})">
                            Remove image
                        </button>
                    </div>
                @endif

                <div class="w-full relative group" data-dropzone>
                    <label class="relative flex flex-col items-center justify-center w-full py-4 px-4 border-2 border-dashed border-(--border2) rounded-xl bg-(--surface) hover:border-(--cyan) transition-all duration-300 cursor-pointer focus-within:ring-2 focus-within:ring-(--cyan) focus-within:ring-offset-2 overflow-hidden">
                        <div class="absolute inset-0 bg-(--elevated) opacity-0 group-hover:opacity-40 transition-opacity duration-300"></div>
                        <div class="relative w-8 h-8 mb-2 rounded-full bg-(--surface) border border-(--border2) flex items-center justify-center text-(--cyan) shadow-sm group-hover:scale-110 transition-transform duration-300 z-10">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <div class="relative text-center z-10">
                            <h4 class="text-[12px] font-semibold text-(--t1) mb-1">Upload variant image</h4>
                            <p class="text-[11px] text-(--t3)">PNG, JPG, WEBP up to 5MB</p>
                        </div>
                        <input type="file" name="variants[{{ $vIdx }}][image]" accept="image/*" class="dropzone-input sr-only">
                    </label>
                    <div class="dropzone-files mt-4 space-y-2 empty:hidden" data-dropzone-files></div>
                </div>
            </div>
        </div>

        {{-- Variant Name --}}
        <div style="margin-bottom:12px">
            <label class="field-label">Variant Name</label>
            <input type="text" name="variants[{{ $vIdx }}][title]" class="field-control"
                value="{{ $variant['title'] ?? '' }}" placeholder="Auto-generated from options if left blank">
        </div>

        {{-- Option Pairs --}}
        <label class="field-label">Variant Composition *</label>
        <div class="v-pairs" style="display:flex;flex-direction:column;gap:8px">
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
                <div class="form-grid form-grid-2 variant-pair-row" style="gap:8px;align-items:end">
                    <div>
                        <select class="field-control" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][variation_id]"
                            onchange="onVariationChange(this)">
                            <option value="">Select variation group</option>
                            @foreach ($variations as $variation)
                                @php
                                    $isCurrent  = (int) ($pair['variation_id'] ?? 0) === $variation->id;
                                    $isExcluded = in_array($variation->id, $otherUsed, true) && !$isCurrent;
                                @endphp
                                @if (!$isExcluded)
                                    <option value="{{ $variation->id }}" {{ $isCurrent ? 'selected' : '' }}>
                                        {{ $variation->translationValue('name') ?? $variation->slug }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center">
                        <div style="flex:1">
                            <select class="field-control" name="variants[{{ $vIdx }}][pairs][{{ $pIdx }}][option_id]">
                                <option value="">Select option value</option>
                                @if ($selectedVariation)
                                    @foreach ($selectedVariation->options as $option)
                                        <option value="{{ $option->id }}"
                                            {{ (int) ($pair['option_id'] ?? 0) === $option->id ? 'selected' : '' }}>
                                            {{ $option->translationValue('name') ?? $option->slug }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @if ($pIdx > 0)
                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                onclick="removeOptionPair(this)" title="Remove this group" style="padding:6px 8px">
                                <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if ($variations->count() > count($variant['pairs'] ?? []))
            <div style="margin-top:8px">
                <button type="button" class="btn btn-secondary btn-sm" onclick="addOptionPair(this)">
                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add another group
                </button>
            </div>
        @endif

        {{-- Price / Stock / SKU / Weight / Active --}}
        <div class="form-grid form-grid-3" style="margin-top:14px">
            <div>
                <label class="field-label">Price *</label>
                <input type="number" step="0.01" min="0" name="variants[{{ $vIdx }}][price]"
                    value="{{ $variant['price'] ?? '' }}" class="field-control">
            </div>
            <div>
                <label class="field-label">Stock</label>
                <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][stock]"
                    value="{{ $variant['stock'] ?? 0 }}" class="field-control">
            </div>
            <div>
                <label class="field-label">SKU (optional)</label>
                <input type="text" name="variants[{{ $vIdx }}][sku]"
                    value="{{ $variant['sku'] ?? '' }}" placeholder="Auto-generated if blank" class="field-control">
            </div>
            <div>
                <label class="field-label">Weight (grams)</label>
                <input type="number" min="0" step="1" name="variants[{{ $vIdx }}][weight_grams]"
                    value="{{ $variant['weight_grams'] ?? '' }}" placeholder="0" class="field-control">
            </div>
            <div>
                <label class="field-label">Status</label>
                <label class="toggle-field">
                    <input type="hidden" name="variants[{{ $vIdx }}][active]" value="0">
                    <input type="checkbox" name="variants[{{ $vIdx }}][active]" value="1"
                        {{ ($variant['active'] ?? true) ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
            </div>
        </div>

    </div>
</div>
