<main id="mn">
    <form wire:submit="save" class="page-stack section-gap">
        <div class="page-head fu d0">
            <div>
                <div class="page-title-row">
                    <h1 class="D page-title">{{ $pageTitle }}</h1>
                    <span class="page-badge">Own Catalog</span>
                </div>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('tenant.own-products.index') }}" class="btn btn-secondary">Back</a>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save Product</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        {{-- ── Core Details ──────────────────────────────────────────────── --}}
        <x-card-collapse title="Core Details" :start-open="true">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Slug <span class="field-optional">(auto-generated from
                            name)</span></label>
                    <x-input wire:model.defer="slug" placeholder="leave-blank-to-auto-generate" />
                    @error('slug') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Price <span class="field-required">*</span></label>
                    <x-input type="number" step="0.01" wire:model.defer="price" />
                    @error('price') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Stock</label>
                    <div style="display:flex;align-items:center;gap:10px">
                        <label class="toggle-field" style="width:auto;white-space:nowrap">
                            <input type="checkbox" wire:model.live="stockUnlimited">
                            <span>Unlimited</span>
                        </label>
                        @if (!$stockUnlimited)
                            <x-input type="number" wire:model.defer="stock" min="0" placeholder="0" />
                        @endif
                    </div>
                    @error('stock') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Categories</label>
                    <x-select multiple wire:model.defer="categoryIds">
                        @foreach ($categories as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    @error('categoryIds') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <label class="toggle-field">
                    <input type="checkbox" wire:model.defer="active">
                    <span>Product is active</span>
                </label>
                <label class="toggle-field">
                    <input type="checkbox" wire:model.defer="featured">
                    <span>Product is featured</span>
                </label>
            </div>
        </x-card-collapse>

        {{-- ── Image ─────────────────────────────────────────────────────── --}}
        <x-card-collapse title="Product Image" :start-open="false">
            <div data-dimension-check data-expect-w="{{ config('image_dimensions.product.width') }}" data-expect-h="{{ config('image_dimensions.product.height') }}">
                <label class="field-label">Upload Image <span class="field-optional">(max 5 MB)</span></label>
                <x-dimension-hint :width="config('image_dimensions.product.width')" :height="config('image_dimensions.product.height')" />
                <input type="file" wire:model="imageFile" accept="image/*" class="field-control"
                    style="padding-top:6px">
                @error('imageFile') <p class="field-error">{{ $message }}</p> @enderror
                @if ($imageFile)
                    <div style="margin-top:10px">
                        <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview"
                            style="max-width:200px;border-radius:8px;border:1px solid rgba(255,255,255,.12)">
                    </div>
                @endif
                <x-dimension-feedback :width="config('image_dimensions.product.width')" :height="config('image_dimensions.product.height')" />
            </div>
        </x-card-collapse>

        {{-- ── Translations ──────────────────────────────────────────────── --}}
        <x-card-collapse title="Translations &amp; Content" :start-open="true">
            {{-- Locale tabs --}}
            <div class="locale-tabs" style="margin-bottom:16px">
                @foreach ($languages as $language)
                    <button type="button" wire:click="setActiveLocale('{{ $language->code }}')"
                        class="locale-tab {{ $activeLocale === $language->code ? 'is-active' : '' }}">
                        {{ $language->name }}
                    </button>
                @endforeach
            </div>

            @foreach ($languages as $language)
                <div wire:key="locale-{{ $language->code }}"
                    class="{{ $activeLocale === $language->code ? '' : 'hidden' }}">
                    <div class="form-grid form-grid-1">
                        <div>
                            <label class="field-label">
                                Name
                                @if ($language->is_default) <span class="field-required">*</span> @endif
                            </label>
                            <x-input wire:model.defer="translations.{{ $language->code }}.name"
                                placeholder="Product name in {{ $language->name }}" />
                            @error("translations.{$language->code}.name")
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="field-label">Description</label>
                            <x-textarea wire:model.defer="translations.{{ $language->code }}.description" rows="5"
                                placeholder="Product description in {{ $language->name }}" />
                            @error("translations.{$language->code}.description")
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="field-label">Meta Keywords</label>
                            <x-input wire:model.defer="translations.{{ $language->code }}.meta_keywords"
                                placeholder="Comma-separated keywords" />
                            @error("translations.{$language->code}.meta_keywords")
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </x-card-collapse>

        {{-- ── Variants ──────────────────────────────────────────────────── --}}
        <x-card-collapse title="Product Variants"
            subtitle="Optional: define variants (e.g. Size: Small + Color: Red) with their own price, stock and image. When variants are present, per-variant stock replaces the product-level Stock field above."
            :start-open="true">

            <div class="notice-muted" style="margin-bottom:14px">
                <strong>How it works:</strong> Click <em>Add Variant</em>, choose a variation group (Size, Color, …) and
                an option value. Use <em>+ Add another group</em> within a variant to combine groups (e.g. Size
                <strong>Small</strong> + Color <strong>Red</strong>). Add another variant for each combination you sell.
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
                    <div class="vgroup-card" wire:key="own-variant-{{ $vIdx }}-{{ $variant['id'] ?? 'new' }}">
                        <div class="vgroup-header">
                            <span class="vgroup-title">Variant #{{ $vIdx + 1 }}</span>
                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                wire:click="removeVariant({{ $vIdx }})">Remove</button>
                        </div>

                        <div class="vgroup-body">
                            <div class="form-grid form-grid-2" style="margin-bottom:12px">
                                <div>
                                    <label class="field-label">Variant Image</label>
                                    <x-dimension-hint :width="config('image_dimensions.product.width')" :height="config('image_dimensions.product.height')" />
                                    <div data-dimension-check data-expect-w="{{ config('image_dimensions.product.width') }}" data-expect-h="{{ config('image_dimensions.product.height') }}" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
                                        @if (!empty($variant['image']))
                                            <img src="{{ $variant['image']->temporaryUrl() }}" alt=""
                                                style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #ddd" />
                                        @elseif (!empty($variant['thumbnail_url']))
                                            <img src="{{ $variant['thumbnail_url'] }}" alt=""
                                                style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:1px solid #ddd" />
                                        @endif
                                        <input type="file" wire:model="variants.{{ $vIdx }}.image" accept="image/*" />
                                        @if (!empty($variant['image']) || !empty($variant['thumbnail_url']))
                                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                wire:click="removeVariantImage({{ $vIdx }})">Remove image</button>
                                        @endif
                                        <x-dimension-feedback :width="config('image_dimensions.product.width')" :height="config('image_dimensions.product.height')" />
                                    </div>
                                    @error("variants.{$vIdx}.image") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <label class="field-label">Variant Composition <span class="field-required">*</span></label>
                            <div style="display:flex; flex-direction:column; gap:8px">
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
                                    <div class="form-grid form-grid-2" style="gap:8px; align-items:end"
                                        wire:key="own-variant-{{ $vIdx }}-pair-{{ $pIdx }}">
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
                                                    wire:click="removeOptionPair({{ $vIdx }}, {{ $pIdx }})">×</button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if (count($usedVariationIds) < $variations->count())
                                <div style="margin-top:8px">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        wire:click="addOptionPair({{ $vIdx }})">+ Add another group</button>
                                </div>
                            @endif

                            <div class="form-grid form-grid-3" style="margin-top:14px">
                                <div>
                                    <label class="field-label">Price <span class="field-required">*</span></label>
                                    <x-input type="number" step="0.01" min="0"
                                        wire:model.defer="variants.{{ $vIdx }}.price" />
                                    @error("variants.{$vIdx}.price") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Stock</label>
                                    <x-input type="number" min="0" step="1" wire:model.defer="variants.{{ $vIdx }}.stock" />
                                    @error("variants.{$vIdx}.stock") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Status</label>
                                    <label class="toggle-field">
                                        <input type="checkbox" wire:model.defer="variants.{{ $vIdx }}.active">
                                        <span>Active</span>
                                    </label>
                                </div>
                            </div>

                            @error("variants.{$vIdx}") <p class="field-error">{{ $message }}</p> @enderror
                            @error("variants.{$vIdx}.pairs") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:14px">
                <button type="button" class="btn btn-secondary" wire:click="addVariant">+ Add Variant</button>
            </div>
        </x-card-collapse>

        <div class="notice-muted">
            <strong>Note:</strong> Own products are not shipped from our central inventory. They are managed entirely by
            you and excluded from central fulfillment flows.
        </div>

    </form>
</main>
