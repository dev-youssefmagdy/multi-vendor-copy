<main id="mn">
  <form wire:submit="save" class="page-stack">
    <div class="page-head fu d0">
      <div>
        <div class="eyebrow">Central Shipping</div>
        <h1 class="D page-title">{{ $pageTitle }}</h1>
        <p class="page-copy">Manage localized zone labels, currency, and weight-based price rules.</p>
      </div>
      <div class="page-actions" style="flex-shrink:0">
        <a href="{{ route('admin.shipping.delivery-charges') }}"  class="btn btn-secondary">Back to Zones</a>
        <x-btn type="submit">Save Zone</x-btn>
      </div>
    </div>

    @if (session('status'))
      <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="card section-gap notice-error">
        <h3 class="panel-title">Please fix the highlighted fields</h3>
        <p class="panel-copy">The zone was not saved because some required values are missing or invalid.</p>
      </div>
    @endif

    <div class="grid gap-2">

      {{-- ── Configuration ────────────────────────────────────────────────── --}}
      <x-card-collapse class="form-card span-4" title="Configuration" subtitle="Zone code, currency, and status." :start-open="true">
        <div class="form-grid">
          <div>
            <label class="field-label">Code</label>
            <x-input type="text" wire:model.defer="code" placeholder="e.g. local, international" />
            @error('code') <p class="field-error">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="field-label">Currency Code *</label>
            <x-input type="text" wire:model.defer="currencyCode" placeholder="USD" maxlength="3" style="text-transform:uppercase" />
            <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">3-letter ISO 4217 code (e.g. USD, EUR, EGP)</p>
            @error('currencyCode') <p class="field-error">{{ $message }}</p> @enderror
          </div>

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
      <x-card-collapse class="form-card span-8" title="Translations" subtitle="Translate the user-facing zone name and description." :start-open="true">
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
                    placeholder="Zone display name" />
                  @error("translations.{$language->code}.name") <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                  <label class="field-label">Description</label>
                  <textarea rows="5" class="field-control field-textarea"
                    wire:model.defer="translations.{{ $language->code }}.description"
                    placeholder="Optional zone description"></textarea>
                  @error("translations.{$language->code}.description") <p class="field-error">{{ $message }}</p> @enderror
                </div>
              </div>
            @endif
          </div>
        @endforeach
      </x-card-collapse>

      {{-- ── Rates ────────────────────────────────────────────────────────── --}}
      <x-card-collapse class="form-card span-12" title="Rates" subtitle="Define one or more weight-band price rules for this zone." :start-open="true">
        <div class="panel-head" style="margin-bottom:16px">
          <div></div>
          <x-btn type="button" variant="secondary" class="btn-sm" wire:click="addRate">Add Rate</x-btn>
        </div>

        <div class="vgroup-list">
          @foreach ($rates as $index => $rate)
            <div class="vgroup-card" wire:key="rate-{{ $index }}">
              <div class="vgroup-header">
                <span class="vgroup-title">Rate {{ $loop->iteration }}</span>
                <button type="button" class="btn btn-secondary btn-sm btn-danger" wire:click="removeRate({{ $index }})">
                  <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                  Remove
                </button>
              </div>

              <div class="vgroup-body">
                <div class="form-grid form-grid-2" style="margin-bottom:10px">
                  <div class="span-2">
                    <label class="field-label">Name *</label>
                    <x-input type="text" wire:model.defer="rates.{{ $index }}.name" placeholder="e.g. Standard, Express" />
                    @error("rates.{$index}.name") <p class="field-error">{{ $message }}</p> @enderror
                  </div>
                </div>

                <div class="form-grid form-grid-3">
                  <div>
                    <label class="field-label">Min Weight (kg)</label>
                    <x-input type="number" step="0.01" min="0" wire:model.defer="rates.{{ $index }}.min_weight" placeholder="0.00" />
                    @error("rates.{$index}.min_weight") <p class="field-error">{{ $message }}</p> @enderror
                  </div>

                  <div>
                    <label class="field-label">Max Weight (kg)</label>
                    <x-input type="number" step="0.01" min="0" wire:model.defer="rates.{{ $index }}.max_weight" placeholder="∞" />
                    @error("rates.{$index}.max_weight") <p class="field-error">{{ $message }}</p> @enderror
                  </div>

                  <div>
                    <label class="field-label">Price *</label>
                    <x-input type="number" step="0.01" min="0" wire:model.defer="rates.{{ $index }}.price" placeholder="0.00" />
                    @error("rates.{$index}.price") <p class="field-error">{{ $message }}</p> @enderror
                  </div>
                </div>

                <div class="field-flag-grid" style="margin-top:10px">
                  <label class="toggle-field">
                    <input type="checkbox" wire:model.defer="rates.{{ $index }}.is_active">
                    <span>Rate Active</span>
                  </label>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        @if ($errors->has('rates'))
          <p class="field-error" style="margin-top:8px">{{ $errors->first('rates') }}</p>
        @endif
      </x-card-collapse>

    </div>
  </form>
</main>
