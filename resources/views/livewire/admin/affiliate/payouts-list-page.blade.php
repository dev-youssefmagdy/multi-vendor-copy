<div>

    @include('livewire.admin.pages.list-page')

    @include('livewire.admin.affiliate.partials.settings-modal')

    <x-modal wire:model="showPayModal" title="Mark Withdrawal Paid" maxWidth="lg" closeAction="closeModal">
        <form wire:submit="markPaid" class="page-stack" enctype="multipart/form-data">
            <p class="panel-copy">
                Paying <strong>{{ $requestAffiliateName }}</strong> —
                available balance: <strong>${{ number_format((float) $requestBalance, 2) }}</strong>
                @if ($requestNote !== '')<br>Affiliate note: <em>{{ $requestNote }}</em>@endif
            </p>
            <p class="field-hint">Send the money to the affiliate first, then upload the transfer receipt here. The amount is deducted from their balance.</p>

            <div class="form-grid-2">
                <div>
                    <label class="field-label">Amount</label>
                    <x-input type="number" step="0.01" min="0.01" wire:model="payAmount" placeholder="0.00" />
                    @error('payAmount') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="field-label">Reference</label>
                    <x-input type="text" wire:model="payReference" placeholder="Transaction reference" />
                    @error('payReference') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="field-label">Notes</label>
                <x-textarea rows="3" wire:model="payNotes" placeholder="Optional notes (visible to the affiliate)" />
                @error('payNotes') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="field-label">Receipt (image or PDF)</label>
                <input type="file" wire:model="payReceipt" class="input" accept="image/*,application/pdf" />
                @error('payReceipt') <span class="field-error">{{ $message }}</span> @enderror
                <div wire:loading wire:target="payReceipt" class="field-hint">Uploading…</div>
            </div>

            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="markPaid,payReceipt">
                    <span wire:loading.remove wire:target="markPaid">Confirm Payment</span>
                    <span wire:loading wire:target="markPaid">Processing…</span>
                </x-btn>
            </div>
        </form>
    </x-modal>

    <x-modal wire:model="showRejectModal" title="Reject Withdrawal Request" maxWidth="lg" closeAction="closeModal">
        <form wire:submit="rejectRequest" class="page-stack">
            <p class="panel-copy">
                Reject the <strong>${{ number_format((float) $payAmount, 2) }}</strong> request from <strong>{{ $requestAffiliateName }}</strong>?
                Their balance is not changed and they can submit a new request.
            </p>
            <div>
                <label class="field-label">Reason (optional, visible to the affiliate)</label>
                <x-textarea rows="3" wire:model="rejectNote" />
                @error('rejectNote') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                <x-btn type="submit" wire:loading.attr="disabled" wire:target="rejectRequest">Reject Request</x-btn>
            </div>
        </form>
    </x-modal>
</div>
