<main id="mn">

    <div class="page-head fu d0">
        <div>
            <h1 class="D page-title">Custom Templates</h1>
            <p class="page-copy">Review, approve, or reject tenant-uploaded custom storefront templates before they go live.</p>
        </div>
    </div>

    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan fu d1">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Pending</div>
                    <div class="D stat-value">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="card card-glow-green fu d2">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Approved</div>
                    <div class="D stat-value">{{ number_format($stats['approved']) }}</div>
                </div>
            </div>
        </div>
        <div class="card fu d3">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Rejected</div>
                    <div class="D stat-value">{{ number_format($stats['rejected']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="appearance-tabs fu d1" style="margin: 20px 0;">
        @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
            <button type="button" wire:click="setStatus('{{ $key }}')" class="appearance-tab {{ $status === $key ? 'act' : '' }}">{{ $label }}</button>
        @endforeach
    </div>

    <section class="card fu d2">
        <div class="ct-table-wrap">
            <table class="ct-table">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Version</th>
                        <th>Uploaded</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->tenant?->name ?? $template->tenant_id }}</td>
                            <td>{{ $template->version }}</td>
                            <td>{{ $template->uploaded_at?->diffForHumans() ?? $template->created_at->diffForHumans() }}</td>
                            <td>
                                <span class="ct-status ct-status-{{ $template->status }}">{{ ucfirst($template->status) }}</span>
                                @if($template->status === 'rejected' && $template->rejection_reason)
                                    <div class="field-hint">{{ $template->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="ct-actions">
                                @if($template->status === 'pending')
                                    <button type="button" wire:click="requestApprove({{ $template->id }})" class="ct-link">Approve</button>
                                    <button type="button" wire:click="openReject({{ $template->id }})" class="ct-link ct-link-danger">Reject</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No templates found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $templates->links() }}</div>
    </section>

    @if($rejectModalOpen)
        <div class="ct-modal-backdrop" wire:click.self="closeReject">
            <div class="ct-modal">
                <h3 class="panel-title">Reject template</h3>
                <label class="field-label">Rejection reason</label>
                <textarea wire:model.defer="rejectionReason" rows="4" class="ct-file-input"></textarea>
                @error('rejectionReason')<div class="field-error">{{ $message }}</div>@enderror
                <div class="gs-actions" style="margin-top:16px;">
                    <x-btn type="button" wire:click="reject">Reject template</x-btn>
                    <button type="button" wire:click="closeReject" class="ct-link">Cancel</button>
                </div>
            </div>
        </div>
    @endif

</main>

<style>
.ct-table-wrap { overflow-x: auto; }
.ct-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ct-table th, .ct-table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border2); }
.ct-status { padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.ct-status-pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
.ct-status-approved { background: rgba(34,197,94,0.15); color: #22c55e; }
.ct-status-rejected { background: rgba(239,68,68,0.15); color: #ef4444; }
.ct-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.ct-link { background: none; border: none; color: var(--primary, #6366f1); cursor: pointer; font-size: 13px; padding: 0; }
.ct-link-danger { color: #ef4444; }
.ct-file-input { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border2); background: rgba(255,255,255,0.02); color: var(--t1); }
.ct-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100; }
.ct-modal { background: var(--card); border-radius: 14px; padding: 24px; width: 100%; max-width: 480px; }
</style>
