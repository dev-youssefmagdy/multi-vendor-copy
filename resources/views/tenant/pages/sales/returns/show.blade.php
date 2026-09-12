@extends('tenant.layouts.app')

@section('title', 'Return #' . $returnRecord['id'])

@section('content')
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
        $value  = $status?->value;
    @endphp

    <x-tenant::page-header :title="'Return #' . $returnRecord['id']" badge="Return Management" description="Review this customer return request, respond, and take action.">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.returns.index') }}">← Returns</a>
            @if(!empty($orderId))
                <a class="btn btn-secondary" target="_blank" href="{{ route('tenant.orders.show', $orderId) }}">View Order</a>
            @endif
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="card fu d1 section-gap">
        <h4 class="panel-title">Return Status</h4>
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <span class="badge {{ $badgeClass }}" style="font-size:14px;padding:6px 14px">
                {{ $returnRecord['status_label'] ?? '-' }}
            </span>

            @if($isOpen)
                @if(in_array($value, ['pending', 'awaiting_merchant_review', 'awaiting_info']))
                    <button type="button" class="btn" style="background:var(--blue);color:#fff"
                        data-action-url="{{ route('tenant.returns.approve', $returnRecord['id']) }}"
                        data-action-method="POST"
                        data-confirm="Approve this return request?"
                        data-success="reload-page">Approve</button>
                    <button type="button" class="btn" style="background:var(--red);color:#fff"
                        data-modal-open="reject-modal">Reject</button>
                    <button type="button" class="btn btn-secondary" data-modal-open="info-modal">Request More Info</button>
                @endif

                @if($value === 'approved')
                    <button type="button" class="btn" style="background:var(--blue);color:#fff"
                        data-action-url="{{ route('tenant.returns.received', $returnRecord['id']) }}"
                        data-action-method="POST"
                        data-confirm="Mark this return's item as received?"
                        data-success="reload-page">Mark Item Received</button>
                @endif

                @if(in_array($value, ['approved', 'item_received']))
                    <button type="button" class="btn" style="background:var(--green);color:#fff"
                        data-modal-open="refund-modal">Mark as Refunded</button>
                @endif
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

        <x-tenant::chat
            id="return-notes-chat"
            empty="No notes yet."
            :messages="collect($returnRecord['notes'] ?? [])->map(fn ($note) => [
                'id' => $note['created_at'] . '-' . $note['author_type'],
                'author' => ucfirst($note['author_type']),
                'at' => $note['created_at'],
                'body' => $note['note'],
                'is_me' => $note['author_type'] === 'tenant',
            ])->all()"
        >
            <x-slot:composer>
                <x-tenant::form action="{{ route('tenant.returns.notes', $returnRecord['id']) }}" validate="{{ route('tenant.returns.notes.validate', $returnRecord['id']) }}" success="reload-page">
                    <textarea name="note_text" rows="3" class="input" placeholder="Add an internal note…" style="resize:vertical"></textarea>
                    <div style="margin-top:10px">
                        <button type="submit" class="btn btn-primary">Add Note</button>
                    </div>
                </x-tenant::form>
            </x-slot:composer>
        </x-tenant::chat>
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

    <x-tenant::modal id="reject-modal" title="Reject Return">
        <x-tenant::form action="{{ route('tenant.returns.reject', $returnRecord['id']) }}" validate="{{ route('tenant.returns.reject.validate', $returnRecord['id']) }}" success="reload-page">
            <textarea name="reject_reason" rows="3" class="input" placeholder="Reason for rejection (shown to customer)…" style="resize:vertical" required></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn" style="background:var(--red);color:#fff">Confirm Reject</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>

    <x-tenant::modal id="info-modal" title="Request More Information">
        <x-tenant::form action="{{ route('tenant.returns.request-info', $returnRecord['id']) }}" validate="{{ route('tenant.returns.request-info.validate', $returnRecord['id']) }}" success="reload-page">
            <textarea name="info_message" rows="3" class="input" placeholder="What do you need from the customer?" style="resize:vertical" required></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Send Request</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>

    <x-tenant::modal id="refund-modal" title="Mark as Refunded">
        <p style="color:var(--muted);margin-bottom:16px;font-size:13px">
            Enter the refund amount issued to the customer and confirm.
        </p>
        <x-tenant::form action="{{ route('tenant.returns.refunded', $returnRecord['id']) }}" validate="{{ route('tenant.returns.refunded.validate', $returnRecord['id']) }}" success="reload-page">
            <div style="margin-bottom:16px">
                <label class="field-label">Refund Amount ($)</label>
                <input type="number" name="refund_amount" class="input" placeholder="0.00" min="0" step="0.01" required>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn" style="background:var(--green);color:#fff">Confirm Refund</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/return-show.js')
@endpush
