<main id="mn">

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">Compliance Center</h1>
                <span class="page-badge">Compliance</span>
            </div>
            <p class="page-copy">Owner details, business registration, and bank information required to keep your store in good standing.</p>
        </div>
        <div class="page-actions">
            <x-btn type="submit" form="compliance-center-form">Save Changes</x-btn>
        </div>
    </div>

    <form id="compliance-center-form" wire:submit="save">

        <section class="card form-card fu d1 section-gap">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Owner Details</h3>
                    <p class="panel-copy">The legal owner or representative of this store.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Owner Full Name</label>
                    <x-input type="text" wire:model.defer="ownerName" :error="$errors->has('ownerName')" />
                    @error('ownerName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Owner ID / National Number</label>
                    <x-input type="text" wire:model.defer="ownerIdNumber" :error="$errors->has('ownerIdNumber')" />
                    @error('ownerIdNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="card form-card fu d2 section-gap">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Business Registration</h3>
                    <p class="panel-copy">Your registered business or commercial registration number.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div class="acct-span-full">
                    <label class="field-label">Business Registration Number</label>
                    <x-input type="text" wire:model.defer="registrationNumber" :error="$errors->has('registrationNumber')" />
                    @error('registrationNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="card form-card fu d3">
            <div class="acct-section-head">
                <div class="acct-section-icon-wrap">
                    <svg class="acct-section-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="panel-title">Bank Information</h3>
                    <p class="panel-copy">Used to process payouts for your store's earnings.</p>
                </div>
            </div>
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">Bank Name</label>
                    <x-input type="text" wire:model.defer="bankName" :error="$errors->has('bankName')" />
                    @error('bankName')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="field-label">Bank Account Number</label>
                    <x-input type="text" wire:model.defer="bankAccountNumber" :error="$errors->has('bankAccountNumber')" />
                    @error('bankAccountNumber')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="acct-span-full">
                    <label class="field-label">IBAN (optional)</label>
                    <x-input type="text" wire:model.defer="bankIban" :error="$errors->has('bankIban')" />
                    @error('bankIban')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

    </form>

</main>
