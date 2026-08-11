<main id="mn">

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">General Settings</h1>
                <span class="page-badge">Settings</span>
            </div>
            <p class="page-copy">Store-wide defaults used across the vendor control panel.</p>
        </div>
    </div>

    <section class="card form-card fu d1 gs-card">
        <div class="gs-section-head">
            <h3 class="panel-title">Default Profit Percentage</h3>
            <p class="panel-copy">
                Used as the markup for every product. Saving recalculates
                "Your Price" (including fixed shipping) on every product and variant.
            </p>
        </div>

        <div class="form-grid gs-grid">
            <div>
                <label class="field-label">Profit Percentage (%)</label>
                <x-input type="number" step="0.01" min="0" wire:model.defer="profitPercentage" :error="$errors->has('profitPercentage')" />
                @error('profitPercentage')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="gs-actions">
                <x-btn type="button"
                       wire:click="save"
                       wire:confirm="This will recalculate and overwrite the 'Your Price' for every product and variant using this profit percentage. Continue?">
                    Save
                </x-btn>
            </div>
        </div>
    </section>

</main>

<style>
.gs-card {
    max-width: 760px;
}

.gs-section-head {
    margin-bottom: 16px;
}

.gs-section-head .panel-title {
    margin-bottom: 4px;
}

.gs-grid {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}

.gs-grid > div:first-child {
    flex: 1 1 240px;
}

.gs-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

@media (max-width: 639px) {
    .gs-grid {
        flex-direction: column;
        align-items: stretch;
    }

    .gs-actions {
        flex-direction: column;
    }
}
</style>
