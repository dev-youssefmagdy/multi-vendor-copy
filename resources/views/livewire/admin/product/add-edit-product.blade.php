<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Central Catalog</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions" style="flex-shrink:0">
                <a href="{{ route('admin.products.index') }}"  class="btn btn-secondary">Back to
                    Products</a>
                <x-btn type="submit">Save Product</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="card section-gap notice-error">
                <h3 class="panel-title">Please fix the highlighted fields</h3>
                <p class="panel-copy">The product was not saved because some required values are missing or invalid.</p>
            </div>
        @endif

        <div class="grid gap-2">

            {{-- ── Basics ──────────────────────────────────────────────────────── --}}
            <x-card-collapse title="Basics" subtitle="Identifiers, publication state, factory origin, and weight."
                class="form-card span-12" :start-open="true">
                <div class="form-grid form-grid-3">
                    <div>
                        <label class="field-label" for="sku">SKU *</label>
                        <x-input id="sku" type="text" :class="!$errors->has('sku') ?: 'is-invalid'"
                            wire:model.defer="sku" placeholder="PRD-001" />
                        @error('sku') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="slug">Slug</label>
                        <x-input id="slug" type="text" :class="!$errors->has('slug') ?: 'is-invalid'"
                            wire:model.defer="slug" placeholder="auto-generated-if-empty" />
                        @error('slug') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="status">Status</label>
                        <x-select id="status" :class="!$errors->has('status') ?: 'is-invalid'"
                            wire:model.defer="status">
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                            @endforeach
                        </x-select>
                        @error('status') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="factory">Factory ID / Name</label>
                        <x-input id="factory" type="text" :class="!$errors->has('factory') ?: 'is-invalid'"
                            wire:model.defer="factory" placeholder="e.g. FAC-023 or Shenzhen Co." />
                        @error('factory') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="weight-grams">Weight (grams)</label>
                        <x-input id="weight-grams" type="number" min="0" :class="!$errors->has('weightGrams') ?: 'is-invalid'" wire:model.defer="weightGrams" placeholder="0" />
                        @error('weightGrams') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="field-flag-grid">
                        <label class="toggle-field">
                            <input type="checkbox" wire:model.defer="manageStock">
                            <span>Manage Stock</span>
                        </label>
                        <label class="toggle-field">
                            <input type="checkbox" wire:model.defer="isTaxable">
                            <span>Taxable</span>
                        </label>
                    </div>
                </div>
            </x-card-collapse>

            {{-- ── Media ───────────────────────────────────────────────────────── --}}
            <x-card-collapse title="Primary Image"
                subtitle="Main product image. Generates large, medium, and thumb derivatives." class="form-card span-4"
                :start-open="true">
                <div class="media-card">
                    @if ($primaryImage)
                        <div class="media-preview-wrap" style="width:120px;aspect-ratio:1/1">
                            <img src="{{ $primaryImage->temporaryUrl() }}" alt="Preview" class="media-preview-image">
                        </div>
                    @elseif ($existingImage && !$removePrimaryImage)
                        <div class="media-preview-wrap" style="width:120px;aspect-ratio:1/1">
                            <img src="{{ $existingImage }}" alt="Current image" class="media-preview-image">
                        </div>
                    @endif

                    <label class="field-label" for="primary-image">Primary Image</label>
                    <x-dropzone id="primary-image" model="primaryImage" remove-action="removeImage" accept="image/*"
                        :multiple="false" label="Upload primary image" sublabel="PNG, JPG, WEBP up to 4MB"
                        :expected-width="config('image_dimensions.product.width')"
                        :expected-height="config('image_dimensions.product.height')" />
                    @error('primaryImage') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </x-card-collapse>

            {{-- ── Gallery ──────────────────────────────────────────────────────── --}}
            <x-card-collapse title="Media Gallery"
                subtitle="Additional images and videos shown in the product slider. JPG, PNG, GIF, WEBP, MP4, WEBM, MOV."
                class="form-card span-8" :start-open="true">

                {{-- Existing gallery files --}}
                @if ($existingGallery->isNotEmpty())
                    <p class="field-hint mb-2">Drag thumbnails to reorder. The image order here controls the display order in the storefront slider.</p>
                    <div class="gallery-grid" id="product-gallery-sortable" wire:ignore.self>
                        @foreach ($existingGallery as $file)
                            @if (!in_array($file->id, $removeGalleryIds))
                                <div class="gallery-item" data-id="{{ $file->id }}" wire:key="gallery-{{ $file->id }}">
                                    <span class="gallery-item-handle" title="Drag to reorder">
                                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <line x1="4" y1="8" x2="20" y2="8" />
                                            <line x1="4" y1="16" x2="20" y2="16" />
                                        </svg>
                                    </span>
                                    @if ($file->file_type->value === 'video')
                                        <div class="gallery-item-video">
                                            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="1.5">
                                                <polygon points="5 3 19 12 5 21 5 3" />
                                            </svg>
                                            <span>{{ strtoupper($file->extension) }}</span>
                                        </div>
                                    @else
                                        <img src="{{ $file->full_path }}" alt="Gallery image" class="gallery-item-thumb">
                                    @endif
                                    <button type="button" class="gallery-item-remove"
                                        wire:click="markGalleryForRemoval({{ $file->id }})" title="Remove">
                                        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2.5">
                                            <line x1="18" y1="6" x2="6" y2="18" />
                                            <line x1="6" y1="6" x2="18" y2="18" />
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                <x-dropzone id="gallery-files" model="galleryFiles"
                    accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/quicktime"
                    :multiple="true" label="Upload images or videos"
                    sublabel="JPG, PNG, GIF, WEBP, MP4, WEBM, MOV — multiple allowed"
                    :expected-width="config('image_dimensions.product.width')"
                    :expected-height="config('image_dimensions.product.height')" />
            </x-card-collapse>

            {{-- ── Translations ─────────────────────────────────────────────────── --}}
            <x-card-collapse title="Translations"
                subtitle="Multilingual product name, summary, description, and SEO meta fields."
                class="form-card span-12" :start-open="true">

                @if ($languages->count())
                    <div class="locale-tabs">
                        @foreach ($languages as $language)
                            <button type="button" class="locale-tab {{ $activeLocale === $language->code ? 'is-active' : '' }}"
                                wire:click="setActiveLocale('{{ $language->code }}')">
                                <span>{{ strtoupper($language->code) }}</span>
                                <small>{{ $language->native_name }}</small>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($languages as $language)
                        <?php $isVisible = $activeLocale === $language->code; ?>
                        <div class="locale-panel {{ $isVisible ? 'is-active' : '' }}">
                            @if ($isVisible)
                                <div class="form-grid form-grid-2">
                                    <div class="span-2">
                                        <label class="field-label" for="name-{{ $language->code }}">Name
                                            ({{ strtoupper($language->code) }}) @if($language->is_default)*@endif</label>
                                        <input id="name-{{ $language->code }}" type="text"
                                            class="field-control {{ $errors->has('translations.' . $language->code . '.name') ? 'is-invalid' : '' }}"
                                            wire:model.defer="translations.{{ $language->code }}.name"
                                            placeholder="Localized product name">
                                        @error('translations.' . $language->code . '.name') <p class="field-error">{{ $message }}
                                        </p> @enderror
                                    </div>

                                    <div>
                                        <label class="field-label" for="label-{{ $language->code }}">Label</label>
                                        <input id="label-{{ $language->code }}" type="text"
                                            class="field-control {{ $errors->has("translations.{$language->code}.label") ? 'is-invalid' : '' }}"
                                            wire:model.defer="translations.{{ $language->code }}.label"
                                            placeholder="Short display label (used in UI, breadcrumbs, listings)">
                                        @error("translations.{$language->code}.label") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="field-label" for="summary-{{ $language->code }}">Summary</label>
                                        <input id="summary-{{ $language->code }}" type="text"
                                            class="field-control {{ $errors->has("translations.{$language->code}.summary") ? 'is-invalid' : '' }}"
                                            wire:model.defer="translations.{{ $language->code }}.summary"
                                            placeholder="Short merchandising summary">
                                        @error("translations.{$language->code}.summary") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="span-2">
                                        <label class="field-label" for="description-{{ $language->code }}">Description</label>
                                        <x-editor model="translations.{{ $language->code }}.description" :value="data_get($translations, $language->code . '.description', '')" :height="320" />
                                    </div>

                                    <div>
                                        <label class="field-label" for="meta-keywords-{{ $language->code }}">Meta Keywords</label>
                                        <input id="meta-keywords-{{ $language->code }}" type="text"
                                            class="field-control {{ $errors->has('translations.' . $language->code . '.meta_keywords') ? 'is-invalid' : '' }}"
                                            wire:model.defer="translations.{{ $language->code }}.meta_keywords"
                                            placeholder="keyword1, keyword2, keyword3">
                                        @error('translations.' . $language->code . '.meta_keywords') <p class="field-error">
                                            {{ $message }}
                                        </p> @enderror
                                    </div>

                                    <div>
                                        <label class="field-label" for="meta-description-{{ $language->code }}">Meta
                                            Description</label>
                                        <textarea id="meta-description-{{ $language->code }}" rows="3"
                                            class="field-control field-textarea {{ $errors->has('translations.' . $language->code . '.meta_description') ? 'is-invalid' : '' }}"
                                            wire:model.defer="translations.{{ $language->code }}.meta_description"
                                            placeholder="SEO-friendly page description (≤ 160 chars)"></textarea>
                                        @error('translations.' . $language->code . '.meta_description')
                                            <p class="field-error">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="notice-muted">Create at least one active language before managing translated product
                        content.</div>
                @endif
            </x-card-collapse>

            {{-- ── Pricing & Inventory ──────────────────────────────────────────── --}}
            <x-card-collapse title="Pricing & Inventory"
                subtitle="Base pricing, promotional pricing, and stock thresholds." class="form-card span-6"
                :start-open="true">
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label" for="base-price">Base Price</label>
                        <x-input id="base-price" type="number" min="0" step="0.01" :class="$errors->has('basePrice') ? 'is-invalid' : ''" wire:model.defer="basePrice" />
                        @error('basePrice') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="sale-price">Sale Price</label>
                        <x-input id="sale-price" type="number" min="0" step="0.01" :class="$errors->has('salePrice') ? 'is-invalid' : ''" wire:model.defer="salePrice" />
                        @error('salePrice') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="cost-price">Cost Price</label>
                        <x-input id="cost-price" type="number" min="0" step="0.01" :class="$errors->has('costPrice') ? 'is-invalid' : ''" wire:model.defer="costPrice" />
                        @error('costPrice') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="stock">Stock</label>
                        <x-input id="stock" type="number" min="0" :class="$errors->has('stock') ? 'is-invalid' : ''"
                            wire:model.defer="stock" />
                        @error('stock') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="field-label" for="min-stock">Minimum Stock</label>
                        <x-input id="min-stock" type="number" min="0" :class="$errors->has('minStock') ? 'is-invalid' : ''" wire:model.defer="minStock" />
                        @error('minStock') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-card-collapse>

            {{-- ── Categories ───────────────────────────────────────────────────── --}}
            <x-card-collapse class="form-card span-6" title="Categories"
                subtitle="Attach this product to one or more catalog categories." :start-open="true">
                @php
                    $flatCategories = [];
                    $walkCategories = function ($items, int $depth = 0) use (&$walkCategories, &$flatCategories) {
                        foreach ($items as $item) {
                            $hasChildren = $item->children && $item->children->isNotEmpty();
                            $flatCategories[] = [
                                'model'       => $item,
                                'depth'       => $depth,
                                'disabled'    => $hasChildren,
                                'prefix'      => str_repeat('-', $depth + 1) . ' ',
                            ];
                            if ($hasChildren) {
                                $walkCategories($item->children, $depth + 1);
                            }
                        }
                    };
                    $walkCategories($categoryTree ?? collect());
                @endphp

                @if (empty($flatCategories))
                    <div class="notice-muted">No categories available yet.</div>
                @else
                    <x-select multiple searchable wire:model.live="categoryIds" placeholder="Search and select categories">
                        @foreach ($flatCategories as $row)
                            <option
                                value="{{ $row['model']->id }}"
                                @if($row['disabled']) disabled @endif
                            >{{ $row['prefix'] }}{{ $row['model']->translationValue('name') ?? $row['model']->slug }}</option>
                        @endforeach
                    </x-select>
                @endif
                @error('categoryIds') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror
            </x-card-collapse>

            {{-- ── Product Variants ─────────────────────────────────────────────── --}}
            <x-card-collapse class="form-card span-12" title="Product Variants"
                subtitle="Optional: build product variants. Each variant is a combination of variation options (e.g. Size: Small + Color: Red) with its own price, stock and image."
                :start-open="true">

                <div class="notice-muted" style="margin-bottom:16px">
                    <strong>How it works:</strong> Click <em>Add Variant</em>, pick one option per variation group
                    (Size, Color, …), and set this variant's own price, stock and thumbnail image. Use
                    <em>+ Add another group</em> within a variant to combine multiple groups (e.g.
                    Size <strong>Small</strong> + Color <strong>Red</strong> = one variant).
                    When variants exist, per-variant stock replaces the product-level stock field below.
                </div>

                <div class="vgroup-list">
                    @foreach ($variants as $vIdx => $variant)
                        @php
                            $usedVariationIds = collect($variant['pairs'] ?? [])
                                ->pluck('variation_id')
                                ->filter(fn($id) => filled($id))
                                ->map(fn($id) => (int) $id)
                                ->all();
                        @endphp
                        <div class="vgroup-card" wire:key="variant-row-{{ $vIdx }}-{{ $variant['id'] ?? 'new' }}">
                            <div class="vgroup-header">
                                <span class="vgroup-title">Variant #{{ $vIdx + 1 }}</span>
                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                    wire:click="removeVariant({{ $vIdx }})">
                                    <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path d="M19 6l-1 14H6L5 6" />
                                        <path d="M10 11v6M14 11v6" />
                                        <path d="M9 6V4h6v2" />
                                    </svg>
                                    Remove
                                </button>
                            </div>
                            <div class="vgroup-body">
                                {{-- Variant Image --}}
                                <div class="form-grid form-grid-1" style="margin-bottom:12px">
                                    <div>
                                        <label class="field-label">Variant Image</label>
                                        <x-dimension-hint :width="config('image_dimensions.product.width')" :height="config('image_dimensions.product.height')" />
                                        <div data-dimension-check data-expect-w="{{ config('image_dimensions.product.width') }}" data-expect-h="{{ config('image_dimensions.product.height') }}" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                                            @if ($variant['image'])
                                                <img src="{{ $variant['image']->temporaryUrl() }}" alt=""
                                                    style="width:80px; height:80px; object-fit:cover; border-radius:6px; border:1px solid #ddd" />
                                            @elseif (!empty($variant['thumbnail_url']))
                                                <img src="{{ $variant['thumbnail_url'] }}" alt=""
                                                    style="width:80px; height:80px; object-fit:cover; border-radius:6px; border:1px solid #ddd" />
                                            @endif
                                            <div class="file-input-wrap">
                                                <input type="file" wire:model="variants.{{ $vIdx }}.image"
                                                    accept="image/*"
                                                    wire:loading.class="opacity-50" wire:target="variants.{{ $vIdx }}.image" />
                                                <div class="file-input-loader" wire:loading wire:target="variants.{{ $vIdx }}.image">
                                                    <svg class="file-input-spinner" viewBox="0 0 24 24" fill="none">
                                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-dasharray="56.55" stroke-dashoffset="14" stroke-linecap="round"/>
                                                    </svg>
                                                    <span>Uploading…</span>
                                                </div>
                                            </div>
                                            <x-dimension-feedback :width="config('image_dimensions.product.width')" :height="config('image_dimensions.product.height')" />
                                            @if ($variant['image'] || !empty($variant['thumbnail_url']))
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    wire:click="removeVariantImage({{ $vIdx }})">Remove image</button>
                                            @endif
                                        </div>
                                        @error("variants.{$vIdx}.image") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                {{-- Variant Name --}}
                                <div class="form-grid form-grid-1" style="margin-bottom:12px">
                                    <div>
                                        <label class="field-label" for="variant-title-{{ $vIdx }}">Variant Name</label>
                                        <x-input id="variant-title-{{ $vIdx }}" type="text"
                                            wire:model.defer="variants.{{ $vIdx }}.title"
                                            placeholder="Auto-generated from options if left blank" />
                                        @error("variants.{$vIdx}.title") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                {{-- Option pairs (variation group + option) --}}
                                <label class="field-label">Variant Composition *</label>
                                <div style="display:flex; flex-direction:column; gap:8px">
                                    @foreach ($variant['pairs'] as $pIdx => $pair)
                                        @php
                                            $selectedVariation = filled($pair['variation_id'])
                                                ? $variations->find((int) $pair['variation_id'])
                                                : null;
                                            // Groups already used by OTHER pairs in this variant should be excluded.
                                            $otherUsed = collect($variant['pairs'])
                                                ->except($pIdx)
                                                ->pluck('variation_id')
                                                ->filter(fn($id) => filled($id))
                                                ->map(fn($id) => (int) $id)
                                                ->all();
                                        @endphp
                                        <div class="form-grid form-grid-2" style="gap:8px; align-items:end"
                                            wire:key="variant-{{ $vIdx }}-pair-{{ $pIdx }}">
                                            <div>
                                                <x-select wire:model.live="variants.{{ $vIdx }}.pairs.{{ $pIdx }}.variation_id">
                                                    <option value="">Select variation group</option>
                                                    @foreach ($variations as $variation)
                                                        @php
                                                            $isCurrent = (int) ($pair['variation_id'] ?? 0) === $variation->id;
                                                            $isExcluded = in_array($variation->id, $otherUsed, true) && !$isCurrent;
                                                        @endphp
                                                        @if (!$isExcluded)
                                                            <option value="{{ $variation->id }}" @selected($isCurrent)>
                                                                {{ $variation->translationValue('name') ?? $variation->slug }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </x-select>
                                            </div>
                                            <div style="display:flex; gap:6px; align-items:center">
                                                <div style="flex:1">
                                                    <x-select wire:model.defer="variants.{{ $vIdx }}.pairs.{{ $pIdx }}.option_id">
                                                        <option value="">Select option value</option>
                                                        @if ($selectedVariation)
                                                            @foreach ($selectedVariation->options as $option)
                                                                <option value="{{ $option->id }}" @selected((int) ($pair['option_id'] ?? 0) === $option->id)>
                                                                    {{ $option->translationValue('name') ?? $option->slug }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </x-select>
                                                </div>
                                                @if ($pIdx > 0)
                                                    <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                        wire:click="removeOptionPair({{ $vIdx }}, {{ $pIdx }})"
                                                        title="Remove this group" style="padding:6px 8px">
                                                        <svg width="11" height="11" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2.5">
                                                            <line x1="18" y1="6" x2="6" y2="18" />
                                                            <line x1="6" y1="6" x2="18" y2="18" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if (count($usedVariationIds) < $variations->count())
                                    <div style="margin-top:8px">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            wire:click="addOptionPair({{ $vIdx }})">
                                            <svg width="11" height="11" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2.5">
                                                <line x1="12" y1="5" x2="12" y2="19" />
                                                <line x1="5" y1="12" x2="19" y2="12" />
                                            </svg>
                                            Add another group
                                        </button>
                                    </div>
                                @endif

                                {{-- Price / Stock / SKU / Weight --}}
                                <div class="form-grid form-grid-3" style="margin-top:14px">
                                    <div>
                                        <label class="field-label">Price *</label>
                                        <x-input type="number" step="0.01" min="0"
                                            wire:model.defer="variants.{{ $vIdx }}.price" />
                                        @error("variants.{$vIdx}.price") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="field-label">Stock</label>
                                        <x-input type="number" min="0" step="1"
                                            wire:model.defer="variants.{{ $vIdx }}.stock" />
                                        @error("variants.{$vIdx}.stock") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="field-label">SKU (optional)</label>
                                        <x-input type="text"
                                            wire:model.defer="variants.{{ $vIdx }}.sku"
                                            placeholder="Auto-generated if blank" />
                                        @error("variants.{$vIdx}.sku") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="field-label">Weight (grams)</label>
                                        <x-input type="number" min="0" step="1"
                                            wire:model.defer="variants.{{ $vIdx }}.weight_grams"
                                            placeholder="0" />
                                        @error("variants.{$vIdx}.weight_grams") <p class="field-error">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                @error("variants.{$vIdx}.pairs") <p class="field-error">{{ $message }}</p> @enderror
                                @error("variants.{$vIdx}") <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:14px">
                    <button type="button" class="btn btn-secondary" wire:click="addVariant">
                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Add Variant
                    </button>
                </div>
            </x-card-collapse>

            {{-- ── Shipping Options ─────────────────────────────────────────────── --}}
            {{-- <x-card-collapse class="form-card span-12" title="Shipping Options"
                subtitle="Control where this product can be delivered." :start-open="true">

                <div class="scope-cards" style="margin-bottom:16px">
                    @foreach ($deliveryScopes as $scope)
                        @php
                            $scopeDesc = match ($scope) {
                                \App\Enums\DeliveryScope::AllZones => 'Ship to all countries globally',
                                \App\Enums\DeliveryScope::SelectedZones => 'Ship to specific zones only',
                                \App\Enums\DeliveryScope::Digital => 'No shipping — digital delivery only',
                            };
                        @endphp
                        <label class="scope-card {{ $deliveryScope === $scope->value ? 'is-selected' : '' }}">
                            <input type="radio" wire:model.live="deliveryScope" value="{{ $scope->value }}">
                            <div class="scope-card-body">
                                <div class="scope-card-label">{{ $scope->label() }}</div>
                                <div class="scope-card-sub">{{ $scopeDesc }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>

                @if ($deliveryScope === \App\Enums\DeliveryScope::SelectedZones->value)
                    <label class="field-label" style="margin-bottom:10px;display:block">Select Zones</label>
                    <div class="selection-list selection-grid">
                        @foreach ($shippingZones as $zone)
                            <label class="selection-item">
                                <input type="checkbox" value="{{ $zone->id }}" wire:model.defer="shippingZoneIds">
                                <span>{{ $zone->name }}</span>
                                <small>{{ strtoupper($zone->code) }}</small>
                            </label>
                        @endforeach
                    </div>
                    @error('shippingZoneIds') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror

                @elseif ($deliveryScope === \App\Enums\DeliveryScope::Digital->value)
                    <div class="notice-muted">Digital products skip shipping and zone assignments entirely.</div>
                @else
                    <div class="notice-muted">This product will be available in every enabled shipping zone.</div>
                @endif

            </x-card-collapse>--}}

            {{-- ── Badges ───────────────────────────────────────────────────── --}}
            <x-card-collapse title="Badges" description="Assign badges such as Featured or Recommended — used to highlight this product on the storefront." :start-open="true">
                @if ($badges->isEmpty())
                    <div class="notice-muted">No badges available yet.</div>
                @else
                    <x-select multiple searchable wire:model.defer="badgeIds" placeholder="Search and select badges">
                        @foreach ($badges as $badge)
                            <option value="{{ $badge->id }}">{{ ucfirst(str_replace('-', ' ', $badge->text)) }}</option>
                        @endforeach
                    </x-select>
                    @error('badgeIds') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror
                @endif
            </x-card-collapse>

            {{-- ── Country Targeting ────────────────────────────────────────── --}}
            <x-card-collapse title="Country Targeting" description="Choose which countries this product is aimed at. It stays visible everywhere — targeted countries simply rank it higher on their storefront." :start-open="true">
                @if ($countries->isEmpty())
                    <div class="notice-muted">No countries are enabled for tenants yet.</div>
                @else
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                        <input type="checkbox" wire:model.live="allCountries" style="width:16px;height:16px;">
                        <span style="font-weight:500;">No country preference (rank equally everywhere)</span>
                    </label>
                    @if (!$allCountries)
                        <x-select multiple searchable wire:model.defer="assignedCountryIds" placeholder="Search and select countries">
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->flag_emoji }} {{ $country->name }}</option>
                            @endforeach
                        </x-select>
                        @error('assignedCountryIds') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror
                        <div class="notice-muted" style="margin-top:8px">Visitors from the selected countries see this product first. Visitors elsewhere still see it, ranked below their own country's products.</div>
                    @else
                        <div class="notice-muted">This product ranks equally in every storefront.</div>
                    @endif
                @endif
            </x-card-collapse>

            {{-- ── Tenant Assignments ──────────────────────────────────────── --}}
            <x-card-collapse title="Tenant Assignments" description="Assign this product to specific tenants — they will be notified and the product will appear in their catalog." :start-open="true">
                @if ($tenants->isEmpty())
                    <div class="notice-muted">No tenants found in the system.</div>
                @else
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;cursor:pointer;">
                        <input type="checkbox" wire:model.live="assignToAllTenants" style="width:16px;height:16px;">
                        <span style="font-weight:500;">Assign to all tenants</span>
                    </label>
                    @if (!$assignToAllTenants)
                        <x-select multiple searchable wire:model.defer="assignedTenantIds" placeholder="Search and select tenants">
                            @foreach ($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name ?: $tenant->id }}</option>
                            @endforeach
                        </x-select>
                        @error('assignedTenantIds') <p class="field-error" style="margin-top:8px">{{ $message }}</p> @enderror
                    @endif
                    <div class="notice-muted" style="margin-top:8px">Newly assigned tenants will receive a notification. Removed tenants will lose access to this product.</div>
                @endif
            </x-card-collapse>

        </div>
    </form>
</main>

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            const initGallerySortable = () => {
                const grid = document.getElementById('product-gallery-sortable');
                if (!grid || typeof Sortable === 'undefined' || grid.dataset.sortableInit) {
                    return;
                }

                grid.dataset.sortableInit = '1';

                Sortable.create(grid, {
                    animation: 150,
                    handle: '.gallery-item-handle',
                    draggable: '.gallery-item',
                    onEnd: () => {
                        const orderedIds = Array.from(grid.querySelectorAll('.gallery-item[data-id]'))
                            .map(item => parseInt(item.dataset.id, 10));
                        @this.call('updateGalleryOrder', orderedIds);
                    },
                });
            };

            initGallerySortable();
        });
    </script>
@endpush
