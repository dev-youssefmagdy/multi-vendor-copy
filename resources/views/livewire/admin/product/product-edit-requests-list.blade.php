<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Catalog</div>
            <h1 class="D page-title">Product Edit Requests</h1>
            <p class="page-copy">Review vendor requests to update product names and descriptions. Approved changes go live immediately.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif
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
            <p class="section-copy">All edit requests across all vendors.</p>
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
            <p class="section-copy">Changes applied to live products.</p>
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
                <p class="panel-copy">Search by product slug or filter by status and vendor.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Search</label>
                <input type="text" class="field-control" placeholder="Product slug or vendor ID…"
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
                <label class="field-label">Vendor</label>
                <select class="field-control" wire:model.live="tenantFilter">
                    <option value="">All vendors</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">
                            {{ $tenant->name }}
                        </option>
                    @endforeach
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
                            <th>Product</th>
                            <th>Vendor</th>
                            <th>Locales</th>
                            <th>Changes</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="ta-r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            @php
                                $locales = array_keys(array_merge(
                                    $record->requested_translations ?? [],
                                    $record->current_translations ?? []
                                ));
                                $changedFields = $this->changedFields($record);
                            @endphp
                            <tr>
                                <td>
                                    <div class="entity-title">{{ $record->product_slug ?: '#' . $record->product_id }}</div>
                                    <div class="entity-subtitle">ID {{ $record->product_id }}</div>
                                </td>
                                <td>
                                    <div class="entity-title">
                                        {{ data_get($record->tenant?->data, 'name', $record->tenant_id) }}
                                    </div>
                                    <div class="entity-subtitle">{{ $record->tenant_id }}</div>
                                </td>
                                <td>
                                    <div class="entity-subtitle">{{ implode(', ', array_map('strtoupper', $locales)) }}</div>
                                </td>
                                <td>
                                    @forelse ($changedFields as $locale => $fields)
                                        <div class="entity-subtitle">
                                            [{{ strtoupper($locale) }}] {{ implode(', ', array_map('ucfirst', array_keys($fields))) }}
                                        </div>
                                    @empty
                                        <span class="entity-subtitle">No changes</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge {{ $record->status->badgeClass() }}">
                                        {{ $record->status->label() }}
                                    </span>
                                </td>
                                <td>
                                    <div class="entity-subtitle">{{ $record->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="ta-r" style="min-width:160px">
                                    <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            wire:click="openDetails({{ $record->id }})">
                                            View changes
                                        </button>
                                        @if ($canManage && $record->status->value === 'pending')
                                                <button type="button" class="btn btn-primary btn-sm"
                                                    wire:click="approve({{ $record->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:confirm="Approve this edit request? The product name and description will be updated immediately.">
                                                    Approve
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    wire:click="openRejectModal({{ $record->id }})">
                                                    Reject
                                                </button>
                                        @elseif ($canManage && $record->status->value === 'approved')
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                wire:click="resetToCentral({{ $record->id }})"
                                                wire:loading.attr="disabled"
                                                wire:confirm="Reset this product's translations back to central values?">
                                                Reset to Central
                                            </button>
                                        @elseif ($record->admin_note)
                                            <div class="entity-subtitle" title="{{ $record->admin_note }}">
                                                {{ Str::limit($record->admin_note, 40) }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
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
                <p class="panel-copy">No product edit requests match the current filters.</p>
            </div>
        @endif
    </section>

    {{-- Changes Modal --}}
    @if ($viewing)
        @php $changes = $this->changedFields($viewing); @endphp
        <div class="modal-backdrop" wire:click="closeDetails">
            <div class="modal-box" wire:click.stop style="max-width:900px;width:100%">
                <div class="modal-head">
                    <div>
                        <h3 class="panel-title">Requested Changes</h3>
                        <p class="panel-copy">
                            {{ $viewing->product_slug ?: '#' . $viewing->product_id }}
                            · {{ data_get($viewing->tenant?->data, 'name', $viewing->tenant_id) }}
                        </p>
                    </div>
                    <button type="button" class="modal-close" wire:click="closeDetails" aria-label="Close">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="modal-body" style="max-height:70vh;overflow:auto">
                    @forelse ($changes as $locale => $fields)
                        @foreach ($fields as $field => $values)
                            <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
                                <div style="flex:1;min-width:260px">
                                    <div class="eyebrow" style="margin-bottom:4px">[{{ strtoupper($locale) }}] Current {{ ucfirst($field) }}</div>
                                    <div class="entity-subtitle" style="white-space:pre-wrap">{{ trim(strip_tags((string) $values['current'])) ?: '—' }}</div>
                                </div>
                                <div style="flex:1;min-width:260px">
                                    <div class="eyebrow" style="margin-bottom:4px">[{{ strtoupper($locale) }}] Requested {{ ucfirst($field) }}</div>
                                    <div class="entity-subtitle" style="white-space:pre-wrap;color:var(--amber)">{{ trim(strip_tags((string) $values['requested'])) ?: '—' }}</div>
                                </div>
                            </div>
                        @endforeach
                    @empty
                        <p class="panel-copy">No differences between current and requested values.</p>
                    @endforelse
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetails">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Reject Modal --}}
    @if ($rejectingId)
        <div class="modal-backdrop" wire:click="cancelReject">
            <div class="modal-box" wire:click.stop>
                <div class="modal-head">
                    <h3 class="panel-title">Reject Edit Request</h3>
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
