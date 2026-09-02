<div>
    @include('livewire.admin.pages.list-page')

    <x-modal wire:model="showPayoutModal" title="Issue Payout" maxWidth="lg" closeAction="closeModal">
        <form wire:submit="issuePayout" class="page-stack">
            <p class="panel-copy">
                Paying out to <strong>{{ $payoutAffiliateName }}</strong> —
                available balance: <strong>${{ number_format((float) $payoutAffiliateBalance, 2) }}</strong>
            </p>

            <div class="form-grid-2">
                <div>
                    <label class="field-label">Amount</label>
                    <x-input type="number" step="0.01" min="0.01" wire:model="payoutAmount" placeholder="0.00" />
                </div>
                <div>
                    <label class="field-label">Method</label>
                    <x-select wire:model="payoutMethod">
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="manual">Manual</option>
                    </x-select>
                </div>
                <div>
                    <label class="field-label">Reference</label>
                    <x-input type="text" wire:model="payoutReference" placeholder="Transaction reference" />
                </div>
                <div>
                    <label class="field-label">Notes</label>
                    <x-input type="text" wire:model="payoutNotes" placeholder="Optional notes" />
                </div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="issuePayout">
                    <span wire:loading.remove wire:target="issuePayout">Confirm Payout</span>
                    <span wire:loading wire:target="issuePayout">Processing…</span>
                </x-btn>
            </div>
        </form>
    </x-modal>
</div>
