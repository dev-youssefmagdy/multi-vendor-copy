<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Catalog</div>
            <h1 class="D page-title">Variations List</h1>
            <p class="page-copy">Create reusable translated variation sets with assignable options for products.</p>
        </div>
        <div class="page-actions">@if ($canManageVariations)<a href="{{ route('admin.variations.create') }}"
        class="btn btn-primary">Add Variation</a>@endif</div>
    </div>
    @if (session('status'))
    <div class="card section-gap notice-success">{{ session('status') }}</div>@endif
    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Variations</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">Reusable attribute groups like size, color, or material.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Active</div>
                    <div class="D stat-value">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Ready for product assignment.</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Options</div>
                    <div class="D stat-value">{{ number_format($stats['options']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">All linked variation options across the catalog.</p>
        </div>
    </div>
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Variation Filters</h3>
                <p class="panel-copy">Search by translated names or slug and filter by status.</p>
            </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label">Search</label><input type="text" class="field-control"
                    wire:model.live.debounce.300ms="search"></div>
            <div><label class="field-label">Status</label><select class="field-control" wire:model.live="statusFilter">
                    <option value="">All statuses</option>@foreach ($statusOptions as $statusOption)<option
                    value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>@endforeach
                </select></div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Variation options are edited inside each variation record.</p><button type="button"
                class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>
    <section class="card table-card-shell">@if ($variations->count())
        <div class="table-scroll-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Variation</th>
                        <th>Options</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="ta-r">Actions</th>
                    </tr>
                </thead>
                <tbody>@foreach ($variations as $variation)<tr>
                    <td>
                        <div class="entity-title">
                            {{ $variation->translationValue('name') ?? $variation->slug }}
                        </div>
                    </td>
                    <td>{{ $variation->options_count }}</td>
                    <td>{{ $variation->products_count }}</td>
                    <td><span
                            class="badge {{ $variation->status === \App\Enums\VariationStatus::Active ? 'badge-green' : 'badge-amber' }}">{{ ucfirst($variation->status->value) }}</span>
                    </td>
                    <td>{{ $variation->updated_at?->format('M d, Y') }}</td>
                    <td class="ta-r">@if ($canManageVariations)
                        <div class="table-actions-inline"><a href="{{ route('admin.variations.edit', $variation) }}"
                                class="btn btn-secondary btn-sm">Edit</a><button type="button"
                                class="btn btn-secondary btn-sm btn-danger"
                    wire:click="deleteVariation({{ $variation->id }})">Delete</button></div>@else<span
                                class="entity-subtitle">View only</span>@endif
                    </td>
                </tr>@endforeach</tbody>
            </table>
    </div>@else<div class="empty-state">
            <h3 class="panel-title">No variations found</h3>
            <p class="panel-copy">Create the first variation set, then define its translated options.</p>
        </div>@endif
    </section>
</main>