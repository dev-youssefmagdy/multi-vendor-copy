<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Settings</div>
            <h1 class="D page-title">Default Banners</h1>
            <p class="page-copy">Define default storefront banners that are automatically synced to every new tenant on registration.</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-primary" wire:click="openModal()">
                Add Banner
            </button>
        </div>
    </div>

    {{-- ── Banner grid ──────────────────────────────────────────────────────── --}}
    @if ($banners->count())
        <section class="card table-card-shell section-gap">
            <div class="table-scroll-wrap">
                <table class="data-table" wire:ignore.self>
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>#</th>
                            <th>Image</th>
                            <th>Title (default locale)</th>
                            <th>URL</th>
                            <th class="ta-r">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="default-banners-sortable">
                        @foreach ($banners as $banner)
                            <tr data-id="{{ $banner->id }}" class="sortable-row" style="cursor:grab">
                                <td>
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="8" x2="20" y2="8" />
                                        <line x1="4" y1="16" x2="20" y2="16" />
                                    </svg>
                                </td>
                                <td class="ta-c">{{ $banner->serial_number }}</td>
                                <td>
                                    @if ($banner->image_path)
                                        <img src="{{ $banner->image_path }}" alt="" class="thumb-sm" style="width:80px;height:44px;object-fit:cover;border-radius:4px;">
                                    @else
                                        <span class="entity-subtitle">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="entity-title">{{ $banner->title ?: '—' }}</div>
                                    @if ($banner->subtitle)
                                        <div class="entity-subtitle">{{ $banner->subtitle }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($banner->url)
                                        <a href="{{ $banner->url }}" target="_blank" rel="noopener noreferrer" class="entity-subtitle link-subtle">{{ $banner->url }}</a>
                                    @else
                                        <span class="entity-subtitle">—</span>
                                    @endif
                                </td>
                                <td class="ta-r">
                                    <div class="table-actions-inline">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            wire:click="openModal({{ $banner->id }})">
                                            Edit
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                            wire:click="delete({{ $banner->id }})"
                                            wire:confirm="Delete this default banner?">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="card section-gap">
            <div class="empty-state">
                <h3 class="panel-title">No default banners yet</h3>
                <p class="panel-copy">Add banners here and they will be automatically created in every new tenant store upon registration.</p>
                <button type="button" class="btn btn-primary mt-4" wire:click="openModal()">Add First Banner</button>
            </div>
        </div>
    @endif

    {{-- ── Create / Edit Modal ─────────────────────────────────────────────── --}}
    <x-modal wire:model="modalOpen" :title="$bannerId ? 'Edit Default Banner' : 'Add Default Banner'" maxWidth="2xl" closeAction="closeModal">
        <form wire:submit.prevent="save" class="space-y-5">

            {{-- Image upload --}}
            <div>
                <label class="field-label">Banner Image</label>
                <p class="dimension-hint">
                    This banner syncs to every tenant's storefront — required size varies by theme:
                    @foreach (config('image_dimensions.themes') as $theme)
                        <strong>{{ $theme['label'] }} {{ $theme['width'] }}×{{ $theme['height'] }}px</strong>@if (!$loop->last), @endif
                    @endforeach
                </p>
                @if ($bannerId && DefaultBanner::find($bannerId)?->image_path)
                    <div class="mb-2">
                        <img src="{{ DefaultBanner::find($bannerId)->image_path }}" alt="" style="max-height:120px;border-radius:6px;object-fit:cover;">
                    </div>
                @endif
                <div class="file-input-wrap">
                    <input type="file" wire:model="bannerImage" accept="image/*" class="field-control"
                        wire:loading.class="opacity-50" wire:target="bannerImage">
                    <div class="file-input-loader" wire:loading wire:target="bannerImage">
                        <svg class="file-input-spinner" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-dasharray="56.55" stroke-dashoffset="14" stroke-linecap="round"/>
                        </svg>
                        <span>Uploading…</span>
                    </div>
                </div>
                @error('bannerImage') <p class="field-error">{{ $message }}</p> @enderror
                @if ($bannerImage)
                    <p class="field-hint mt-1">New image selected — will replace existing on save.</p>
                @endif
            </div>

            {{-- URL + serial --}}
            <div class="grid grid-2-col gap-4">
                <div>
                    <label class="field-label">Link URL <span class="field-optional">(optional)</span></label>
                    <input type="url" wire:model="bannerUrl" class="field-control" placeholder="https://...">
                    @error('bannerUrl') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Sort Order</label>
                    <input type="number" wire:model="bannerSerial" class="field-control" min="0">
                    @error('bannerSerial') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Translations --}}
            @if (count($bannerTranslations) > 1)
                <div>
                    <label class="field-label">Translations</label>
                    <div class="tab-nav mb-3">
                        @foreach ($languages as $lang)
                            <button type="button"
                                class="tab-btn {{ $loop->first ? 'tab-btn-active' : '' }}"
                                onclick="switchBannerTab('{{ $lang->code }}', this)">
                                {{ strtoupper($lang->code) }}
                                @if ($lang->is_default) <span class="ml-1 text-xs opacity-60">default</span> @endif
                            </button>
                        @endforeach
                    </div>
                    @foreach ($languages as $lang)
                        <div class="banner-locale-tab" id="btab-{{ $lang->code }}" style="{{ !$loop->first ? 'display:none' : '' }}">
                            <div class="space-y-3">
                                <div>
                                    <label class="field-label">Title</label>
                                    <input type="text" wire:model="bannerTranslations.{{ $lang->code }}.title" class="field-control" placeholder="Banner title…">
                                    @error("bannerTranslations.{$lang->code}.title") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Subtitle</label>
                                    <input type="text" wire:model="bannerTranslations.{{ $lang->code }}.subtitle" class="field-control" placeholder="Banner subtitle…">
                                    @error("bannerTranslations.{$lang->code}.subtitle") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="field-label">Button Text</label>
                                    <input type="text" wire:model="bannerTranslations.{{ $lang->code }}.button_text" class="field-control" placeholder="Shop Now">
                                    @error("bannerTranslations.{$lang->code}.button_text") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Single language --}}
                @foreach ($languages as $lang)
                    <div class="space-y-3">
                        <div>
                            <label class="field-label">Title</label>
                            <input type="text" wire:model="bannerTranslations.{{ $lang->code }}.title" class="field-control">
                            @error("bannerTranslations.{$lang->code}.title") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Subtitle</label>
                            <input type="text" wire:model="bannerTranslations.{{ $lang->code }}.subtitle" class="field-control">
                            @error("bannerTranslations.{$lang->code}.subtitle") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="field-label">Button Text</label>
                            <input type="text" wire:model="bannerTranslations.{{ $lang->code }}.button_text" class="field-control" placeholder="Shop Now">
                            @error("bannerTranslations.{$lang->code}.button_text") <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endforeach
            @endif

            {{-- Footer --}}
            <div class="modal-footer-actions">
                <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $bannerId ? 'Update Banner' : 'Create Banner' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </form>
    </x-modal>
</main>

@script
<script>
function switchBannerTab(code, btn) {
    document.querySelectorAll('.banner-locale-tab').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('tab-btn-active'));
    const tab = document.getElementById('btab-' + code);
    if (tab) tab.style.display = '';
    btn.classList.add('tab-btn-active');
}
</script>
@endscript

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            const list = document.getElementById('default-banners-sortable');
            if (!list || typeof Sortable === 'undefined') {
                return;
            }

            Sortable.create(list, {
                animation: 150,
                handle: '.sortable-row',
                onEnd: () => {
                    const orderedIds = Array.from(list.querySelectorAll('tr[data-id]')).map(row => parseInt(row.dataset.id, 10));
                    @this.call('updateOrder', orderedIds);
                },
            });
        });
    </script>
@endpush
