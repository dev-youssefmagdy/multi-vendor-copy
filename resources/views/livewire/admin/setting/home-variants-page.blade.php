<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <x-btn type="button" wire:click="openCreate">Add Variant</x-btn>
        </div>
    </div>

    @foreach ($themes as $theme)
        <div class="card table-card-shell fu d1 section-gap">
            <div class="table-header-shell">
                <div>
                    <h3 class="panel-title">{{ ucfirst($theme) }}</h3>
                    <p class="panel-copy">Home page variants available for the {{ ucfirst($theme) }} theme.</p>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Sections</th>
                        <th>Default</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $themeVariants = $variants->where('theme_slug', $theme); @endphp
                    @forelse ($themeVariants as $variant)
                        <tr>
                            <td>{{ $variant['name'] }}</td>
                            <td><code>{{ $variant['key'] }}</code></td>
                            <td>{{ $variant['section_count'] ?? 'Theme default' }}</td>
                            <td>{{ $variant['is_default'] ? 'Yes' : '—' }}</td>
                            <td>{{ $variant['is_active'] ? 'Yes' : 'No' }}</td>
                            <td>
                                <div class="flex gap-2">
                                    <x-btn type="button" variant="secondary" class="btn-sm"
                                        wire:click="openEdit({{ $variant['id'] }})">Edit</x-btn>
                                    @unless ($variant['is_default'])
                                        <x-btn type="button" variant="secondary" class="btn-sm"
                                            wire:click="confirmDelete({{ $variant['id'] }})">Delete</x-btn>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-title">No variants yet</div>
                                    <p class="empty-state-copy">Add a home page variant for this theme.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    @if ($modalOpen)
        <x-modal wire:model="modalOpen"
            title="{{ $editingId ? 'Edit Home Variant' : 'Add Home Variant' }}"
            maxWidth="3xl"
            closeAction="closeModal">
            <form wire:submit="save" class="page-stack">
                <div class="form-grid form-grid-2">
                    <div>
                        <label class="field-label">Base Theme</label>
                        <select wire:model.live="themeSlug" class="field-control" @disabled($editingId)>
                            @foreach ($themes as $theme)
                                <option value="{{ $theme }}">{{ ucfirst($theme) }}</option>
                            @endforeach
                        </select>
                        @error('themeSlug')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Name</label>
                        <x-input type="text" wire:model.live.debounce.400ms="name" :error="$errors->has('name')" />
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Key</label>
                        <x-input type="text" wire:model.defer="key" :error="$errors->has('key')" />
                        @error('key')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">View override (optional)</label>
                        <x-input type="text" wire:model.defer="view" placeholder="themes.{{ $themeSlug ?: 'theme' }}.pages.home" />
                        <p class="panel-copy" style="margin-top:4px;">Leave empty to use the theme's standard home page layout file.</p>
                    </div>
                    <div class="span-2">
                        <label class="field-label">Description</label>
                        <x-input type="text" wire:model.defer="description" :error="$errors->has('description')" />
                    </div>
                    <div class="span-2">
                        <label class="field-label">Preview Image</label>
                        @if ($previewImageUrl)
                            <div style="margin-bottom:8px;">
                                <img src="{{ $previewImageUrl }}" alt="Current preview" style="max-width:200px;border-radius:8px;border:1px solid var(--border2);">
                            </div>
                        @endif
                        <input type="file" wire:model="previewImageFile" accept="image/*" class="field-control">
                        @error('previewImageFile')<div class="field-error">{{ $message }}</div>@enderror
                        @if ($previewImageFile)
                            <div style="margin-top:8px;">
                                <img src="{{ $previewImageFile->temporaryUrl() }}" alt="New preview" style="max-width:200px;border-radius:8px;border:1px solid var(--border2);">
                            </div>
                        @endif
                    </div>
                </div>

                <div class="form-grid form-grid-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model.defer="isDefault" />
                        <span>Default variant for this theme</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model.defer="isActive" />
                        <span>Active (selectable by tenants)</span>
                    </label>
                </div>

                <div>
                    <h4 class="panel-title">Sections</h4>
                    <p class="panel-copy">Choose which sections appear on this variant's home page.</p>
                    <div class="form-grid form-grid-2">
                        @foreach ($sectionLabels as $sectionKey => $label)
                            <label class="flex items-center gap-2">
                                <input type="checkbox"
                                    @checked(in_array($sectionKey, $sections, true))
                                    wire:click="toggleSection('{{ $sectionKey }}')" />
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                    <x-btn type="submit">{{ $editingId ? 'Update Variant' : 'Create Variant' }}</x-btn>
                </div>
            </form>
        </x-modal>
    @endif
</main>
