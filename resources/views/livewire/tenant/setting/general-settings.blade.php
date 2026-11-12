<main id="mn">

    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ __('General Settings') }}</h1>
                <span class="page-badge">{{ __('Settings') }}</span>
            </div>
            <p class="page-copy">{{ __('Store-wide defaults used across the vendor control panel.') }}</p>
        </div>
    </div>

    <section class="card form-card fu d1 gs-card">
        <div class="gs-section-head">
            <h3 class="panel-title">{{ __('Default Profit Percentage') }}</h3>
            <p class="panel-copy">
                {{ __('Used as the markup for every product. Saving recalculates "Your Price" (including fixed shipping) on every product and variant.') }}
            </p>
        </div>

        <div class="form-grid gs-grid">
            <div>
                <label class="field-label">{{ __('Profit Percentage (%)') }}</label>
                <x-input type="number" step="0.01" min="0" wire:model.defer="profitPercentage" :error="$errors->has('profitPercentage')" />
                @error('profitPercentage')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="gs-actions">
                <x-btn type="button"
                       wire:click="save"
                       :wire:confirm="__('This will recalculate and overwrite the \'Your Price\' for every product and variant using this profit percentage. Continue?')">
                    {{ __('Save') }}
                </x-btn>
            </div>
        </div>
    </section>

    <section class="card form-card fu d2 gs-card">
        <div class="gs-section-head gs-section-head-row">
            <div>
                <h3 class="panel-title">{{ __('Target Countries') }}</h3>
                <p class="panel-copy">
                    {{ __('Countries your store currently targets. This is read-only — request a change and an admin will review and apply it.') }}
                </p>
            </div>
            @if ($pendingCountryRequest)
                <span class="page-badge">{{ __('Pending review') }}</span>
            @else
                <x-btn type="button" wire:click="openCountryRequestModal">{{ __('Request Change') }}</x-btn>
            @endif
        </div>

        <div class="gs-chip-list">
            @forelse ($currentCountries as $country)
                <span class="chip">{{ $country->flag_emoji }} {{ $country->name }}</span>
            @empty
                <span class="gs-empty">{{ __('No target countries set yet.') }}</span>
            @endforelse
        </div>
    </section>

    <section class="card form-card fu d3 gs-card">
        <div class="gs-section-head gs-section-head-row">
            <div>
                <h3 class="panel-title">{{ __('Categories') }}</h3>
                <p class="panel-copy">
                    {{ __('Categories your store currently sells in. This is read-only — request a change and an admin will review and apply it.') }}
                </p>
            </div>
            @if ($pendingCategoryRequest)
                <span class="page-badge">{{ __('Pending review') }}</span>
            @else
                <x-btn type="button" wire:click="openCategoryRequestModal">{{ __('Request Change') }}</x-btn>
            @endif
        </div>

        <div class="gs-chip-list">
            @forelse ($currentCategories as $category)
                <span class="chip">{{ $category->name }}</span>
            @empty
                <span class="gs-empty">{{ __('No categories set yet.') }}</span>
            @endforelse
        </div>
    </section>

    @if ($showCountryRequestModal)
        <div class="gs-modal-backdrop" wire:click="closeCountryRequestModal">
            <div class="gs-modal-box" wire:click.stop>
                <div class="gs-modal-head">
                    <h3 class="panel-title">{{ __('Request Target Countries Change') }}</h3>
                    <button type="button" class="modal-close" wire:click="closeCountryRequestModal" aria-label="{{ __('Close') }}">{{ __('×') }}</button>
                </div>
                <div class="gs-modal-body">
                    @error('requestedCountryIds')<div class="field-error">{{ $message }}</div>@enderror
                    <div class="gs-checkbox-grid">
                        @foreach ($allCountries as $country)
                            <label class="gs-checkbox">
                                <input type="checkbox" value="{{ $country->id }}" wire:model="requestedCountryIds">
                                {{ $country->flag_emoji }} {{ $country->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="gs-modal-foot">
                    <x-btn type="button" variant="secondary" wire:click="closeCountryRequestModal">{{ __('Cancel') }}</x-btn>
                    <x-btn type="button" wire:click="submitCountryChangeRequest">{{ __('Send Request') }}</x-btn>
                </div>
            </div>
        </div>
    @endif

    @if ($showCategoryRequestModal)
        <div class="gs-modal-backdrop" wire:click="closeCategoryRequestModal">
            <div class="gs-modal-box" wire:click.stop>
                <div class="gs-modal-head">
                    <h3 class="panel-title">{{ __('Request Categories Change') }}</h3>
                    <button type="button" class="modal-close" wire:click="closeCategoryRequestModal" aria-label="{{ __('Close') }}">{{ __('×') }}</button>
                </div>
                <div class="gs-modal-body">
                    @error('requestedCategoryIds')<div class="field-error">{{ $message }}</div>@enderror
                    <div class="gs-checkbox-grid">
                        @foreach ($allCategories as $category)
                            <label class="gs-checkbox">
                                <input type="checkbox" value="{{ $category->id }}" wire:model="requestedCategoryIds">
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="gs-modal-foot">
                    <x-btn type="button" variant="secondary" wire:click="closeCategoryRequestModal">{{ __('Cancel') }}</x-btn>
                    <x-btn type="button" wire:click="submitCategoryChangeRequest">{{ __('Send Request') }}</x-btn>
                </div>
            </div>
        </div>
    @endif

</main>

<style>
.gs-card {
    max-width: 760px;
}

.gs-section-head-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.gs-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.gs-empty {
    color: var(--t3);
    font-size: 13px;
}

.gs-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.gs-modal-box {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: 100%;
    max-width: 520px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.gs-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
}

.gs-modal-body {
    padding: 16px 20px;
    overflow-y: auto;
}

.gs-modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 20px;
    border-top: 1px solid var(--border);
}

.gs-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 8px;
}

.gs-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--t2);
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
