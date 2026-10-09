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

    {{-- Status & Actions (buttons only from ReturnRequestService::availableActions(rr, 'admin')) --}}
    @php
        $actions = $returnRecord['available_actions'] ?? [];
        $can = fn (string $action) => in_array($action, $actions, true);
        $badgeClass = match($returnRecord['status_color'] ?? 'amber') {
            'green' => 'badge-green',
            'blue'  => 'badge-blue',
            'red'   => 'badge-red',
            'gray'  => 'badge-gray',
            default => 'badge-yellow',
        };
        $inspection = $returnRecord['inspection_result_value'] ?? null;
        $replacement = $returnRecord['replacement'] ?? null;
        $breakdown = $returnRecord['refund_breakdown'] ?? null;
    @endphp
    <div class="card fu d1 section-gap">
        <h4 class="panel-title">Return Status</h4>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <span class="badge {{ $badgeClass }}" style="font-size:14px;padding:6px 14px" data-return-status>
                {{ $returnRecord['status_label'] ?? '-' }}
            </span>
            @if (!empty($returnRecord['type_label']))
                <span class="badge {{ !empty($returnRecord['is_exchange']) ? 'badge-cyan' : 'badge-gray' }}">{{ $returnRecord['type_label'] }}</span>
            @endif

            @if ($can('approve'))
                <x-btn type="button" wire:click="openApproveModal" style="background:var(--blue);color:#fff" data-action="approve">Approve</x-btn>
            @endif
            @if ($can('reject'))
                <x-btn type="button" wire:click="openRejectModal" style="background:var(--red);color:#fff" data-action="reject">Reject</x-btn>
            @endif
            @if ($can('request_info'))
                <x-btn type="button" wire:click="openInfoModal" variant="secondary" data-action="request_info">Request More Info</x-btn>
            @endif
            @if ($can('forward_to_merchant'))
                <x-btn type="button" wire:click="forwardToMerchant" variant="secondary" data-action="forward_to_merchant"
                    wire:confirm="Forward this request to the merchant for review?">Forward to Merchant</x-btn>
            @endif
            @if ($can('mark_received'))
                <x-btn type="button" wire:click="markItemReceived" style="background:var(--blue);color:#fff" data-action="mark_received"
                    wire:confirm="Mark the returned item as received?">Mark Item Received</x-btn>
            @endif
            @if ($can('inspect'))
                <x-btn type="button" wire:click="openInspectModal" style="background:var(--blue);color:#fff" data-action="inspect">Record Inspection</x-btn>
            @endif
            @if ($can('issue_refund'))
                <x-btn type="button" wire:click="openRefundModal" style="background:var(--green);color:#fff" data-action="issue_refund">Issue Refund</x-btn>
            @endif
            @if ($can('mark_exchange_shipped'))
                <x-btn type="button" wire:click="openShipModal" style="background:var(--blue);color:#fff" data-action="mark_exchange_shipped">Mark Replacement Shipped</x-btn>
            @endif
            @if ($can('mark_exchange_completed'))
                <x-btn type="button" wire:click="markExchangeCompleted" style="background:var(--green);color:#fff" data-action="mark_exchange_completed"
                    wire:confirm="Mark this exchange as completed?">Complete Exchange</x-btn>
            @endif
            @if ($can('convert_to_refund'))
                <x-btn type="button" wire:click="convertToRefund" variant="secondary" data-action="convert_to_refund"
                    wire:confirm="Convert this exchange to a refund? Any reserved replacement stock is released.">Convert to Refund</x-btn>
            @endif
            @if ($can('close'))
                <x-btn type="button" wire:click="close" variant="secondary" data-action="close"
                    wire:confirm="Close this return request?">Close Request</x-btn>
            @endif

            @if ($returnRecord['reviewed_by'] ?? false)
                <span class="field-hint">
                    Reviewed by {{ $returnRecord['reviewed_by'] }}@if ($returnRecord['reviewed_at'] ?? null) on {{ $returnRecord['reviewed_at'] }}@endif
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
                <div class="field-label">Tenant / Store</div>
                <div class="D" style="font-size:13px">{{ $returnRecord['tenant_name'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Return Submitted</div>
                <div class="D">{{ $returnRecord['created_at'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Type</div>
                <div class="D">{{ $returnRecord['type_label'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Item</div>
                <div class="D">{{ $returnRecord['item'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Quantity</div>
                <div class="D">{{ $returnRecord['quantity'] ?? 1 }}</div>
            </div>
            <div>
                <div class="field-label">Return Method</div>
                <div class="D">{{ $returnRecord['return_method'] ?? '-' }}</div>
            </div>
            @if(($returnRecord['refund_amount'] ?? null) !== null)
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

        @if($returnRecord['description'] ?? false)
        <div style="margin-top:12px">
            <div class="field-label">Customer Description</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6;color:var(--muted)">
                {{ $returnRecord['description'] }}
            </div>
        </div>
        @endif

        @if($returnRecord['customer_note'] ?? false)
        <div style="margin-top:12px">
            <div class="field-label">Customer Note</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6;color:var(--muted)">
                {{ $returnRecord['customer_note'] }}
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

    @if ($replacement)
    <div class="card fu d2 section-gap" id="return-exchange-panel">
        <h4 class="panel-title">Exchange</h4>
        <div class="form-grid form-grid-2" style="gap:20px">
            <div>
                <div class="field-label">Replacement</div>
                <div class="D">{{ $replacement['label'] }} × {{ $replacement['quantity'] }}</div>
            </div>
            <div>
                <div class="field-label">Available stock</div>
                <div class="D">
                    @if ($replacement['stock'] === null)
                        Unlimited
                    @else
                        <span @if ($replacement['stock'] < $replacement['quantity'] && !$replacement['reserved']) style="color:var(--red);font-weight:600" @endif>{{ $replacement['stock'] }}</span>
                    @endif
                    @if ($replacement['reserved'])
                        <span class="badge badge-blue" style="margin-inline-start:6px">Reserved</span>
                    @endif
                </div>
            </div>
            @if ($returnRecord['exchange_tracking_number'] ?? null)
            <div>
                <div class="field-label">Tracking number</div>
                <div class="D" style="font-family:ui-monospace,monospace">{{ $returnRecord['exchange_tracking_number'] }}</div>
            </div>
            @endif
            @if ($returnRecord['exchange_shipped_at'] ?? null)
            <div>
                <div class="field-label">Shipped at</div>
                <div class="D">{{ $returnRecord['exchange_shipped_at'] }}</div>
            </div>
            @endif
            @if ($returnRecord['exchange_completed_at'] ?? null)
            <div>
                <div class="field-label">Completed at</div>
                <div class="D">{{ $returnRecord['exchange_completed_at'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card fu d2 section-gap" id="return-handling-panel">
        <h4 class="panel-title">Handling</h4>
        <div class="form-grid form-grid-2" style="gap:20px">
            <div>
                <div class="field-label">Item received</div>
                <div class="D">{{ $returnRecord['received_at'] ?? '—' }}</div>
            </div>
            <div>
                <div class="field-label">Inspection result</div>
                <div class="D">
                    @if ($inspection)
                        <span class="badge {{ $inspection === 'passed' ? 'badge-green' : ($inspection === 'failed' ? 'badge-red' : 'badge-yellow') }}">{{ $returnRecord['inspection_result_label'] }}</span>
                        <span class="field-hint">{{ $returnRecord['inspected_at'] ?? '' }}</span>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <div class="field-label">Restock</div>
                <div class="D">
                    @if ($returnRecord['restocked_at'] ?? null)
                        Restocked on {{ $returnRecord['restocked_at'] }}
                    @elseif ($inspection)
                        Not restocked
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <div class="field-label">Reviewed by</div>
                <div class="D">{{ $returnRecord['reviewed_by'] ?? '—' }}@if ($returnRecord['reviewed_at'] ?? null) <span class="field-hint">· {{ $returnRecord['reviewed_at'] }}</span>@endif</div>
            </div>
            @if ($returnRecord['forwarded_at'] ?? null)
            <div>
                <div class="field-label">Forwarded to merchant</div>
                <div class="D">{{ $returnRecord['forwarded_at'] }}</div>
            </div>
            @endif
            @if ($returnRecord['cancelled_at'] ?? null)
            <div>
                <div class="field-label">Withdrawn by the customer</div>
                <div class="D">{{ $returnRecord['cancelled_at'] }}</div>
            </div>
            @endif
        </div>

        @if ($returnRecord['inspection_notes'] ?? false)
        <div style="margin-top:12px">
            <div class="field-label">Inspection notes</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6">
                {{ $returnRecord['inspection_notes'] }}
            </div>
        </div>
        @endif
    </div>

    <div class="card fu d3 section-gap" id="return-refunds-panel">
        <h4 class="panel-title">Refunds</h4>
        @include('livewire.admin.order.partials.refunds-table', [
            'refunds' => $returnRecord['refunds'] ?? [],
            'canManage' => true,
            'showReturnLink' => false,
            'emptyText' => 'No refund has been issued for this request yet.',
        ])
    </div>

    {{-- Notes Thread --}}
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
        <div>
            <label class="field-label">Add Note</label>
            <textarea wire:model="noteText" rows="4"
                class="input" placeholder="Add an internal note about this return…"
                style="resize:vertical"></textarea>
        </div>
        <div style="margin-top:10px">
            <x-btn type="button" wire:click="addNote">Add Note</x-btn>
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
    @if ($showApproveModal && $can('approve'))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Approve Return</h3>
            @if (!empty($returnRecord['is_exchange']))
                <p style="color:var(--muted);margin-bottom:12px;font-size:13px">Approving reserves the replacement stock.</p>
            @endif
            <label class="field-label">Note to the customer (optional)</label>
            <textarea wire:model="approveNote" rows="3" maxlength="2000" class="input" style="resize:vertical"></textarea>
            @error('approveNote')<div class="field-error">{{ $message }}</div>@enderror
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" wire:click="$set('showApproveModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="approve">Approve</x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if ($showRejectModal && $can('reject'))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Reject Return</h3>
            <p style="color:var(--muted);margin-bottom:16px;font-size:13px">
                Explain the rejection reason — this note is visible to the customer.
            </p>
            <textarea wire:model="rejectReason" rows="3" maxlength="2000" class="input" placeholder="Reason for rejection…" style="resize:vertical"></textarea>
            @error('rejectReason')<div class="field-error">{{ $message }}</div>@enderror
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" wire:click="$set('showRejectModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="reject" style="background:var(--red);color:#fff">Confirm Reject</x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Request Info Modal --}}
    @if ($showInfoModal && $can('request_info'))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Request More Information</h3>
            <textarea wire:model="infoMessage" rows="3" maxlength="2000" class="input" placeholder="What do you need from the customer?" style="resize:vertical"></textarea>
            @error('infoMessage')<div class="field-error">{{ $message }}</div>@enderror
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" wire:click="$set('showInfoModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="requestMoreInfo">Send Request</x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Inspection Modal --}}
    @if ($showInspectModal && $can('inspect'))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:460px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:16px">Record Inspection</h3>
            <div style="margin-bottom:14px">
                <label class="field-label">Inspection result <span style="color:var(--red)">*</span></label>
                <x-select wire:model="inspectionResult">
                    <option value="">— select a result —</option>
                    @foreach ($inspectionResults as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                @error('inspectionResult')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:14px">
                <label class="field-label">Inspection notes</label>
                <textarea wire:model="inspectionNotes" rows="3" maxlength="2000" class="input" style="resize:vertical"></textarea>
                @error('inspectionNotes')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:16px">
                <x-checkbox wire:model="restock" label="Put the returned units back into stock" />
                <p class="field-hint">Only applies when the item passed or was partially accepted.</p>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <x-btn type="button" wire:click="$set('showInspectModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="inspect">Save Inspection</x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Issue Refund Modal --}}
    @if ($showRefundModal && $can('issue_refund'))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:440px;max-width:95vw;padding:24px" id="admin-return-refund-modal">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Issue Refund</h3>
            @if ($breakdown)
                <div class="details-list" style="margin-bottom:16px">
                    <div class="details-kv"><span class="details-label">Items</span><span class="details-value">${{ number_format((float) $breakdown['items_amount'], 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Shipping</span><span class="details-value">${{ number_format((float) $breakdown['shipping_amount'], 2) }}</span></div>
                    <div class="details-kv"><span class="details-label">Return fee</span><span class="details-value">−${{ number_format((float) $breakdown['return_fee'], 2) }}@if (!empty($breakdown['fee_waived'])) <span class="field-hint">(waived)</span>@endif</span></div>
                    <div class="details-kv"><span class="details-label">Maximum refund</span><span class="details-value" style="font-weight:700">${{ number_format((float) $breakdown['max'], 2) }}</span></div>
                </div>
            @endif
            <label class="field-label">Refund amount ($)</label>
            <input type="number" wire:model="refundAmount" class="input" min="0.01" step="0.01"
                @if ($breakdown) max="{{ number_format((float) $breakdown['max'], 2, '.', '') }}" @endif>
            <p class="field-hint">You can lower the amount (e.g. after a partial inspection) but not raise it above the maximum.</p>
            @error('refundAmount')<div class="field-error">{{ $message }}</div>@enderror
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" wire:click="$set('showRefundModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="issueRefund" wire:loading.attr="disabled" wire:target="issueRefund" style="background:var(--green);color:#fff">Issue Refund</x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Replacement Shipped Modal --}}
    @if ($showShipModal && $can('mark_exchange_shipped'))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:420px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Mark Replacement Shipped</h3>
            <label class="field-label">Tracking number <span style="color:var(--red)">*</span></label>
            <input type="text" wire:model="trackingNumber" class="input" maxlength="190">
            @error('trackingNumber')<div class="field-error">{{ $message }}</div>@enderror
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" wire:click="$set('showShipModal', false)" variant="secondary">Cancel</x-btn>
                <x-btn type="button" wire:click="markExchangeShipped">Mark Shipped</x-btn>
            </div>
        </div>
    </div>
    @endif

</main>
