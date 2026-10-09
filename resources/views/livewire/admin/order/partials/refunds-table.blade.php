{{-- Refund rows with their actions (RefundPresenter::panel() arrays). Shared by the admin order page and the
     admin return page; the component uses App\Livewire\Admin\Concerns\ManagesRefundActions.
     Params: $refunds (array), $canManage (bool), $showReturnLink (bool, default true), $emptyText. --}}
@php
    $refunds = $refunds ?? [];
    $canManage = $canManage ?? false;
    $showReturnLink = $showReturnLink ?? true;
    $emptyText = $emptyText ?? 'No refunds have been issued for this order yet.';
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('M d, Y H:i') : '—';
    $money = fn ($refund, $value) => ($refund['currency'] ?? 'USD') . ' ' . number_format((float) $value, 2);
@endphp

@if (empty($refunds))
    <p class="field-hint" data-refunds-empty>{{ $emptyText }}</p>
@else
    <x-table :headers="['Reference', 'Source', 'Amount', 'Method', 'Status', 'Dates', 'Actions']">
        @foreach ($refunds as $refund)
            <tr wire:key="refund-row-{{ $refund['id'] }}" data-refund-row="{{ $refund['id'] }}">
                <td>
                    <div class="entity-title" style="font-family:ui-monospace,monospace">{{ $refund['reference'] }}</div>
                    @if ($showReturnLink && !empty($refund['return_request_id']))
                        <a class="link-btn" href="{{ route('admin.orders.returns.show', $refund['return_request_id']) }}">Return #{{ $refund['return_request_id'] }}</a>
                    @endif
                </td>
                <td>
                    <div class="entity-title">{{ $refund['source_label'] ?? '—' }}</div>
                    @if (filled($refund['reason'] ?? null))
                        <div class="entity-subtitle">{{ $refund['reason'] }}</div>
                    @endif
                </td>
                <td>
                    <div class="entity-title">{{ $money($refund, $refund['amount']) }}</div>
                    <div class="entity-subtitle">
                        Items {{ number_format((float) $refund['items_amount'], 2) }}
                        · Shipping {{ number_format((float) $refund['shipping_amount'], 2) }}
                        · Fee −{{ number_format((float) $refund['return_fee'], 2) }}
                    </div>
                </td>
                <td>
                    <div class="entity-title">{{ $refund['method_label'] ?? '—' }}</div>
                    @if (filled($refund['gateway'] ?? null))
                        <div class="entity-subtitle">{{ str((string) $refund['gateway'])->replace(['_', '-'], ' ')->headline() }}@if (filled($refund['gateway_refund_id'] ?? null)) · {{ $refund['gateway_refund_id'] }}@endif</div>
                    @endif
                    @if (filled($refund['manual_reference'] ?? null))
                        <div class="entity-subtitle">Reference: {{ $refund['manual_reference'] }}</div>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{ $refund['status_color'] ?? 'gray' }}">{{ $refund['status_label'] ?? '—' }}</span>
                    @if (filled($refund['failure_reason'] ?? null))
                        <div class="entity-subtitle" style="color:var(--red);margin-top:4px">{{ $refund['failure_reason'] }}</div>
                    @endif
                </td>
                <td>
                    <div class="entity-subtitle">Requested: {{ $date($refund['requested_at'] ?? null) }}</div>
                    <div class="entity-subtitle">Processed: {{ $date($refund['processed_at'] ?? null) }}</div>
                    @if (filled($refund['approved_by'] ?? null))
                        <div class="entity-subtitle">Approved by: {{ $refund['approved_by'] }}</div>
                    @endif
                </td>
                <td>
                    @if ($canManage && (!empty($refund['can_retry']) || !empty($refund['can_complete']) || !empty($refund['can_reject'])))
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            @if (!empty($refund['can_retry']))
                                <x-btn type="button" variant="secondary" wire:click="retryRefund({{ $refund['id'] }})"
                                    wire:loading.attr="disabled" wire:target="retryRefund({{ $refund['id'] }})"
                                    wire:confirm="Send this refund to the payment gateway now?">Retry</x-btn>
                            @endif
                            @if (!empty($refund['can_complete']))
                                <x-btn type="button" variant="secondary" wire:click="openCompleteRefund({{ $refund['id'] }})">Mark completed</x-btn>
                            @endif
                            @if (!empty($refund['can_reject']))
                                <x-btn type="button" wire:click="openRejectRefund({{ $refund['id'] }})" style="background:var(--red);color:#fff">Reject</x-btn>
                            @endif
                        </div>
                    @else
                        <span class="entity-subtitle">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-table>
@endif

{{-- Mark completed (manual) modal --}}
@if ($canManage && $completeRefundId)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:440px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Mark refund as completed</h3>
            <p style="color:var(--muted);margin-bottom:16px;font-size:13px">
                Use this when the money was returned outside the system (bank transfer, cash, …).
            </p>
            <label class="field-label">External reference (optional)</label>
            <input type="text" wire:model="completeReference" class="input" maxlength="190" placeholder="e.g. bank transfer ID">
            @error('completeReference')<div class="field-error">{{ $message }}</div>@enderror
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" variant="secondary" wire:click="closeCompleteRefund">Cancel</x-btn>
                <x-btn type="button" wire:click="completeRefund" wire:loading.attr="disabled" wire:target="completeRefund">Mark completed</x-btn>
            </div>
        </div>
    </div>
@endif

{{-- Reject modal --}}
@if ($canManage && $rejectRefundId)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:440px;max-width:95vw;padding:24px">
            <h3 class="D" style="font-size:16px;margin-bottom:8px">Reject refund</h3>
            <p style="color:var(--muted);margin-bottom:16px;font-size:13px">The reason is shown to the customer.</p>
            <textarea wire:model="rejectRefundReason" rows="3" maxlength="1000" class="input" placeholder="Reason for rejecting this refund…" style="resize:vertical"></textarea>
            @error('rejectRefundReason')<div class="field-error">{{ $message }}</div>@enderror
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <x-btn type="button" variant="secondary" wire:click="closeRejectRefund">Cancel</x-btn>
                <x-btn type="button" wire:click="rejectRefund" wire:loading.attr="disabled" wire:target="rejectRefund" style="background:var(--red);color:#fff">Reject refund</x-btn>
            </div>
        </div>
    </div>
@endif
