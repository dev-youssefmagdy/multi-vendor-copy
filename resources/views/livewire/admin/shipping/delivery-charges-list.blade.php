<main id="mn">
  <div class="page-head fu d0">
    <div>
      <div class="eyebrow">Central Shipping</div>
      <h1 class="D page-title">Shipping Zones</h1>
      <p class="page-copy">Manage shipping regions, localized labels, and weight-based rate bands.</p>
    </div>
    <div class="page-actions">@if ($canManageShipping)<a href="{{ route('admin.shipping.delivery-charges.create') }}"
    class="btn btn-primary">Add Zone</a>@endif</div>
  </div>
  @if (session('status'))
  <div class="card section-gap notice-success">{{ session('status') }}</div>@endif
  <div class="g-stats3 section-gap">
    <div class="card card-glow-cyan">
      <div class="stat-head">
        <div>
          <div class="eyebrow">Zones</div>
          <div class="D stat-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="mini-stat-dot dot-cyan"></div>
      </div>
      <p class="section-copy">Configured shipping regions.</p>
    </div>
    <div class="card card-glow-green">
      <div class="stat-head">
        <div>
          <div class="eyebrow">Active</div>
          <div class="D stat-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="mini-stat-dot dot-green"></div>
      </div>
      <p class="section-copy">Live for checkout calculations.</p>
    </div>
    <div class="card card-glow-violet">
      <div class="stat-head">
        <div>
          <div class="eyebrow">Rates</div>
          <div class="D stat-value">{{ number_format($stats['rates']) }}</div>
        </div>
        <div class="mini-stat-dot dot-violet"></div>
      </div>
      <p class="section-copy">Weight-band rules across all zones.</p>
    </div>
  </div>
  <details class="card filters-card section-gap" open>
    <summary class="filters-summary">
      <div>
        <h3 class="panel-title">Zone Filters</h3>
        <p class="panel-copy">Search by localized name or code and filter by status.</p>
      </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2.5">
        <polyline points="6 9 12 15 18 9" />
      </svg>
    </summary>
    <div class="filters-grid">
      <div><label class="field-label">Search</label><input type="text" class="field-control"
          wire:model.live.debounce.300ms="search"></div>
      <div><label class="field-label">Status</label><select class="field-control" wire:model.live="statusFilter">
          <option value="">All statuses</option>@foreach ($statusOptions as $statusOption)<option
          value="{{ $statusOption->value }}">{{ ucfirst($statusOption->value) }}</option>@endforeach
        </select></div>
    </div>
    <div class="filters-actions">
      <p class="filters-note">Rates are managed inside each zone editor.</p><button type="button"
        class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
    </div>
  </details>
  <section class="card table-card-shell">@if ($zones->count())
    <div class="table-scroll-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Zone</th>
            <th>Code</th>
            <th>Rates</th>
            <th>Products</th>
            <th>Status</th>
            <th class="ta-r">Actions</th>
          </tr>
        </thead>
        <tbody>@foreach ($zones as $zone)<tr>
          <td>
            <div class="entity-title">{{ $zone->name }}</div>
            <div class="entity-subtitle">{{ $zone->title ?? 'Localized shipping zone' }}</div>
          </td>
          <td>{{ strtoupper($zone->code) }}</td>
          <td>{{ $zone->rates_count }}</td>
          <td>{{ $zone->products_count }}</td>
          <td><span
              class="badge {{ $zone->status === \App\Enums\ShippingZoneStatus::Active ? 'badge-green' : 'badge-amber' }}">{{ ucfirst($zone->status->value) }}</span>
          </td>
          <td class="ta-r">@if ($canManageShipping)
            <div class="table-actions-inline"><a href="{{ route('admin.shipping.delivery-charges.edit', $zone) }}"
                class="btn btn-secondary btn-sm">Edit</a><button type="button"
                class="btn btn-secondary btn-sm btn-danger"
          wire:click="deleteShippingZone({{ $zone->id }})">Delete</button></div>@else<span
                class="entity-subtitle">View only</span>@endif
          </td>
        </tr>@endforeach</tbody>
      </table>
  </div>@else<div class="empty-state">
      <h3 class="panel-title">No shipping zones found</h3>
      <p class="panel-copy">Create the first delivery zone and attach its weight-rate bands.</p>
    </div>@endif
  </section>
</main>