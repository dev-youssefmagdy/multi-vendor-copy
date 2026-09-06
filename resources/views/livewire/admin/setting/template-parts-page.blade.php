<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Templates</div>
            <h1 class="D page-title">Template Parts</h1>
            <p class="page-copy">Manage header and footer parts for each storefront template. Parts are synced to
                tenant theme parts on save.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.settings.template-parts.create', array_filter(['templateId' => $template?->id, 'type' => 'header'])) }}"
                class="btn btn-secondary">+ Header</a>
            <a href="{{ route('admin.settings.template-parts.create', array_filter(['templateId' => $template?->id, 'type' => 'footer'])) }}"
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
            <p class="section-copy">{{ $template ? 'Parts in ' . $template->name : 'All template parts' }}</p>
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
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Footers</div>
                    <div class="D stat-value">{{ $stats['footers'] }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Footer type parts.</p>
        </div>
    </div>

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Filters</h3>
                <p class="panel-copy">Filter by template and part type.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Template</label>
                <select class="field-control" wire:model.live="templateId">
                    <option value="">All Templates</option>
                    @foreach ($templates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
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
                wire:click="$set('templateId', null); $set('typeFilter', '')">Reset Filters</button>
        </div>
    </details>

    <section class="card table-card-shell">
        @if ($parts->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Part</th>
                            @if (!$templateId)
                                <th>Template</th>
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
                                @if (!$templateId)
                                    <td>
                                        <div class="entity-title">{{ $part->template?->name ?? '—' }}</div>
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
                                        <span
                                            class="badge {{ $part->is_active ? 'badge-green' : 'badge-red' }}">{{ $part->is_active ? 'Active' : 'Inactive' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="entity-subtitle">{{ $part->updated_at?->diffForHumans() ?? '—' }}</span>
                                </td>
                                <td class="ta-r">
                                    <div class="table-actions-inline">
                                        <a href="{{ route('admin.settings.template-parts.edit', $part->id) }}"
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
                <h3 class="panel-title">No template parts found</h3>
                <p class="panel-copy">
                    @if ($template)
                        Add the first header or footer for <strong>{{ $template->name }}</strong> using the buttons
                        above.
                    @else
                        Select a template to add parts, or run the template parts seeder.
                    @endif
                </p>
            </div>
        @endif
    </section>
</main>