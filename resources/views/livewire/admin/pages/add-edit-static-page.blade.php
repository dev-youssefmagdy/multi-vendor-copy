<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Static Pages</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage public-facing static content such as about, privacy, and support pages.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">Back to Pages</a>
                <x-btn type="submit">Save Page</x-btn>
            </div>
        </div>

        <div class="grid gap-2">
            <x-card-collapse class="span-4" title="Page Settings" subtitle="Publishing status and compliance flag."
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
                    <div>
                        <label class="field-label">Compliance Page</label>
                        <label class="flex items-center gap-2 cursor-pointer mt-1">
                            <input type="checkbox" wire:model.defer="isCompliance" class="form-checkbox">
                            <span class="text-sm">Require vendor acceptance in onboarding</span>
                        </label>
                        <p class="field-hint mt-1">Compliance pages (e.g. Terms of Service, Privacy Policy) will be shown to new vendors in the onboarding tour and must be accepted before proceeding.</p>
                        @error('isCompliance')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </x-card-collapse>

            {{-- ── Translations ──────────────────────────────────────────────────── --}}
            <x-card-collapse title="Translations" subtitle="Localized title, URL slug, and full page body per language." class="form-card span-8" :start-open="true">

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
                            <div class="form-grid form-grid-2">
                                <div class="span-2">
                                    <label class="field-label">Title ({{ strtoupper($language->code) }}) @if($language->is_default)*@endif</label>
                                    <x-input type="text"
                                        :class="$errors->has('translations.' . $language->code . '.title') ? 'is-invalid' : ''"
                                        wire:model.defer="translations.{{ $language->code }}.title"
                                        placeholder="Page title" />
                                    @error("translations.{$language->code}.title") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Slug ({{ strtoupper($language->code) }})</label>
                                    <x-input type="text"
                                        :class="$errors->has('translations.' . $language->code . '.slug') ? 'is-invalid' : ''"
                                        wire:model.defer="translations.{{ $language->code }}.slug"
                                        placeholder="auto-generated-from-title" />
                                    @error("translations.{$language->code}.slug") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Content ({{ strtoupper($language->code) }})</label>
                                    <x-editor
                                        model="translations.{{ $language->code }}.content"
                                        :value="$translations[$language->code]['content'] ?? ''"
                                        :height="500" />
                                    @error("translations.{$language->code}.content") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="notice-muted">Create at least one active language before managing translated page content.</div>
                @endif

            </x-card-collapse>
        </div>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary">Back to Pages</a>
            <x-btn type="submit">Save Page</x-btn>
        </div>
    </form>
</main>
