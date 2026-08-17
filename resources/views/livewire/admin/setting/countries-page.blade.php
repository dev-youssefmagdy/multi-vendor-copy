<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Localization</div>
            <h1 class="D page-title">Countries</h1>
            <p class="page-copy">Control which countries tenants can select as target markets during registration, and which of them are free.</p>
        </div>
    </div>
    @if (session('status'))
    <div class="card section-gap notice-success">{{ session('status') }}</div>@endif
    <details class="card filters-card section-gap" open>
        <summary class="filters-summary">
            <div>
                <h3 class="panel-title">Country Filters</h3>
                <p class="panel-copy">Search by name or ISO code.</p>
            </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2.5">
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </summary>
        <div class="filters-grid">
            <div><label class="field-label">Search</label><input type="text" class="field-control"
                    wire:model.live.debounce.300ms="search"></div>
        </div>
    </details>
    <section class="card table-card-shell">@if ($countries->count())
        <div class="table-scroll-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>ISO</th>
                        <th>Available to Tenants</th>
                        <th>Free</th>
                    </tr>
                </thead>
                <tbody>@foreach ($countries as $country)<tr>
                    <td>
                        <div class="entity-title">@if($country->flag_emoji) {{ $country->flag_emoji }} @endif {{ $country->name ?: $country->iso2 }}</div>
                    </td>
                    <td>{{ strtoupper($country->iso2) }}</td>
                    <td>
                        @if ($canManageCountries)
                            <button type="button" wire:click="toggleActiveForTenants({{ $country->id }})"
                                class="badge {{ $country->is_active_for_tenants ? 'badge-green' : 'badge-amber' }}" style="border:none;cursor:pointer;">
                                {{ $country->is_active_for_tenants ? 'Available' : 'Disabled' }}
                            </button>
                        @else
                            <span class="badge {{ $country->is_active_for_tenants ? 'badge-green' : 'badge-amber' }}">{{ $country->is_active_for_tenants ? 'Available' : 'Disabled' }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($canManageCountries)
                            <button type="button" wire:click="toggleFree({{ $country->id }})"
                                class="badge {{ $country->is_free ? 'badge-green' : 'badge-secondary' }}" style="border:none;cursor:pointer;">
                                {{ $country->is_free ? 'Free' : 'Standard' }}
                            </button>
                        @else
                            <span class="badge {{ $country->is_free ? 'badge-green' : 'badge-secondary' }}">{{ $country->is_free ? 'Free' : 'Standard' }}</span>
                        @endif
                    </td>
                </tr>@endforeach</tbody>
            </table>
        </div>
        <div class="section-gap">{{ $countries->links() }}</div>
    @else<div class="empty-state">
            <h3 class="panel-title">No countries found</h3>
            <p class="panel-copy">Adjust your search filters.</p>
        </div>@endif
    </section>
</main>
