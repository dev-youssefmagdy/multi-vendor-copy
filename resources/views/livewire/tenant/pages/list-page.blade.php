<main id="mn">
    @php
        $filterFields = $filterFields ?? [];
        $rows = $rows ?? [];
        $rowClasses = $rowClasses ?? [];
        $records = $records ?? null;
        $actionUrl = $actionUrl ?? null;
        $actionMethod = $actionMethod ?? null;
        $emptyTitle = $emptyTitle ?? 'No records found';
        $emptyCopy = $emptyCopy ?? 'No tenant records match the current filters yet.';
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
        $extraModals = $extraModals ?? [];
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
                <x-btn variant="secondary" class="xs-hide" type="button" wire:click="export">Export</x-btn>
            @endif
            @if (!empty($secondaryActionLabel) && $secondaryActionUrl)
                <a href="{{ $secondaryActionUrl }}" class="btn btn-secondary">{{ $secondaryActionLabel }}</a>
            @endif
            @if (!empty($actionLabel))
                @if ($actionUrl)
                    <a href="{{ $actionUrl }}" class="btn btn-primary">{{ $actionLabel }}</a>
                @elseif ($actionMethod)
                    <x-btn type="button" wire:click="{{ $actionMethod }}">{{ $actionLabel }}</x-btn>
                @else
                    <x-btn type="button">{{ $actionLabel }}</x-btn>
                @endif
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
                <span class="filter-pill">Tenant</span>
                <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </summary>

        <div class="filters-grid">
            @forelse ($filterFields as $field)
                <div>
                    <label class="field-label">{{ $field['label'] }}</label>
                    @if (($field['type'] ?? 'text') === 'select')
                        @if (!empty($field['multiple']))
                            <x-select multiple wire:model.live="{{ $field['model'] }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="field-control {{ $errors->has($field['model']) ? 'is-invalid' : '' }}">
                                @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                @endforeach
                            </x-select>
                        @else
                            <x-select wire:model.live="{{ $field['model'] }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="field-control {{ $errors->has($field['model']) ? 'is-invalid' : '' }}">
                                @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                @endforeach
                            </x-select>
                        @endif
                    @else
                        <x-input type="{{ $field['type'] ?? 'text' }}" wire:model.live.debounce.300ms="{{ $field['model'] }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" :error="$errors->has($field['model'])" />
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
                {{ $filtersNote ?? 'Filters are wired to repository-backed tenant queries for this module.' }}
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
            @forelse ($rows as $rowIndex => $row)
                <tr class="{{ $rowClasses[$rowIndex] ?? '' }}">
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
                    records for {{ strtolower($title) }}.
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

    @if ($modalModel && ($modalFieldGroups || !empty($credentialFields) || $modalContentView) && ($showFormModal ?? false))
        <x-modal wire:model="{{ $modalModel }}" title="{{ $modalTitle }}" maxWidth="{{ $modalMaxWidth }}"
            closeAction="{{ $modalCloseAction }}">
            @if ($modalContentView)
                <form wire:submit="{{ $modalSubmitAction }}" class="page-stack">
                    @include($modalContentView, $modalContentData)
                    <div class="page-actions compact-actions justify-end">
                        <x-btn type="button" variant="secondary" wire:click="{{ $modalCloseAction }}">Cancel</x-btn>
                        <x-btn type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                            wire:target="{{ $modalSubmitAction }}">
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
                                        @if (!empty($field['multiple']))
                                            <x-select multiple wire:model.defer="{{ $field['model'] }}"
                                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                                class="{{ $errors->has($field['model']) ? 'is-invalid' : '' }}">
                                                @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                                @endforeach
                                            </x-select>
                                        @else
                                            <x-select wire:model.defer="{{ $field['model'] }}" placeholder="{{ $field['placeholder'] ?? '' }}"
                                                class="{{ $errors->has($field['model']) ? 'is-invalid' : '' }}">
                                                @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                                @endforeach
                                            </x-select>
                                        @endif
                                    @elseif (($field['type'] ?? 'text') === 'checkbox-group')
                                        <div class="checkbox-group-grid">
                                            @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                                <x-checkbox value="{{ $optionValue }}" wire:model.defer="{{ $field['model'] }}"
                                                    label="{{ $optionLabel }}" />
                                            @endforeach
                                        </div>
                                    @elseif (($field['type'] ?? 'text') === 'checkbox')
                                        @if (!empty($field['live']))
                                            <x-checkbox wire:model.live="{{ $field['model'] }}"
                                                label="{{ $field['toggleLabel'] ?? $field['label'] }}" />
                                        @else
                                            <x-checkbox wire:model.defer="{{ $field['model'] }}"
                                                label="{{ $field['toggleLabel'] ?? $field['label'] }}" />
                                        @endif
                                    @else
                                        @if (($field['type'] ?? 'text') === 'file')
                                            <x-input type="file" wire:model="{{ $field['model'] }}" :error="$errors->has($field['model'])" />
                                        @else
                                            <x-input type="{{ $field['type'] ?? 'text' }}" wire:model.defer="{{ $field['model'] }}"
                                                :error="$errors->has($field['model'])" />
                                        @endif
                                    @endif
                                    @error($field['model'])<div class="field-error">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    @if (!empty($credentialFields))
                        @if (count($credentialFields) > 0)
                            <div>
                                <label class="field-label">Credentials</label>
                                <div class="pg-credentials-list">
                                    @foreach ($credentialFields as $index => $cred)
                                        @if($cred['key'] == 'sandbox')
                                            <div class="pg-credential-row">
                                                <div class="pg-credential-label-col">
                                                    <code class="pg-credential-code">{{ $cred['key'] }}</code>
                                                </div>
                                                <div class="pg-credential-input-col">
                                                    <x-checkbox wire:model.defer="requiredFields.{{ $index }}.value" label="Sandbox mode" />
                                                    @error("requiredFields.{$index}.value")
                                                        <div class="field-error">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        @else
                                            <div class="pg-credential-row">
                                                <div class="pg-credential-label-col">
                                                    <code class="pg-credential-code">{{ $cred['key'] }}</code>
                                                </div>
                                                <div class="pg-credential-input-col">
                                                    <x-input type="password" wire:model.defer="requiredFields.{{ $index }}.value"
                                                        placeholder="Enter {{ str($cred['key'])->headline()->lower() }}" autocomplete="off" />
                                                    @error("requiredFields.{$index}.value")
                                                        <div class="field-error">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="empty-state" style="padding:16px 0;">
                                <div class="empty-state-title">No credentials required</div>
                                <p class="empty-state-copy">This gateway has no credential keys defined.</p>
                            </div>
                        @endif
                    @endif

                    <div class="page-actions compact-actions justify-end">
                        <x-btn type="button" variant="secondary" wire:click="{{ $modalCloseAction }}">Cancel</x-btn>
                        <x-btn type="submit">{{ $modalSubmitLabel }}</x-btn>
                    </div>
                </form>
            @endif
        </x-modal>
    @endif

    {{-- ── Extra modals (price-finder, share, etc.) ─────────────────────── --}}
    @foreach ($extraModals as $em)
        @if (!empty($em['model']))
            <x-modal wire:model="{{ $em['model'] }}" title="{{ $em['title'] ?? '' }}" maxWidth="{{ $em['maxWidth'] ?? '2xl' }}"
                closeAction="{{ $em['closeAction'] ?? 'closeModal' }}">
                @if (!empty($em['contentView']))
                    @include($em['contentView'], $em['contentData'] ?? [])
                @endif
                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="{{ $em['closeAction'] ?? 'closeModal' }}">Close</x-btn>
                </div>
            </x-modal>
        @endif
    @endforeach
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

@push('styles')
    @if (!empty($credentialFields))
        <style>
            .pg-credentials-list {
                display: flex;
                flex-direction: column;
                gap: 0;
                border: 1px solid var(--border);
                border-radius: 10px;
                overflow: hidden;
            }

            .pg-credential-row {
                display: grid;
                grid-template-columns: 200px 1fr;
                align-items: center;
                gap: 16px;
                padding: 14px 16px;
                border-bottom: 1px solid var(--border);
            }

            .pg-credential-row:last-child {
                border-bottom: none;
            }

            .pg-credential-row:nth-child(even) {
                background: var(--surface);
            }

            .pg-credential-label-col {
                display: flex;
                flex-direction: column;
                gap: 3px;
            }

            .pg-credential-key {
                font-size: 13px;
                font-weight: 600;
                color: var(--t1);
            }

            .pg-credential-code {
                font-size: 11px;
                color: var(--t3);
                font-family: monospace;
                background: none;
                padding: 0;
            }

            .pg-credential-input-col {
                flex: 1;
            }

            @media (max-width: 600px) {
                .pg-credential-row {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endif
@endpush
