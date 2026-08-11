<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Localization</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage ISO currency metadata and the USD-based conversion rate used for tenant
                    sync.</p>
            </div>
            <div class="page-actions"><a href="{{ route('admin.settings.currencies') }}" class="btn btn-secondary">Back
                    to Currencies</a><x-btn type="submit">Save Currency</x-btn></div>
        </div>
        <x-card-collapse title="Currency Details"
            subtitle="ISO code, label, symbol, default selection, and current USD conversion rate."
            class="form-card section-gap" :start-open="true">
            <div class="form-grid form-grid-2">
                <div><label class="field-label">Currency Code</label><x-input type="text" maxlength="3"
                        wire:model.defer="code" />@error('code')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Currency Name</label><x-input type="text"
                        wire:model.defer="name" />@error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Symbol</label><x-input type="text" maxlength="12"
                        wire:model.defer="sign" />@error('sign')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div><label class="field-label">Conversion Rate From USD</label><x-input type="number" step="0.000001"
                        min="0.000001" wire:model.defer="conversionRate" />@error('conversionRate')<div
                        class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field-flag-grid span-2"><label class="toggle-field"><input type="checkbox"
                            wire:model.defer="isDefault"><span>Set as default currency</span></label></div>
            </div>
        </x-card-collapse>
    </form>
</main>