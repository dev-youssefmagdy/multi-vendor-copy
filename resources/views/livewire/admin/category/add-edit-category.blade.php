<main id="mn">
  <form wire:submit="save" class="page-stack">
    <div class="page-head fu d0">
      <div>
        <div class="eyebrow">Central Catalog</div>
        <h1 class="D page-title">{{ $pageTitle }}</h1>
        <p class="page-copy">Manage localized category labels, SEO meta, thumbnail image, and parent hierarchy.</p>
      </div>
      <div class="page-actions" style="flex-shrink:0">
        <a href="{{ route('admin.categories.index') }}"  class="btn btn-secondary">Back to Categories</a>
        <x-btn type="submit">Save Category</x-btn>
      </div>
    </div>

    @if (session('status'))
      <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="card section-gap notice-error">
        <h3 class="panel-title">Please fix the highlighted fields</h3>
        <p class="panel-copy">The category was not saved because some required values are missing or invalid.</p>
      </div>
    @endif

    <div class="grid gap-2">

      {{-- ── Configuration ────────────────────────────────────────────────── --}}
      <x-card-collapse title="Configuration" subtitle="Catalog scope, hierarchy, status, and placement." class="form-card span-4" :start-open="true">
        <div class="form-grid">
          <div>
            <label class="field-label">Catalogs *</label>
            <div class="checkbox-list">
              @foreach ($catalogs as $catalog)
                <label class="toggle-field">
                  <input type="checkbox" wire:model.live="catalogIds" value="{{ $catalog->id }}">
                  <span>{{ $catalog->name ?? $catalog->slug }}</span>
                </label>
              @endforeach
            </div>
            @error('catalogIds') <p class="field-error">{{ $message }}</p> @enderror
            @error('catalogIds.*') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="field-label">Parent Category</label>

            @foreach ($categoryLevels as $level)
              <div style="{{ $loop->first ? '' : 'margin-top:10px' }}" wire:key="parent-level-{{ $level['index'] }}">
                <label class="field-label" style="{{ $loop->first ? 'display:none' : 'font-size:11px;color:var(--t3);margin-bottom:4px' }}">{{ $level['label'] }}</label>
                <x-select searchable wire:model.live="parentPath.{{ $level['index'] }}" placeholder="{{ $loop->first ? 'Root category (no parent)' : 'Select sub-category…' }}">
                  <option value="">{{ $loop->first ? '— Root category —' : '— None —' }}</option>
                  @foreach ($level['categories'] as $cat)
                    <option value="{{ $cat->id }}" @selected((int) ($level['selected'] ?? 0) === $cat->id)>
                      {{ $cat->translationValue('name') ?? $cat->slug }}
                    </option>
                  @endforeach
                </x-select>
              </div>
            @endforeach

            @error('parentPath') <p class="field-error">{{ $message }}</p> @enderror
            @error('parentPath.*') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="field-label">Status</label>
            <x-select wire:model.defer="status">
              @foreach ($statusOptions as $statusOption)
                <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
              @endforeach
            </x-select>
            @error('status') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <label class="toggle-field">
            <input type="checkbox" wire:model.defer="isFeatured">
            <span>Featured Category</span>
          </label>
        </div>
      </x-card-collapse>

      {{-- ── Thumbnail Image ──────────────────────────────────────────────── --}}
      <x-card-collapse title="Thumbnail Image" subtitle="Category image shown in listings, nav menus, and hero blocks. Stored at 600px max." class="form-card span-4" :start-open="true">
        <div class="media-card">
          @if ($thumbImage)
            <div class="media-preview-wrap">
              <img src="{{ $thumbImage->temporaryUrl() }}" alt="Preview" class="media-preview-image">
            </div>
          @elseif ($existingThumb && ! $removeThumbImage)
            <div class="media-preview-wrap">
              <img src="{{ $existingThumb }}" alt="Current thumbnail" class="media-preview-image">
            </div>
          @endif

          <label class="field-label">Thumbnail</label>
          <x-dropzone id="thumb-image" model="thumbImage" remove-action="removeThumb" accept="image/*" :multiple="false" label="Upload thumbnail" sublabel="PNG, JPG, WEBP up to 4MB — resized to 600px" />
          @error('thumbImage') <p class="field-error">{{ $message }}</p> @enderror
        </div>
      </x-card-collapse>

      {{-- ── Translations ─────────────────────────────────────────────────── --}}
      <x-card-collapse title="Translations" subtitle="Localized name, description, and SEO meta fields per language." class="form-card span-8" :start-open="true">

        @if ($languages->count())
          <div class="locale-tabs">
            @foreach ($languages as $language)
              <button type="button"
                class="locale-tab {{ $activeLocale === $language->code ? 'is-active' : '' }}"
                wire:click="setActiveLocale('{{ $language->code }}')">
                <span>{{ strtoupper($language->code) }}</span>
                <small>{{ $language->native_name }}</small>
              </button>
            @endforeach
          </div>

          @foreach ($languages as $language)
            <div class="locale-panel {{ $activeLocale === $language->code ? 'is-active' : '' }}">
              @if ($activeLocale === $language->code)
                <div class="form-grid form-grid-2">
                  <div class="span-2">
                    <label class="field-label">Name ({{ strtoupper($language->code) }}) @if($language->is_default)*@endif</label>
                    <x-input type="text"
                      :class="$errors->has('translations.' . $language->code . '.name') ? 'is-invalid' : ''"
                      wire:model.defer="translations.{{ $language->code }}.name"
                      placeholder="Localized category name" />
                    @error("translations.{$language->code}.name") <p class="field-error">{{ $message }}</p> @enderror
                  </div>

                  <div class="span-2">
                    <label class="field-label">Slug ({{ strtoupper($language->code) }})</label>
                    <x-input type="text"
                      :class="$errors->has('translations.' . $language->code . '.slug') ? 'is-invalid' : ''"
                      wire:model.defer="translations.{{ $language->code }}.slug"
                      placeholder="auto-generated-from-name" />
                    @error("translations.{$language->code}.slug") <p class="field-error">{{ $message }}</p> @enderror
                  </div>

                  <div class="span-2">
                    <label class="field-label">Description</label>
                    <textarea rows="4"
                      class="field-control field-textarea {{ $errors->has('translations.' . $language->code . '.description') ? 'is-invalid' : '' }}"
                      wire:model.defer="translations.{{ $language->code }}.description"
                      placeholder="Optional category description"></textarea>
                    @error("translations.{$language->code}.description") <p class="field-error">{{ $message }}</p> @enderror
                  </div>

                  <div>
                    <label class="field-label">Meta Keywords</label>
                    <x-input type="text"
                      :class="$errors->has('translations.' . $language->code . '.meta_keywords') ? 'is-invalid' : ''"
                      wire:model.defer="translations.{{ $language->code }}.meta_keywords"
                      placeholder="keyword1, keyword2, keyword3" />
                    @error("translations.{$language->code}.meta_keywords") <p class="field-error">{{ $message }}</p> @enderror
                  </div>

                  <div>
                    <label class="field-label">Meta Description</label>
                    <x-input type="text"
                      :class="$errors->has('translations.' . $language->code . '.meta_description') ? 'is-invalid' : ''"
                      wire:model.defer="translations.{{ $language->code }}.meta_description"
                      placeholder="SEO-friendly description (≤ 160 chars)" />
                    @error("translations.{$language->code}.meta_description") <p class="field-error">{{ $message }}</p> @enderror
                  </div>
                </div>
              @endif
            </div>
          @endforeach
        @else
          <div class="notice-muted">Create at least one active language before managing translated category content.</div>
        @endif

      </x-card-collapse>

    </div>

    {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
    <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
      <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Back to Categories</a>
      <x-btn type="submit">Save Category</x-btn>
    </div>
  </form>
</main>
