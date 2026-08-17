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
            <a class="btn btn-secondary" href="{{ route('tenant.returns.index') }}">← Returns</a>
            @if(!empty($orderId))
                <a class="btn btn-secondary" target="_blank"
                    href="{{ route('tenant.orders.show', $orderId) }}">View Order</a>
            @endif
        </div>
    </div>

    <div class="card fu d1 section-gap">
        <h4 class="panel-title">Return Status</h4>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            @php
                $statusColor = $returnRecord['status_color'] ?? 'amber';
                $badgeClass  = match($statusColor) {
                    'green' => 'badge-green',
                    'blue'  => 'badge-blue',
                    'red'   => 'badge-red',
                    'gray'  => 'badge-gray',
                    default => 'badge-yellow',
                };
                $status = $returnRecord['status'] ?? null;
                $isOpen = $status?->isOpen() ?? false;
            @endphp
            <span class="badge {{ $badgeClass }}" style="font-size:14px;padding:6px 14px">
                {{ $returnRecord['status_label'] ?? '-' }}
            </span>

            @if($isOpen)
                <x-btn type="button" wire:click="approve" style="background:var(--blue);color:#fff">Approve</x-btn>
                <x-btn type="button" wire:click="$set('showRejectModal', true)" style="background:var(--red);color:#fff">Reject</x-btn>
                <x-btn type="button" wire:click="$set('showInfoModal', true)" variant="secondary">Request More Info</x-btn>
            @endif
        </div>
    </div>

    <div class="card fu d2 section-gap">
        <h4 class="panel-title">Return Details</h4>
        <div class="form-grid form-grid-2" style="gap:20px">
            <div>
                <div class="field-label">Order Number</div>
                <div class="D" style="font-family:ui-monospace,monospace;font-size:15px">{{ $returnRecord['order_number'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Submitted</div>
                <div class="D">{{ $returnRecord['created_at'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Reason</div>
                <div class="D">{{ $returnRecord['reason'] ?? '-' }}</div>
            </div>
            @if($returnRecord['refund_amount'] ?? null)
            <div>
                <div class="field-label">Refund Amount</div>
                <div class="D" style="font-weight:700;color:var(--green)">${{ number_format((float) $returnRecord['refund_amount'], 2) }}</div>
            </div>
            @endif
        </div>

        @if($returnRecord['description'] ?? false)
        <div style="margin-top:12px">
            <div class="field-label">Customer Description</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6">
                {{ $returnRecord['description'] }}
            </div>
        </div>
        @endif

        @if(!empty($returnRecord['media']))
        <div style="margin-top:16px">
            <div class="field-label">Evidence</div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:6px">
                @foreach($returnRecord['media'] as $m)
                    @if($m['type'] === 'photo')
                        <a href="{{ $m['url'] }}" target="_blank">
                            <img src="{{ $m['url'] }}" style="width:96px;height:96px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
                        </a>
                    @else
                        <a href="{{ $m['url'] }}" target="_blank" class="link-btn">Watch Video</a>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="card fu d3 section-gap">
        <h4 class="panel-title">Notes</h4>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px">
            @forelse($returnRecord['notes'] ?? [] as $note)
                <div style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:10px 14px">
                    <div style="font-size:12px;color:var(--muted);margin-bottom:4px">
                        {{ ucfirst($note['author_type']) }} · {{ $note['created_at'] }}
                    </div>
                    <div class="D">{{ $note['note'] }}</div>
                </div>
            @empty
                <p class="field-hint">No notes yet.</p>
            @endforelse
        </div>
        <textarea wire:model.defer="noteText" rows="3" class="input" placeholder="Add an internal note…" style="resize:vertical"></textarea>
        <div style="margin-top:10px">
            <x-btn type="button" wire:click="addNote">Add Note</x-btn>
        </div>
    </div>

    @if(!empty($order))
    <div class="card fu d4 section-gap">
        <h4 class="panel-title">Order Summary</h4>
        @include('livewire.tenant.order.partials.order-details', [
            'order' => $order,
            'showShippingControls' => false,
        ])
    </div>
    @endif

    @if($showRejectModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Reject Return</h3>
            <textarea wire:model.defer="rejectReason" rows="3" class="input" placeholder="Reason for rejection (shown to customer)…" style="resize:vertical"></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" wire:click="$set('showRejectModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="reject" style="background:var(--red);color:#fff">Confirm Reject</x-btn>
            </div>
        </div>
    </div>
    @endif

    @if($showInfoModal)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Request More Information</h3>
            <textarea wire:model.defer="infoMessage" rows="3" class="input" placeholder="What do you need from the customer?" style="resize:vertical"></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" wire:click="$set('showInfoModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="requestMoreInfo">Send Request</x-btn>
            </div>
        </div>
    </div>
    @endif
</main>
