<main id="mn">
    <form wire:submit="save" class="page-stack section-gap">
        <div class="page-head fu d0">
            <div>
                <div class="page-title-row">
                    <h1 class="D page-title">{{ $pageTitle }}</h1><span class="page-badge">Catalog</span>
                </div>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions"><x-btn type="submit">Save Category</x-btn></div>
        </div>

        <x-card-collapse title="Category Details" subtitle="Hierarchy, state, and storefront order."
            class="form-card section-gap" :start-open="true">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Central Category</label>
                    <x-select disabled class="{{ $errors->has('centralCategoryId') ? 'is-invalid' : '' }}">
                        @if ($centralCategory)
                            <option value="{{ $centralCategory['id'] }}" selected>{{ $centralCategory['name'] }}
                                (#{{ $centralCategory['id'] }})</option>
                        @else
                            <option value="" selected>No linked central category</option>
                        @endif
                    </x-select>
                    @error('centralCategoryId')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Parent Category</label><x-select wire:model.defer="parentId"
                        class="{{ $errors->has('parentId') ? 'is-invalid' : '' }}" placeholder="Root">
                        <option value="">Root</option>@foreach($parents as $id => $label)<option value="{{ $id }}">
                            {{ $label }}
                        </option>@endforeach
                    </x-select>@error('parentId')<div class="field-error">{{ $message }}</div>@enderror</div>
                <div><label class="field-label">Order Number</label><x-input type="number"
                        wire:model.defer="orderNumber"
                        class="{{ $errors->has('orderNumber') ? 'is-invalid' : '' }}" />@error('orderNumber')<div
                        class="field-error">{{ $message }}</div>@enderror</div>
                <label class="toggle-field"><input type="checkbox" wire:model.defer="active"><span>Category is
                        active</span></label>
                <label class="toggle-field"><input type="checkbox" wire:model.defer="featured"><span>Category is
                        featured</span></label>
            </div>
        </x-card-collapse>

        {{-- ── Category Image ──────────────────────────────────────────────────── --}}
        <x-card-collapse title="Category Image" subtitle="Upload a thumbnail for this category. Overrides any central category image."
            class="form-card" :start-open="true">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Current Image</label>
                    @php
                        $previewSrc = $thumbUpload ? $thumbUpload->temporaryUrl() : $currentThumbUrl;
                    @endphp
                    @if ($previewSrc)
                        <div style="position:relative;display:inline-block;">
                            <img src="{{ $previewSrc }}" alt="Category thumbnail"
                                style="width:100%;max-width:320px;aspect-ratio:4/3;object-fit:cover;border-radius:16px;border:1px solid rgba(255,255,255,.12);">
                            @if ($currentThumbUrl && !$thumbUpload)
                                <div style="margin-top:8px;">
                                    <label class="toggle-field">
                                        <input type="checkbox" wire:model.defer="removeThumb">
                                        <span>Remove current image</span>
                                    </label>
                                </div>
                            @endif
                        </div>
                    @else
                        <div
                            style="width:100%;max-width:320px;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;border-radius:16px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:rgba(255,255,255,.4);font-size:14px;">
                            No image uploaded
                        </div>
                    @endif
                    @if ($centralCategory && !empty($centralCategory['image_url']) && !$currentThumbUrl && !$thumbUpload)
                        <div class="entity-subtitle" style="margin-top:8px;">Showing central category image as fallback</div>
                    @endif
                </div>
                <div>
                    <label class="field-label">Upload New Image</label>
                    <input type="file" wire:model="thumbUpload" accept="image/*"
                        class="field-control {{ $errors->has('thumbUpload') ? 'is-invalid' : '' }}"
                        style="padding:8px;">
                    @error('thumbUpload')<div class="field-error">{{ $message }}</div>@enderror
                    <div class="entity-subtitle" style="margin-top:8px;">JPEG, PNG, WebP — max 4 MB. Will be resized to 600×600.</div>
                    <div wire:loading wire:target="thumbUpload" style="margin-top:8px;font-size:13px;color:rgba(255,255,255,.5);">Uploading…</div>
                </div>
            </div>
        </x-card-collapse>

        {{-- ── Central Category Info ────────────────────────────────────────────── --}}
        @if ($centralCategory)
        <x-card-collapse title="Linked Central Category" subtitle="Read-only reference from the central catalog."
            class="form-card" :start-open="false">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Central Image</label>
                    @if (!empty($centralCategory['image_url']))
                        <img src="{{ $centralCategory['image_url'] }}" alt="{{ $centralCategory['name'] }}"
                            style="width:100%;max-width:320px;aspect-ratio:4/3;object-fit:cover;border-radius:16px;border:1px solid rgba(255,255,255,.12);">
                    @else
                        <div
                            style="width:100%;max-width:320px;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;border-radius:16px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);">
                            No central image</div>
                    @endif
                </div>
                <div class="page-stack">
                    <div>
                        <div class="entity-title">{{ $centralCategory['name'] }}</div>
                        <div class="entity-subtitle">/{{ $centralCategory['slug'] }} · {{ $centralCategory['status'] }}</div>
                    </div>
                    <div class="form-grid">
                        <div>
                            <label class="field-label">Central Parent</label>
                            <div class="field-control" style="display:flex;align-items:center;">
                                {{ $centralCategory['parent_name'] ?? 'Root' }}
                            </div>
                        </div>
                        <div>
                            <label class="field-label">Featured State</label>
                            <div class="field-control" style="display:flex;align-items:center;">
                                {{ !empty($centralCategory['featured']) ? 'Featured' : 'Standard' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card-collapse>
        @endif

        <x-card-collapse title="Translations" subtitle="Localized storefront content for the selected language."
            class="form-card" :start-open="true">
            <div class="page-actions compact-actions mb-4">
                @foreach ($languages as $language)
                    <x-btn type="button" variant="{{ $activeLocale === $language->code ? 'primary' : 'secondary' }}"
                        class="btn-sm"
                        wire:click="setActiveLocale('{{ $language->code }}')">{{ strtoupper($language->code) }}</x-btn>
                @endforeach
            </div>
            <div class="form-grid" wire:key="translations-panel-{{ $activeLocale }}">
                <div><label class="field-label">Name</label><x-input type="text"
                        wire:key="translations-{{ $activeLocale }}-name"
                        wire:model.defer="translations.{{ $activeLocale }}.name"
                        class="{{ $errors->has('translations.' . $activeLocale . '.name') ? 'is-invalid' : '' }}" />@error('translations.' . $activeLocale . '.name')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Slug</label><x-input type="text"
                        wire:key="translations-{{ $activeLocale }}-slug"
                        wire:model.defer="translations.{{ $activeLocale }}.slug" placeholder="auto-generated-from-name"
                        class="{{ $errors->has('translations.' . $activeLocale . '.slug') ? 'is-invalid' : '' }}" />@error('translations.' . $activeLocale . '.slug')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Meta Keywords</label><x-input type="text"
                        wire:key="translations-{{ $activeLocale }}-meta_keywords"
                        wire:model.defer="translations.{{ $activeLocale }}.meta_keywords"
                        class="{{ $errors->has('translations.' . $activeLocale . '.meta_keywords') ? 'is-invalid' : '' }}" />@error('translations.' . $activeLocale . '.meta_keywords')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Meta Description</label><x-input type="text"
                        wire:key="translations-{{ $activeLocale }}-meta_description"
                        wire:model.defer="translations.{{ $activeLocale }}.meta_description"
                        class="{{ $errors->has('translations.' . $activeLocale . '.meta_description') ? 'is-invalid' : '' }}" />@error('translations.' . $activeLocale . '.meta_description')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Description</label><textarea rows="8"
                        wire:key="translations-{{ $activeLocale }}-description"
                        wire:model.defer="translations.{{ $activeLocale }}.description"
                        class="field-control field-textarea {{ $errors->has('translations.' . $activeLocale . '.description') ? 'is-invalid' : '' }}"></textarea>@error('translations.' . $activeLocale . '.description')
                        <div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </x-card-collapse>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('tenant.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
            <x-btn type="submit">Save Category</x-btn>
        </div>
    </form>
</main>
