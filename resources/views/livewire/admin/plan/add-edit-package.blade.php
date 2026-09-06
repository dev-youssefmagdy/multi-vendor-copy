<main id="mn">
    <form wire:submit="save" class="page-stack">
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">{{ __('Packages') }}</div>
                <h1 class="D page-title">{{ __($pageTitle) }}</h1>
                <p class="page-copy">
                    {{ __('Manage package pricing, comparison features, and translated marketing copy.') }}</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.plans.index') }}"
                    class="btn btn-secondary">{{ __('Back to Packages') }}</a>
                <x-btn type="submit">{{ __('Save Package') }}</x-btn>
            </div>
        </div>
        <div class="grid gap-2">
            <x-card-collapse class="span-4" :title="__('Commercial Settings')" :subtitle="__('Status, billing term, and pricing.')" open>
                <div class="form-grid">
                    <div>
                        <label class="field-label">{{ __('Status') }}</label>
                        <x-select wire:model.defer="status">
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption->value }}">{{ __(ucfirst($statusOption->value)) }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div>
                        <label class="field-label">{{ __('Price') }}</label>
                        <x-input type="number" step="0.01" min="0" wire:model.defer="price" placeholder="0.00" />
                        <p class="field-hint">{{ __('Set to 0 to offer this package as free.') }}</p>
                    </div>
                    <div>
                        <label class="field-label">{{ __('Trial Days') }}</label>
                        <x-input type="number" min="0" wire:model.defer="trialDays" />
                    </div>
                </div>

                <div>
                    <label class="field-label">{{ __('Icon') }}</label>
                    <input type="hidden" wire:model.defer="icon">
                    <details class="icon-dropdown">
                        <summary class="icon-dropdown__summary">
                            <span class="icon-preview"><i class="{{ $icon ?: 'fas fa-box-open' }}"></i></span>
                            <span class="icon-label">{{ $icon ? ($iconOptions[$icon] ?? $icon) : __('Select icon') }}</span>
                        </summary>
                        <div class="icon-options-grid">
                            @foreach($iconOptions as $iconValue => $iconLabel)
                                <button type="button" class="icon-option {{ $icon === $iconValue ? 'is-active' : '' }}"
                                    wire:click="$set('icon','{{ $iconValue }}')">
                                    <i class="{{ $iconValue }}"></i>
                                    <span>{{ __($iconLabel) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </details>
                    <p class="field-hint">{{ __('Optional: select an icon to visually represent this package on public pages.') }}</p>
                </div>

                {{-- Billing Term Selector (select) --}}
                <div class="section-gap">
                    <h3 class="panel-title" style="margin-bottom:.5rem;">{{ __('Billing Term') }}</h3>
                    <p class="panel-copy" style="margin-bottom:1rem;">
                        {{ __('Select the billing cycle this package is sold under.') }}</p>

                    <div>
                        <label class="field-label">{{ __('Billing Term') }}</label>
                        <x-select wire:model.defer="term">
                            @foreach ($termOptions as $termOption)
                                <option value="{{ $termOption['value'] }}">{{ __($termOption['label']) }} - {{ __($termOption['description']) }}</option>
                            @endforeach
                        </x-select>
                        <p class="field-hint">{{ __('Choose the billing term shown on pricing pages.') }}</p>
                    </div>
                </div>

                {{-- Categories Count --}}
                <div class="section-gap">
                    <h3 class="panel-title" style="margin-bottom:.5rem;">{{ __('Category Access') }}</h3>
                    <p class="panel-copy" style="margin-bottom:1rem;">
                        {{ __('Control which product categories subscribers of this plan can access. Choose between full access to all categories or a limited selection during registration.') }}
                    </p>

                    {{-- All-categories toggle --}}
                    <label style="display:flex;align-items:center;gap:12px;cursor:pointer;padding:14px 16px;border-radius:10px;border:2px solid {{ $allCategories ? 'var(--primary, #FF4B2B)' : 'var(--border)' }};background:{{ $allCategories ? 'color-mix(in srgb, var(--primary, #FF4B2B) 6%, transparent)' : 'var(--card)' }};transition:border-color .15s,background .15s;user-select:none;">
                        <div style="position:relative;width:40px;height:22px;flex-shrink:0">
                            <input type="checkbox" wire:model.live="allCategories"
                                style="position:absolute;opacity:0;width:0;height:0">
                            <div style="position:absolute;inset:0;border-radius:999px;background:{{ $allCategories ? 'var(--primary, #FF4B2B)' : '#cbd5e1' }};transition:background .15s"></div>
                            <div style="position:absolute;top:3px;left:{{ $allCategories ? '21px' : '3px' }};width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:left .15s"></div>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--t1)">{{ __('All Categories Included') }}</div>
                            <div style="font-size:11.5px;color:var(--t3);margin-top:1px">{{ __('Subscriber gets access to every category — no selection step at registration.') }}</div>
                        </div>
                        @if($allCategories)
                            <span style="margin-left:auto;flex-shrink:0;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:color-mix(in srgb,var(--primary,#FF4B2B) 15%,transparent);color:var(--primary,#FF4B2B);border:1px solid color-mix(in srgb,var(--primary,#FF4B2B) 30%,transparent)">
                                {{ __('All') }}
                            </span>
                        @endif
                    </label>

                    {{-- Limited count input (only shown when allCategories is off) --}}
                    @if(!$allCategories)
                        <div class="form-field" style="max-width:260px;margin-top:14px;">
                            <label class="field-label">{{ __('Max Parent Categories') }}</label>
                            <input type="number" class="field-control"
                                wire:model.defer="categoriesCount"
                                min="1" step="1" placeholder="1">
                            <p class="field-hint" style="margin-top:4px;">
                                {{ __('Number of root categories the subscriber can select during registration.') }}
                            </p>
                            @error('categoriesCount') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <p class="field-hint" style="margin-top:10px;display:flex;align-items:center;gap:6px">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary,#FF4B2B);flex-shrink:0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            {{ __('All product categories will be synced to the subscriber\'s store automatically after registration.') }}
                        </p>
                    @endif
                </div>

                <div class="section-gap">
                    <h3 class="panel-title">{{ __('Plan Limits') }}</h3>
                    <p class="panel-copy" style="margin-bottom:12px;">
                        {{ __('Set -1 for unlimited on any field.') }}
                    </p>
                    <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
                        <div class="form-field">
                            <label class="field-label">{{ __('Products') }}</label>
                            <input type="number" class="field-control" wire:model.defer="productsLimit" min="-1" step="1">
                            @error('productsLimit') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label class="field-label">{{ __('Banners') }}</label>
                            <input type="number" class="field-control" wire:model.defer="bannersLimit" min="-1" step="1">
                            @error('bannersLimit') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label class="field-label">{{ __('Languages') }}</label>
                            <input type="number" class="field-control" wire:model.defer="languagesLimit" min="-1" step="1">
                            @error('languagesLimit') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label class="field-label">{{ __('Orders per Month') }}</label>
                            <input type="number" class="field-control" wire:model.defer="ordersPerMonthLimit" min="-1" step="1">
                            @error('ordersPerMonthLimit') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label class="field-label">{{ __('AI Calls') }}</label>
                            <input type="number" class="field-control" wire:model.defer="aiCallsLimit" min="-1" step="1">
                            @error('aiCallsLimit') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label class="field-label">{{ __('Image Searches') }}</label>
                            <input type="number" class="field-control" wire:model.defer="imageSearchesLimit" min="-1" step="1">
                            @error('imageSearchesLimit') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="section-gap">
                    <h3 class="panel-title">{{ __('Add-ons') }}</h3>
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" wire:model.defer="aiTranslationEnabled" class="field-control" style="width:auto;">
                        <span class="field-label" style="margin:0;">{{ __('Enable AI Translation add-on') }}</span>
                    </label>
                    @error('aiTranslationEnabled') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="section-gap">
                    <div class="fu d0"
                        style="justify-content: space-between; align-items: end; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <h3 class="panel-title">{{ __('Package Features') }}</h3>
                            <p class="panel-copy">
                                {{ __('Use a label and optional value for pricing-table comparison. Leave value blank to mark a feature as included.') }}
                            </p>
                        </div>
                        <button type="button" class="btn btn-secondary"
                            wire:click="addFeatureRow">{{ __('Add Feature') }}</button>
                    </div>

                    <div class="features-grid">
                        @foreach ($featureRows as $index => $featureRow)
                            <div class="feature-card" wire:key="feature-{{ $index }}">
                                <div class="feature-card-row">
                                    <div class="feature-inputs">
                                        <div class="form-field">
                                            <label class="field-label">{{ __('Feature Label') }}</label>
                                            <input type="text" class="field-control"
                                                wire:model.defer="featureRows.{{ $index }}.label"
                                                placeholder="{{ __('Orders per month') }}">
                                        </div>
                                        <div class="form-field">
                                            <label class="field-label">{{ __('Feature Value') }}</label>
                                            <input type="text" class="field-control"
                                                wire:model.defer="featureRows.{{ $index }}.value"
                                                placeholder="{{ __('Unlimited, true, false, 100') }}">
                                        </div>
                                    </div>
                                    <div class="feature-actions">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            wire:click="removeFeatureRow({{ $index }})"
                                            @disabled(count($featureRows) === 1) aria-label="{{ __('Remove') }}">
                                            <i class="fa fa-trash"></i>
                                            <span class="sr-only">{{ __('Remove') }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-card-collapse>

            <x-card-collapse class="span-8" :title="__('Translations')" :subtitle="__('Translate name and package description across all active languages.')" open>
                <div class="locale-tabs">
                    @foreach ($languages as $language)
                        <button type="button" class="locale-tab {{ $activeLocale === $language->code ? 'is-active' : '' }}"
                            wire:click="setActiveLocale('{{ $language->code }}')"><span>{{ strtoupper($language->code) }}</span><small>{{ $language->native_name }}</small></button>
                    @endforeach
                </div>
                @foreach ($languages as $language)
                    <div class="locale-panel {{ $activeLocale === $language->code ? 'is-active' : '' }}">
                        @if ($activeLocale === $language->code)
                            <div class="form-grid">
                                <div>
                                    <label class="field-label">{{ __('Name') }}</label>
                                    <x-input type="text" wire:model.defer="translations.{{ $language->code }}.name" />
                                </div>
                                <div>
                                    <label class="field-label">{{ __('Description') }}</label>
                                    <textarea rows="7" class="field-control field-textarea"
                                        wire:model.defer="translations.{{ $language->code }}.description"></textarea>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </x-card-collapse>
        </div>

        {{-- ── Bottom Actions ───────────────────────────────────────────────── --}}
        <div class="page-actions" style="display:flex;justify-content:flex-end;gap:8px;padding-top:8px">
            <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">{{ __('Back to Packages') }}</a>
            <x-btn type="submit">{{ __('Save Package') }}</x-btn>
        </div>
    </form>
</main>

<style>
    .term-selector-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .75rem;
    }

    .term-option {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .35rem;
        padding: .9rem .75rem;
        border: 2px solid var(--border);
        border-radius: .75rem;
        background: var(--card);
        cursor: pointer;
        text-align: center;
        transition: border-color .15s, box-shadow .15s;
    }

    .term-option:hover {
        border-color: var(--primary, #FF4B2B);
    }

    .term-option--active {
        border-color: var(--primary, #FF4B2B);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary, #FF4B2B) 15%, transparent);
    }

    .term-option__icon {
        width: 2.25rem;
        height: 2.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: .5rem;
        background: color-mix(in srgb, var(--primary) 12%, transparent);
        font-size: 1rem;
        color: var(--primary);
    }

    .term-option--active .term-option__icon {
        background: var(--primary, #FF4B2B);
        color: #fff;
    }

    .term-option__label {
        font-size: .8rem;
        font-weight: 700;
        color: var(--t1);
        line-height: 1.2;
    }

    .term-option__desc {
        font-size: .7rem;
        color: var(--t3);
        line-height: 1.3;
    }

    .field-hint {
        font-size: .7rem;
        color: var(--t3);
        margin-top: .3rem;
    }

    /* Feature cards */
    .features-grid {
        display: grid;
        gap: .75rem;
    }

    .feature-card {
        padding: .75rem;
        border: 1px solid var(--border);
        border-radius: .75rem;
        background: var(--card);
        box-shadow: var(--shadow, 0 1px 2px rgba(16,24,40,0.03));
    }

    .feature-card-row {
        display: flex;
        gap: .75rem;
        align-items: flex-start;
    }

    .feature-inputs {
        display: flex;
        gap: .6rem;
        flex: 1 1 auto;
        flex-wrap: wrap;
    }

    .feature-inputs .form-field {
        flex: 1 1 220px;
        min-width: 140px;
    }

    .feature-actions {
        display: flex;
        align-items: flex-start;
        margin: auto;
    }

    .feature-actions .btn {
        margin-top: 1.25rem;
    }

    @media (max-width: 640px) {
        .feature-card-row { flex-direction: column; }
        .feature-actions .btn { margin-top: .5rem; }
    }

    /* Icon dropdown selector */
    .icon-dropdown { max-width: 520px; }
    .icon-dropdown summary { list-style: none; cursor: pointer; display:flex; align-items:center; gap:.6rem; padding:.5rem .75rem; border:1px solid var(--border); border-radius:.5rem; background:var(--card); color:var(--t1); }
    .icon-dropdown summary::-webkit-details-marker{ display: none; }
    .icon-dropdown__summary .icon-preview { width:1.6rem; height:1.6rem; display:flex; align-items:center; justify-content:center; border-radius:.375rem; background: color-mix(in srgb, var(--primary, #FF4B2B) 12%, transparent); color:var(--primary); }
    .icon-options-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(110px,1fr)); gap:.5rem; padding:.6rem; margin-top:.5rem; border-radius:.5rem; background: var(--card); border:1px solid var(--border); }
    .icon-option { display:flex; gap:.5rem; align-items:center; width:100%; padding:.45rem .5rem; border-radius:.5rem; background:transparent; border:1px solid transparent; cursor:pointer; text-align:left; color:var(--t1); }
    .icon-option i { width:1.1rem; text-align:center; font-size:1.05rem; color:var(--primary); }
    .icon-option.is-active { border-color: var(--primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 12%, transparent); background: color-mix(in srgb, var(--primary) 8%, transparent); }

</style>
