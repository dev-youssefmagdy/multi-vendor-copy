<main id="mn">
    <form wire:submit="save" class="page-stack section-gap">
        <div class="page-head fu d0">
            <div>
                <div class="page-title-row">
                    <h1 class="D page-title">{{ $pageTitle }}</h1><span class="page-badge">Storefront</span>
                </div>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('tenant.store.pages') }}" class="btn btn-secondary">Back to Pages</a>
                <x-btn type="submit">Save Page</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        <x-card-collapse title="Page Settings" subtitle="Slug, visibility, and title translation."
            class="form-card section-gap" :start-open="true">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Slug</label>
                    <x-input type="text" wire:model.defer="slug"
                        class="{{ $errors->has('slug') ? 'is-invalid' : '' }}" />
                    @error('slug')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <label class="toggle-field"><input type="checkbox" wire:model.defer="active"><span>Page is
                            active</span></label>
                    @error('active')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </x-card-collapse>

        <x-card-collapse title="Translations"
            subtitle="Switch locale tabs to manage translated page titles and content." class="form-card section-gap"
            :start-open="true">
            @if ($languages->count())
                <div wire:ignore.self x-data="{ activeLocale: '{{ $activeLocale }}' }">
                    <div class="page-actions compact-actions mb-4">
                        @foreach ($languages as $language)
                            <x-btn type="button" variant="{{ $activeLocale === $language->code ? 'primary' : 'secondary' }}"
                                class="btn-sm" x-on:click="activeLocale = '{{ $language->code }}'"
                                x-bind:class="{ 'btn-primary': activeLocale === '{{ $language->code }}', 'btn-secondary': activeLocale !== '{{ $language->code }}' }">{{ strtoupper($language->code) }}</x-btn>
                        @endforeach
                    </div>

                    @foreach ($languages as $language)
                        <div x-show="activeLocale === '{{ $language->code }}'" x-cloak class="form-grid">
                            <div>
                                <label class="field-label">Title</label>
                                <x-input type="text" wire:model.defer="translations.{{ $language->code }}.title"
                                    class="{{ $errors->has('translations.' . $language->code . '.title') ? 'is-invalid' : '' }}" />
                                @error('translations.' . $language->code . '.title')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div wire:key="page-editor-{{ $language->code }}">
                                <label class="field-label">Content</label>
                                <x-editor model="translations.{{ $language->code }}.body"
                                    :value="$translations[$language->code]['body'] ?? ''" :height="520" />
                                @error('translations.' . $language->code . '.body')
                                    <div class="field-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-title">No active languages</div>
                    <p class="empty-state-copy">Enable at least one tenant language before creating translated storefront
                        pages.</p>
                </div>
            @endif
        </x-card-collapse>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('tenant.store.pages') }}" class="btn btn-secondary">Back to Pages</a>
            <x-btn type="submit">Save Page</x-btn>
        </div>
    </form>
</main>