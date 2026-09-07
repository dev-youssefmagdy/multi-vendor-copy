<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Blog Categories</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage translated blog category names and publishing status.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.blog.categories') }}" class="btn btn-secondary">Back to Categories</a>
                <x-btn type="submit">Save Category</x-btn>
            </div>
        </div>

        <div class="grid gap-2">
            <x-card-collapse class="span-4" title="Category Settings" subtitle="Publishing status."
                :start-open="true">
                <div class="form-grid">
                    <div>
                        <label class="field-label">Status</label>
                        <x-select wire:model.defer="status">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('status')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </x-card-collapse>

            {{-- ── Translations ──────────────────────────────────────────────────── --}}
            <x-card-collapse title="Translations" subtitle="Localized category name and URL slug per language." class="form-card span-8" :start-open="true">

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
            <a href="{{ route('admin.blog.categories') }}" class="btn btn-secondary">Back to Categories</a>
            <x-btn type="submit">Save Category</x-btn>
        </div>
    </form>
</main>
