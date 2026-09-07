<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Central Shipping</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Define the per-gram shipping cost for a country. Total cost = product weight ×
                    price per gram.</p>
            </div>
            <div class="page-actions" style="flex-shrink:0">
                <a href="{{ route('admin.shipping.fixed-costs') }}" class="btn btn-secondary">Back to List</a>
                <x-btn type="submit">Save Rule</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="card section-gap notice-error">
                <h3 class="panel-title">Please fix the highlighted fields</h3>
                <p class="panel-copy">The rule was not saved because some required values are missing or invalid.</p>
            </div>
        @endif

        <x-card-collapse class="form-card" title="Cost Rule Details"
            subtitle="One record per country — shipping cost = product weight (g) × price per gram." :start-open="true">
            <div class="form-grid">

                <div>
                    <label class="field-label">Country <span class="req-star">*</span></label>
                    <x-select wire:model.defer="countryId" :searchable="true" placeholder="— Select country —"
                        :error="$errors->has('countryId')">
                        <option value="">— Select country —</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">
                                {{ $country->flag_emoji }} {{ $country->name }} ({{ strtoupper($country->iso2) }})
                            </option>
                        @endforeach
                    </x-select>
                    @error('countryId') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label">Price per Gram <span class="req-star">*</span></label>
                    <x-input type="number" min="0" step="0.000001" wire:model.defer="pricePerGram"
                        class="{{ $errors->has('pricePerGram') ? 'is-invalid' : '' }}" />
                    <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">Cost charged per gram of
                        product weight (e.g. 0.05 means 5.00 for 100 g).</p>
                    @error('pricePerGram') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label">Currency Code <span class="req-star">*</span></label>
                    <x-input type="text" maxlength="3" wire:model.defer="currencyCode" placeholder="USD"
                        style="text-transform:uppercase"
                        class="{{ $errors->has('currencyCode') ? 'is-invalid' : '' }}" />
                    <p class="field-hint" style="font-size:11px;color:var(--t3);margin-top:4px">3-letter ISO 4217 code
                        (e.g. USD, EUR, EGP).</p>
                    @error('currencyCode') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label">Status</label>
                    <label class="toggle-field">
                        <input type="checkbox" wire:model.defer="isActive">
                        <span>Active (applies at checkout and storefront)</span>
                    </label>
                    @error('isActive') <p class="field-error">{{ $message }}</p> @enderror
                </div>

            </div>
        </x-card-collapse>

    </form>
</main>