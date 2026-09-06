<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Plans</div>
            <h1 class="D page-title">Tenant Change Requests</h1>
            <p class="page-copy">Review vendor requests to change their target countries or categories. Approving re-syncs their store data automatically.</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="g-stats4 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">All change requests across all vendors.</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Pending</div>
                    <div class="D stat-value">{{ number_format($stats['pending']) }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="section-copy">Awaiting your review and decision.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Approved</div>
                    <div class="D stat-value">{{ number_format($stats['approved']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Changes applied and re-synced.</p>
        </div>
        <div class="card card-glow-red">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Rejected</div>
                    <div class="D stat-value">{{ number_format($stats['rejected']) }}</div>
                </div>
                <div class="mini-stat-dot dot-red"></div>
            </div>
            <p class="section-copy">Requests declined by admin.</p>
        </div>
    </div>

    {{-- Filters --}}
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Filters</h3>
                <p class="panel-copy">Search by vendor ID or filter by status and type.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Search</label>
                <input type="text" class="field-control" placeholder="Vendor ID…"
                    wire:model.live.debounce.300ms="search">
            </div>
            <div>
                <label class="field-label">Status</label>
                <select class="field-control" wire:model.live="statusFilter">
                    <option value="pending">Pending</option>
                    <option value="all">All statuses</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div>
                <label class="field-label">Type</label>
                <select class="field-control" wire:model.live="typeFilter">
                    <option value="">All types</option>
                    <option value="countries">Target Countries</option>
                    <option value="categories">Categories</option>
                </select>
            </div>
        </div>
    </details>

    {{-- Table --}}
    <section class="card table-card-shell">
        @if ($records->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Type</th>
                            <th>Current</th>
                            <th>Requested</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            @if ($canManage)
                                <th class="ta-r">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            @php
                                $names = $record->type->value === 'countries' ? $countryNames : $categoryNames;
                                $currentLabels = collect($record->current_data ?? [])->map(fn ($id) => $names[$id] ?? "#$id");
                                $requestedLabels = collect($record->requested_data ?? [])->map(fn ($id) => $names[$id] ?? "#$id");
                            @endphp
                            <tr>
                                <td>
                                    <div class="entity-title">
                                        {{ data_get($record->tenant?->data, 'name', $record->tenant_id) }}
                                    </div>
                                    <div class="entity-subtitle">{{ $record->tenant_id }}</div>
                                </td>
                                <td>
                                    <div class="entity-subtitle">{{ $record->type->label() }}</div>
                                </td>
                                <td style="max-width:220px">
                                    <div class="entity-subtitle" style="white-space:normal">{{ $currentLabels->implode(', ') ?: '—' }}</div>
                                </td>
                                <td style="max-width:220px">
                                    <div class="entity-title text-amber-500" style="white-space:normal">{{ $requestedLabels->implode(', ') ?: '—' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $record->status->badgeClass() }}">
                                        {{ $record->status->label() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="entity-subtitle">{{ $record->created_at->diffForHumans() }}</div>
                                </td>
                                @if ($canManage)
                                    <td class="ta-r" style="min-width:160px">
                                        @if ($record->status->value === 'pending')
                                            <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap">
                                                <button type="button" class="btn btn-primary btn-sm"
                                                    wire:click="approve({{ $record->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:confirm="Approve this change request? The vendor's data will be updated and re-synced immediately.">
                                                    Approve
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    wire:click="openRejectModal({{ $record->id }})">
                                                    Reject
                                                </button>
                                            </div>
                                        @else
                                            @if ($record->admin_note)
                                                <div class="entity-subtitle" title="{{ $record->admin_note }}">
                                                    {{ Str::limit($record->admin_note, 40) }}
                                                </div>
                                            @else
                                                <span class="entity-subtitle">—</span>
                                            @endif
                                        @endif
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
                <h3 class="panel-title">No requests found</h3>
                <p class="panel-copy">No tenant change requests match the current filters.</p>
            </div>
        @endif
    </section>

    {{-- Reject Modal --}}
    @if ($rejectingId)
        <div class="modal-backdrop" wire:click="cancelReject">
            <div class="modal-box" wire:click.stop>
                <div class="modal-head">
                    <h3 class="panel-title">Reject Change Request</h3>
                    <button type="button" class="modal-close" wire:click="cancelReject" aria-label="Close">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <label class="field-label">Admin Note <span class="field-optional">(optional — visible to vendor)</span></label>
                    <textarea class="field-control" rows="3" placeholder="Reason for rejection…"
                        wire:model="adminNote"></textarea>
                    @error('adminNote') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" wire:click="cancelReject">Cancel</button>
                    <button type="button" class="btn btn-primary btn-danger" wire:click="confirmReject">Reject Request</button>
                </div>
            </div>
        </div>
    @endif
</main>
