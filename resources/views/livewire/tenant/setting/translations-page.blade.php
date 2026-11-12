<main id="mn">
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
    </div>

    @unless ($aiTranslationEnabled)
        <div class="card section-gap fu d1" style="border-color:var(--warning,#f59e0b);">
            <p class="panel-copy" style="margin:0;">
                <strong>{{ __('AI Translation is a paid add-on.') }}</strong> {{ __('Upgrade your plan to unlock "Translate with AI" and "Translate Store". You can still edit translations manually below.') }}
            </p>
        </div>
    @endunless

    {{-- ── Language selector + Translate Store ─────────────────────────── --}}
    <section class="card section-gap fu d2" @if($polling) wire:poll.3s="$refresh" @endif>
        <div class="table-header-shell" style="flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <div class="form-field" style="min-width:220px;">
                    <label class="field-label">{{ __('Language') }}</label>
                    <select class="field-control" wire:model.live="selectedLanguageId">
                        @foreach ($languages as $language)
                            <option value="{{ $language->id }}">{{ $language->name }} ({{ strtoupper($language->code) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field" style="min-width:220px;">
                    <label class="field-label">{{ __('Search') }}</label>
                    <input type="text" class="field-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search keys or values...') }}">
                </div>
                <label style="display:flex;align-items:center;gap:8px;margin-top:22px;cursor:pointer;">
                    <input type="checkbox" wire:model.live="showOnlyMissing">
                    <span class="panel-copy" style="margin:0;">{{ __('Show only missing') }}</span>
                </label>
            </div>

            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                <button type="button" class="btn btn-primary" wire:click="translateStore"
                        @disabled(!$aiTranslationEnabled || $polling)>
                    <i class="fas fa-language"></i> {{ __('Translate Store') }}
                </button>

                @if ($selectedLanguage && $selectedLanguage->translation_status)
                    <div style="min-width:220px;">
                        @if (in_array($selectedLanguage->translation_status, ['queued', 'running']))
                            <div class="progress-track" style="height:8px;border-radius:999px;background:var(--border);overflow:hidden;">
                                <div style="height:100%;width:{{ $selectedLanguage->translation_progress }}%;background:var(--primary,#FF4B2B);transition:width .3s;"></div>
                            </div>
                            <p class="panel-copy" style="margin:4px 0 0;font-size:12px;">
                                {{ __(ucfirst($selectedLanguage->translation_status)) }} — {{ $selectedLanguage->translation_progress }}%
                            </p>
                        @elseif ($selectedLanguage->translation_status === 'completed')
                            @php $summary = json_decode((string) $selectedLanguage->translation_summary, true); @endphp
                            <span class="badge badge-green">{{ __('Completed — :itemstranslated items translated', ['itemstranslated' => $summary['items_translated'] ?? 0]) }}</span>
                        @elseif ($selectedLanguage->translation_status === 'failed')
                            <span class="badge badge-red">{{ __('Translation failed') }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ── Selected-keys AI translate bar ───────────────────────────────── --}}
    @if (count($selectedKeys ?? []) > 0)
        <div class="card section-gap fu d3" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <span class="panel-copy" style="margin:0;">{{ __(':selectedKeysCount key(s) selected', ['selectedKeysCount' => count($selectedKeys)]) }}</span>
            <button type="button" class="btn btn-secondary" wire:click="translateSelectedWithAi" @disabled(!$aiTranslationEnabled)>
                <i class="fas fa-robot"></i> {{ __('Translate Selected with AI') }}
            </button>
        </div>
    @endif

    {{-- ── Keys table ────────────────────────────────────────────────────── --}}
    <section class="card table-card-shell section-gap fu d4">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">{{ __('Translation Keys') }}</h3>
                <p class="panel-copy">{{ __('Manual edits always take priority over the default text. Locked keys are managed by the marketplace admin.') }}</p>
            </div>
        </div>

        @if (count($rows) > 0)
            <x-table :headers="['', __('Key'), __('Default'), __('Current Value'), __('Actions')]">
                @foreach ($rows as $row)
                    <tr wire:key="tr-{{ $row['key'] }}">
                        <td>
                            <input type="checkbox" value="{{ $row['key'] }}" wire:model.live="selectedKeys" @disabled($row['locked'])>
                        </td>
                        <td>
                            <div class="entity-title" style="font-family:monospace;font-size:12px;">{{ $row['key'] }}</div>
                            @if ($row['locked'])
                                <span class="badge badge-secondary" style="font-size:10px;">{{ __('Locked') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="muted">{{ \Illuminate\Support\Str::limit($row['default'], 80) }}</span>
                        </td>
                        <td>
                            <form wire:submit.prevent="saveKey('{{ $row['key'] }}', $refs.value_{{ $loop->index }}.value)">
                                <input type="text" x-ref="value_{{ $loop->index }}" class="field-control" value="{{ $row['value'] }}" @disabled($row['locked'])>
                            </form>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <button type="button" class="btn btn-sm btn-secondary"
                                        onclick="this.closest('tr').querySelector('form').requestSubmit()"
                                        @disabled($row['locked'])>
                                    {{ __('Save') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-primary"
                                        wire:click="translateKeyWithAi('{{ $row['key'] }}')"
                                        @disabled(!$aiTranslationEnabled || $row['locked'])>
                                    <i class="fas fa-robot"></i> {{ __('AI') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="table-footer-shell">
                <span class="panel-copy">{{ __('Showing :rowsCount of :total keys', ['rowsCount' => count($rows), 'total' => $total]) }}</span>
                <div style="display:flex;gap:6px;">
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="setPage({{ max(1, $page - 1) }})" @disabled($page <= 1)>{{ __('Prev') }}</button>
                    <span class="panel-copy">{{ __('Page :page / :lastPage', ['page' => $page, 'lastPage' => $lastPage]) }}</span>
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="setPage({{ min($lastPage, $page + 1) }})" @disabled($page >= $lastPage)>{{ __('Next') }}</button>
                </div>
            </div>
        @else
            <p class="panel-copy" style="padding:24px;">{{ __('No translation keys match your filters.') }}</p>
        @endif
    </section>
</main>
