{{-- Admin order page modals: cancel order (reason → confirm) and manual refund. --}}
@if ($showCancelModal && !empty($afterSales['can_cancel']))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:480px;max-width:95vw;padding:24px" id="admin-cancel-modal">
            @if ($cancelStep === 1)
                <h3 class="D" style="font-size:16px;margin-bottom:4px">Cancel order</h3>
                <p style="color:var(--muted);font-size:13px;margin-bottom:20px">
                    Order #{{ $orderNumber }} · {{ $order['tenant']['name'] ?? '' }}. Stock is restored and paid orders are refunded according to the store's policy.
                </p>
                <div style="margin-bottom:14px">
                    <label class="field-label">Reason <span style="color:var(--red)">*</span></label>
                    <x-select wire:model="cancelReason">
                        <option value="">— select a reason —</option>
                        @foreach ($afterSales['cancel_reasons'] ?? [] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    @error('cancelReason')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div style="margin-bottom:20px">
                    <label class="field-label">Note @if ($cancelReason === 'other')<span style="color:var(--red)">*</span>@endif</label>
                    <textarea wire:model="cancelNote" rows="3" maxlength="1000" class="input" style="resize:vertical"
                        placeholder="Required when the reason is “Other”. Shown to the customer."></textarea>
                    @error('cancelNote')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <x-btn type="button" variant="secondary" wire:click="closeCancelModal">Close</x-btn>
                    <x-btn type="button" wire:click="continueCancel">Continue</x-btn>
                </div>
            @else
                <h3 class="D" style="font-size:16px;margin-bottom:8px">Are you sure?</h3>
                <p style="color:var(--muted);font-size:13px;margin-bottom:12px">
                    Are you sure you want to cancel order #{{ $orderNumber }}? This cannot be undone.
                </p>
                <div style="background:var(--elevated);border:1px solid var(--border);border-radius:8px;padding:10px 14px;margin-bottom:20px;font-size:13px">
                    <strong>{{ $afterSales['cancel_reasons'][$cancelReason] ?? $cancelReason }}</strong>
                    @if (filled($cancelNote))
                        <div style="color:var(--muted);margin-top:4px">{{ $cancelNote }}</div>
                    @endif
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <x-btn type="button" variant="secondary" wire:click="backToCancelReason">Back</x-btn>
                    <x-btn type="button" wire:click="cancelOrder" wire:loading.attr="disabled" wire:target="cancelOrder"
                        style="background:var(--red);color:#fff">
                        <span wire:loading.remove wire:target="cancelOrder">Confirm cancellation</span>
                        <span wire:loading wire:target="cancelOrder">Cancelling…</span>
                    </x-btn>
                </div>
            @endif
        </div>
    </div>
@endif

@if ($showRefundModal && !empty($afterSales['can_refund']))
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center">
        <div class="card" style="width:440px;max-width:95vw;padding:24px" id="admin-refund-modal">
            <h3 class="D" style="font-size:16px;margin-bottom:4px">Refund</h3>
            <p style="color:var(--muted);font-size:13px;margin-bottom:20px">
                Up to ${{ number_format((float) ($afterSales['refundable_amount'] ?? 0), 2) }} can still be refunded on this order.
            </p>
            <div style="margin-bottom:14px">
                <label class="field-label">Amount ($) <span style="color:var(--red)">*</span></label>
                <input type="number" wire:model="refundAmount" class="input" min="0.01" step="0.01"
                    max="{{ number_format((float) ($afterSales['refundable_amount'] ?? 0), 2, '.', '') }}">
                @error('refundAmount')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:20px">
                <label class="field-label">Reason <span style="color:var(--red)">*</span></label>
                <input type="text" wire:model="refundReason" class="input" maxlength="255">
                @error('refundReason')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <x-btn type="button" variant="secondary" wire:click="closeRefundModal">Cancel</x-btn>
                <x-btn type="button" wire:click="issueRefund" wire:loading.attr="disabled" wire:target="issueRefund">Issue refund</x-btn>
            </div>
        </div>
    </div>
@endif
