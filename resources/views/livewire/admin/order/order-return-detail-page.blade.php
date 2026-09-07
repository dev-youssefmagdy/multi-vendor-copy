<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('admin.orders.returns.index') }}">← Returns</a>
            @if(!empty($returnRecord['order_number']) && !empty($returnRecord['tenant_id']))
                <a class="btn btn-secondary" target="_blank"
                    href="{{ route('admin.orders.show', [$returnRecord['tenant_id'], $returnRecord['order_number']]) }}">
                    View Order
                </a>
            @endif
        </div>
    </div>

    {{-- Status & Actions --}}
    <div class="card fu d1 section-gap">
        <h4 class="panel-title">Return Status</h4>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            @php
                $statusColor = $returnRecord['status_color'] ?? 'amber';
                $badgeClass  = match($statusColor) {
                    'green' => 'badge-green',
                    'blue'  => 'badge-blue',
                    'red'   => 'badge-red',
                    default => 'badge-yellow',
                };
            @endphp
            <span class="badge {{ $badgeClass }}" style="font-size:14px;padding:6px 14px">
                {{ $returnRecord['status_label'] ?? '-' }}
            </span>

            @if(($returnRecord['status'] ?? null)?->value === 'pending' || ($returnRecord['status'] ?? null)?->value === 'approved')
                <x-btn type="button" wire:click="$set('showApproveModal', true)"
                    style="background:var(--blue);color:#fff">
                    Approve Return
                </x-btn>
                <x-btn type="button" wire:click="$set('showRejectModal', true)"
                    style="background:var(--red);color:#fff">
                    Reject Return
                </x-btn>
            @endif

            @if(($returnRecord['status'] ?? null)?->value === 'approved')
                <x-btn type="button" wire:click="$set('showRefundModal', true)"
                    style="background:var(--green);color:#fff">
                    Mark as Refunded
                </x-btn>
            @endif

            @if($returnRecord['reviewed_by'] ?? false)
                <span class="field-hint">
                    Reviewed by {{ $returnRecord['reviewed_by'] }} on {{ $returnRecord['reviewed_at'] }}
                </span>
            @endif
        </div>
    </div>

    {{-- Return Info --}}
    <div class="card fu d2 section-gap">
        <h4 class="panel-title">Return Details</h4>
        <div class="form-grid form-grid-2" style="gap:20px">
            <div>
                <div class="field-label">Order Number</div>
                <div class="D" style="font-family:ui-monospace,monospace;font-size:15px">
                    {{ $returnRecord['order_number'] ?? '-' }}
                </div>
            </div>
            <div>
                <div class="field-label">Tenant / Store ID</div>
                <div class="D" style="font-size:13px;color:var(--muted)">{{ $returnRecord['tenant_id'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Return Submitted</div>
                <div class="D">{{ $returnRecord['created_at'] ?? '-' }}</div>
            </div>
            @if($returnRecord['refund_amount'] !== null)
            <div>
                <div class="field-label">Refund Amount</div>
                <div class="D" style="font-size:16px;font-weight:700;color:var(--green)">
                    ${{ number_format((float) $returnRecord['refund_amount'], 2) }}
                </div>
            </div>
            @endif
        </div>

        <div style="margin-top:16px">
            <div class="field-label">Return Reason</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6">
                {{ $returnRecord['reason'] ?? '-' }}
            </div>
        </div>

        @if($returnRecord['customer_notes'] ?? false)
        <div style="margin-top:12px">
            <div class="field-label">Customer Notes</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6;color:var(--muted)">
                {{ $returnRecord['customer_notes'] }}
            </div>
        </div>
        @endif
    </div>

    {{-- Admin Notes --}}
    <div class="card fu d3 section-gap">
        <h4 class="panel-title">Admin Notes</h4>
        <div>
            <label class="field-label">Internal Notes</label>
            <textarea wire:model.defer="adminNotes" rows="4"
                class="input" placeholder="Add internal notes about this return…"
                style="resize:vertical"></textarea>
        </div>
        <div style="margin-top:10px">
            <x-btn type="button" wire:click="saveNotes">Save Notes</x-btn>
        </div>
    </div>

    {{-- Order Summary --}}
    @if(!empty($order))
    <div class="card fu d4 section-gap">
        <h4 class="panel-title">Order Summary</h4>
        @include('livewire.admin.order.partials.order-details', [
            'order'              => $order,
            'showShippingControls' => false,
            'showAttachments'    => false,
            'showActivityLog'    => false,
        ])
    </div>
    @endif

    {{-- Approve Modal --}}
    @if($showApproveModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Approve Return</h3>
            <p style="color:var(--muted);margin-bottom:20px;font-size:13px">
                This will mark the return as approved. You can issue a refund in the next step.
            </p>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <x-btn type="button" wire:click="$set('showApproveModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="approve" style="background:var(--blue);color:#fff">Confirm Approve</x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Reject Return</h3>
            <p style="color:var(--muted);margin-bottom:20px;font-size:13px">
                This will mark the return as rejected. Please add admin notes explaining the reason before confirming.
            </p>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <x-btn type="button" wire:click="$set('showRejectModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="reject" style="background:var(--red);color:#fff">Confirm Reject</x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Refund Modal --}}
    @if($showRefundModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Mark as Refunded</h3>
            <p style="color:var(--muted);margin-bottom:16px;font-size:13px">
                Enter the refund amount issued to the customer and confirm.
            </p>
            <div style="margin-bottom:16px">
                <label class="field-label">Refund Amount ($)</label>
                <input type="number" wire:model.defer="refundAmount" class="input" placeholder="0.00" min="0" step="0.01">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <x-btn type="button" wire:click="$set('showRefundModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="markRefunded" style="background:var(--green);color:#fff">Confirm Refund</x-btn>
            </div>
        </div>
    </div>
    @endif

</main>
