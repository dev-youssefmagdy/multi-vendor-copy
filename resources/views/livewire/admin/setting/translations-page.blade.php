<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Localization</div>
            <h1 class="D page-title">Translation Files</h1>
            <p class="page-copy">Browse language resources in the lang folder and edit key/value pairs across active
                locales from one screen.</p>
        </div>
        {{--<div class="page-actions">
            @if ($canManageTranslations)
            <button type="button" class="btn btn-primary" wire:click="addRow">Add Key</button>
            @endif
        </div>--}}
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Resources</div>
                    <div class="D stat-value">{{ number_format($resourceCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">JSON translations plus locale PHP files discovered under the lang directory.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Locales</div>
                    <div class="D stat-value">{{ number_format(count($locales)) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Each row can be updated for every active or discovered locale at once.</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Keys</div>
                    <div class="D stat-value">{{ number_format($keyCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">Use JSON for phrase-based translations and PHP files for dotted, nested translation
                keys.</p>
        </div>
    </div>

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Translation Filters</h3>
                <p class="panel-copy">Switch between resources, search keys or values, and surface missing locale
                    entries.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>

        <div class="filters-grid">
            <div>
                <label class="field-label">Translation Resource</label>
                <select class="field-control" wire:model.live="selectedResource">
                    @foreach ($resources as $resource)
                        <option value="{{ $resource['key'] }}">{{ $resource['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Search</label>
                <input type="text" class="field-control" wire:model.live.debounce.300ms="search"
                    placeholder="Search key or translation value">
            </div>
            <div>
                <label class="field-label">Rows</label>
                <label class="field-inline-toggle">
                    <input type="checkbox" wire:model.live="showOnlyMissing">
                    <span>Show only missing translations</span>
                </label>
            </div>
            <div>
                <label class="field-label">Per page</label>
                <select class="field-control" wire:model.live="perPage">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="filters-actions">
            <p class="filters-note">`JSON Translations` edits the root locale files such as `lang/en.json`. PHP
                resources edit `lang/{locale}/{file}.php` across all locales.</p>
        </div>
    </details>

    {{-- @if ($canManageTranslations)
    <section class="card section-gap">
        <div class="filters-grid">
            <div>
                <label class="field-label">New PHP Translation File</label>
                <input type="text" class="field-control" wire:model.defer="newPhpFile"
                    placeholder="Example: storefront">
                @error('newPhpFile')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="filters-actions" style="align-items:flex-end;justify-content:flex-start;">
                <button type="button" class="btn btn-secondary" wire:click="createPhpFile">Create File</button>
            </div>
        </div>
    </section>
    @endif --}}

    <section class="card table-card-shell">
        @if ($filteredRows !== [])
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="min-width:280px;">Key</th>
                            @foreach ($locales as $locale)
                                <th style="min-width:240px;">{{ strtoupper($locale) }}</th>
                            @endforeach
                            @if ($canManageTranslations)
                                <th class="ta-r">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($filteredRows as $entry)
                            @php
                                $rowIndex = $entry['index'];
                                $row = $entry['row'];
                            @endphp
                            <tr wire:key="translation-row-{{ $rowIndex }}">
                                <td>
                                    <input readonly type="text" class="field-control" wire:model.defer="rows.{{ $rowIndex }}.key" {{ $canManageTranslations ? '' : 'disabled' }}>
                                    @error('rows.' . $rowIndex . '.key')<p class="field-error">{{ $message }}</p>@enderror
                                </td>
                                @foreach ($locales as $locale)
                                    <td>
                                        <textarea class="field-control" rows="3"
                                            wire:model.defer="rows.{{ $rowIndex }}.values.{{ $locale }}" {{ $canManageTranslations ? '' : 'disabled' }}></textarea>
                                    </td>
                                @endforeach
                                @if ($canManageTranslations)
                                    <td class="ta-r">
                                        <div class="flex gap-2 justify-end">
                                            <button type="button" class="btn btn-primary btn-sm"
                                                wire:click="saveRow({{ $rowIndex }})"
                                                wire:loading.attr="disabled"
                                                wire:target="saveRow({{ $rowIndex }})">
                                                Save
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                wire:click="removeRow({{ $rowIndex }})"
                                                wire:loading.attr="disabled"
                                                wire:target="removeRow({{ $rowIndex }})">Remove</button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(isset($total) && $total > $perPage)
                @php
                    $rangeStart = ($page - 1) * $perPage + 1;
                    $rangeEnd = min($page * $perPage, $total);

                    $basePages = collect([
                        1,
                        2,
                        $page - 1,
                        $page,
                        $page + 1,
                        $lastPage - 1,
                        $lastPage,
                    ])
                        ->filter(fn ($p) => $p >= 1 && $p <= $lastPage)
                        ->unique()
                        ->sort()
                        ->values();

                    $pageItems = collect();
                    $previousPage = null;
                    foreach ($basePages as $p) {
                        if ($previousPage !== null && $p - $previousPage > 1) {
                            $pageItems->push('ellipsis-' . $p);
                        }
                        $pageItems->push($p);
                        $previousPage = $p;
                    }
                @endphp
                <div class="table-pagination-bar">
                    <div class="table-pagination-summary">
                        Showing <strong>{{ number_format($rangeStart) }}</strong>&ndash;<strong>{{ number_format($rangeEnd) }}</strong>
                        of <strong>{{ number_format($total) }}</strong>
                    </div>

                    <nav class="app-pagination" role="navigation" aria-label="Pagination">
                        <div class="app-pagination-mobile">
                            @if ($page <= 1)
                                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">Previous</span>
                            @else
                                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="setPage({{ $page - 1 }})" wire:loading.attr="disabled">Previous</button>
                            @endif

                            <span class="pagination-chip pagination-chip-status">{{ $page }} / {{ $lastPage }}</span>

                            @if ($page >= $lastPage)
                                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">Next</span>
                            @else
                                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="setPage({{ $page + 1 }})" wire:loading.attr="disabled">Next</button>
                            @endif
                        </div>

                        <div class="app-pagination-desktop">
                            @if ($page <= 1)
                                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8249;</span>
                            @else
                                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="setPage({{ $page - 1 }})" wire:loading.attr="disabled" aria-label="Previous page">&#8249;</button>
                            @endif

                            <div class="app-pagination-pages">
                                @foreach ($pageItems as $item)
                                    @if (is_string($item))
                                        <span class="pagination-chip pagination-chip-ellipsis" aria-hidden="true">&hellip;</span>
                                    @elseif ($item === $page)
                                        <span class="pagination-chip active" aria-current="page">{{ $item }}</span>
                                    @else
                                        <button type="button" class="pagination-chip" wire:key="translations-page-{{ $item }}" wire:click="setPage({{ $item }})" wire:loading.attr="disabled" aria-label="Go to page {{ $item }}">{{ $item }}</button>
                                    @endif
                                @endforeach
                            </div>

                            @if ($page >= $lastPage)
                                <span class="pagination-chip pagination-chip-nav is-disabled" aria-disabled="true">&#8250;</span>
                            @else
                                <button type="button" class="pagination-chip pagination-chip-nav" wire:click="setPage({{ $page + 1 }})" wire:loading.attr="disabled" aria-label="Next page">&#8250;</button>
                            @endif
                        </div>
                    </nav>
                </div>
            @endif
        @else
            <div class="empty-state">
                <h3 class="panel-title">No translation rows found</h3>
                <p class="panel-copy">Add a key or switch to another translation resource to start editing language values.
                </p>
            </div>
        @endif
    </section>
</main>
