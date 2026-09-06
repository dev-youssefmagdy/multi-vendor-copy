<x-modal wire:model="showTestEmailModal" title="Send Test Email" maxWidth="md" closeAction="closeTestEmailModal">
    <form wire:submit="sendTestEmail" class="space-y-5">
        <div>
            <label class="field-label">Recipient Email</label>
            <x-input type="email" wire:model.defer="testEmailAddress" placeholder="you@example.com"
                :error="$errors->has('testEmailAddress')" />
            @error('testEmailAddress') <p class="field-error">{{ $message }}</p> @enderror
            <p class="field-hint mt-1">A test email will be sent using the current (unsaved) settings above, falling back to the central mail configuration for any blank fields.</p>
        </div>

        <div class="modal-footer-actions">
            <x-btn type="button" variant="secondary" wire:click="closeTestEmailModal">Cancel</x-btn>
            <x-btn type="submit" wire:loading.attr="disabled" wire:target="sendTestEmail">
                <span wire:loading.remove wire:target="sendTestEmail">Send Test Email</span>
                <span wire:loading wire:target="sendTestEmail">Sending…</span>
            </x-btn>
        </div>
    </form>
</x-modal>
