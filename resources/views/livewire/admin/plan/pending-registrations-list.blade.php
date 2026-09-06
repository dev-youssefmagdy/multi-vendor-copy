<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Plans</div>
            <h1 class="D page-title">Pending Registrations</h1>
            <p class="page-copy">Monitor registrations awaiting email confirmation and store setup completion.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    <div class="g-stats4 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">All pending registration records.</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Awaiting</div>
                    <div class="D stat-value">{{ number_format($stats['pending']) }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="section-copy">Email sent, link not yet used.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Completed</div>
                    <div class="D stat-value">{{ number_format($stats['completed']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Store setup successfully finished.</p>
        </div>
        <div class="card card-glow-red">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Expired</div>
                    <div class="D stat-value">{{ number_format($stats['expired']) }}</div>
                </div>
                <div class="mini-stat-dot dot-red"></div>
            </div>
            <p class="section-copy">Links that were not used before expiry.</p>
        </div>
    </div>

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Filters</h3>
                <p class="panel-copy">Search by email or phone and filter by status or package.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Search</label>
                <input type="text" class="field-control" placeholder="Email or phone…"
                    wire:model.live.debounce.300ms="search">
            </div>
            <div>
                <label class="field-label">Status</label>
                <select class="field-control" wire:model.live="statusFilter">
                    <option value="all">All statuses</option>
                    <option value="pending">Awaiting</option>
                    <option value="completed">Completed</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div>
                <label class="field-label">Package</label>
                <select class="field-control" wire:model.live="packageFilter">
                    <option value="">All packages</option>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}">{{ $package->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Completed registrations have a fully created tenant account.</p>
            <button type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>

    <section class="card table-card-shell">
        @if ($records->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Package</th>
                            <th>Payment</th>
                            <th>Expires</th>
                            <th>Status</th>
                            @if ($canManage)
                            <th class="ta-r">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td>
                                    <div class="entity-title">{{ $record->email }}</div>
                                    <div class="entity-subtitle">Created {{ $record->created_at->diffForHumans() }}</div>
                                </td>
                                <td>{{ $record->phone ?: '—' }}</td>
                                <td>{{ $record->package?->name ?? 'Free / Default' }}</td>
                                <td>
                                    @if ($record->payment_data)
                                        <div class="entity-title">{{ strtoupper($record->payment_data['gateway_code'] ?? '—') }}
                                        </div>
                                        <div class="entity-subtitle">{{ $record->payment_data['amount'] ?? '' }}
                                            {{ $record->payment_data['currency'] ?? '' }}</div>
                                    @else
                                        <span class="entity-subtitle">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="entity-subtitle">{{ $record->expires_at->format('M d, Y H:i') }}</div>
                                </td>
                                <td>
                                    @if ($record->completed_at)
                                        <span class="badge badge-green">Completed</span>
                                    @elseif ($record->expires_at->isPast())
                                        <span class="badge badge-red">Expired</span>
                                    @else
                                        <span class="badge badge-amber">Awaiting</span>
                                    @endif
                                </td>
                                @if ($canManage)
                                    <td class="ta-r">
                                        <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                            wire:click="deleteRecord({{ $record->id }})"
                                            wire:confirm="Delete this pending registration record?">
                                            Delete
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="table-pagination">
                {{ $records->links() }}
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No records found</h3>
                <p class="panel-copy">No pending registrations match the current filters.</p>
            </div>
        @endif
    </section>
</main>