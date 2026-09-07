<main id="mn">
    <form wire:submit="save" class="page-stack section-gap">
        <div class="page-head fu d0">
            <div>
                <div class="page-title-row">
                    <h1 class="D page-title">{{ $pageTitle }}</h1><span class="page-badge">{{ __('Settings') }}</span>
                </div>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('tenant.settings.email-templates') }}" class="btn btn-secondary">{{ __('Back to Templates') }}</a>
                <button type="button" class="btn btn-secondary" wire:click="resyncFromCentral" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="resyncFromCentral">{{ __('Re-sync from Central') }}</span>
                    <span wire:loading wire:target="resyncFromCentral">{{ __('Syncing…') }}</span>
                </button>
                <x-btn type="submit">{{ __('Save Template') }}</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        <x-card-collapse title="{{ __('Template Details') }}"
            subtitle="{{ __('Reference information synced from the central template catalog.') }}" class="form-card section-gap"
            :start-open="true">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">{{ __('Template') }}</label>
                    <x-input type="text" :value="$name" disabled />
                </div>

                <div>
                    <label class="field-label">{{ __('Event') }}</label>
                    <x-input type="text" :value="$actionLabel" disabled />
                </div>

                <div class="span-2">
                    <label class="field-label">{{ __('Status') }}</label>
                    <label class="toggle-field"><input type="checkbox" wire:model.defer="isActive"><span>{{ __('Template is active') }}</span></label>
                    @error('isActive')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </x-card-collapse>

        <x-card-collapse title="{{ __('Default Content') }}" subtitle="{{ __('Default subject and body used when no language-specific translation exists.') }}"
            class="form-card section-gap" :start-open="true">
            <div class="page-stack">
                <div>
                    <label class="field-label">{{ __('Subject') }}</label>
                    <x-input type="text" wire:model.defer="subject"
                        class="{{ $errors->has('subject') ? 'is-invalid' : '' }}" />
                    @error('subject')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div>
                    <label class="field-label">{{ __('Body') }}</label>
                    <x-editor model="body" :value="$bodyContent" :height="420" />
                    @error('body')<div class="field-error">{{ $message }}</div>@enderror
                </div>
            </div>
        </x-card-collapse>

        @if ($languages->isNotEmpty())
            <x-card-collapse title="{{ __('Language Translations') }}" subtitle="{{ __('Per-language subject and body. Emails will use the matching translation for the customer\'s language.') }}"
                class="form-card section-gap" :start-open="true">

                {{-- Language tab bar --}}
                <div class="tabs" style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;">
                    @foreach ($languages as $lang)
                        <button type="button"
                            class="btn {{ $activeLocaleTab === $lang->code ? 'btn-primary' : 'btn-secondary' }} btn-sm"
                            wire:click="setActiveLocaleTab('{{ $lang->code }}')">
                            {{ $lang->native_name ?: $lang->name }}
                            @if ($lang->is_default)
                                <span style="font-size:10px;opacity:.7;">({{ __('default') }})</span>
                            @endif
                            @if (!empty($translations[$lang->code]['subject']))
                                <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;margin-left:4px;"></span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Per-language fields --}}
                @foreach ($languages as $lang)
                    <div @if ($activeLocaleTab !== $lang->code) style="display:none;" @endif>
                        <div class="page-stack">
                            <div>
                                <label class="field-label">
                                    {{ __('Subject') }} — {{ $lang->native_name ?: $lang->name }}
                                    <span class="entity-subtitle" style="margin-left:4px;">({{ $lang->code }})</span>
                                </label>
                                <x-input type="text"
                                    wire:model.defer="translations.{{ $lang->code }}.subject"
                                    placeholder="{{ __('Leave blank to use default subject') }}" />
                                @error("translations.{$lang->code}.subject")<div class="field-error">{{ $message }}</div>@enderror
                            </div>

                            <div>
                                <label class="field-label">
                                    {{ __('Body') }} — {{ $lang->native_name ?: $lang->name }}
                                </label>
                                <x-editor
                                    model="translations.{{ $lang->code }}.body"
                                    :value="$translations[$lang->code]['body'] ?? ''"
                                    :height="380" />
                                @error("translations.{$lang->code}.body")<div class="field-error">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </x-card-collapse>
        @endif

        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('tenant.settings.email-templates') }}" class="btn btn-secondary">{{ __('Back to Templates') }}</a>
            <button type="button" class="btn btn-secondary" wire:click="resyncFromCentral" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="resyncFromCentral">{{ __('Re-sync from Central') }}</span>
                <span wire:loading wire:target="resyncFromCentral">{{ __('Syncing…') }}</span>
            </button>
            <x-btn type="submit">{{ __('Save Template') }}</x-btn>
        </div>
    </form>
</main>
