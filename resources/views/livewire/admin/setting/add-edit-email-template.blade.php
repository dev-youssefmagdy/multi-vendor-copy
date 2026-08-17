<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">{{ __('Email Templates') }}</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">{{ $pageDescription }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.settings.email-templates') }}" class="btn btn-secondary">{{ __('Back to Templates') }}</a>
                @if ($templateId)
                    <button type="button" class="btn btn-secondary" wire:click="syncToTenants" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="syncToTenants">{{ __('Sync to Tenants') }}</span>
                        <span wire:loading wire:target="syncToTenants">{{ __('Syncing…') }}</span>
                    </button>
                @endif
                <x-btn type="submit">{{ __('Save Template') }}</x-btn>
            </div>
        </div>

        @if (session('status'))
            <div class="card section-gap notice-success">{{ session('status') }}</div>
        @endif

        <div class="grid gap-2">
            <x-card-collapse class="span-4" title="{{ __('Template Settings') }}"
                subtitle="{{ __('Control the template identity, event, and activation state.') }}" :start-open="true">
                <div class="form-grid">
                    <div>
                        <label class="field-label">{{ __('Template Name') }}</label>
                        <x-input type="text" wire:model.defer="name" />
                        @error('name')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="field-label">{{ __('Template Type') }}</label>
                        <x-select wire:model.live="templateType">
                            @foreach ($templateTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </x-select>
                        @error('templateType')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="span-2">
                        <label class="field-label">{{ __('Template Event') }}</label>
                        <x-select wire:model.defer="templateAction">
                            @foreach ($templateActionOptions as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </x-select>
                        @error('templateAction')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="span-2">
                        <label class="field-label">{{ __('Status') }}</label>
                        <x-select wire:model.defer="status">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </x-select>
                        @error('status')<div class="field-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </x-card-collapse>

            <x-card-collapse class="span-8" title="{{ __('Default Content') }}"
                subtitle="{{ __('Default subject and body used when no language-specific translation is available.') }}"
                :start-open="true">
                <div class="page-stack">
                    <div>
                        <label class="field-label">{{ __('Subject') }}</label>
                        <x-input type="text" wire:model.defer="subject" />
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
                <x-card-collapse class="span-12" title="{{ __('Language Translations') }}"
                    subtitle="{{ __('Per-language subject and body. When synced to tenants, these translations will also be synced and used for customer emails.') }}"
                    :start-open="true">

                    {{-- Language tab bar --}}
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px;">
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
        </div>
    </form>
</main>
