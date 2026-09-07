<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">FAQs</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage translated frequently asked questions for the central help center.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary">Back to FAQs</a>
                <x-btn type="submit">Save FAQ</x-btn>
            </div>
        </div>

        <div class="grid gap-2">
            <x-card-collapse class="span-4" title="FAQ Settings" subtitle="Category grouping and publishing status."
                :start-open="true">
                <div class="form-grid">
                    <div>
                        <label class="field-label">Status</label>
                        <x-select wire:model.defer="status">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        @error('status')<div class="text-red-500 text-sm mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </x-card-collapse>

            {{-- ── Translations ──────────────────────────────────────────────────── --}}
            <x-card-collapse title="Translations" subtitle="Localized question, category label, and answer per language." class="form-card span-8" :start-open="true">

                @if ($languages->count())
                    <div class="locale-tabs">
                        @foreach ($languages as $language)
                            <button type="button"
                                class="locale-tab {{ $activeLocale === $language->code ? 'is-active' : '' }}"
                                wire:click="setActiveLocale('{{ $language->code }}')">
                                <span>{{ strtoupper($language->code) }}</span>
                                <small>{{ $language->native_name }}</small>
                            </button>
                        @endforeach
                    </div>

                    @foreach ($languages as $language)
                        <div class="locale-panel {{ $activeLocale === $language->code ? 'is-active' : '' }}">
                            <div class="form-grid form-grid-2">
                                <div class="span-2">
                                    <label class="field-label">Question ({{ strtoupper($language->code) }}) @if($language->is_default)*@endif</label>
                                    <x-input type="text"
                                        :class="$errors->has('translations.' . $language->code . '.question') ? 'is-invalid' : ''"
                                        wire:model.defer="translations.{{ $language->code }}.question"
                                        placeholder="The frequently asked question" />
                                    @error("translations.{$language->code}.question") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Category ({{ strtoupper($language->code) }})</label>
                                    <x-input type="text"
                                        :class="$errors->has('translations.' . $language->code . '.category') ? 'is-invalid' : ''"
                                        wire:model.defer="translations.{{ $language->code }}.category"
                                        placeholder="Group label e.g. Shipping, Payments" />
                                    @error("translations.{$language->code}.category") <p class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div class="span-2">
                                    <label class="field-label">Answer ({{ strtoupper($language->code) }})</label>
                                    <x-editor
                                        model="translations.{{ $language->code }}.answer"
                                        :value="$translations[$language->code]['answer'] ?? ''"
                                        :height="500" />
                                    @error("translations.{$language->code}.answer") <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="notice-muted">Create at least one active language before managing translated FAQ content.</div>
                @endif

            </x-card-collapse>
        </div>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-secondary">Back to FAQs</a>
            <x-btn type="submit">Save FAQ</x-btn>
        </div>
    </form>
</main>
