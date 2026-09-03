<main id="mn">
    @php
        $cards = $cards ?? [];
        $fieldGroups = $fieldGroups ?? [];
        $actions = $actions ?? [];
        $tableHeaders = $tableHeaders ?? [];
        $tableRows = $tableRows ?? [];
        $tableSections = $tableSections ?? [];
        $chartSections = $chartSections ?? [];
        $chartPayload = $chartPayload ?? null;
        $cardsGridClass = $cardsGridClass ?? (count($cards) > 3 ? 'g-stats4' : 'g-stats3');
        $formAction = $formAction ?? null;
        $submitLabel = $submitLabel ?? 'Save Changes';

        if (!$tableSections && $tableHeaders && $tableRows) {
            $tableSections = [
                [
                    'title' => 'Tenant Data',
                    'description' => null,
                    'headers' => $tableHeaders,
                    'rows' => $tableRows,
                ]
            ];
        }
      @endphp

    @if ($chartPayload)
        <script id="dashboard-chart-data"
            type="application/json">{!! json_encode($chartPayload, JSON_THROW_ON_ERROR) !!}</script>
    @endif

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
        @if ($formAction || !empty($secondaryActions))
            <div class="page-actions">
                @foreach ($secondaryActions ?? [] as $secondaryAction)
                    <x-btn type="button" variant="secondary"
                        wire:click="{{ $secondaryAction['method'] }}">{{ $secondaryAction['label'] }}</x-btn>
                @endforeach
                @if ($formAction)
                    <x-btn type="submit" form="content-page-form">{{ $submitLabel }}</x-btn>
                @endif
            </div>
        @endif
    </div>

    @if ($cards)
        <div class="{{ $cardsGridClass }} section-gap">
            @foreach ($cards as $card)
                <div class="card {{ $card['glow'] ?? 'card-glow-cyan' }}">
                    <div class="stat-head">
                        <div>
                            <div class="eyebrow">{{ $card['label'] }}</div>
                            <div class="D stat-value">{{ $card['value'] }}</div>
                        </div>
                        <div class="mini-stat-dot {{ $card['dot'] ?? 'dot-cyan' }}"></div>
                    </div>
                    <p class="panel-copy">{{ $card['caption'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="content-grid section-gap">
        <div class="card fu d1">
            <h3 class="panel-title">Tenant Scope</h3>
            <p class="panel-copy panel-copy-spaced">{{ $contentIntro }}</p>
            <div class="content-list">
                @foreach ($bullets as $bullet)
                    <div class="content-list-item">
                        <span class="dot dot-cyan"></span>
                        <span>{{ $bullet }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card fu d2">
            <h3 class="panel-title">{{ $actions ? 'Actions' : 'Implementation Notes' }}</h3>
            <div class="content-note-stack">
                @if ($actions)
                    @foreach ($actions as $action)
                        <x-btn type="button"
                            variant="{{ ($action['variant'] ?? 'btn-secondary') === 'btn-primary' ? 'primary' : 'secondary' }}"
                            wire:click="{{ $action['method'] }}">{{ $action['label'] }}</x-btn>
                    @endforeach
                @else
                    <div class="content-note"><strong>Tenant only.</strong> This page works inside the current tenant
                        database and
                        vendor shell.</div>
                    <div class="content-note"><strong>Next step.</strong> Connect additional charts, alerts, and approval
                        flows as
                        needed.</div>
                @endif
            </div>
        </div>
    </div>

    @foreach ($chartSections as $section)
        <div class="{{ $section['layoutClass'] ?? 'g-r2' }} section-gap">
            @foreach ($section['cards'] as $chartCard)
                <section class="card {{ $chartCard['wrapperClass'] ?? '' }}">
                    <div class="panel-head">
                        <div>
                            <h3 class="panel-title">{{ $chartCard['title'] }}</h3>
                            @if (!empty($chartCard['description']))
                                <p class="panel-copy">{{ $chartCard['description'] }}</p>
                            @endif
                        </div>
                    </div>

                    @if (!empty($chartCard['canvas']))
                        <canvas id="{{ $chartCard['canvas'] }}" @if (!empty($chartCard['height']))
                        height="{{ $chartCard['height'] }}" @endif></canvas>
                    @endif

                    @if (!empty($chartCard['legend']))
                        <div class="legend-list">
                            @foreach ($chartCard['legend'] as $legend)
                                <div class="legend-row">
                                    <div class="legend-meta">
                                        <span class="dot {{ $legend['dot'] ?? 'dot-cyan' }}"></span>
                                        <span class="text-t2">{{ $legend['label'] }}</span>
                                    </div>
                                    <span class="legend-value">{{ $legend['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($chartCard['metrics']))
                        <div class="metric-list">
                            @foreach ($chartCard['metrics'] as $metric)
                                <div class="metric-row">
                                    <span class="metric-label">{{ $metric['label'] }}</span>
                                    <span class="metric-value">{{ $metric['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    @endforeach

    @if ($fieldGroups)
        <form id="content-page-form" @if ($formAction) wire:submit="{{ $formAction }}" @endif
            class="section-gap page-stack">
            @foreach ($fieldGroups as $group)
                <section class="card form-card">
                    <div class="panel-head mb-5">
                        <div>
                            <h3 class="panel-title">{{ $group['title'] }}</h3>
                            <p class="panel-copy">{{ $group['description'] ?? '' }}</p>
                        </div>
                    </div>
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
                                    <x-select wire:model.defer="{{ $field['model'] }}"
                                        class="{{ $errors->has($field['model']) ? 'is-invalid' : '' }}">
                                        @foreach ($field['options'] ?? [] as $optionValue => $optionLabel)
                                            <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                        @endforeach
                                    </x-select>
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
                </section>
            @endforeach
        </form>
    @endif

    @if ($tableSections)
        @foreach ($tableSections as $section)
            <section class="card section-gap table-card-shell">
                <div class="table-header-shell">
                    <div>
                        <h3 class="panel-title">{{ $section['title'] }}</h3>
                        @if (!empty($section['description']))
                            <p class="panel-copy">{{ $section['description'] }}</p>
                        @endif
                    </div>
                </div>
                <x-table :headers="$section['headers']">
                    @forelse ($section['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td>{!! $cell !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($section['headers']) }}">
                                <div class="empty-state">
                                    <div class="empty-state-title">No data available</div>
                                    <p class="empty-state-copy">This tenant module does not have enough records to populate the
                                        section yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
                @if (!empty($section['paginator']))
                    <div style="padding: 12px 0;">
                        <x-pagination :paginator="$section['paginator']" />
                    </div>
                @endif
            </section>
        @endforeach
    @endif

    @if (!empty($modalView))
        @include($modalView)
    @endif
</main>
