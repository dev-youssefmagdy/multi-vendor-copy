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
                            <th>Locale</th>
                            <th>Current Name</th>
                            <th>Requested Name</th>
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
                                $locales = array_keys(array_merge(
                                    $record->requested_translations ?? [],
                                    $record->current_translations ?? []
                                ));
                                $firstLocale = $locales[0] ?? 'en';
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
                                    <div class="entity-subtitle">
                                        {{ implode(', ', $locales) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="entity-title">
                                        {{ data_get($record->current_translations, "$firstLocale.name") ?: '—' }}
                                    </div>
                                    @if (count($locales) > 1)
                                        <div class="entity-subtitle">+{{ count($locales) - 1 }} more locale(s)</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="entity-title text-amber-500">
                                        {{ data_get($record->requested_translations, "$firstLocale.name") ?: '—' }}
                                    </div>
                                    @if (count($locales) > 1)
                                        <div class="entity-subtitle">+{{ count($locales) - 1 }} more locale(s)</div>
                                    @endif
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
                                                    wire:confirm="Approve this edit request? The product name and description will be updated immediately.">
                                                    Approve
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                    wire:click="openRejectModal({{ $record->id }})">
                                                    Reject
                                                </button>
                                            </div>
                                        @elseif ($record->status->value === 'approved')
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                wire:click="resetToCentral({{ $record->id }})"
                                                wire:loading.attr="disabled"
                                                wire:confirm="Reset this product's translations back to central values?">
                                                Reset to Central
                                            </button>
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
                            {{-- Expanded diff row --}}
                            @foreach ($locales as $locale)
                                @php
                                    $curName  = data_get($record->current_translations,   "$locale.name",        '');
                                    $reqName  = data_get($record->requested_translations, "$locale.name",        '');
                                    $curDesc  = data_get($record->current_translations,   "$locale.description", '');
                                    $reqDesc  = data_get($record->requested_translations, "$locale.description", '');
                                @endphp
                                @if ($curDesc !== $reqDesc || ($curName !== $reqName && count($locales) > 1))
                                <tr style="background:var(--surface-2)">
                                    <td colspan="{{ $canManage ? 8 : 7 }}" style="padding:12px 16px">
                                        <div style="display:flex;gap:16px;flex-wrap:wrap">
                                            <div style="flex:1;min-width:260px">
                                                <div class="eyebrow" style="margin-bottom:4px">[{{ strtoupper($locale) }}] Current Description</div>
                                                <div class="entity-subtitle" style="white-space:pre-wrap;max-height:80px;overflow:auto">{{ $curDesc ?: '—' }}</div>
                                            </div>
                                            <div style="flex:1;min-width:260px">
                                                <div class="eyebrow" style="margin-bottom:4px">[{{ strtoupper($locale) }}] Requested Description</div>
                                                <div class="entity-subtitle" style="white-space:pre-wrap;max-height:80px;overflow:auto;color:var(--amber)">{{ $reqDesc ?: '—' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
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
