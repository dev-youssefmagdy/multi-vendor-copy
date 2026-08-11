<main id="mn">
  <form wire:submit="save" class="page-stack">
    <div class="page-head fu d0">
      <div>
        <div class="eyebrow">Central Catalog</div>
        <h1 class="D page-title">{{ $pageTitle }}</h1>
        <p class="page-copy">Manage the catalog scope used to group central categories and tenant assignments.</p>
      </div>
      <div class="page-actions"><a href="{{ route('admin.catalogs.index') }}" class="btn btn-secondary">Back to
          Catalogs</a><x-btn type="submit">Save Catalog</x-btn></div>
    </div>
    <div class="grid gap-2">
      <x-card-collapse title="Configuration" subtitle="Slug, status, and ordering." class="form-card span-4"
        :start-open="true">
        <div class="form-grid">
          <div><label class="field-label">Slug</label><x-input type="text" wire:model.defer="slug" /></div>
          <div><label class="field-label">Status</label><x-select
              wire:model.defer="status">@foreach ($statusOptions as $value => $label)<option value="{{ $value }}">
                {{ $label }}
              </option>@endforeach</x-select></div>
        </div>
      </x-card-collapse>
      <x-card-collapse title="Translations" subtitle="Translate the catalog label shown in central management views."
        class="form-card span-8" :start-open="true">
        <div class="locale-tabs">@foreach ($languages as $language)<button type="button"
          class="locale-tab {{ $activeLocale === $language->code ? 'is-active' : '' }}"
        wire:click="setActiveLocale('{{ $language->code }}')"><span>{{ strtoupper($language->code) }}</span><small>{{ $language->native_name }}</small></button>@endforeach
        </div>@foreach ($languages as $language)<div
          class="locale-panel {{ $activeLocale === $language->code ? 'is-active' : '' }}">
          @if ($activeLocale === $language->code)
            <div class="form-grid">
              <div><label class="field-label">Name</label><x-input type="text"
                  wire:model.defer="translations.{{ $language->code }}.name" /></div>
          </div>@endif
        </div>@endforeach
      </x-card-collapse>

      {{-- ── Category Assignment ──────────────────────────────────────────── --}}
      @if ($rootCategories->isNotEmpty())
        <x-card-collapse title="Category Assignment"
          subtitle="Select root categories to include in this catalog. Sub-categories are auto-assigned."
          class="form-card span-12" :start-open="true">
          <p class="panel-copy" style="margin-bottom:var(--space-3)">Only root-level (top-level) categories are shown.
            When a root category is checked, all its sub-categories are automatically included in this catalog.</p>
          <div class="checkbox-list">
            @foreach ($rootCategories as $rootCategory)
              <label class="toggle-field">
                <input type="checkbox" wire:model.defer="parentCategoryIds" value="{{ $rootCategory->id }}">
                <span>{{ $rootCategory->name ?? $rootCategory->slug }}</span>
              </label>
            @endforeach
          </div>
          @error('parentCategoryIds') <p class="field-error">{{ $message }}</p> @enderror
          @error('parentCategoryIds.*') <p class="field-error">{{ $message }}</p> @enderror
        </x-card-collapse>
      @endif
    </div>
  </form>
</main>
