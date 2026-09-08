<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Localization</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage locale metadata, direction, and default-language behavior.</p>
            </div>
            <div class="page-actions"><a href="{{ route('admin.settings.languages') }}" class="btn btn-secondary">Back
                    to Languages</a><x-btn type="submit">Save Language</x-btn></div>
        </div>
        <x-card-collapse title="Language Details" subtitle="Locale metadata, direction, and default-language behavior."
            class="form-card section-gap" :start-open="true">
            <div class="form-grid form-grid-2">
                <div><label class="field-label">Language Name</label><x-input type="text" wire:model.defer="name" />
                </div>
                <div><label class="field-label">Language Code</label><x-input type="text" wire:model.defer="code" />
                </div>
                <div><label class="field-label">Native Name</label><x-input type="text" wire:model.defer="nativeName" />
                </div>
                <div><label class="field-label">Direction</label><x-select
                        wire:model.defer="direction">@foreach ($directionOptions as $directionOption)<option
                            value="{{ $directionOption->value }}">{{ strtoupper($directionOption->value) }}</option>
                        @endforeach</x-select></div>
                <div class="field-flag-grid"><label class="toggle-field"><input type="checkbox"
                            wire:model.defer="isDefault"><span>Default Language</span></label><label
                        class="toggle-field"><input type="checkbox" wire:model.defer="isActive"><span>Active
                            Language</span></label></div>
                <div class="field-flag-grid mt-4">
                    <label class="toggle-field"><input type="checkbox" wire:model.live="isFree"><span>Free Language
                            (sync to all tenants automatically)</span></label>
                </div>
                @if (!$isFree)
                    <div class="mt-4">
                        <label class="field-label">Price (USD)</label>
                        <x-input type="number" step="0.01" min="0.01" wire:model.defer="price" placeholder="e.g. 9.99" />
                        <p class="field-help mt-1">Tenants must purchase this language add-on to use it in their store.</p>
                        @error('price') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div class="field-flag-grid mt-4">
                    <label class="toggle-field"><input type="checkbox"
                            wire:model.live="offersAiTranslation"><span>Offer AI Translation for this
                            language</span></label>
                </div>
                @if ($offersAiTranslation)
                    <div class="mt-4">
                        <label class="field-label">AI Translation Price (USD)</label>
                        <x-input type="number" step="0.01" min="0" wire:model.defer="aiTranslationPrice"
                            placeholder="0 for free" />
                        <p class="field-help mt-1">Set to 0 for free AI translation (plan must allow it). Charged
                            once per AI translation run tenants trigger for this language.</p>
                        @error('aiTranslationPrice') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
            <div class="mt-4">
                <x-multi-select
                    label="Associated Countries"
                    :options="$countryOptions"
                    model="countries"
                    :selected="$countries"
                    placeholder="Search countries…"
                    errorKey="countries"
                />
                <p class="field-help mt-1">ISO 3166-1 alpha-2 country codes recommended for this language.</p>
            </div>
            @if (!$languageId)
                <div class="field-flag-grid mt-4">
                    <label class="toggle-field"><input type="checkbox"
                            wire:model.defer="autoTranslateCatalog"><span>Auto-translate lang files and catalog with
                            OpenAI</span></label>
                </div>
                <p class="field-help mt-2">Queues translation for lang resources plus central catalogs, categories,
                    products, variations, and variation options when the language is created.</p>
                @error('autoTranslateCatalog') <p class="field-error">{{ $message }}</p> @enderror
            @endif
        </x-card-collapse>
        <x-card-collapse title="Language Image" subtitle="Optional flag or image representing the language."
            class="form-card section-gap" :start-open="true">
            <div class="media-card">
                @if ($image)
                    <div class="media-preview-wrap">
                        <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="media-preview-image">
                    </div>
                @elseif ($existingImageUrl && !$removeImage)
                    <div class="media-preview-wrap">
                        <img src="{{ $existingImageUrl }}" alt="Current thumbnail" class="media-preview-image">
                    </div>
                @endif

                <label class="field-label">Thumbnail</label>
                <x-dropzone id="thumb-image" model="image" remove-action="removeImage" accept="image/*"
                    :multiple="false" label="Upload thumbnail" sublabel="PNG, JPG, WEBP up to 4MB — resized to 600px" />
                @error('image') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </x-card-collapse>

    </form>
</main>