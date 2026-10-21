<div>

    @include('livewire.admin.pages.list-page')

    <x-modal wire:model="showSettingsModal" title="Affiliate Program Settings" maxWidth="lg" closeAction="closeModal">
        <form wire:submit="saveSettings" class="page-stack">
            <div class="form-grid-2">
                <div>
                    <label class="field-label">Attribution window (days)</label>
                    <x-input type="number" min="1" wire:model="settingCookieDays" />
                    @error('settingCookieDays') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="field-label">Minimum payout ($)</label>
                    <x-input type="number" step="0.01" min="0" wire:model="settingMinPayout" />
                    @error('settingMinPayout') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="field-label">Default commission type (new affiliates)</label>
                    <select wire:model="settingCommissionType" class="input">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount ($)</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">Default commission value</label>
                    <x-input type="number" step="0.01" min="0" wire:model="settingCommissionValue" />
                    @error('settingCommissionValue') <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <p class="field-hint">Changes apply to new clicks and new affiliate sign-ups; existing conversions keep the rate they were created with.</p>
            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                <x-btn type="submit">Save Settings</x-btn>
            </div>
        </form>
    </x-modal>

    <x-modal wire:model="showPayoutModal" title="Issue Payout" maxWidth="lg" closeAction="closeModal">
        <form wire:submit="issuePayout" class="page-stack" enctype="multipart/form-data">
            <p class="panel-copy">
                Paying out to <strong>{{ $payoutAffiliateName }}</strong> —
                available balance: <strong>${{ number_format((float) $payoutAffiliateBalance, 2) }}</strong>
                @if ($payoutRequestId) <span class="badge badge-amber">Responding to a payout request</span> @endif
            </p>

            <div class="form-grid-2">
                <div>
                    <label class="field-label">Amount</label>
                    <x-input type="number" step="0.01" min="0.01" wire:model="payoutAmount" placeholder="0.00" />
                </div>
                <div>
                    <label class="field-label">Reference</label>
                    <x-input type="text" wire:model="payoutReference" placeholder="Transaction reference" />
                </div>
            </div>

            <div>
                <label class="field-label">Notes</label>
                <textarea
                    rows="4"
                    wire:model="payoutNotes"
                    placeholder="Optional notes"
                    style="padding: 7px 11px!important;"
                    class="w-full bg-[var(--input)] border border-[var(--border)] rounded-[8px] text-[13px] text-[var(--t1)]
                        outline-none transition-all duration-200 shadow-sm placeholder:text-[var(--t3)] resize-none
                        focus:border-[var(--cyan)] focus:ring-1 focus:ring-[var(--cyan)]/50 hover:border-gray-400/80"
                ></textarea>
            </div>

            <div>
                <label class="field-label">Attachment</label>
                <input type="file" wire:model="payoutAttachment" class="input" />
                @error('payoutAttachment') <span class="field-error">{{ $message }}</span> @enderror
                <div wire:loading wire:target="payoutAttachment" class="field-hint">Uploading…</div>
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
