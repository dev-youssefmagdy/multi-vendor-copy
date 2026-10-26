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
        <div x-data @keydown.escape.window="$wire.closeDetails()">
        <template x-teleport="body">
        <div class="ier-backdrop" wire:click="closeDetails">
            <div class="ier-modal" wire:click.stop style="max-width:960px">
                <div class="ier-head">
                    <div>
                        <h3 class="panel-title">Requested Changes</h3>
                        <p class="panel-copy" style="margin-top:2px">
                            {{ $viewing->product_slug ?: '#' . $viewing->product_id }}
                            · {{ data_get($viewing->tenant?->data, 'name', $viewing->tenant_id) }}
                        </p>
                    </div>
                    <button type="button" class="ier-close" wire:click="closeDetails" aria-label="Close">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="ier-body">
                    @forelse ($changes as $locale => $fields)
                        <section class="ier-locale">
                            <div class="ier-locale-head">
                                <span class="badge">{{ strtoupper($locale) }}</span>
                                <span class="entity-subtitle">{{ count($fields) }} {{ Str::plural('field', count($fields)) }} changed</span>
                            </div>
                            @foreach ($fields as $field => $values)
                                <div class="ier-field">
                                    <div class="ier-field-label">{{ ucfirst($field) }}</div>
                                    <div class="ier-diff">
                                        <div class="ier-pane ier-old">
                                            <div class="ier-pane-label">Current</div>
                                            <div class="ier-text">{{ trim(strip_tags((string) $values['current'])) ?: '—' }}</div>
                                        </div>
                                        <div class="ier-pane ier-new">
                                            <div class="ier-pane-label">Requested</div>
                                            <div class="ier-text">{{ trim(strip_tags((string) $values['requested'])) ?: '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </section>
                    @empty
                        <p class="panel-copy">No differences between current and requested values.</p>
                    @endforelse
                </div>
                <div class="ier-foot">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetails">Close</button>
                </div>
            </div>
        </div>
        </template>
        </div>
    @endif

    {{-- Reject Modal --}}
    @if ($rejectingId)
        <div x-data @keydown.escape.window="$wire.cancelReject()">
        <template x-teleport="body">
        <div class="ier-backdrop" wire:click="cancelReject">
            <div class="ier-modal" wire:click.stop>
                <div class="ier-head">
                    <h3 class="panel-title">Reject Edit Request</h3>
                    <button type="button" class="ier-close" wire:click="cancelReject" aria-label="Close">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="ier-body">
                    <label class="field-label">Admin Note <span class="field-optional">(optional — visible to vendor)</span></label>
                    <textarea class="field-control" rows="3" placeholder="Reason for rejection…"
                        wire:model="adminNote"></textarea>
                    @error('adminNote') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="ier-foot">
                    <button type="button" class="btn btn-secondary" wire:click="cancelReject">Cancel</button>
                    <button type="button" class="btn btn-primary btn-danger" wire:click="confirmReject">Reject Request</button>
                </div>
            </div>
        </div>
        </template>
        </div>
    @endif

    <style>
        .ier-backdrop { position: fixed; inset: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(3, 7, 15, .66); backdrop-filter: blur(3px); }
        .ier-modal { width: 100%; max-width: 520px; max-height: calc(100vh - 48px); display: flex; flex-direction: column; background: var(--card); border: 1px solid var(--border2); border-radius: 16px; box-shadow: 0 24px 64px rgba(0, 0, 0, .45); overflow: hidden; }
        .ier-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 18px 22px; border-bottom: 1px solid var(--border); }
        .ier-body { padding: 20px 22px; overflow-y: auto; flex: 1; }
        .ier-foot { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 22px; border-top: 1px solid var(--border); background: var(--surface); }
        .ier-close { flex-shrink: 0; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--border); background: transparent; color: var(--t3); cursor: pointer; }
        .ier-close:hover { color: var(--t1); border-color: var(--border2); }
        .ier-locale + .ier-locale { margin-top: 22px; }
        .ier-locale-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
        .ier-field + .ier-field { margin-top: 14px; }
        .ier-field-label { font-size: 12px; font-weight: 600; color: var(--t2); margin-bottom: 6px; }
        .ier-diff { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .ier-pane { border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px; min-width: 0; }
        .ier-old { background: rgba(255, 77, 109, .07); border-color: rgba(255, 77, 109, .28); }
        .ier-new { background: rgba(0, 214, 143, .07); border-color: rgba(0, 214, 143, .3); }
        .ier-pane-label { font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--t3); margin-bottom: 4px; }
        .ier-text { font-size: 13px; line-height: 1.55; color: var(--t1); white-space: pre-wrap; overflow-wrap: anywhere; max-height: 260px; overflow-y: auto; }
        @media (max-width: 720px) { .ier-diff { grid-template-columns: 1fr; } .ier-backdrop { padding: 12px; } }
    </style>
</main>
