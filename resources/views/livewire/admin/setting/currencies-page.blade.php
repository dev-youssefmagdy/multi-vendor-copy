<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Localization</div>
            <h1 class="D page-title">Currencies</h1>
            <p class="page-copy">Manage the central currency catalog used for exchange-rate sync and tenant storefront
                currency records.</p>
        </div>
        <div class="page-actions">@if ($canManageCurrencies)<a href="{{ route('admin.settings.currencies.create') }}"
        class="btn btn-primary">Add Currency</a>@endif</div>
    </div>
    @if (session('status'))
    <div class="card section-gap notice-success">{{ session('status') }}</div>@endif
    <div class="g-stats3 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Currencies</div>
                    <div class="D stat-value">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="mini-stat-dot dot-cyan"></div>
            </div>
            <p class="section-copy">Central currencies available for tenant sync.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Default</div>
                    <div class="D stat-value">{{ $stats['default'] }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="section-copy">Current marketplace default currency code.</p>
        </div>
        <div class="card card-glow-amber">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Rates</div>
                    <div class="D stat-value">{{ number_format($stats['configured_rates']) }}</div>
                </div>
                <div class="mini-stat-dot dot-amber"></div>
            </div>
            <p class="section-copy">Currencies currently holding a conversion rate value.</p>
        </div>
    </div>
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Currency Filters</h3>
                <p class="panel-copy">Search currencies by ISO code, name, or symbol.</p>
            </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label">Search</label><input type="text" class="field-control"
                    wire:model.live.debounce.300ms="search"></div>
        </div>
        <div class="filters-actions">
            <p class="filters-note">Use the rate update command to refresh values from USD for all seeded currencies.
            </p><button type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
        </div>
    </details>
    <section class="card table-card-shell">@if ($currencies->count())
        <div class="table-scroll-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Symbol</th>
                        <th>Rate From USD</th>
                        <th>Default</th>
                        <th class="ta-r">Actions</th>
                    </tr>
                </thead>
                <tbody>@foreach ($currencies as $currency)<tr>
                    <td>{{ strtoupper((string) $currency->code) }}</td>
                    <td>
                        <div class="entity-title">{{ $currency->name }}</div>
                    </td>
                    <td>{{ $currency->sign ?: '—' }}</td>
                    <td>{{ number_format((float) $currency->conversion_rate, 6) }}</td>
                    <td>{{ $currency->code === $stats['default'] ? 'Default' : 'Secondary' }}</td>
                    <td class="ta-r">
                        @if ($canManageCurrencies)
                            <div class="table-actions-inline"><a
                                    href="{{ route('admin.settings.currencies.edit', $currency) }}"
                                    class="btn btn-secondary btn-sm">Edit</a><button type="button"
                                    class="btn btn-secondary btn-sm btn-danger"
                                    wire:click="deleteCurrency({{ $currency->id }})">Delete</button></div>
                        @else
                            <span class="entity-subtitle">View only</span>
                        @endif
                    </td>
                </tr>@endforeach</tbody>
            </table>
        </div>
        <div class="table-footer-shell">
            <div class="panel-copy">Showing {{ $currencies->firstItem() ?? 0 }} to {{ $currencies->lastItem() ?? 0 }} of
                {{ $currencies->total() }} currencies.
            </div>
            <div class="pagination-shell"><x-pagination :paginator="$currencies" /></div>
    </div>@else<div class="empty-state">
            <h3 class="panel-title">No currencies found</h3>
            <p class="panel-copy">Seed the world currency list or add the first currency manually.</p>
        </div>@endif
    </section>
</main>