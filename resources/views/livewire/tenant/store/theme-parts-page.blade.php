<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Storefront</div>
            <h1 class="D page-title">Theme Parts</h1>
            <p class="page-copy">Manage headers and footers for your active storefront theme.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('tenant.store.theme-parts.create', array_filter(['themeId' => $theme->id ?? null, 'type' => 'header'])) }}"
                class="btn btn-secondary">+ Header</a>
            <a href="{{ route('tenant.store.theme-parts.create', array_filter(['themeId' => $theme->id ?? null, 'type' => 'footer'])) }}"
                class="btn btn-primary">+ Footer</a>
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Parts</div>
                    <div class="D stat-value">{{ $stats['total'] }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">{{ isset($theme) && $theme ? 'Parts in ' . $theme->name : 'All theme parts' }}</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Headers</div>
                    <div class="D stat-value">{{ $stats['headers'] }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">Header type parts.</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Customised</div>
                    <div class="D stat-value">{{ $stats['customised'] }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="section-copy">Parts you've edited locally.</p>
        </div>
    </div>

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Filters</h3>
                <p class="panel-copy">Filter by theme and part type.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Theme</label>
                <select class="field-control" wire:model.live="themeId">
                    <option value="">All Themes</option>
                    @foreach ($themes as $thm)
                        <option value="{{ $thm->id }}">{{ $thm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Type</label>
                <select class="field-control" wire:model.live="typeFilter">
                    <option value="">All Types</option>
                    <option value="header">Header</option>
                    <option value="footer">Footer</option>
                </select>
            </div>
        </div>
        <div class="filters-actions">
            <button type="button" class="btn btn-secondary"
                wire:click="$set('themeId', null); $set('typeFilter', '')">Reset Filters</button>
        </div>
    </details>

    <section class="card table-card-shell">
        @if ($parts->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Part</th>
                            @if (!$themeId)
                                <th>Theme</th>
                            @endif
                            <th>Type</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="ta-r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parts as $part)
                            <tr>
                                <td>
                                    <div class="entity-title">{{ $part->name }}</div>
                                    <div class="entity-subtitle">{{ $part->slug }}</div>
                                </td>
                                @if (!$themeId)
                                    <td>
                                        <div class="entity-title">{{ $part->theme?->name ?? '—' }}</div>
                                    </td>
                                @endif
                                <td>
                                    <span class="badge {{ $part->type === 'header' ? 'badge-violet' : 'badge-cyan' }}">
                                        {{ ucfirst($part->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($part->image)
                                        <img src="{{ $part->image }}" alt="Part image"
                                            style="height:36px;width:auto;object-fit:contain;border-radius:4px;border:1px solid var(--border2)">
                                    @else
                                        <span class="entity-subtitle">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                        @if ($part->is_default)
                                            <span class="badge badge-amber">Default</span>
                                        @endif
                                        @if ($part->is_customized)
                                            <span class="badge badge-violet">Customised</span>
                                        @endif
                                        <span
                                            class="badge {{ $part->is_active ? 'badge-green' : 'badge-red' }}">{{ $part->is_active ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="entity-subtitle">{{ $part->updated_at?->diffForHumans() ?? '—' }}</span>
                                </td>
                                <td class="ta-r">
                                    <div class="table-actions-inline">
                                        <a href="{{ route('tenant.store.theme-parts.edit', $part->id) }}"
                                            class="btn btn-secondary btn-sm">Edit</a>
                                        @if (!$part->is_default)
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                wire:click="setDefault({{ $part->id }})">Set Default</button>
                                        @endif
                                        <button type="button"
                                            class="btn btn-secondary btn-sm {{ $part->is_active ? 'btn-warning' : '' }}"
                                            wire:click="toggleActive({{ $part->id }})">
                                            {{ $part->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        @if ($part->central_part_id)
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                wire:click="revertToCentral({{ $part->id }})"
                                                wire:confirm="Reset this part to the original central template content? Your customisations will be lost."
                                                title="Overwrite with current central template content">Reset</button>
                                        @endif
                                        <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                            wire:click="requestDelete({{ $part->id }})">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No theme parts found</h3>
                <p class="panel-copy">
                    @if (isset($theme) && $theme)
                        Add the first header or footer for <strong>{{ $theme->name }}</strong> using the buttons
                        above.
                    @else
                        Select a theme to view or add parts.
                    @endif
                </p>
            </div>
        @endif
    </section>
</main>