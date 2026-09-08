<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Localization</div>
            <h1 class="D page-title">Translation Key Access</h1>
            <p class="page-copy">Control which static translation keys tenants may edit or AI-translate on their storefront. Locked keys can only be changed here.</p>
        </div>
    </div>

    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Total Keys</div>
                    <div class="D stat-value">{{ number_format($keyCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">Static UI-string keys discovered across lang resources.</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Locked</div>
                    <div class="D stat-value">{{ number_format($lockedCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="section-copy">Keys tenants cannot edit or translate on their own.</p>
        </div>
    </div>

    <section class="card table-card-shell section-gap fu d2">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">Keys</h3>
                <p class="panel-copy">Toggle a key to Locked to prevent tenant edits/AI translation.</p>
            </div>
            <div class="form-field" style="min-width:260px;">
                <input type="text" class="field-control" wire:model.live.debounce.400ms="search" placeholder="Search keys...">
            </div>
        </div>

        @if (count($rows) > 0)
            <x-table :headers="['Key', 'Access', 'Actions']">
                @foreach ($rows as $row)
                    <tr wire:key="key-{{ $row['key'] }}">
                        <td><span style="font-family:monospace;font-size:12px;">{{ $row['key'] }}</span></td>
                        <td>
                            @if ($row['locked'])
                                <span class="badge badge-amber">Locked</span>
                            @else
                                <span class="badge badge-green">Tenant editable</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-secondary"
                                    wire:click="toggleLock('{{ $row['key'] }}')"
                                    @disabled(!$canManage)>
                                {{ $row['locked'] ? 'Unlock' : 'Lock' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="table-footer-shell">
                <span class="panel-copy">Showing {{ count($rows) }} of {{ $total }} keys</span>
                <div style="display:flex;gap:6px;">
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="setPage({{ max(1, $page - 1) }})" @disabled($page <= 1)>Prev</button>
                    <span class="panel-copy">Page {{ $page }} / {{ $lastPage }}</span>
                    <button type="button" class="btn btn-sm btn-secondary" wire:click="setPage({{ min($lastPage, $page + 1) }})" @disabled($page >= $lastPage)>Next</button>
                </div>
            </div>
        @else
            <p class="panel-copy" style="padding:24px;">No translation keys match your search.</p>
        @endif
    </section>
</main>
