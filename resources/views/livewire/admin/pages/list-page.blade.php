<main id="mn">
    @php
        $filterFields = $filterFields ?? [];
        $rows = $rows ?? [];
        $rowClasses = $rowClasses ?? [];
        $records = $records ?? null;
        $actionUrl = $actionUrl ?? null;
        $actionMethod = $actionMethod ?? null;
        $emptyTitle = $emptyTitle ?? 'No records found';
        $emptyCopy = $emptyCopy ?? 'No records match the current filters yet.';
        $modalModel = $modalModel ?? null;
        $modalTitle = $modalTitle ?? 'Manage Record';
        $modalCloseAction = $modalCloseAction ?? 'closeModal';
        $modalSubmitAction = $modalSubmitAction ?? 'save';
        $modalSubmitLabel = $modalSubmitLabel ?? 'Save';
        $modalFieldGroups = $modalFieldGroups ?? [];
        $modalMaxWidth = $modalMaxWidth ?? '2xl';
        $statisticsGridClass = $statisticsGridClass ?? (count($statistics) > 3 ? 'g-stats4' : 'g-stats3');
        $modalContentView = $modalContentView ?? null;
        $modalContentData = $modalContentData ?? [];
        $modalCountryPicker = $modalCountryPicker ?? false;
        $modalCountryPickerHint = $modalCountryPickerHint ?? null;
        $countries = $countries ?? [];
        $allCountries = $allCountries ?? true;
        $assignedCountryIds = $assignedCountryIds ?? [];
        $modalAffiliatePicker = $modalAffiliatePicker ?? false;
        $modalAffiliates = $modalAffiliates ?? [];
        $showPrimaryAction = ($actionUrl || $actionMethod);
        $secondaryActionLabel = $secondaryActionLabel ?? null;
        $secondaryActionUrl = $secondaryActionUrl ?? null;
      @endphp
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            @if ($exportable ?? false)
                <x-btn variant="secondary" class="xs-hide" type="button" wire:click="export">
                    Export
                </x-btn>
            @endif
            @if (!empty($secondaryActionLabel) && $secondaryActionUrl)
                <a href="{{ $secondaryActionUrl }}" class="btn btn-secondary">{{ $secondaryActionLabel }}</a>
            @endif
            @if ($actionUrl)
                <a href="{{ $actionUrl }}" class="btn btn-primary">{{ $actionLabel }}</a>
            @elseif ($actionMethod)
                <x-btn type="button" wire:click="{{ $actionMethod }}">{{ $actionLabel }}</x-btn>
            @endif
        </div>
    </div>

    <details class="card filters-card fu d1 section-gap" wire:ignore.self>
        <summary class="filters-summary">
            <div>
                <div class="panel-title">{{ $filtersTitle }}</div>
                <p class="panel-copy">{{ $filtersDescription }}</p>
            </div>
            <div class="filters-summary-meta">
                <span class="filter-pill">Central</span>
                <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </summary>

        <div class="filters-grid">
            @forelse ($filterFields as $field)
                <div>
                    @if (($field['type'] ?? 'text') !== 'checkbox')
                        <label class="field-label">{{ $field['label'] }}</label>
                    @endif
                    @if (($field['type'] ?? 'text') === 'select')
                        <x-select wire:model.live="{{ $field['model'] }}">
                            @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                            @endforeach
                        </x-select>
                    @elseif (($field['type'] ?? 'text') === 'checkbox')
                        <x-checkbox wire:model.live="{{ $field['model'] }}" label="{{ $field['toggleLabel'] ?? $field['label'] }}" />
                    @else
                        <x-input type="{{ $field['type'] ?? 'text' }}" wire:model.live.debounce.300ms="{{ $field['model'] }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" />
                    @endif
                </div>
            @empty
                <div>
                    <label class="field-label">Keyword</label>
                    <x-input placeholder="Search {{ strtolower($title) }}" />
                </div>
            @endforelse
        </div>

        <div class="filters-actions">
            <p class="filters-note">
                {{ $filtersNote ?? 'Filters are wired to repository-backed queries for this central module.' }}
            </p>
            <div class="page-actions compact-actions">
                <x-btn type="button" variant="secondary" wire:click="clearFilters">Reset</x-btn>
            </div>
        </div>
    </details>

    <div class="{{ $statisticsGridClass }} section-gap">
        @foreach ($statistics as $stat)
            <div class="card {{ $stat['glow'] ?? '' }} fu d{{ min($loop->iteration, 6) }}">
                <div class="stat-head">
                    <div>
                        <div class="eyebrow">{{ $stat['label'] }}</div>
                        <div class="D stat-value">{{ $stat['value'] }}</div>
                    </div>
                    <div class="mini-stat-dot {{ $stat['dot'] ?? 'dot-cyan' }}"></div>
                </div>
                <p class="panel-copy">{{ $stat['caption'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="card fu d4 table-card-shell">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">{{ $tableTitle }}</h3>
                <p class="panel-copy">{{ $tableDescription }}</p>
            </div>
            <div class="table-header-actions">
                @if (collect($filterFields)->contains(fn($field) => ($field['model'] ?? null) === 'search'))
                    <x-input wire:model.live.debounce.300ms="search" placeholder="Quick search" class="table-search" />
                @endif
            </div>
        </div>

        <x-table :headers="$headers">
            @forelse ($rows as $index => $row)
                <tr @if($rowClasses[$index] ?? null) class="{{ $rowClasses[$index] }}" @endif>
                    @foreach ($row as $cell)
                        <td>{!! $cell !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">
                        <div class="empty-state">
                            <div class="empty-state-title">{{ $emptyTitle }}</div>
                            <p class="empty-state-copy">{{ $emptyCopy }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="table-footer-shell">
            <div class="panel-copy">
                @if ($records)
                    Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }}
                    records
                    for {{ strtolower($title) }}.
                @else
                    Showing 0 to 0 of 0 records for {{ strtolower($title) }}.
                @endif
            </div>
            <div class="pagination-shell">
                @if ($records)
                    <x-pagination :paginator="$records" />
                @else
                    <span class="pagination-chip active">1</span>
                @endif
            </div>
        </div>
    </div>

    @if ($modalModel && ($modalFieldGroups || $modalContentView))
        <x-modal wire:model="{{ $modalModel }}" title="{{ $modalTitle }}" maxWidth="{{ $modalMaxWidth }}"
            closeAction="{{ $modalCloseAction }}">
            @if ($modalContentView)
                <form wire:submit="{{ $modalSubmitAction }}" class="page-stack">
                    @include($modalContentView, $modalContentData)
                    <div class="page-actions compact-actions justify-end">
                        <x-btn type="button" variant="secondary" wire:click="{{ $modalCloseAction }}">Cancel</x-btn>
                        <x-btn type="submit" wire:loading.attr="disabled" wire:target="{{ $modalSubmitAction }}">
                            <span wire:loading.remove wire:target="{{ $modalSubmitAction }}">{{ $modalSubmitLabel }}</span>
                            <span wire:loading wire:target="{{ $modalSubmitAction }}">Saving…</span>
                        </x-btn>
                    </div>
                </form>
            @else
                <form wire:submit="{{ $modalSubmitAction }}" class="page-stack">
                    @foreach ($modalFieldGroups as $group)
                        <div class="form-grid {{ $group['gridClass'] ?? '' }}">
                            @foreach ($group['fields'] as $field)
                                <div class="{{ $field['wrapperClass'] ?? '' }}">
                                    <label class="field-label">{{ $field['label'] }}</label>
                                    @if (($field['type'] ?? 'text') === 'editor')
                                        <div wire:key="{{ $field['editorKey'] ?? $field['model'] }}">
                                            <x-editor model="{{ $field['model'] }}" :value="data_get($this, $field['model'])"
                                                :height="$field['height'] ?? 400" />
                                        </div>
                                    @elseif (($field['type'] ?? 'text') === 'textarea')
                                        <x-textarea rows="{{ $field['rows'] ?? 4 }}" wire:model.defer="{{ $field['model'] }}"
                                            :error="$errors->has($field['model'])" />
                                    @elseif (($field['type'] ?? 'text') === 'select')
                                        <x-select wire:model.defer="{{ $field['model'] }}" :error="$errors->has($field['model'])">
                                            @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                            @endforeach
                                        </x-select>
                                    @elseif (($field['type'] ?? 'text') === 'checkbox-group')
                                        <div class="checkbox-group-grid">
                                            @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                                <x-checkbox value="{{ $optionValue }}" wire:model.defer="{{ $field['model'] }}"
                                                    label="{{ $optionLabel }}" />
                                            @endforeach
                                        </div>
                                    @elseif (($field['type'] ?? 'text') === 'checkbox')
                                        <x-checkbox wire:model.defer="{{ $field['model'] }}"
                                            label="{{ $field['toggleLabel'] ?? $field['label'] }}" />
                                    @else
                                        <x-input type="{{ $field['type'] ?? 'text' }}" wire:model.defer="{{ $field['model'] }}"
                                            :error="$errors->has($field['model'])" />
                                    @endif
                                    @error($field['model'])<div class="field-error">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    @if (!empty($modalCountryPicker))
                        <div class="card section-gap" style="padding:18px 20px;">
                            <h3 class="panel-title" style="margin-bottom:8px;">Country Availability</h3>
                            <p class="panel-copy" style="margin-bottom:14px;">{{ $modalCountryPickerHint ?? 'Leave set to All Countries to make this coupon usable everywhere.' }}</p>

                            <x-checkbox wire:model.live="allCountries" label="All Countries (default)" />

                            @if (!$allCountries)
                                <div class="form-grid-3" style="gap:6px;max-height:260px;overflow-y:auto;margin-top:12px;">
                                    @foreach ($countries as $country)
                                        <label style="display:flex;align-items:center;gap:7px;padding:6px 8px;border-radius:8px;border:1px solid var(--border);background:var(--surface);cursor:pointer;font-size:12px;">
                                            <input type="checkbox"
                                                   value="{{ $country->id }}"
                                                   wire:model.defer="assignedCountryIds"
                                                   style="accent-color:var(--cyan);width:13px;height:13px;flex-shrink:0;">
                                            {{ $country->flag_emoji }} {{ $country->name }}
                                        </label>
                                    @endforeach
                                </div>
                                @if (!empty($assignedCountryIds))
                                    <p class="field-hint" style="margin-top:8px;">{{ count($assignedCountryIds) }} {{ Str::plural('country', count($assignedCountryIds)) }} selected.</p>
                                @endif
                            @endif
                        </div>
                    @endif

                    @if (!empty($modalAffiliatePicker))
                        <div class="card section-gap" style="padding:18px 20px;">
                            <h3 class="panel-title" style="margin-bottom:4px;">Affiliate Commission <span class="field-hint">(optional)</span></h3>
                            <p class="panel-copy" style="margin-bottom:14px;">Link this coupon to a specific affiliate. When a user applies this coupon during registration, the affiliate earns a commission on the sale.</p>

                            <div class="form-grid-2">
                                <div>
                                    <label class="field-label">Linked Affiliate</label>
                                    <select wire:model.live="affiliateId" class="field-control">
                                        <option value="">None (global coupon)</option>
                                        @foreach ($modalAffiliates as $aff)
                                            <option value="{{ $aff->id }}">{{ $aff->name }} — {{ $aff->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if (!empty($affiliateId))
                                    <div>
                                        <label class="field-label">Commission Override (%)</label>
                                        <input type="number" step="0.01" min="0" max="100"
                                               class="field-control {{ $errors->has('affiliateCommissionValue') ? 'is-invalid' : '' }}"
                                               wire:model.defer="affiliateCommissionValue"
                                               placeholder="Leave blank = affiliate's default rate">
                                        @error('affiliateCommissionValue') <span class="field-error">{{ $message }}</span> @enderror
                                        <p class="field-hint" style="margin-top:5px;">Percentage of the package sale amount credited to the affiliate when this coupon is used. Leave blank to use the affiliate's default rate.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="page-actions compact-actions justify-end">
                        <x-btn type="button" variant="secondary" wire:click="{{ $modalCloseAction }}">Cancel</x-btn>
                        <x-btn type="submit">{{ $modalSubmitLabel }}</x-btn>
                    </div>
                </form>
            @endif
        </x-modal>
    @endif
</main>

@script
<script>
    $wire.on('csv-download', ({ content, filename }) => {
        const bytes = Uint8Array.from(atob(content), c => c.charCodeAt(0));
        const blob = new Blob([bytes], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
</script>
@endscript
