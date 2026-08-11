<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Commerce</div>
            <h1 class="D page-title">Manufacturing Requests</h1>
            <p class="page-copy">Review and manage tenant manufacturing requests with status tracking and notifications.
            </p>
        </div>
    </div>

    {{-- ── Stats ─────────────────────────────────────────────────────────── --}}
    <div class="g-stats4 section-gap">
        <div class="card card-glow-cyan fu d1">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total Requests</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="panel-copy">All manufacturing requests across tenants.</p>
        </div>
        <div class="card card-glow-amber fu d2">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Pending</div>
                    <div class="D stat-value">{{ number_format($stats['pending']) }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="panel-copy">Awaiting admin review.</p>
        </div>
        <div class="card card-glow-violet fu d3">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">In Production</div>
                    <div class="D stat-value">{{ number_format($stats['in_production']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="panel-copy">Currently being manufactured.</p>
        </div>
        <div class="card card-glow-green fu d4">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Completed</div>
                    <div class="D stat-value">{{ number_format($stats['completed']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="panel-copy">Successfully fulfilled requests.</p>
        </div>
    </div>

    {{-- ── Filters ───────────────────────────────────────────────────────── --}}
    <details class="card filters-card fu d5 section-gap">
        <summary class="filters-summary">
            <div>
                <div class="panel-title">Filters</div>
                <p class="panel-copy">Filter requests by tenant, product name, or status.</p>
            </div>
            <div class="filters-summary-meta">
                <span class="filter-pill">Central</span>
                <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Search Product</label>
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by product name..." />
            </div>
            <div>
                <label class="field-label">Status</label>
                <x-select wire:model.live="statusFilter">
                    <option value="">All Statuses</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <label class="field-label">Tenant</label>
                <x-select wire:model.live="tenantFilter">
                    <option value="">All Tenants</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>
        <div class="filters-actions">
            <div class="page-actions compact-actions">
                <x-btn type="button" variant="secondary" wire:click="clearFilters">Reset</x-btn>
            </div>
        </div>
    </details>

    {{-- ── Table ─────────────────────────────────────────────────────────── --}}
    <div class="card fu d6 table-card-shell">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Manufacturing Requests</h3>
                <p class="panel-copy">All tenant manufacturing requests sorted by latest.</p>
            </div>
        </div>

        <x-table :headers="['#', 'Tenant', 'Product', 'Qty', 'Status', 'Submitted', 'Actions']">
            @forelse ($requests as $request)
                <tr>
                    <td>
                        <span class="entity-subtitle">#{{ $request->id }}</span>
                    </td>
                    <td>
                        <div class="entity-title">{{ $request->tenant?->name ?? $request->tenant_id }}</div>
                    </td>
                    <td>
                        <div class="entity-title">{{ $request->product_name }}</div>
                        @if ($request->description)
                            <div class="entity-subtitle"
                                style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $request->description }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="entity-title">{{ $request->quantity }}</span>
                    </td>
                    <td>
                        <span class="badge {{ $request->status->badgeClass() }}">
                            {{ $request->status->label() }}
                        </span>
                    </td>
                    <td>
                        <span class="entity-subtitle">{{ $request->created_at->format('M d, Y') }}</span>
                    </td>
                    <td>
                        <div class="table-actions-inline">
                            <a href="{{ route('admin.manufacturing.show', $request->id) }}" class="btn btn-secondary btn-sm"
                                title="View full details">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2" style="margin-right:4px;vertical-align:-1px;">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                View
                            </a>
                            @if ($canManage)
                                <button type="button" class="btn btn-primary btn-sm"
                                    wire:click="openStatusModal({{ $request->id }})" title="Update status">
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2" style="margin-right:4px;vertical-align:-1px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                    Update
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-title">No manufacturing requests</div>
                            <p class="empty-state-copy">No requests match the current filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="table-footer-shell">
            <div class="panel-copy">
                Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of
                {{ $requests->total() }} requests.
            </div>
            <div class="pagination-shell">
                <x-pagination :paginator="$requests" />
            </div>
        </div>
    </div>

    {{-- ── Status Update Modal ───────────────────────────────────────────── --}}
    <x-modal wire:model="showModal" title="Update Request Status" maxWidth="md" closeAction="closeModal">
        <form wire:submit="updateStatus" class="page-stack">

            {{-- Context header --}}
            @if ($selectedProductName)
                <div
                    style="background:var(--surface-2,#f9fafb);border:1px solid var(--border);border-radius:8px;padding:14px 16px;display:flex;flex-direction:column;gap:6px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                        <div>
                            <div
                                style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--t3);margin-bottom:2px;">
                                Product</div>
                            <div style="font-size:14px;font-weight:600;color:var(--t1);">{{ $selectedProductName }}</div>
                        </div>
                        <span class="{{ $selectedCurrentStatusClass }}">{{ $selectedCurrentStatus }}</span>
                    </div>
                    <div style="font-size:12px;color:var(--t3);">
                        Tenant: <span style="color:var(--t2);font-weight:500;">{{ $selectedTenantName }}</span>
                    </div>
                </div>
            @endif

            <div>
                <label class="field-label">New Status</label>
                <x-select wire:model.defer="newStatus">
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </x-select>
                @error('newStatus') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label">Admin Notes <span class="field-optional">(optional)</span></label>
                <x-textarea wire:model.defer="adminNotes" rows="3"
                    placeholder="Add a note for the tenant about this status update..." />
                @error('adminNotes') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="updateStatus">
                    <span wire:loading.remove wire:target="updateStatus">Save &amp; Notify Tenant</span>
                    <span wire:loading wire:target="updateStatus">Saving...</span>
                </x-btn>
            </div>
        </form>
    </x-modal>
</main>