<main id="mn">
  <form wire:submit="save" class="page-stack">
    <div class="page-head fu d0">
      <div>
        <div class="eyebrow">Central Catalog</div>
        <h1 class="D page-title">{{ $pageTitle }}</h1>
        <p class="page-copy">Manage translated variation metadata and the option rows attached to it.</p>
      </div>
      <div class="page-actions" style="flex-shrink:0">
        <a href="{{ route('admin.variations.index') }}"  class="btn btn-secondary">Back to Variations</a>
        <x-btn type="submit">Save Variation</x-btn>
      </div>
    </div>

    @if (session('status'))
      <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="card section-gap notice-error">
        <h3 class="panel-title">Please fix the highlighted fields</h3>
        <p class="panel-copy">The variation was not saved because some required values are missing or invalid.</p>
      </div>
    @endif

    <div class="grid gap-2">

      {{-- ── Configuration ────────────────────────────────────────────────── --}}
      <x-card-collapse class="form-card span-4" title="Configuration" subtitle="State and ordering." :start-open="true">
        <div class="form-grid">
          <div>
            <label class="field-label">Status</label>
            <x-select wire:model.defer="status">
              @foreach ($statusOptions as $statusOption)
                <option value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>
              @endforeach
            </x-select>
            @error('status') <p class="field-error">{{ $message }}</p> @enderror
          </div>
        </div>
      </x-card-collapse>

      {{-- ── Translations ─────────────────────────────────────────────────── --}}
      <x-card-collapse class="form-card span-8" title="Translations" subtitle="Translate the variation name and description." :start-open="true">
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
              <div class="form-grid">
                <div>
                  <label class="field-label">Name @if($language->is_default)*@endif</label>
                  <x-input type="text"
                    :class="$errors->has('translations.' . $language->code . '.name') ? 'is-invalid' : ''"
                    wire:model.defer="translations.{{ $language->code }}.name"
                    placeholder="e.g. Size, Color" />
                  @error("translations.{$language->code}.name") <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                  <label class="field-label">Description</label>
                  <textarea rows="5" class="field-control field-textarea"
                    wire:model.defer="translations.{{ $language->code }}.description"
                    placeholder="Optional description"></textarea>
                  @error("translations.{$language->code}.description") <p class="field-error">{{ $message }}</p> @enderror
                </div>
              </div>
            @endif
          </div>
        @endforeach
      </x-card-collapse>

      {{-- ── Options ──────────────────────────────────────────────────────── --}}
      <x-card-collapse class="form-card span-12" title="Options" subtitle="Each option has a translated name and optional swatch color." :start-open="true">
        <div class="panel-head" style="margin-bottom:16px">
          <div></div>
          <x-btn type="button" variant="secondary" class="btn-sm" wire:click="addOption">Add Option</x-btn>
        </div>

        <div class="vgroup-list">
          @foreach ($options as $index => $option)
            <div class="vgroup-card" wire:key="option-{{ $index }}">
              <div class="vgroup-header">
                <span class="vgroup-title">Option {{ $loop->iteration }}</span>
                <button type="button" class="btn btn-secondary btn-sm btn-danger" wire:click="removeOption({{ $index }})">
                  <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Remove
                </button>
              </div>

              <div class="vgroup-body">
                <div class="form-grid form-grid-2" style="margin-bottom:12px">
                  <div>
                    <label class="field-label">Swatch</label>
                    <x-input type="text" wire:model.defer="options.{{ $index }}.swatch" placeholder="#000000 or color name" />
                    @error("options.{$index}.swatch") <p class="field-error">{{ $message }}</p> @enderror
                  </div>
                </div>

                <div class="form-grid form-grid-2">
                  @foreach ($languages as $language)
                    <div wire:key="option-{{ $index }}-lang-{{ $language->code }}">
                      <label class="field-label">{{ strtoupper($language->code) }} Name @if($language->is_default)*@endif</label>
                      <x-input type="text"
                        wire:model.defer="options.{{ $index }}.translations.{{ $language->code }}.name"
                        placeholder="Option label in {{ $language->native_name }}" />
                      @error("options.{$index}.translations.{$language->code}.name") <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          @endforeach
        </div>

        @if ($errors->has('options'))
          <p class="field-error" style="margin-top:8px">{{ $errors->first('options') }}</p>
        @endif
      </x-card-collapse>

    </div>

    {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
    <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
      <a href="{{ route('admin.variations.index') }}" class="btn btn-secondary">Back to Variations</a>
      <x-btn type="submit">Save Variation</x-btn>
    </div>
  </form>
</main>
