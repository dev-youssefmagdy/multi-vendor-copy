@php
    $fmt = fn($v) => '$' . number_format((float) ($v ?? 0), 2);
    $entry = $ledgerEntry;
@endphp

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
            <a class="btn btn-secondary" href="{{ route('admin.wallets.index') }}">Back to Ledger</a>
        </div>
    </div>

    <div class="g-stats3 section-gap">
        <div class="card">
            <div class="eyebrow">Tenant</div>
            <div class="D stat-value">{{ $entry->store_name ?? '—' }}</div>
            <p class="panel-copy">{{ $entry->tenant_email ?? $entry->tenant_id }}</p>
        </div>
        <div class="card card-glow-amber">
            <div class="eyebrow">Outstanding to Tenant</div>
            <div class="D stat-value">{{ $fmt($entry->central_owes_tenant ?? 0) }}</div>
            <p class="panel-copy">vendor_net − payouts_sent</p>
        </div>
        <div class="card">
            <div class="eyebrow">Already Paid Out</div>
            <div class="D stat-value">{{ $fmt($entry->payouts_sent ?? 0) }}</div>
            <p class="panel-copy">Σ tenant_payouts.amount</p>
        </div>
    </div>

    <div class="card fu d2 section-gap">
        <h3 class="panel-title">Payout Details</h3>
        <form wire:submit="save" class="page-stack">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Invoice Number</label>
                    <x-input wire:model.defer="invoiceNumber" :error="$errors->has('invoiceNumber')" />
                    @error('invoiceNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Amount</label>
                    <x-input type="number" step="0.01" wire:model.defer="amount" :error="$errors->has('amount')" />
                    @error('amount')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">Method</label>
                    <x-select wire:model.live="method">
                        <option value="bank">Bank transfer</option>
                        <option value="gateway">Payment gateway</option>
                    </x-select>
                </div>
                <div>
                    <label class="field-label">Status</label>
                    <x-select wire:model.defer="status" :error="$errors->has('status')">
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                    </x-select>
                    @error('status')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Paid At</label>
                    <x-input type="datetime-local" wire:model.defer="paidAt" :error="$errors->has('paidAt')" />
                </div>

                @if ($method === 'bank')
                    <div>
                        <label class="field-label">Bank Name</label>
                        <x-input wire:model.defer="bankName" :error="$errors->has('bankName')" />
                        @error('bankName')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">Bank Account / IBAN <span
                                class="entity-subtitle">(optional)</span></label>
                        <x-input wire:model.defer="bankAccount" />
                    </div>
                @else
                    <div class="span-2">
                        <label class="field-label">Gateway Name</label>
                        <x-input wire:model.defer="gatewayName" :error="$errors->has('gatewayName')"
                            placeholder="Stripe, PayPal, Wise..." />
                        @error('gatewayName')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                @endif

                <div class="span-2">
                    <label class="field-label">Transaction Reference <span
                            class="entity-subtitle">(optional)</span></label>
                    <x-input wire:model.defer="transactionReference" placeholder="Bank wire ID, gateway txn ID..." />
                </div>

                <div class="span-2">
                    <label class="field-label">Note <span class="entity-subtitle">(optional)</span></label>
                    <x-textarea rows="3" wire:model.defer="note" />
                </div>

                <div class="span-2">
                    <x-checkbox wire:model.defer="sendEmail" label="Send confirmation email to tenant admin" />
                </div>
            </div>

            <div class="page-actions justify-end">
                <a class="btn btn-secondary" href="{{ route('admin.wallets.index') }}">Cancel</a>
                <x-btn type="submit">Record Payout</x-btn>
            </div>
        </form>
    </div>
</main>