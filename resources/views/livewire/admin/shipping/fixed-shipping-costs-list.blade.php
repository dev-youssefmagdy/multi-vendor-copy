<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Central Shipping</div>
            <h1 class="D page-title">Fixed Shipping Costs</h1>
            <p class="page-copy">One record per country — shipping cost = product weight × price per gram.</p>
        </div>
        <div class="page-actions">@if ($canManage)<a href="{{ route('admin.shipping.fixed-costs.create') }}"
        class="btn btn-primary">Add Cost Rule</a>@endif</div>
    </div>

    @if (session('status'))
    <div class="card section-gap notice-success">{{ session('status') }}</div>@endif

    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Rules</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">Total shipping cost rules.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Active</div>
                    <div class="D stat-value">{{ number_format($stats['active']) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Live at checkout and storefront.</p>
        </div>
        <div class="card card-glow-violet">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Countries</div>
                    <div class="D stat-value">{{ number_format($stats['countries']) }}</div>
                </div>
                <div class="mini-stat-dot dot-violet"></div>
            </div>
            <p class="section-copy">Countries with at least one active rule.</p>
        </div>
    </div>

    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Filters</h3>
                <p class="panel-copy">Narrow by country ID, ISO2, ISO3, language code, or phone code.</p>
            </div>
            <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label">Country</label><input type="text" class="field-control"
                    placeholder="ID, ISO2, ISO3, language code, or phone code…" wire:model.live.debounce.300ms="countryFilter"></div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Use the table below to browse all rules.</p>
            <button type="button" class="btn btn-secondary" wire:click="clearFilters">Reset</button>
        </div>
    </details>

    <section class="card table-card-shell">
        @if ($costs->count())
            <div class="table-scroll-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Country</th>
                            <th>ID</th>
                            <th>ISO2</th>
                            <th>ISO3</th>
                            <th>Language</th>
                            <th>Phone Code</th>
                            <th>Price / gram</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th class="ta-r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($costs as $cost)
                            <tr>
                                <td>
                                    <div class="entity-title">
                                        {{ $cost->country?->flag_emoji }} {{ $cost->country?->name ?? '—' }}
                                    </div>
                                </td>
                                <td>{{ $cost->country_id }}</td>
                                <td>{{ $cost->country?->iso2 ?? '—' }}</td>
                                <td>{{ $cost->country?->iso3 ?? '—' }}</td>
                                <td>{{ $cost->country?->language_code ?? '—' }}</td>
                                <td>{{ $cost->country?->phone_code ?? '—' }}</td>
                                <td>{{ number_format((float) $cost->price_per_gram, 6) }}</td>
                                <td>{{ strtoupper($cost->currency_code) }}</td>
                                <td>
                                    <span class="badge {{ $cost->is_active ? 'badge-green' : 'badge-amber' }}">
                                        {{ $cost->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="ta-r">
                                    @if ($canManage)
                                        <div class="table-actions-inline">
                                            <a href="{{ route('admin.shipping.fixed-costs.edit', $cost) }}"
                                                class="btn btn-secondary btn-sm">Edit</a>
                                            <button type="button" class="btn btn-secondary btn-sm btn-danger"
                                                wire:click="deleteFixedShippingCost({{ $cost->id }})"
                                                wire:confirm="Delete this shipping cost rule?">Delete</button>
                                        </div>
                                    @else
                                        <span class="entity-subtitle">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $costs->links() }}</div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">No fixed shipping costs found</h3>
                <p class="panel-copy">Add your first rule to define per-country shipping rates (price per gram).</p>
            </div>
        @endif
    </section>
</main>