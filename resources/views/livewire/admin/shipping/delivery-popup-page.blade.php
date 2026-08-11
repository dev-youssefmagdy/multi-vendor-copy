<main id="mn">
    @php
        $actionMethod = $actionMethod ?? null;
        $groups       = $groups       ?? [];
    @endphp

    {{-- Page head --}}
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
            @if ($actionMethod)
                <x-btn type="button" wire:click="{{ $actionMethod }}">{{ $actionLabel }}</x-btn>
            @endif
        </div>
    </div>

    {{-- Statistics --}}
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

    {{-- Country-grouped day sections --}}
    @if (empty($groups))
        <div class="card fu d4 section-gap">
            <div class="empty-state">
                <div class="empty-state-title">No day entries yet</div>
                <p class="empty-state-copy">Click "Add Day" to create your first delivery-probability entry.</p>
            </div>
        </div>
    @else
        @foreach ($groups as $group)
            @php
                $cid      = $group['country_id'];
                $cidArg   = $cid === null ? 'null' : $cid;
                $isDefault = $group['is_default'];
                $pctOk    = $group['pct_ok'];
                $sumPct   = $group['sum_pct'];
            @endphp
            <div class="card fu d{{ min($loop->iteration + 3, 9) }} table-card-shell section-gap" wire:key="group-{{ $cid ?? 'default' }}">
                {{-- Group header --}}
                <div class="table-header-shell">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h3 class="panel-title" style="margin:0;">
                            @if ($isDefault)
                                <span style="margin-right:4px;">🌐</span>
                                Global Default
                                <span class="badge badge-cyan" style="margin-left:6px;font-size:10px;vertical-align:middle;">Fallback</span>
                            @else
                                {{ $group['label'] }}
                            @endif
                        </h3>
                        <span class="badge {{ $pctOk ? 'badge-green' : 'badge-amber' }}" title="Sum of all percentage values in this group">
                            {{ number_format($sumPct, 1) }}% total
                        </span>
                        @if (!$pctOk)
                            <span class="badge badge-red" style="font-size:10px;">Should total ~100%</span>
                        @endif
                    </div>
                    <div class="table-header-actions">
                        <button type="button" class="btn btn-secondary btn-sm"
                            wire:click="openCreateModal({{ $cidArg }})">
                            + Add Day
                        </button>
                    </div>
                </div>

                {{-- Days table --}}
                @if (empty($group['rows']))
                    <div class="empty-state" style="padding:24px 0;">
                        <p class="empty-state-copy">No days configured for this group yet.</p>
                    </div>
                @else
                    <x-table :headers="['Day', 'Percentage', 'Bar Preview', 'Actions']">
                        @foreach ($group['rows'] as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td>{!! $cell !!}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </x-table>
                @endif
            </div>
        @endforeach
    @endif

    {{-- Add / Edit modal --}}
    @if ($modalModel && $modalContentView)
        <x-modal wire:model="{{ $modalModel }}" title="{{ $modalTitle }}" maxWidth="{{ $modalMaxWidth }}"
            closeAction="{{ $modalCloseAction }}">
            <form wire:submit="{{ $modalSubmitAction }}" class="page-stack">
                @include($modalContentView)
                <div class="page-actions compact-actions justify-end">
                    <x-btn type="button" variant="secondary" wire:click="{{ $modalCloseAction }}">Cancel</x-btn>
                    <x-btn type="submit" wire:loading.attr="disabled" wire:target="{{ $modalSubmitAction }}">
                        <span wire:loading.remove wire:target="{{ $modalSubmitAction }}">{{ $modalSubmitLabel }}</span>
                        <span wire:loading wire:target="{{ $modalSubmitAction }}">Saving...</span>
                    </x-btn>
                </div>
            </form>
        </x-modal>
    @endif
</main>
