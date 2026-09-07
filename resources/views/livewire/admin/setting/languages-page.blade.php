<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Localization</div>
            <h1 class="D page-title">Languages</h1>
            <p class="page-copy">Manage database-backed interface languages and keep language directories in sync.</p>
        </div>
        <div class="page-actions">@if ($canManageLanguages)<a href="{{ route('admin.settings.languages.create') }}"
        class="btn btn-primary">Add Language</a>@endif</div>
    </div>
    @if (session('status'))
    <div class="card section-gap notice-success">{{ session('status') }}</div>@endif
    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Languages</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">All registered interface locales.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Active</div>
                    <div class="D stat-value">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Languages enabled for translation entry.</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">RTL</div>
                    <div class="D stat-value">{{ number_format($stats['rtl']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">Right-to-left languages requiring mirrored layout support.</p>
        </div>
    </div>
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Language Filters</h3>
                <p class="panel-copy">Search by name or code, then refine by direction or active state.</p>
            </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label">Search</label><input type="text" class="field-control"
                    wire:model.live.debounce.300ms="search"></div>
            <div><label class="field-label">Direction</label><select class="field-control"
                    wire:model.live="directionFilter">
                    <option value="">All directions</option>@foreach ($directionOptions as $directionOption)<option
                        value="{{ $directionOption->value }}">{{ strtoupper($directionOption->value) }}</option>
                    @endforeach
                </select></div>
            <div><label class="field-label">Active State</label><select class="field-control"
                    wire:model.live="activeFilter">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select></div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Creating a language also creates its `lang/{code}` directory.</p><button
                type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>
    <section class="card table-card-shell">@if ($languages->count())
        <div class="table-scroll-wrap">
            <table class="data-table" @if ($canManageLanguages) wire:ignore.self @endif>
                <thead>
                    <tr>
                        @if ($canManageLanguages)<th></th>@endif
                        <th>Image</th>
                        <th>Language</th>
                        <th>Code</th>
                        <th>Direction</th>
                        <th>Default</th>
                        <th>Countries</th>
                        <th>Pricing</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Tokens Used</th>
                        <th class="ta-r">Actions</th>
                    </tr>
                </thead>
                <tbody id="languages-sortable">@foreach ($languages as $language)<tr data-id="{{ $language->id }}" @if ($canManageLanguages) class="sortable-row" style="cursor:grab" @endif>
                    @if ($canManageLanguages)<td>
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="8" x2="20" y2="8" />
                            <line x1="4" y1="16" x2="20" y2="16" />
                        </svg>
                    </td>@endif
                    <td>@if($language->imageFile)
                        <img src="{{ $language->imageFile->full_path }}"
                        alt="{{ $language->name }}"
                    style="width:40px;height:28px;object-fit:cover;border-radius:4px;border:1px solid #eee" />
                    @else
                    <span
                            class="muted">—</span>
                    @endif
                </td>
                    <td>
                        <div class="entity-title">{{ $language->name }}</div>
                        <div class="entity-subtitle">{{ $language->native_name }}</div>
                    </td>
                    <td>{{ strtoupper($language->code) }}</td>
                    <td>{{ strtoupper($language->direction->value) }}</td>
                    <td>{{ $language->is_default ? 'Default' : 'Secondary' }}</td>
                    <td>
                        @if (!empty($language->countries))
                            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                @foreach ($language->countries as $iso)
                                    <span class="badge badge-secondary" style="font-size:11px;font-family:monospace;">{{ strtoupper($iso) }}</span>
                                @endforeach
                            </div>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if ($language->is_free)
                            <span class="badge badge-green">Free</span>
                        @else
                            <span class="badge badge-amber">Paid</span>
                            <div class="entity-subtitle">${{ number_format((float) $language->price, 2) }}</div>
                        @endif
                    </td>
                    <td>
                        @php $progress = (int) $language->translation_progress; $translationStatus = $language->translation_status; @endphp
                        @if ($translationStatus === 'failed')
                            <span class="badge badge-red" title="{{ $language->translation_error }}">Failed</span>
                        @elseif ($translationStatus === 'processing')
                            <div style="display:flex;align-items:center;gap:8px;min-width:100px;">
                                <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;">
                                    <div style="width:{{ $progress }}%;height:100%;background:#3b82f6;border-radius:3px;transition:width .3s;"></div>
                                </div>
                                <span style="font-size:12px;white-space:nowrap;">{{ $progress }}%</span>
                            </div>
                        @else
                            <span class="badge badge-green">100%</span>
                        @endif
                    </td>
                    <td><span
                            class="badge {{ $language->is_active ? 'badge-green' : 'badge-amber' }}">{{ $language->is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td>{{ number_format((int) $language->ai_tokens_used) }}</td>
                    <td class="ta-r">
                        @if ($canManageLanguages)
                            <div class="table-actions-inline"><a
                                    href="{{ route('admin.settings.languages.edit', $language) }}"
                                    class="btn btn-secondary btn-sm">Edit</a>
                                @if ($language->translation_status === 'failed')
                                    <button type="button" class="btn btn-secondary btn-sm"
                                            wire:click="retryTranslation({{ $language->id }})">Continue</button>
                                @endif
                                <button type="button"
                                    class="btn btn-secondary btn-sm btn-danger"
                                    wire:click="deleteLanguage({{ $language->id }})">Delete</button></div>
                        @else
                            <span class="entity-subtitle">View only</span>
                        @endif
                    </td>
                </tr>@endforeach</tbody>
            </table>
    </div>@else<div class="empty-state">
            <h3 class="panel-title">No languages found</h3>
            <p class="panel-copy">Add the first language to start filling translation records across the admin.</p>
        </div>@endif
    </section>
    @if ($canManageLanguages)
        @push('scripts')
            <script>
                document.addEventListener('livewire:init', () => {
                    const list = document.getElementById('languages-sortable');
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
    @endif
</main>
