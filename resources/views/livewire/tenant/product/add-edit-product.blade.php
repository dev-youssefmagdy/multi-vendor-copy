<main id="mn">
    <form wire:submit="save" class="page-stack section-gap">
        <div class="page-head fu d0">
            <div>
                <div class="page-title-row">
                    <h1 class="D page-title">{{ $pageTitle }}</h1><span class="page-badge">Catalog</span>
                </div>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions"><x-btn type="submit">Save Product</x-btn></div>
        </div>

        <x-card-collapse title="Core Details" subtitle="Vendor pricing and assignment details."
            class="form-card section-gap" :start-open="true">
            <div class="form-grid form-grid-1">
                <x-input type="hidden" wire:model.live.debounce.300ms="centralProductId"
                    class="{{ $errors->has('centralProductId') ? 'is-invalid' : '' }}" />
                <div><label class="field-label">Slug</label><x-input type="text" wire:model.defer="slug"
                        class="{{ $errors->has('slug') ? 'is-invalid' : '' }}" />@error('slug')<div class="field-error">
                            {{ $message }}
                        </div>@enderror</div>
                <div><label class="field-label">Vendor Sale Price</label><x-input type="number" step="0.01"
                        wire:model.defer="price"
                        class="{{ $errors->has('price') ? 'is-invalid' : '' }}" />@if($centralProduct)
                            <div class="entity-subtitle mt-2">Central current price:
                        ${{ number_format((float) $centralProduct['current_price'], 2) }}</div>@endif @error('price')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Categories</label><x-select multiple searchable tree
                        wire:model.defer="categoryIds" class="{{ $errors->has('categoryIds') ? 'is-invalid' : '' }}"
                        placeholder="Search and select categories">@foreach($categoryTree as $row)<option
                            value="{{ $row['id'] }}" data-depth="{{ $row['depth'] }}">
                            {{ str_repeat('— ', $row['depth']) }}{{ $row['name'] }}
                        </option>@endforeach</x-select>@error('categoryIds')<div class="field-error">{{ $message }}
                        </div>@enderror
                </div>
                <div><label class="field-label">Badges</label><x-select multiple searchable
                        wire:model.defer="badgeIds" class="{{ $errors->has('badgeIds') ? 'is-invalid' : '' }}"
                        placeholder="Search and select badges">@foreach($badges as $badge)<option
                            value="{{ $badge->id }}">{{ ucfirst(str_replace('-', ' ', $badge->text)) }}
                        </option>@endforeach</x-select>@error('badgeIds')<div class="field-error">{{ $message }}
                        </div>@enderror
                </div>
                <label class="toggle-field"><input type="checkbox" wire:model.defer="active"><span>Product is
                        active</span></label>
                <label class="toggle-field"><input type="checkbox" wire:model.defer="featured"><span>Product is
                        featured</span></label>
            </div>
        </x-card-collapse>

        @if ($centralProduct)
            <x-card-collapse title="Central Product Snapshot"
                subtitle="Read-only product details synced from the central catalog." class="form-card section-gap"
                :start-open="true">
                <div class="form-grid form-grid-2">
                    <div>
                        @if (!empty($centralProduct['image_url']))
                            <img src="{{ $centralProduct['image_url'] }}" alt="{{ $centralProduct['name'] }}"
                                style="width:100%;max-width:320px;aspect-ratio:4/3;object-fit:cover;border-radius:16px;border:1px solid rgba(255,255,255,.12);">
                        @else
                            <div
                                style="width:100%;max-width:320px;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;border-radius:16px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);">
                                No image</div>
                        @endif
                    </div>
                    <div class="page-stack">
                        <div>
                            <div class="entity-title">{{ $centralProduct['name'] }}</div>
                            <div class="entity-subtitle">SKU {{ $centralProduct['sku'] }} · {{ $centralProduct['status'] }}
                            </div>
                        </div>
                        <div class="form-grid form-grid-2">
                            <div><label class="field-label">Base Price</label>
                                <div class="field-control" style="display:flex;align-items:center;">
                                    ${{ number_format((float) $centralProduct['base_price'], 2) }}</div>
                            </div>
                            <div><label class="field-label">Current Central Price</label>
                                <div class="field-control" style="display:flex;align-items:center;">
                                    ${{ number_format((float) $centralProduct['current_price'], 2) }}</div>
                            </div>
                            <div><label class="field-label">Factory</label>
                                <div class="field-control" style="display:flex;align-items:center;">
                                    {{ $centralProduct['factory'] ?: '—' }}
                                </div>
                            </div>
                            <div><label class="field-label">Stock</label>
                                <div class="field-control" style="display:flex;align-items:center;">
                                    {{ number_format((int) $centralProduct['stock']) }}
                                </div>
                            </div>
                            <div><label class="field-label">Delivery Scope</label>
                                <div class="field-control" style="display:flex;align-items:center;">
                                    {{ $centralProduct['delivery_scope'] ?: '—' }}
                                </div>
                            </div>
                            <div><label class="field-label">Categories</label>
                                <div class="field-control" style="display:flex;align-items:center;">
                                    {{ implode(', ', $centralProduct['categories'] ?: ['Unassigned']) }}
                                </div>
                            </div>
                        </div>
                        @if (!empty($centralProduct['summary']))
                            <div><label class="field-label">Summary</label>
                                <div class="field-control"
                                    style="min-height:88px;white-space:normal;align-items:flex-start;padding-top:12px;">
                                    {{ $centralProduct['summary'] }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </x-card-collapse>

            <x-card-collapse title="Synced Variations"
                subtitle="Central variant price and image are read-only. Only vendor sell price is editable."
                class="form-card section-gap" :start-open="true">
                @if ($variants)
                    <div class="table-scroll-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Variant</th>
                                    <th>Main Price</th>
                                    <th>Weight</th>
                                    <th>Vendor Sell Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($variants as $index => $variant)
                                    <tr>
                                        <td>
                                            <input type="hidden" wire:model.defer="variants.{{ $index }}.id">
                                            <input type="hidden"
                                                wire:model.defer="variants.{{ $index }}.central_product_variant_id">
                                            <input type="hidden" wire:model.defer="variants.{{ $index }}.real_price">
                                            <input type="hidden" wire:model.defer="variants.{{ $index }}.thumbnail_path">
                                            <input type="hidden" wire:model.defer="variants.{{ $index }}.active">
                                            <div style="display:flex;align-items:center;gap:12px;">
                                                @if (!empty($variant['image_url']))
                                                    <img src="{{ $variant['image_url'] }}" alt="{{ $variant['title'] }}"
                                                        style="width:44px;height:44px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,.12);">
                                                @else
                                                    <div
                                                        style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);">
                                                        —</div>
                                                @endif
                                                <div>
                                                    <div class="entity-title">{{ $variant['title'] }}</div>
                                                    <div class="entity-subtitle">
                                                        {{ $variant['sku'] ?: ($variant['options_label'] ?: 'Central variant') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="entity-title">${{ number_format((float) $variant['real_price'], 2) }}</div>
                                            <div class="entity-subtitle">Central price</div>
                                        </td>
                                        <td>
                                            @if (!empty($variant['weight_grams']))
                                                <div class="entity-title">{{ $variant['weight_grams'] }} g</div>
                                            @else
                                                <div class="entity-subtitle">—</div>
                                            @endif
                                        </td>
                                        <td>
                                            <x-input type="number" step="0.01" wire:model.defer="variants.{{ $index }}.sell_price"
                                                class="{{ $errors->has('variants.' . $index . '.sell_price') ? 'is-invalid' : '' }}" />
                                            @error('variants.' . $index . '.sell_price')<div class="field-error">{{ $message }}
                                            </div>@enderror
                                        </td>
                                        <td>{{ $variant['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <h3 class="panel-title">No synced variants</h3>
                        <p class="panel-copy">This central product does not currently expose any variants.</p>
                    </div>
                @endif
            </x-card-collapse>
        @endif

        <x-card-collapse title="Translations" subtitle="Storefront copy for each active tenant language."
            class="form-card" :start-open="true">
            <div class="page-actions compact-actions mb-4">
                @foreach ($languages as $language)
                    <x-btn type="button" variant="{{ $activeLocale === $language->code ? 'primary' : 'secondary' }}"
                        class="btn-sm"
                        wire:click="setActiveLocale('{{ $language->code }}')">{{ strtoupper($language->code) }}</x-btn>
                @endforeach
            </div>
            <div class="form-grid">
                <div><label class="field-label">Name</label><x-input type="text"
                        wire:model.defer="translations.{{ $activeLocale }}.name"
                        class="{{ $errors->has('translations.' . $activeLocale . '.name') ? 'is-invalid' : '' }}" />@error('translations.' . $activeLocale . '.name')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Meta Keywords</label><x-input type="text"
                        wire:model.defer="translations.{{ $activeLocale }}.meta_keywords"
                        class="{{ $errors->has('translations.' . $activeLocale . '.meta_keywords') ? 'is-invalid' : '' }}" />@error('translations.' . $activeLocale . '.meta_keywords')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Meta Description</label><textarea rows="3"
                        wire:model.defer="translations.{{ $activeLocale }}.meta_description"
                        class="field-control field-textarea {{ $errors->has('translations.' . $activeLocale . '.meta_description') ? 'is-invalid' : '' }}"
                        placeholder="SEO-friendly page description (≤ 160 chars)"></textarea>@error('translations.' . $activeLocale . '.meta_description')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Description</label>
                    <x-editor model="translations.{{ $activeLocale }}.description" :value="data_get($translations, $activeLocale . '.description', '')" :height="320" />
                    @error('translations.' . $activeLocale . '.description')
                    <div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </x-card-collapse>


        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to Products</a>
            <x-btn type="submit">Save Product</x-btn>
        </div>
    </form>
</main>
