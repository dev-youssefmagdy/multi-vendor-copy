<main id="mn">
    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
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
            <x-btn type="button" wire:click="openCreateModal">{{ $actionLabel }}</x-btn>
        </div>
    </div>

    {{-- ── Statistics ──────────────────────────────────────────────────────── --}}
    <div class="g-stats3 section-gap">
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

    {{-- ── Default Shipping Days ────────────────────────────────────────────── --}}
    <div class="card fu d3 section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Default Shipping Days</h3>
                <p class="panel-copy">The base number of days used to estimate delivery for all orders before any stop
                    periods are added.</p>
            </div>
        </div>
        <form wire:submit="saveDefaultDays" class="page-stack" style="max-width:28rem;">
            <div>
                <label class="field-label">Default Shipping Days</label>
                <x-input type="number" wire:model.defer="defaultShippingDays" min="1" max="365" placeholder="e.g. 3" />
                @error('defaultShippingDays')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="page-actions compact-actions">
                <x-btn type="submit">Save Default Days</x-btn>
            </div>
        </form>
    </div>

    {{-- ── Stop Periods Table ───────────────────────────────────────────────── --}}
    <div class="card fu d4 table-card-shell">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">{{ $tableTitle }}</h3>
                <p class="panel-copy">Date ranges during which shipping is paused. These are synced to all tenant
                    storefronts and automatically add days to delivery estimates.</p>
            </div>
        </div>

        <x-table :headers="$headers">
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{!! $cell !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">
                        <div class="empty-state">
                            <div class="empty-state-title">No stop periods configured</div>
                            <p class="empty-state-copy">Add a stop period to pause shipping during holidays, maintenance
                                windows, or other planned outages.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-table>

        <div class="table-footer-shell">
            <div class="panel-copy">
                @if ($records)
                    Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of {{ $records->total() }}
                    records.
                @else
                    Showing 0 to 0 of 0 records.
                @endif
            </div>
            <div class="pagination-shell">
                @if ($records)
                    <x-pagination :paginator="$records" />
                @endif
            </div>
        </div>
    </div>

    {{-- ── Create / Edit Stop Period Modal ─────────────────────────────────── --}}
    <x-modal wire:model="showFormModal" :title="$modalTitle" maxWidth="2xl" closeAction="closeModal">
        <form wire:submit="saveStop" class="page-stack">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">From Date</label>
                    <x-input type="date" wire:model.defer="fromDate" />
                    @error('fromDate') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">To Date</label>
                    <x-input type="date" wire:model.defer="toDate" />
                    @error('toDate') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="span-2">
                    <label class="field-label">Reason</label>
                    <x-input type="text" wire:model.defer="reason"
                        placeholder="e.g. National holiday, warehouse maintenance" />
                    @error('reason') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-checkbox wire:model.defer="stopActive" label="This stop period is active" />
                </div>
            </div>
            <div class="page-actions compact-actions justify-end">
                <x-btn type="button" variant="secondary" wire:click="closeModal">Cancel</x-btn>
                <x-btn type="submit">{{ $modalSubmitLabel }}</x-btn>
            </div>
        </form>
    </x-modal>
</main>