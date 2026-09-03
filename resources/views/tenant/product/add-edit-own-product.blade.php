@extends('layouts.tenant')

@section('content')
@php
    $isEdit = isset($product) && $product !== null;
    $pd     = $productData ?? [];
    $f      = fn(string $key, mixed $fallback = '') => old($key, $pd[$key] ?? $fallback);
@endphp

<main id="mn">
    <form id="own-product-form"
          method="POST"
          action="{{ $isEdit ? route('tenant.own-products.update', $product) : route('tenant.own-products.store') }}"
          enctype="multipart/form-data"
          class="page-stack">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        {{-- ── Page Header ──────────────────────────────────────────────── --}}
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Own Catalog</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions" style="flex-shrink:0">
                <a href="{{ route('tenant.own-products.index') }}" class="btn btn-secondary">Back to Products</a>
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="card section-gap notice-error">
                <h3 class="panel-title">Please fix the highlighted fields</h3>
                <p class="panel-copy">The product was not saved because some required values are missing or invalid.</p>
                <ul style="margin-top:8px;padding-left:1.2em">
                    @foreach ($errors->all() as $error)
                        <li style="font-size:13px">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-2">

            {{-- ── Basics ────────────────────────────────────────────────── --}}
            <x-card-collapse title="Basics"
                subtitle="Identifiers, publication state, and catalog flags."
                class="form-card span-12"
                :start-open="true">
                <div class="form-grid form-grid-3">

                    <div>
                        <label class="field-label" for="sku">SKU</label>
                        <input id="sku" name="sku" type="text"
                               class="field-control {{ $errors->has('sku') ? 'is-invalid' : '' }}"
                               value="{{ $f('sku') }}"
                               placeholder="PRD-001">
                        @error('sku') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="slug">Slug</label>
                        <input id="slug" name="slug" type="text"
                               class="field-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
                               value="{{ $f('slug') }}"
                               placeholder="auto-generated-if-empty">
                        @error('slug') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="weight_grams">Weight (grams)</label>
                        <input id="weight_grams" name="weight_grams" type="number" min="0"
                               class="field-control {{ $errors->has('weight_grams') ? 'is-invalid' : '' }}"
                               value="{{ $f('weight_grams') }}"
                               placeholder="0">
                        @error('weight_grams') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field-flag-grid span-3">
                        <label class="toggle-field">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1"
                                {{ $f('active', true) ? 'checked' : '' }}>
                            <span>Product is Active</span>
                        </label>
                        <label class="toggle-field">
                            <input type="hidden" name="featured" value="0">
                            <input type="checkbox" name="featured" value="1"
                                {{ $f('featured', false) ? 'checked' : '' }}>
                            <span>Featured</span>
                        </label>
                        <label class="toggle-field">
                            <input type="hidden" name="manage_stock" value="0">
                            <input type="checkbox" name="manage_stock" value="1"
                                id="manage_stock_cb"
                                onchange="toggleManageStock(this)"
                                {{ $f('manage_stock', true) ? 'checked' : '' }}>
                            <span>Manage Stock</span>
                        </label>
                        <label class="toggle-field">
                            <input type="hidden" name="is_taxable" value="0">
                            <input type="checkbox" name="is_taxable" value="1"
                                {{ $f('is_taxable', true) ? 'checked' : '' }}>
                            <span>Taxable</span>
                        </label>
                    </div>

                </div>
            </x-card-collapse>

            {{-- ── Primary Image ─────────────────────────────────────────── --}}
            <x-card-collapse title="Primary Image"
                subtitle="Main product image shown on listings and the product page."
                class="form-card span-4"
                :start-open="true">

                <div class="media-card">
                    @if ($existingImage)
                        <div id="primary-image-preview" style="margin-bottom:12px">
                            <div class="media-preview-wrap" style="width:120px;aspect-ratio:1/1">
                                <img id="primary-img-tag" src="{{ $existingImage }}" alt="Current image" class="media-preview-image">
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm btn-danger" style="margin-top:6px"
                                    onclick="removePrimaryImage()">
                                Remove current image
                            </button>
                        </div>
                    @endif

                    <input type="hidden" name="remove_primary_image" id="remove_primary_image_input" value="0">

                    <div class="w-full relative group" data-dropzone>
                        <label class="relative flex flex-col items-center justify-center w-full py-4 px-4 border-2 border-dashed border-(--border2) rounded-xl bg-(--surface) hover:border-(--cyan) transition-all duration-300 cursor-pointer focus-within:ring-2 focus-within:ring-(--cyan) focus-within:ring-offset-2 overflow-hidden">
                            <div class="absolute inset-0 bg-(--elevated) opacity-0 group-hover:opacity-40 transition-opacity duration-300"></div>
                            <div class="relative w-8 h-8 mb-2 rounded-full bg-(--surface) border border-(--border2) flex items-center justify-center text-(--cyan) shadow-sm group-hover:scale-110 transition-transform duration-300 z-10">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <div class="relative text-center z-10">
                                <h4 class="text-[12px] font-semibold text-(--t1) mb-1">Upload primary image</h4>
                                <p class="text-[11px] text-(--t3)">PNG, JPG, WEBP up to 5MB</p>
                            </div>
                            <input id="primary_image" name="primary_image" type="file" accept="image/*" class="dropzone-input sr-only">
                        </label>
                        <div class="dropzone-files mt-4 space-y-2 empty:hidden" data-dropzone-files></div>
                    </div>
                    @error('primary_image') <p class="field-error" style="margin-top:6px">{{ $message }}</p> @enderror
                </div>
            </x-card-collapse>

            {{-- ── Media Gallery ─────────────────────────────────────────── --}}
            <x-card-collapse title="Media Gallery"
                subtitle="Additional images and videos shown in the product slider. JPG, PNG, GIF, WEBP, MP4, WEBM, MOV."
                class="form-card span-8"
                :start-open="true">

                @if ($existingGallery->isNotEmpty())
                    <div class="gallery-grid" id="existing-gallery">
                        @foreach ($existingGallery as $file)
                            <div class="gallery-item" id="gallery-item-{{ $file->id }}">
                                @if ($file->file_type->value === 'video')
                                    <div class="gallery-item-video"
                                         style="cursor:pointer"
                                         title="Click to preview"
                                         data-video-url="{{ $file->full_path }}"
                                         onclick="openVideoModal(this)">
                                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <polygon points="5 3 19 12 5 21 5 3"/>
                                        </svg>
                                        <span>{{ strtoupper($file->extension) }}</span>
                                    </div>
                                @else
                                    <img src="{{ $file->full_path }}" alt="Gallery image" class="gallery-item-thumb">
                                @endif
                                <button type="button" class="gallery-item-remove" title="Remove"
                                        onclick="removeGalleryItem({{ $file->id }}, this)">
                                    <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div id="gallery-remove-inputs"></div>

                <div class="w-full relative group" style="{{ $existingGallery->isNotEmpty() ? 'margin-top:16px' : '' }}" data-dropzone>
                    <label class="relative flex flex-col items-center justify-center w-full py-10 px-4 border-2 border-dashed border-(--border2) rounded-xl bg-(--surface) hover:border-(--cyan) transition-all duration-300 cursor-pointer focus-within:ring-2 focus-within:ring-(--cyan) focus-within:ring-offset-2 overflow-hidden">
                        <div class="absolute inset-0 bg-(--elevated) opacity-0 group-hover:opacity-40 transition-opacity duration-300"></div>
                        <div class="relative w-12 h-12 mb-4 rounded-full bg-(--surface) border border-(--border2) flex items-center justify-center text-(--cyan) shadow-sm group-hover:scale-110 transition-transform duration-300 z-10">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <div class="relative text-center z-10">
                            <h4 class="text-[14px] font-semibold text-(--t1) mb-1">Upload images or videos</h4>
                            <p class="text-[12px] text-(--t3)">JPG, PNG, GIF, WEBP, MP4, WEBM, MOV — multiple allowed</p>
                        </div>
                        <input name="gallery_files[]" type="file" multiple
                               accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime"
                               class="dropzone-input sr-only">
                    </label>
                    <div class="dropzone-files mt-4 space-y-2 empty:hidden" data-dropzone-files></div>
                </div>
            </x-card-collapse>

            {{-- ── Translations ──────────────────────────────────────────── --}}
            <x-card-collapse title="Translations"
                subtitle="Multilingual product name, summary, description, and SEO meta fields."
                class="form-card span-12"
                :start-open="true">

                @if ($languages->count())
                    <div class="locale-tabs" id="locale-tabs">
                        @foreach ($languages as $language)
                            <button type="button"
                                    class="locale-tab {{ $loop->first ? 'is-active' : '' }}"
                                    data-locale="{{ $language->code }}"
                                    onclick="switchLocale('{{ $language->code }}')">
                                <span>{{ strtoupper($language->code) }}</span>
                                <small>{{ $language->native_name ?? $language->name }}</small>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($languages as $language)
                        @php
                            $t = old("translations.{$language->code}", $translations[$language->code] ?? []);
                        @endphp
                        <div class="locale-panel {{ $loop->first ? 'is-active' : '' }}"
                             id="locale-panel-{{ $language->code }}"
                             style="{{ $loop->first ? '' : 'display:none' }}">
                            <div class="form-grid form-grid-2">

                                <div class="span-2">
                                    <label class="field-label">
                                        Name ({{ strtoupper($language->code) }})
                                        @if ($language->is_default) * @endif
                                    </label>
                                    <input type="text"
                                           name="translations[{{ $language->code }}][name]"
                                           class="field-control {{ $errors->has("translations.{$language->code}.name") ? 'is-invalid' : '' }}"
                                           value="{{ $t['name'] ?? '' }}"
                                           placeholder="Localized product name">
                                    @error("translations.{$language->code}.name")
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="field-label">Label</label>
                                    <input type="text"
                                           name="translations[{{ $language->code }}][label]"
                                           class="field-control {{ $errors->has("translations.{$language->code}.label") ? 'is-invalid' : '' }}"
                                           value="{{ $t['label'] ?? '' }}"
                                           placeholder="Short display label (used in UI, breadcrumbs, listings)">
                                    @error("translations.{$language->code}.label")
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="field-label">Summary</label>
                                    <input type="text"
                                           name="translations[{{ $language->code }}][summary]"
                                           class="field-control {{ $errors->has("translations.{$language->code}.summary") ? 'is-invalid' : '' }}"
                                           value="{{ $t['summary'] ?? '' }}"
                                           placeholder="Short merchandising summary">
                                    @error("translations.{$language->code}.summary")
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Description</label>
                                    <textarea id="tme-desc-{{ $language->code }}"
                                              name="translations[{{ $language->code }}][description]"
                                              class="field-control field-textarea"
                                              rows="6">{!! $t['description'] ?? '' !!}</textarea>
                                    @error("translations.{$language->code}.description")
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="field-label">Meta Keywords</label>
                                    <input type="text"
                                           name="translations[{{ $language->code }}][meta_keywords]"
                                           class="field-control {{ $errors->has("translations.{$language->code}.meta_keywords") ? 'is-invalid' : '' }}"
                                           value="{{ $t['meta_keywords'] ?? '' }}"
                                           placeholder="keyword1, keyword2, keyword3">
                                    @error("translations.{$language->code}.meta_keywords")
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="field-label">Meta Description</label>
                                    <textarea name="translations[{{ $language->code }}][meta_description]"
                                              rows="3"
                                              class="field-control field-textarea {{ $errors->has("translations.{$language->code}.meta_description") ? 'is-invalid' : '' }}"
                                              placeholder="SEO-friendly page description (≤ 160 chars)">{{ $t['meta_description'] ?? '' }}</textarea>
                                    @error("translations.{$language->code}.meta_description")
                                        <p class="field-error">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="notice-muted">Create at least one active language before managing translated product content.</div>
                @endif
            </x-card-collapse>

            {{-- ── Pricing & Inventory ───────────────────────────────────── --}}
            <x-card-collapse title="Pricing &amp; Inventory"
                subtitle="Base pricing, promotional pricing, and stock thresholds."
                class="form-card span-6"
                :start-open="true">
                <div class="form-grid form-grid-2">

                    <div>
                        <label class="field-label" for="base_price">Base Price *</label>
                        <input id="base_price" name="base_price" type="number" min="0" step="0.01"
                               class="field-control {{ $errors->has('base_price') ? 'is-invalid' : '' }}"
                               value="{{ $f('base_price', '0.00') }}">
                        @error('base_price') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="sale_price">Sale Price</label>
                        <input id="sale_price" name="sale_price" type="number" min="0" step="0.01"
                               class="field-control {{ $errors->has('sale_price') ? 'is-invalid' : '' }}"
                               value="{{ $f('sale_price') }}">
                        @error('sale_price') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="cost_price">Cost Price</label>
                        <input id="cost_price" name="cost_price" type="number" min="0" step="0.01"
                               class="field-control {{ $errors->has('cost_price') ? 'is-invalid' : '' }}"
                               value="{{ $f('cost_price') }}">
                        @error('cost_price') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div id="stock-field-wrap" style="{{ !$f('manage_stock', true) ? 'display:none' : '' }}">
                        <label class="field-label" for="stock">Stock</label>
                        <input id="stock" name="stock" type="number" min="0"
                               class="field-control {{ $errors->has('stock') ? 'is-invalid' : '' }}"
                               value="{{ $f('stock', 0) }}">
                        @error('stock') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div id="min-stock-field-wrap" style="{{ !$f('manage_stock', true) ? 'display:none' : '' }}">
                        <label class="field-label" for="min_stock">Minimum Stock</label>
                        <input id="min_stock" name="min_stock" type="number" min="0"
                               class="field-control {{ $errors->has('min_stock') ? 'is-invalid' : '' }}"
                               value="{{ $f('min_stock', 0) }}">
                        @error('min_stock') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </x-card-collapse>

            {{-- ── Categories ────────────────────────────────────────────── --}}
            <x-card-collapse class="form-card span-6" title="Categories"
                subtitle="Drill into sub-categories; selecting the deepest level adds it as a tag."
                :start-open="true">
                @php
                    $selectedCategoryIds = array_map('intval', old('category_ids', $categoryIds ?? []));
                @endphp

                <div id="cat-tags" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px"></div>
                <div id="cat-selects"></div>
                <div id="cat-hidden-inputs"></div>

                @error('category_ids') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror
                @error('category_ids.*') <p class="field-error" style="margin-top:4px">{{ $message }}</p> @enderror

                <script>
                (function () {
                    var catMap      = @json($catMap);
                    var childrenOf  = {};
                    var selectedIds = @json($selectedCategoryIds);
                    var catPath     = [];

                    Object.values(catMap).forEach(function (cat) {
                        var key = cat.parent_id === null ? '__root__' : String(cat.parent_id);
                        if (!childrenOf[key]) childrenOf[key] = [];
                        childrenOf[key].push(cat);
                    });

                    function label(id) {
                        var parts = [], cur = catMap[id];
                        while (cur) { parts.unshift(cur.name); cur = cur.parent_id !== null ? catMap[cur.parent_id] : null; }
                        return parts.join(' › ');
                    }

                    function renderTags() {
                        var el = document.getElementById('cat-tags');
                        if (!el) return;
                        el.innerHTML = selectedIds.map(function (id) {
                            return '<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;'
                                + 'background:rgba(34,211,238,.1);color:var(--cyan);'
                                + 'border:1px solid rgba(34,211,238,.25);border-radius:20px;font-size:12px;line-height:1.4">'
                                + esc(label(id))
                                + '<button type="button" onclick="__catRemove(' + id + ')" title="Remove"'
                                + ' style="display:flex;align-items:center;background:none;border:none;cursor:pointer;color:inherit;padding:0;opacity:.65">'
                                + '<svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">'
                                + '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'
                                + '</svg></button></span>';
                        }).join('');
                        el.style.display = selectedIds.length ? 'flex' : 'none';
                    }

                    function renderSelects() {
                        var el  = document.getElementById('cat-selects');
                        if (!el) return;
                        var html = '';
                        var key  = '__root__';

                        for (var i = 0; i <= catPath.length; i++) {
                            var children = childrenOf[key] || [];
                            if (!children.length) break;

                            var activeId = catPath[i] !== undefined ? catPath[i] : '';

                            if (i > 0) {
                                var parentName = catMap[catPath[i - 1]] ? catMap[catPath[i - 1]].name : '';
                                html += '<label class="field-label" style="font-size:11px;color:var(--t3);margin-top:10px;margin-bottom:4px;display:block">'
                                    + 'Sub categories of ' + esc(parentName) + '</label>';
                            }

                            html += '<select class="field-control" onchange="__catChange(' + i + ',this.value)">';
                            html += '<option value="">' + (i === 0 ? '— Select category —' : '— None —') + '</option>';
                            children.forEach(function (cat) {
                                var sel = String(activeId) === String(cat.id) ? ' selected' : '';
                                html += '<option value="' + cat.id + '"' + sel + '>' + esc(cat.name) + '</option>';
                            });
                            html += '</select>';

                            if (!activeId) break;

                            var selCat = catMap[Number(activeId)];
                            if (!selCat || !selCat.has_children) break;
                            key = String(activeId);
                        }

                        el.innerHTML = html || '<div class="notice-muted">No categories available yet.</div>';
                    }

                    function syncInputs() {
                        var el = document.getElementById('cat-hidden-inputs');
                        if (!el) return;
                        el.innerHTML = selectedIds.map(function (id) {
                            return '<input type="hidden" name="category_ids[]" value="' + Number(id) + '">';
                        }).join('');
                    }

                    function render() { renderTags(); renderSelects(); syncInputs(); }

                    window.__catChange = function (level, value) {
                        catPath = catPath.slice(0, level);
                        if (!value) { render(); return; }
                        var id  = Number(value);
                        var cat = catMap[id];
                        if (cat && !cat.has_children) {
                            if (selectedIds.indexOf(id) === -1) selectedIds.push(id);
                            catPath = [];
                        } else {
                            catPath[level] = id;
                        }
                        render();
                    };

                    window.__catRemove = function (id) {
                        selectedIds = selectedIds.filter(function (s) { return s !== id; });
                        render();
                    };

                    function esc(s) {
                        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                    }

                    document.addEventListener('DOMContentLoaded', render);
                })();
                </script>
            </x-card-collapse>

            {{-- ── Product Variants ──────────────────────────────────────── --}}
            <x-card-collapse class="form-card span-12" title="Product Variants"
                subtitle="Optional: build product variants. Each variant is a combination of variation options (e.g. Size: Small + Color: Red) with its own price, stock and image."
                :start-open="true">

                <div class="notice-muted" style="margin-bottom:16px">
                    <strong>How it works:</strong> Click <em>Add Variant</em>, pick one option per variation group
                    (Size, Color, …), and set this variant's own price, stock and image.
                    Use <em>+ Add another group</em> to combine multiple groups per variant.
                    When variants exist, per-variant stock replaces the product-level stock field.
                </div>

                @php
                    $vjson = $variations->keyBy('id')->map(fn($v) => [
                        'name'    => $v->translationValue('name') ?? $v->slug,
                        'options' => $v->options->map(fn($o) => [
                            'id'   => $o->id,
                            'name' => $o->translationValue('name') ?? $o->slug,
                        ])->values()->all(),
                    ]);
                @endphp
                <script id="variations-data" type="application/json">
                    @json($vjson)
                </script>

                <div id="variants-list">
                    @foreach ($variants as $vIdx => $variant)
                        @include('tenant.product._variant-row', [
                            'vIdx'       => $vIdx,
                            'variant'    => $variant,
                            'variations' => $variations,
                        ])
                    @endforeach
                </div>

                @error('variants') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror

                <div style="margin-top:14px">
                    <button type="button" class="btn btn-secondary" onclick="addVariant()">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Add Variant
                    </button>
                </div>
            </x-card-collapse>

        </div>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('tenant.own-products.index') }}" class="btn btn-secondary">Back to Products</a>
            <button type="submit" class="btn btn-primary">Save Product</button>
        </div>
    </form>

    {{-- ── Video Preview Modal ────────────────────────────────────────── --}}
    <div id="video-modal"
         style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);align-items:center;justify-content:center"
         onclick="closeVideoModal()">
        <div style="position:relative;max-width:92vw" onclick="event.stopPropagation()">
            <button type="button" onclick="closeVideoModal()"
                    style="position:absolute;top:-38px;right:0;background:none;border:none;color:#fff;font-size:26px;line-height:1;cursor:pointer;opacity:.85"
                    title="Close">&times;</button>
            <video id="video-modal-player" controls
                   style="display:block;max-width:92vw;max-height:82vh;border-radius:10px;background:#000;box-shadow:0 8px 40px rgba(0,0,0,.7)">
            </video>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Variation data ─────────────────────────────────────────────────────
    var __variations = {};
    try {
        __variations = JSON.parse(document.getElementById('variations-data').textContent);
    } catch (e) {}

    // ── Locale tab switching ───────────────────────────────────────────────
    window.switchLocale = function (code) {
        document.querySelectorAll('.locale-tab').forEach(function (tab) {
            tab.classList.toggle('is-active', tab.dataset.locale === code);
        });
        document.querySelectorAll('.locale-panel').forEach(function (panel) {
            var isActive = panel.id === 'locale-panel-' + code;
            panel.classList.toggle('is-active', isActive);
            panel.style.display = isActive ? '' : 'none';
        });
    };

    // ── Manage Stock toggle ────────────────────────────────────────────────
    window.toggleManageStock = function (checkbox) {
        var stockWrap    = document.getElementById('stock-field-wrap');
        var minStockWrap = document.getElementById('min-stock-field-wrap');
        if (stockWrap)    stockWrap.style.display    = checkbox.checked ? '' : 'none';
        if (minStockWrap) minStockWrap.style.display = checkbox.checked ? '' : 'none';
    };

    // ── Primary image ──────────────────────────────────────────────────────
    window.removePrimaryImage = function () {
        document.getElementById('remove_primary_image_input').value = '1';
        var preview = document.getElementById('primary-image-preview');
        if (preview) preview.style.display = 'none';
        var fileInput = document.getElementById('primary_image');
        if (fileInput) {
            try { fileInput.files = new DataTransfer().files; } catch (e) { fileInput.value = ''; }
        }
        var filesDiv = fileInput ? fileInput.closest('[data-dropzone]')?.querySelector('[data-dropzone-files]') : null;
        if (filesDiv) filesDiv.innerHTML = '';
    };

    // Clear the remove flag when the user picks a new primary image.
    document.addEventListener('DOMContentLoaded', function () {
        var primaryInput = document.getElementById('primary_image');
        if (primaryInput) {
            primaryInput.addEventListener('change', function () {
                if (primaryInput.files && primaryInput.files.length > 0) {
                    document.getElementById('remove_primary_image_input').value = '0';
                }
            });
        }
    });

    // ── Video modal ────────────────────────────────────────────────────────
    window.openVideoModal = function (el) {
        var url    = el.dataset.videoUrl;
        var modal  = document.getElementById('video-modal');
        var player = document.getElementById('video-modal-player');
        if (!url || !modal || !player) return;
        player.src = url;
        modal.style.display = 'flex';
        player.load();
        player.play().catch(function () {});
    };

    window.closeVideoModal = function () {
        var modal  = document.getElementById('video-modal');
        var player = document.getElementById('video-modal-player');
        if (!modal || !player) return;
        player.pause();
        player.src = '';
        modal.style.display = 'none';
    };

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeVideoModal(); });

    // ── Gallery removal ────────────────────────────────────────────────────
    window.removeGalleryItem = function (fileId, btn) {
        var item = document.getElementById('gallery-item-' + fileId);
        if (item) item.remove();
        var container = document.getElementById('gallery-remove-inputs');
        var hidden    = document.createElement('input');
        hidden.type   = 'hidden';
        hidden.name   = 'remove_gallery_ids[]';
        hidden.value  = fileId;
        container.appendChild(hidden);
    };

    // ── TinyMCE initialisation ────────────────────────────────────────────
    function initTinyMCE() {
        document.querySelectorAll('[id^="tme-desc-"]').forEach(function (textarea) {
            if (tinymce.get(textarea.id)) return;
            tinymce.init({
                selector: '#' + textarea.id,
                height: 320,
                menubar: 'file edit view insert format tools table',
                plugins: 'lists link image media table wordcount code fullscreen',
                toolbar: 'undo redo | styles | bold italic underline strikethrough | link image media table | bullist numlist | code fullscreen',
                branding: false,
                promotion: false,
                setup: function (editor) {
                    editor.on('init', function () {
                        editor.setContent(textarea.value || '');
                    });
                    editor.on('change input', function () {
                        textarea.value = editor.getContent();
                    });
                },
            });
        });
    }

    function bootTinyMCE() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTinyMCE);
        } else {
            initTinyMCE();
        }
    }

    // ── Variant management ─────────────────────────────────────────────────
    var nextVariantIdx = {{ count($variants) }};

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function buildPairRow(vIdx, pIdx, variationId, optionId) {
        variationId = variationId || '';
        optionId    = optionId    || '';

        var variationOptions = '<option value="">Select variation group</option>';
        Object.keys(__variations).forEach(function (id) {
            var sel = String(variationId) === String(id) ? ' selected' : '';
            variationOptions += '<option value="' + id + '"' + sel + '>' + escHtml(__variations[id].name) + '</option>';
        });

        var optionOptions = '<option value="">Select option value</option>';
        if (variationId && __variations[variationId]) {
            __variations[variationId].options.forEach(function (opt) {
                var sel = String(optionId) === String(opt.id) ? ' selected' : '';
                optionOptions += '<option value="' + opt.id + '"' + sel + '>' + escHtml(opt.name) + '</option>';
            });
        }

        var removeBtn = pIdx > 0
            ? '<button type="button" class="btn btn-secondary btn-sm btn-danger" onclick="removeOptionPair(this)" title="Remove" style="padding:6px 8px">'
                + '<svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">'
                + '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'
                + '</svg></button>'
            : '';

        return '<div class="form-grid form-grid-2 variant-pair-row" style="gap:8px;align-items:end">'
            + '<div><select class="field-control" name="variants[' + vIdx + '][pairs][' + pIdx + '][variation_id]" onchange="onVariationChange(this)">'
            + variationOptions + '</select></div>'
            + '<div class="var-options" style="display:flex;gap:6px;align-items:center">'
            + '<div style="flex:1"><select class="field-control" name="variants[' + vIdx + '][pairs][' + pIdx + '][option_id]">'
            + optionOptions + '</select></div>'
            + removeBtn
            + '</div></div>';
    }

    function buildVariantRow(vIdx) {
        var dropzoneHtml = '<div class="w-full relative group" data-dropzone>'
            + '<label class="relative flex flex-col items-center justify-center w-full py-4 px-4 border-2 border-dashed border-(--border2) rounded-xl bg-(--surface) hover:border-(--cyan) transition-all duration-300 cursor-pointer focus-within:ring-2 focus-within:ring-(--cyan) focus-within:ring-offset-2 overflow-hidden">'
            + '<div class="absolute inset-0 bg-(--elevated) opacity-0 group-hover:opacity-40 transition-opacity duration-300"></div>'
            + '<div class="relative w-8 h-8 mb-2 rounded-full bg-(--surface) border border-(--border2) flex items-center justify-center text-(--cyan) shadow-sm group-hover:scale-110 transition-transform duration-300 z-10">'
            + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">'
            + '<path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>'
            + '</svg></div>'
            + '<div class="relative text-center z-10"><h4 class="text-[12px] font-semibold text-(--t1) mb-1">Upload variant image</h4>'
            + '<p class="text-[11px] text-(--t3)">PNG, JPG, WEBP up to 5MB</p></div>'
            + '<input type="file" name="variants[' + vIdx + '][image]" accept="image/*" class="dropzone-input sr-only">'
            + '</label><div class="dropzone-files mt-4 space-y-2 empty:hidden" data-dropzone-files></div></div>';

        return '<div class="vgroup-card" data-vcard>'
            + '<input type="hidden" name="variants[' + vIdx + '][id]" value="">'
            + '<input type="hidden" name="variants[' + vIdx + '][remove_image]" value="0" class="v-remove-img-flag">'
            + '<div class="vgroup-header">'
            + '<span class="vgroup-title">Variant #<span class="v-num"></span></span>'
            + '<button type="button" class="btn btn-secondary btn-sm btn-danger" onclick="removeVariant(this)">'
            + '<svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">'
            + '<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/>'
            + '</svg> Remove</button></div>'
            + '<div class="vgroup-body">'
            + '<div class="form-grid form-grid-2" style="margin-bottom:12px"><div>'
            + '<label class="field-label">Variant Image</label>' + dropzoneHtml + '</div></div>'
            + '<div style="margin-bottom:12px"><label class="field-label">Variant Name</label>'
            + '<input type="text" name="variants[' + vIdx + '][title]" class="field-control" value="" placeholder="Auto-generated from options if left blank"></div>'
            + '<label class="field-label">Variant Composition *</label>'
            + '<div class="v-pairs" style="display:flex;flex-direction:column;gap:8px">'
            + buildPairRow(vIdx, 0, '', '')
            + '</div>'
            + '<div style="margin-top:8px"><button type="button" class="btn btn-secondary btn-sm" onclick="addOptionPair(this)">'
            + '<svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">'
            + '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>'
            + '</svg> Add another group</button></div>'
            + '<div class="form-grid form-grid-3" style="margin-top:14px">'
            + '<div><label class="field-label">Price *</label><input type="number" step="0.01" min="0" name="variants[' + vIdx + '][price]" value="" class="field-control"></div>'
            + '<div><label class="field-label">Stock</label><input type="number" min="0" step="1" name="variants[' + vIdx + '][stock]" value="0" class="field-control"></div>'
            + '<div><label class="field-label">SKU (optional)</label><input type="text" name="variants[' + vIdx + '][sku]" value="" placeholder="Auto-generated if blank" class="field-control"></div>'
            + '<div><label class="field-label">Weight (grams)</label><input type="number" min="0" step="1" name="variants[' + vIdx + '][weight_grams]" value="" placeholder="0" class="field-control"></div>'
            + '<div><label class="field-label">Status</label>'
            + '<label class="toggle-field"><input type="hidden" name="variants[' + vIdx + '][active]" value="0">'
            + '<input type="checkbox" name="variants[' + vIdx + '][active]" value="1" checked><span>Active</span></label></div>'
            + '</div>'
            + '</div></div>';
    }

    window.addVariant = function () {
        var container = document.getElementById('variants-list');
        var div       = document.createElement('div');
        div.innerHTML = buildVariantRow(nextVariantIdx);
        container.appendChild(div.firstElementChild);
        nextVariantIdx++;
        updateVariantNumbers();
    };

    window.removeVariant = function (btn) {
        var card = btn.closest('[data-vcard]');
        if (card) card.remove();
        renumberVariants();
    };

    window.addOptionPair = function (btn) {
        var card  = btn.closest('[data-vcard]');
        var pairs = card.querySelector('.v-pairs');
        var vIdx  = getVariantIdx(card);
        var pIdx  = pairs.querySelectorAll('.variant-pair-row').length;
        var div   = document.createElement('div');
        div.innerHTML = buildPairRow(vIdx, pIdx, '', '');
        pairs.appendChild(div.firstElementChild);
    };

    window.removeOptionPair = function (btn) {
        var row  = btn.closest('.variant-pair-row');
        var card = btn.closest('[data-vcard]');
        if (row) row.remove();
        renumberPairs(card);
    };

    window.onVariationChange = function (select) {
        var row          = select.closest('.variant-pair-row');
        var optionSelect = row.querySelector('.var-options select');
        if (!optionSelect) return;
        var varId = select.value;
        optionSelect.innerHTML = '<option value="">Select option value</option>';
        if (varId && __variations[varId]) {
            __variations[varId].options.forEach(function (opt) {
                var o = document.createElement('option');
                o.value       = opt.id;
                o.textContent = opt.name;
                optionSelect.appendChild(o);
            });
        }
    };

    window.removeVariantImage = function (btn, vIdx) {
        var card    = btn.closest('[data-vcard]');
        var imgWrap = btn.closest('[style*="display:flex"]') || btn.parentElement;
        if (imgWrap) imgWrap.style.display = 'none';
        var fileInput = card.querySelector('.dropzone-input');
        if (fileInput) {
            try { fileInput.files = new DataTransfer().files; } catch (e) { fileInput.value = ''; }
        }
        var filesDiv = card.querySelector('[data-dropzone-files]');
        if (filesDiv) filesDiv.innerHTML = '';
        var flag = card.querySelector('.v-remove-img-flag');
        if (flag) flag.value = '1';
    };

    // ── Variant numbering helpers ──────────────────────────────────────────
    function getVariantIdx(card) {
        var idInput = card.querySelector('input[name^="variants["]');
        if (!idInput) return 0;
        var m = idInput.name.match(/variants\[(\d+)\]/);
        return m ? parseInt(m[1]) : 0;
    }

    function updateVariantNumbers() {
        document.querySelectorAll('[data-vcard]').forEach(function (card, idx) {
            var numEl = card.querySelector('.v-num');
            if (numEl) numEl.textContent = idx + 1;
        });
    }

    function renumberVariants() {
        document.querySelectorAll('[data-vcard]').forEach(function (card, idx) {
            var numEl = card.querySelector('.v-num');
            if (numEl) numEl.textContent = idx + 1;
            card.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/variants\[\d+\]/, 'variants[' + idx + ']');
            });
        });
        nextVariantIdx = document.querySelectorAll('[data-vcard]').length;
    }

    function renumberPairs(card) {
        if (!card) return;
        var vIdx = getVariantIdx(card);
        card.querySelectorAll('.variant-pair-row').forEach(function (row, pIdx) {
            row.querySelectorAll('[name]').forEach(function (el) {
                el.name = el.name.replace(/pairs\[\d+\]/, 'pairs[' + pIdx + ']');
            });
            var removeBtn = row.querySelector('button[onclick="removeOptionPair(this)"]');
            if (removeBtn) removeBtn.style.display = pIdx === 0 ? 'none' : '';
        });
    }

    if (typeof window.tinymce !== 'undefined') {
        bootTinyMCE();
    } else {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
        s.referrerPolicy = 'origin';
        s.onload = bootTinyMCE;
        document.head.appendChild(s);
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateVariantNumbers();
        document.querySelectorAll('[data-vcard]').forEach(function (card) {
            renumberPairs(card);
        });
    });

    // ── AJAX pre-submit validation ───────────────────────────────────────────
    (function () {
        var form = document.getElementById('own-product-form');
        var validateUrl = @json($isEdit ? route('tenant.own-products.validate.update', $product) : route('tenant.own-products.validate'));

        function clearFieldErrors() {
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            form.querySelectorAll('.field-error[data-js-error]').forEach(function (el) { el.remove(); });
        }

        function dotToBracketName(dotted) {
            var parts = dotted.split('.');
            return parts[0] + parts.slice(1).map(function (p) { return '[' + p + ']'; }).join('');
        }

        function showFieldErrors(errors) {
            clearFieldErrors();
            var firstEl = null;
            Object.keys(errors || {}).forEach(function (key) {
                var messages = errors[key];
                var input = form.querySelector('[name="' + dotToBracketName(key) + '"]');
                var msg = document.createElement('p');
                msg.className = 'field-error';
                msg.setAttribute('data-js-error', '1');
                msg.textContent = Array.isArray(messages) ? messages[0] : messages;
                if (input) {
                    input.classList.add('is-invalid');
                    input.insertAdjacentElement('afterend', msg);
                    if (!firstEl) firstEl = input;
                } else if (!firstEl) {
                    form.insertAdjacentElement('afterbegin', msg);
                }
            });
            if (firstEl) {
                firstEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstEl.focus({ preventScroll: true });
            }
        }

        form.addEventListener('submit', function (e) {
            if (typeof window.tinymce !== 'undefined') {
                tinymce.triggerSave();
            }

            if (form.dataset.validated === '1') {
                return; // already validated via AJAX — let the real submit proceed
            }

            e.preventDefault();

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            var validateFormData = new FormData(form);
            validateFormData.delete('_method');

            fetch(validateUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: validateFormData,
            })
                .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data }; }); })
                .then(function (result) {
                    if (result.status === 200 && result.data.success) {
                        clearFieldErrors();
                        form.dataset.validated = '1';
                        form.submit();
                    } else {
                        showFieldErrors(result.data.errors);
                        if (submitBtn) submitBtn.disabled = false;
                    }
                })
                .catch(function () {
                    // Validation request failed (e.g. network issue) — fall back to a normal submit.
                    form.dataset.validated = '1';
                    form.submit();
                });
        });
    })();
})();
</script>
@endpush
