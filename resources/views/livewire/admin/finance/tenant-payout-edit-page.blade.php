<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                <span class="page-badge">{{ $badge }}</span>
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('admin.finance.payouts.index') }}">Back to Payouts</a>
        </div>
    </div>

    <div class="card fu d2 section-gap">
        <h3 class="panel-title">
            Payout {{ $invoiceNumber }}
            <span class="status-pill {{ $originalStatus === 'paid' ? 'badge-green' : ($originalStatus === 'cancelled' ? 'badge-red' : 'badge-amber') }}">
                {{ ucwords($originalStatus) }}
            </span>
        </h3>

        @if (!$editable)
            <p class="panel-copy">This payout is {{ $originalStatus }} and can no longer be edited.</p>
        @endif

        <form wire:submit="save" class="page-stack">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Invoice Number</label>
                    <x-input value="{{ $invoiceNumber }}" disabled />
                </div>
                <div>
                    <label class="field-label">Amount</label>
                    <x-input type="number" step="0.01" wire:model.defer="amount" :error="$errors->has('amount')" :disabled="!$editable" />
                    @error('amount')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">Method</label>
                    <x-select wire:model.live="method" :disabled="!$editable">
                        <option value="bank">Bank transfer</option>
                        <option value="gateway">Payment gateway</option>
                    </x-select>
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <x-select wire:model.defer="status" :error="$errors->has('status')" :disabled="!$editable">
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                    </x-select>
                    @error('status')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">Paid At</label>
                    <x-input type="datetime-local" wire:model.defer="paidAt" :error="$errors->has('paidAt')" :disabled="!$editable" />
                </div>
                <div></div>

                @if ($method === 'bank')
                    <div>
                        <label class="field-label">Bank Name</label>
                        <x-input wire:model.defer="bankName" :error="$errors->has('bankName')" :disabled="!$editable" />
                        @error('bankName')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Bank Account / IBAN <span
                                class="entity-subtitle">(optional)</span></label>
                        <x-input wire:model.defer="bankAccount" :disabled="!$editable" />
                    </div>
                @else
                    <div class="span-2">
                        <label class="field-label">Gateway Name</label>
                        <x-input wire:model.defer="gatewayName" :error="$errors->has('gatewayName')"
                            placeholder="Stripe, PayPal, Wise..." :disabled="!$editable" />
                        @error('gatewayName')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="span-2">
                    <label class="field-label">Transaction Reference <span
                            class="entity-subtitle">(optional)</span></label>
                    <x-input wire:model.defer="transactionReference" placeholder="Bank wire ID, gateway txn ID..." :disabled="!$editable" />
                </div>

                <div class="span-2">
                    <label class="field-label">Note <span class="entity-subtitle">(optional)</span></label>
                    <x-textarea rows="3" wire:model.defer="note" :disabled="!$editable" />
                </div>
            </div>

            <div class="page-actions justify-end">
                <a class="btn btn-secondary" href="{{ route('admin.finance.payouts.index') }}">Cancel</a>

                @if ($originalStatus === 'paid')
                    <x-btn type="button" variant="secondary" class="btn-danger" wire:click="cancelPayout"
                        wire:confirm="Cancel this payout? This will reverse the payout_released flag on its orders and exclude it from future balances.">
                        Cancel Payout
                    </x-btn>
                @elseif ($editable)
                    <x-btn type="submit">Save Changes</x-btn>
                @endif
            </div>
        </form>
    </div>
</main>
