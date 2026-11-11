    <x-modal wire:model="showSettingsModal" title="Affiliate Program Settings" maxWidth="lg" closeAction="closeModal">
        <form wire:submit="saveSettings" class="page-stack">
            <div class="form-grid-2">
                <div>
                    <label class="field-label">Attribution window (days)</label>
                    <x-input type="number" min="1" wire:model="settingCookieDays" />
                    @error('settingCookieDays') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="field-label">Minimum withdrawal amount ($)</label>
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
