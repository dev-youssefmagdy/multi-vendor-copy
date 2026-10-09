@extends('tenant.layouts.app')

@section('title', 'Return #' . $returnRecord['id'])

@section('content')
    @php
        $actions = $returnRecord['available_actions'] ?? [];
        $can = fn (string $action) => in_array($action, $actions, true);
        $replacement = $returnRecord['replacement'] ?? null;
        $breakdown = $returnRecord['refund_breakdown'] ?? null;
        $inspection = $returnRecord['inspection_result'] ?? null;
    @endphp

    <x-tenant::page-header :title="'Return #' . $returnRecord['id']" badge="Return Management" description="Review this customer return request, respond, and take action.">
        <x-slot:actions>
            <a class="btn btn-secondary" href="{{ route('tenant.returns.index') }}">← Returns</a>
            @if(!empty($orderId))
                <a class="btn btn-secondary" target="_blank" href="{{ route('tenant.orders.show', $orderId) }}">View Order</a>
            @endif
        </x-slot:actions>
    </x-tenant::page-header>

    <div class="card form-card fu d1 section-gap">
        <h4 class="panel-title">Return Status</h4>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <x-tenant::status-badge :status="$returnRecord['status']" />
            @if(!empty($returnRecord['type_label']))
                <span class="badge badge-{{ !empty($returnRecord['is_exchange']) ? 'cyan' : 'gray' }}">{{ $returnRecord['type_label'] }}</span>
            @endif

            @if($can('approve'))
                <button type="button" class="btn" style="background:var(--blue);color:#fff" data-modal-open="approve-modal">{{ __('Approve') }}</button>
            @endif
            @if($can('reject'))
                <button type="button" class="btn" style="background:var(--red);color:#fff" data-modal-open="reject-modal">{{ __('Reject') }}</button>
            @endif
            @if($can('request_info'))
                <button type="button" class="btn btn-secondary" data-modal-open="info-modal">{{ __('Request More Info') }}</button>
            @endif
            @if($can('mark_received'))
                <button type="button" class="btn" style="background:var(--blue);color:#fff"
                    data-action-url="{{ route('tenant.returns.received', $returnRecord['id']) }}"
                    data-action-method="POST"
                    data-confirm="{{ __('Mark the returned item as received?') }}"
                    data-success="reload-page">{{ __('Mark Item Received') }}</button>
            @endif
            @if($can('inspect'))
                <button type="button" class="btn" style="background:var(--blue);color:#fff" data-modal-open="inspect-modal">{{ __('Record Inspection') }}</button>
            @endif
            @if($can('issue_refund'))
                <button type="button" class="btn" style="background:var(--green);color:#fff" data-modal-open="issue-refund-modal">{{ __('Issue Refund') }}</button>
            @endif
            @if($can('mark_exchange_shipped'))
                <button type="button" class="btn" style="background:var(--blue);color:#fff" data-modal-open="exchange-shipped-modal">{{ __('Mark Replacement Shipped') }}</button>
            @endif
            @if($can('mark_exchange_completed'))
                <button type="button" class="btn" style="background:var(--green);color:#fff"
                    data-action-url="{{ route('tenant.returns.exchange-completed', $returnRecord['id']) }}"
                    data-action-method="POST"
                    data-confirm="{{ __('Mark this exchange as completed?') }}"
                    data-success="reload-page">{{ __('Complete Exchange') }}</button>
            @endif
            @if($can('convert_to_refund'))
                <button type="button" class="btn btn-secondary"
                    data-action-url="{{ route('tenant.returns.convert-to-refund', $returnRecord['id']) }}"
                    data-action-method="POST"
                    data-confirm="{{ __('Convert this exchange to a refund? Any reserved replacement stock is released.') }}"
                    data-confirm-danger
                    data-success="reload-page">{{ __('Convert to Refund') }}</button>
            @endif
            @if($can('close'))
                <button type="button" class="btn btn-secondary"
                    data-action-url="{{ route('tenant.returns.close', $returnRecord['id']) }}"
                    data-action-method="POST"
                    data-confirm="{{ __('Close this return request?') }}"
                    data-success="reload-page">{{ __('Close Request') }}</button>
            @endif
        </div>
    </div>

    <div class="card form-card fu d2 section-gap">
        <h4 class="panel-title">Return Details</h4>
        <div class="form-grid form-grid-2">
            <div>
                <div class="field-label">Order Number</div>
                <div class="D" style="font-family:ui-monospace,monospace;font-size:15px">{{ $returnRecord['order_number'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">Submitted</div>
                <div class="D">{{ $returnRecord['created_at'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">{{ __('Type') }}</div>
                <div class="D">{{ $returnRecord['type_label'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">{{ __('Item') }}</div>
                <div class="D">{{ $returnRecord['item'] ?? '-' }}</div>
            </div>
            <div>
                <div class="field-label">{{ __('Quantity') }}</div>
                <div class="D">{{ $returnRecord['quantity'] ?? 1 }}</div>
            </div>
            <div>
                <div class="field-label">{{ __('Return method') }}</div>
                <div class="D">{{ $returnRecord['return_method'] ?? '-' }}</div>
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

        @if($returnRecord['customer_note'] ?? false)
        <div style="margin-top:12px">
            <div class="field-label">{{ __('Customer note') }}</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6">
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

    @if($replacement)
    <div class="card form-card fu d2 section-gap" id="return-exchange-panel">
        <h4 class="panel-title">{{ __('Exchange') }}</h4>
        <div class="form-grid form-grid-2">
            <div>
                <div class="field-label">{{ __('Replacement') }}</div>
                <div class="D">{{ $replacement['label'] }} × {{ $replacement['quantity'] }}</div>
            </div>
            <div>
                <div class="field-label">{{ __('Available stock') }}</div>
                <div class="D">
                    @if($replacement['stock'] === null)
                        {{ __('Unlimited') }}
                    @else
                        <span @if($replacement['stock'] < $replacement['quantity'] && !$replacement['reserved']) style="color:var(--red);font-weight:600" @endif>{{ $replacement['stock'] }}</span>
                    @endif
                    @if($replacement['reserved'])
                        <span class="badge badge-blue" style="margin-inline-start:6px">{{ __('Reserved') }}</span>
                    @endif
                </div>
            </div>
            @if($returnRecord['exchange_tracking_number'] ?? null)
            <div>
                <div class="field-label">{{ __('Tracking number') }}</div>
                <div class="D" style="font-family:ui-monospace,monospace">{{ $returnRecord['exchange_tracking_number'] }}</div>
            </div>
            @endif
            @if($returnRecord['exchange_shipped_at'] ?? null)
            <div>
                <div class="field-label">{{ __('Shipped at') }}</div>
                <div class="D">{{ $returnRecord['exchange_shipped_at'] }}</div>
            </div>
            @endif
            @if($returnRecord['exchange_completed_at'] ?? null)
            <div>
                <div class="field-label">{{ __('Completed at') }}</div>
                <div class="D">{{ $returnRecord['exchange_completed_at'] }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="card form-card fu d2 section-gap" id="return-handling-panel">
        <h4 class="panel-title">{{ __('Handling') }}</h4>
        <div class="form-grid form-grid-2">
            <div>
                <div class="field-label">{{ __('Item received') }}</div>
                <div class="D">{{ $returnRecord['received_at'] ?? '—' }}</div>
            </div>
            <div>
                <div class="field-label">{{ __('Inspection result') }}</div>
                <div class="D">
                    @if($inspection)
                        <x-tenant::status-badge :status="$inspection" />
                        <span class="entity-subtitle">{{ $returnRecord['inspected_at'] ?? '' }}</span>
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <div class="field-label">{{ __('Restock') }}</div>
                <div class="D">
                    @if($returnRecord['restocked_at'] ?? null)
                        {{ __('Restocked on :date', ['date' => $returnRecord['restocked_at']]) }}
                    @elseif($inspection)
                        {{ __('Not restocked') }}
                    @else
                        —
                    @endif
                </div>
            </div>
            <div>
                <div class="field-label">{{ __('Reviewed by') }}</div>
                <div class="D">{{ $returnRecord['reviewed_by'] ?? '—' }}@if($returnRecord['reviewed_at'] ?? null) <span class="entity-subtitle">· {{ $returnRecord['reviewed_at'] }}</span>@endif</div>
            </div>
            @if($returnRecord['forwarded_at'] ?? null)
            <div>
                <div class="field-label">{{ __('Forwarded by support') }}</div>
                <div class="D">{{ $returnRecord['forwarded_at'] }}</div>
            </div>
            @endif
            @if($returnRecord['cancelled_at'] ?? null)
            <div>
                <div class="field-label">{{ __('Withdrawn by the customer') }}</div>
                <div class="D">{{ $returnRecord['cancelled_at'] }}</div>
            </div>
            @endif
        </div>

        @if($returnRecord['inspection_notes'] ?? false)
        <div style="margin-top:12px">
            <div class="field-label">{{ __('Inspection notes') }}</div>
            <div class="D" style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:12px 14px;margin-top:4px;line-height:1.6">
                {{ $returnRecord['inspection_notes'] }}
            </div>
        </div>
        @endif
    </div>

    <div class="card form-card fu d3 section-gap" id="return-refunds-panel">
        <h4 class="panel-title">{{ __('Refunds') }}</h4>
        @include('tenant.pages.sales.orders._partials.refund-list', [
            'refunds' => $returnRecord['refunds'] ?? [],
            'canManage' => true,
            'showReturnLink' => false,
        ])
    </div>

    <div class="card form-card fu d3 section-gap">
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
    <div class="card form-card fu d4 section-gap">
        <h4 class="panel-title">Order Summary</h4>
        @include('tenant.pages.sales.orders._partials.details', ['order' => $order])
    </div>
    @endif

    @if($can('approve'))
    <x-tenant::modal id="approve-modal" :title="__('Approve Return')">
        <x-tenant::form action="{{ route('tenant.returns.approve', $returnRecord['id']) }}" validate="{{ route('tenant.returns.approve.validate', $returnRecord['id']) }}" success="reload-page">
            @if(!empty($returnRecord['is_exchange']))
                <p class="panel-copy" style="margin-bottom:12px">{{ __('Approving reserves the replacement stock.') }}</p>
            @endif
            <x-tenant::textarea name="approve_note" :label="__('Note to the customer (optional)')" rows="3" maxlength="2000" />
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Approve') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
    @endif

    @if($can('reject'))
    <x-tenant::modal id="reject-modal" title="Reject Return">
        <x-tenant::form action="{{ route('tenant.returns.reject', $returnRecord['id']) }}" validate="{{ route('tenant.returns.reject.validate', $returnRecord['id']) }}" success="reload-page">
            <textarea name="reject_reason" rows="3" class="input" placeholder="Reason for rejection (shown to customer)…" style="resize:vertical" required>{{ $inspection?->value === 'failed' ? ($returnRecord['inspection_notes'] ?? '') : '' }}</textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn" style="background:var(--red);color:#fff">Confirm Reject</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
    @endif

    @if($can('request_info'))
    <x-tenant::modal id="info-modal" title="Request More Information">
        <x-tenant::form action="{{ route('tenant.returns.request-info', $returnRecord['id']) }}" validate="{{ route('tenant.returns.request-info.validate', $returnRecord['id']) }}" success="reload-page">
            <textarea name="info_message" rows="3" class="input" placeholder="What do you need from the customer?" style="resize:vertical" required></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Send Request</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
    @endif

    @if($can('inspect'))
    <x-tenant::modal id="inspect-modal" :title="__('Record Inspection')">
        <x-tenant::form action="{{ route('tenant.returns.inspect', $returnRecord['id']) }}" validate="{{ route('tenant.returns.inspect.validate', $returnRecord['id']) }}" success="reload-page">
            <x-tenant::select name="inspection_result" :label="__('Inspection result')" :options="$inspectionResults" :placeholder="__('— select a result —')" required />
            <div style="margin-top:12px">
                <x-tenant::textarea name="inspection_notes" :label="__('Inspection notes')" rows="3" maxlength="2000" />
            </div>
            <div style="margin-top:12px">
                <x-tenant::checkbox name="restock" :checked="$restockDefault" :label="__('Put the returned units back into stock')" :description="__('Only applies when the item passed or was partially accepted.')" />
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save Inspection') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
    @endif

    @if($can('issue_refund'))
    <x-tenant::modal id="issue-refund-modal" :title="__('Issue Refund')">
        @if($breakdown)
            <div class="details-list" style="margin-bottom:16px">
                <div class="details-kv"><span class="details-label">{{ __('Items') }}</span><span class="details-value">${{ number_format((float) $breakdown['items_amount'], 2) }}</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Shipping') }}</span><span class="details-value">${{ number_format((float) $breakdown['shipping_amount'], 2) }}</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Return fee') }}</span><span class="details-value">−${{ number_format((float) $breakdown['return_fee'], 2) }}@if(!empty($breakdown['fee_waived'])) <span class="entity-subtitle">({{ __('waived') }})</span>@endif</span></div>
                <div class="details-kv"><span class="details-label">{{ __('Maximum refund') }}</span><span class="details-value" style="font-weight:700">${{ number_format((float) $breakdown['max'], 2) }}</span></div>
            </div>
        @endif
        <x-tenant::form action="{{ route('tenant.returns.issue-refund', $returnRecord['id']) }}" validate="{{ route('tenant.returns.issue-refund.validate', $returnRecord['id']) }}" success="reload-page">
            <x-tenant::input
                name="amount"
                type="number"
                :label="__('Refund amount ($)')"
                :value="$breakdown ? number_format((float) $breakdown['max'], 2, '.', '') : null"
                min="0.01"
                :max="$breakdown ? number_format((float) $breakdown['max'], 2, '.', '') : null"
                step="0.01"
                :help="__('You can lower the amount (e.g. after a partial inspection) but not raise it above the maximum.')"
                required
            />
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn" style="background:var(--green);color:#fff">{{ __('Issue Refund') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
    @endif

    @if($can('mark_exchange_shipped'))
    <x-tenant::modal id="exchange-shipped-modal" :title="__('Mark Replacement Shipped')">
        <x-tenant::form action="{{ route('tenant.returns.exchange-shipped', $returnRecord['id']) }}" validate="{{ route('tenant.returns.exchange-shipped.validate', $returnRecord['id']) }}" success="reload-page">
            <x-tenant::input name="tracking_number" :label="__('Tracking number')" maxlength="190" required />
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Mark Shipped') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
    @endif
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/sales/return-show.js')
@endpush
