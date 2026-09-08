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

    <div class="g-stats3 section-gap">
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

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Gateway Filters</h3>
                <p class="panel-copy">Narrow by payment type and activation status.</p>
            </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div>
                <label class="field-label">Type</label>
                <select class="field-control" wire:model.live="typeFilter">
                    <option value="">All types</option>
                    @foreach ($typeOptions as $typeOption)
                        <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Status</label>
                <select class="field-control" wire:model.live="statusFilter">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Configure owner-level gateway credentials and required key or value mappings.</p>
            <button type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>

    <section class="card section-gap table-card-shell">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Central Data</h3>
            </div>
        </div>

        <x-table :headers="$tableHeaders">
            @forelse ($tableRows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{!! $cell !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($tableHeaders) }}">
                        <div class="empty-state">
                            <div class="empty-state-title">No gateways match these filters</div>
                            <p class="empty-state-copy">Try clearing the filters or adjusting your selection.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>
    </section>
</main>
