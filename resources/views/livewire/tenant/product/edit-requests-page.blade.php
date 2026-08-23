<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Product Edit Requests</h1>
                <span class="page-badge">Catalog</span>
                @if ($pendingCount > 0)
                    <span class="badge badge-amber">{{ $pendingCount }} pending</span>
                @endif
            </div>
            <p class="page-copy">When you edit a product name or description, it goes through admin review before going
                live. Track your requests here.</p>
        </div>
    </div>

    <div class="g-stats4 section-gap" style="grid-template-columns:repeat(3,1fr)">
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Pending</div>
                    <div class="D stat-value">{{ $pendingCount }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Approved</div>
                    <div class="D stat-value">{{ $approvedCount }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
        </div>
        <div class="card card-glow-red">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Rejected</div>
                    <div class="D stat-value">{{ $rejectedCount }}</div>
                </div>
                <div class="mini-stat-dot dot-red"></div>
            </div>
        </div>
    </div>

    <div class="card fu d2 section-gap" style="padding:14px 20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span class="entity-subtitle">Filter:</span>
        @foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label)
            <button type="button" wire:click="$set('statusFilter', '{{ $val }}')"
                class="btn btn-sm {{ $statusFilter === $val ? 'btn-primary' : 'btn-secondary' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="card fu d2 table-card-shell section-gap">
        @forelse ($requests as $request)
            <div class="details-kv" style="align-items:flex-start;gap:16px;padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.06)"
                wire:key="req-{{ $request->id }}">
                <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
                        <div class="entity-title">{{ $request->product_slug ?: 'Product #' . $request->product_id }}</div>
                        <span class="badge {{ $request->status->badgeClass() }}">{{ $request->status->label() }}</span>
                        <span class="entity-subtitle" style="margin-left:auto">{{ $request->created_at->diffForHumans() }}</span>
                    </div>

                    @foreach ($request->requested_translations as $locale => $fields)
                        <div style="margin-bottom:8px">
                            <span class="entity-subtitle" style="font-size:11px;text-transform:uppercase">{{ $locale }}</span>
                            @if (isset($fields['name']))
                                <div style="margin-top:4px">
                                    <span class="entity-subtitle">Name: </span>
                                    <span style="font-size:12px">{{ \Illuminate\Support\Str::limit($fields['name'], 80) }}</span>
                                </div>
                            @endif
                            @if (isset($fields['description']))
                                <div style="margin-top:2px">
                                    <span class="entity-subtitle">Description: </span>
                                    <span style="font-size:12px">{{ \Illuminate\Support\Str::limit(strip_tags($fields['description']), 100) }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if ($request->status->value === 'rejected' && $request->admin_note)
                        <div style="margin-top:8px;padding:8px 12px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:6px">
                            <span class="entity-subtitle" style="color:var(--color-red,#ef4444);font-size:11px">Admin note: </span>
                            <span style="font-size:12px">{{ $request->admin_note }}</span>
                        </div>
                    @endif

                    @if ($request->reviewed_at)
                        <div style="margin-top:6px">
                            <span class="entity-subtitle" style="font-size:11px">
                                Reviewed {{ $request->reviewed_at->diffForHumans() }}
                            </span>
                        </div>
                    @endif
                </div>

                @if ($request->status->value === 'rejected')
                    <a href="{{ route('tenant.products.edit', $request->product_id) }}"
                        class="btn btn-secondary btn-sm" style="flex-shrink:0">
                        Edit Again
                    </a>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <div class="empty-state-title">No edit requests yet</div>
                <p class="empty-state-copy">When you change a product name or description, your request will appear here
                    for admin review.</p>
            </div>
        @endforelse

        @if ($requests->hasPages())
            <div class="table-footer-shell">
                <div class="pagination-shell">
                    <x-pagination :paginator="$requests" />
                </div>
            </div>
        @endif
    </div>
</main>
