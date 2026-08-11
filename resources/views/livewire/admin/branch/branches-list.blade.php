<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Commerce</div>
            <h1 class="D page-title">Branches</h1>
            <p class="page-copy">Manage physical branches, their shipping zones, and product inventory.</p>
        </div>
        <div class="page-actions">
            @if ($canManage)
                <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">Add Branch</a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="card section-gap notice-error">{{ session('error') }}</div>
    @endif

    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">Configured branches.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Active</div>
                    <div class="D stat-value">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Currently operating.</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Default</div>
                    <div class="D stat-value">{{ number_format($stats['default']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">Primary fulfilment branch.</p>
        </div>
    </div>

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Branch Filters</h3>
                <p class="panel-copy">Search by name or code and filter by status.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Search</label>
                <input type="text" class="field-control" wire:model.live.debounce.300ms="search"
                    placeholder="Name, code, city…">
            </div>
            <div>
                <label class="field-label">Status</label>
                <select class="field-control" wire:model.live="activeFilter">
                    <option value="">All statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Shipping zones and rates are managed inside each branch editor.</p>
            <button type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>

    <section class="card table-card-shell">
        @if ($branches->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Code</th>
                            <th>Location</th>
                            <!-- <th>Products</th> -->
                            <th>Zones</th>
                            <th>Status</th>
                            <th class="ta-r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td>
                                    <div class="entity-title">
                                        {{ $branch->name }}
                                        @if ($branch->is_default)
                                            <span class="badge badge-cyan" style="margin-left:6px">Default</span>
                                        @endif
                                    </div>
                                    <div class="entity-subtitle">{{ $branch->email ?: '—' }}</div>
                                </td>
                                <td>{{ strtoupper($branch->code) }}</td>
                                <td>
                                    <div>{{ $branch->city ?: '—' }}</div>
                                    @if ($branch->country)
                                        <div class="entity-subtitle">{{ $branch->country }}</div>
                                    @endif
                                </td>
                                {{-- <td>{{ $branch->products_count }}</td> --}}
                                <td>{{ $branch->shipping_zones_count }}</td>
                                <td>
                                    <span class="badge {{ $branch->is_active ? 'badge-green' : 'badge-amber' }}">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="ta-r">
                                    @if ($canManage)
                                        <div class="table-actions-inline">
                                            <a href="{{ route('admin.branches.edit', $branch) }}"
                                                class="btn btn-secondary btn-sm">Edit</a>
                                            @if (!$branch->is_default)
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    wire:click="deleteBranch({{ $branch->id }})"
                                                    wire:confirm="Delete this branch? This will also remove its shipping zones and product assignments.">
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <span class="entity-subtitle">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-pagination">
                {{ $branches->links() }}
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No branches found</h3>
                <p class="panel-copy">Create the first branch to manage inventory and shipping zones.</p>
            </div>
        @endif
    </section>
</main>