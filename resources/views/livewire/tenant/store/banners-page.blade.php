<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">
                @if ($country)
                    Banners shown to visitors from {{ $country->flag_emoji }} {{ $country->name }}.
                @else
                    Default banners, shown when no country-specific banners exist.
                @endif
            </p>
        </div>
        <a href="{{ route('tenant.store.banners.index') }}" class="btn btn-secondary">Back to countries</a>
    </div>

    <div class="appearance-tab-panel fu d1">
        <div class="card table-card-shell">
            <div class="table-header-shell">
                <div>
                    <h3 class="panel-title">Banners</h3>
                    <p class="panel-copy">Homepage and promotional banners with multi-language content.</p>
                </div>
                <div class="table-header-actions">
                    <x-btn type="button" wire:click="openBannerModal()">Add Banner</x-btn>
                </div>
            </div>

            <div class="tw">
                <table class="tb" wire:ignore.self>
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>Title</th>
                            <th>URL</th>
                            <th>Image</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tenant-banners-sortable">
                        @forelse ($banners as $banner)
                            <tr data-id="{{ $banner->id }}">
                                <td class="drag-handle" style="cursor:grab">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="8" x2="20" y2="8" />
                                        <line x1="4" y1="16" x2="20" y2="16" />
                                    </svg>
                                </td>
                                <td>{{ e($banner->title ?? '—') }}</td>
                                <td>
                                    @if ($banner->url)
                                        <a href="{{ e($banner->url) }}" target="_blank" rel="noopener"
                                            class="panel-copy" style="text-decoration:underline;word-break:break-all;">
                                            {{ e(\Illuminate\Support\Str::limit($banner->url, 40)) }}
                                        </a>
                                    @else
                                        <span class="panel-copy">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($banner->image_path)
                                        <img src="{{ $banner->image_path }}"
                                            alt="" style="height:36px;border-radius:6px;object-fit:cover;" />
                                    @else
                                        <span class="panel-copy">—</span>
                                    @endif
                                </td>
                                <td>{{ e($banner->serial_number) }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        <x-btn type="button" variant="secondary" class="btn-sm"
                                            wire:click="openBannerModal({{ $banner->id }})">Edit</x-btn>
                                        <x-btn type="button" variant="secondary" class="btn-sm"
                                            wire:click="confirmDeleteBanner({{ $banner->id }})">Delete</x-btn>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-state-title">No banners yet</div>
                                        <p class="empty-state-copy">Add your first banner to display on the storefront.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @script
                <script>
                    (function () {
                        function initBannerSort() {
                            const list = document.getElementById('tenant-banners-sortable');
                            if (!list || typeof Sortable === 'undefined') return;
                            if (list._sortable) { list._sortable.destroy(); }
                            list._sortable = Sortable.create(list, {
                                animation: 150,
                                handle: '.drag-handle',
                                onEnd: () => {
                                    const orderedIds = Array.from(
                                        list.querySelectorAll('tr[data-id]')
                                    ).map(row => parseInt(row.dataset.id, 10));
                                    $wire.updateBannerOrder(orderedIds);
                                },
                            });
                        }
                        initBannerSort();
                    })();
                </script>
            @endscript
        </div>
    </div>

    {{-- Banner modal --}}
    <x-modal wire:model="bannerModalOpen"
        title="{{ $bannerId ? 'Edit Banner' : 'Add Banner' }}"
        maxWidth="2xl"
        closeAction="closeBannerModal">
        <form wire:submit="saveBanner" class="page-stack">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">URL</label>
                    <x-input type="url" wire:model.defer="bannerUrl" :error="$errors->has('bannerUrl')" />
                    @error('bannerUrl')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Sort Order</label>
                    <x-input type="number" wire:model.defer="bannerSerial" :error="$errors->has('bannerSerial')" />
                    @error('bannerSerial')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="span-2">
                    <label class="field-label">Image</label>
                    <p class="panel-copy" style="margin-bottom:6px;">
                        This banner is displayed on the <strong>{{ $activeThemeLabel }}</strong> theme.
                    </p>
                    <x-dropzone id="banner-image" model="bannerImage" :multiple="false" accept="image/*"
                        label="Upload banner image"
                        sublabel="PNG, JPG, WEBP up to 2MB"
                        :expected-width="$bannerWidth"
                        :expected-height="$bannerHeight"
                        :dimension-label="$activeThemeLabel" />
                    <p class="panel-copy" style="margin-top:4px;">Max 2 MB. Leave empty to keep existing image.</p>
                    @error('bannerImage')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Translations --}}
            @foreach ($languages as $language)
                <div class="locale-fields-group">
                    <div class="locale-badge">{{ $language->native_name ?? $language->name }} ({{ $language->code }})</div>
                    <div class="form-grid form-grid-2">
                        <div>
                            <label class="field-label">Title</label>
                            <x-input type="text"
                                wire:model.defer="bannerTranslations.{{ $language->code }}.title"
                                :error="$errors->has('bannerTranslations.' . $language->code . '.title')" />
                            @error('bannerTranslations.' . $language->code . '.title')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="field-label">Button Text</label>
                            <x-input type="text"
                                wire:model.defer="bannerTranslations.{{ $language->code }}.button_text"
                                :error="$errors->has('bannerTranslations.' . $language->code . '.button_text')" />
                            @error('bannerTranslations.' . $language->code . '.button_text')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="span-2">
                            <label class="field-label">Subtitle</label>
                            <x-input type="text"
                                wire:model.defer="bannerTranslations.{{ $language->code }}.subtitle"
                                :error="$errors->has('bannerTranslations.' . $language->code . '.subtitle')" />
                            @error('bannerTranslations.' . $language->code . '.subtitle')
                                <div class="field-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeBannerModal">Cancel</x-btn>
                <x-btn type="submit">
                    {{ $bannerId ? 'Update Banner' : 'Create Banner' }}
                </x-btn>
            </div>
        </form>
    </x-modal>
</main>
