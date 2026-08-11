<main id="mn">
  <div class="page-head fu d0">
    <div>
      <div class="eyebrow">Central Catalog</div>
      <h1 class="D page-title">Catalogs</h1>
      <p class="page-copy">Manage catalog scopes that tenants and central categories are assigned to.</p>
    </div>
    <div class="page-actions">
      @if ($canManageCatalogs)
        <a href="{{ route('admin.catalogs.create') }}" class="btn btn-primary">Add Catalog</a>
      @endif
    </div>
  </div>

  @if (session('status'))
    <div class="card section-gap notice-success">{{ session('status') }}</div>
  @endif

  <div class="g-stats3 section-gap">
    <div class="card card-glow-cyan">
      <div class="stat-head">
        <div>
          <div class="eyebrow">Catalogs</div>
          <div class="D stat-value">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="mini-stat-dot dot-cyan"></div>
      </div>
      <p class="section-copy">Catalog scopes available for tenant assignment.</p>
    </div>
    <div class="card card-glow-green">
      <div class="stat-head">
        <div>
          <div class="eyebrow">Active</div>
          <div class="D stat-value">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="mini-stat-dot dot-green"></div>
      </div>
      <p class="section-copy">Catalogs ready for product/category syncing.</p>
    </div>
    <div class="card card-glow-amber">
      <div class="stat-head">
        <div>
          <div class="eyebrow">Linked Tenants</div>
          <div class="D stat-value">{{ number_format($stats['linked_tenants']) }}</div>
        </div>
        <div class="mini-stat-dot dot-amber"></div>
      </div>
      <p class="section-copy">Catalogs currently assigned to at least one tenant.</p>
    </div>
  </div>

  <details class="card filters-card section-gap" open>
    <summary class="filters-summary">
      <div>
        <h3 class="panel-title">Catalog Filters</h3>
        <p class="panel-copy">Search by slug, translated name, or status.</p>
      </div><svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        stroke-width="2.5">
        <polyline points="6 9 12 15 18 9" />
      </svg>
    </summary>
    <div class="filters-grid">
      <div><label class="field-label">Search</label><input type="text" class="field-control"
          wire:model.live.debounce.300ms="search" placeholder="Slug or translated name"></div>
      <div><label class="field-label">Status</label><select class="field-control" wire:model.live="statusFilter">
          <option value="">All statuses</option>
          <option value="active">Active</option>
          <option value="draft">Draft</option>
          <option value="archived">Archived</option>
        </select></div>
    </div>
    <div class="filters-actions">
      <p class="filters-note">Catalogs define which tenants receive which central categories and products.</p><button
        type="button" class="btn btn-secondary" wire:click="clearFilters">Reset Filters</button>
    </div>
  </details>

  <section class="card table-card-shell">
    @if ($catalogs->count())
      <div class="table-scroll-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Catalog</th>
              <th>Status</th>
              <th>Categories</th>
              <th>Tenants</th>
              <th>Updated</th>
              <th class="ta-r">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($catalogs as $catalog)
              <tr>
                <td>
                  <div class="entity-title">{{ $catalog->name ?? $catalog->slug }}</div>
                  <div class="entity-subtitle">/{{ $catalog->slug }}</div>
                </td>
                <td><span
                    class="badge {{ $catalog->status === 'active' ? 'badge-green' : ($catalog->status === 'archived' ? 'badge-red' : 'badge-amber') }}">{{ ucfirst($catalog->status) }}</span>
                </td>
                <td>{{ number_format($catalog->categories_count) }}</td>
                <td>{{ number_format($catalog->tenants_count) }}</td>
                <td>{{ $catalog->updated_at?->format('M d, Y') }}</td>
                <td class="ta-r">@if ($canManageCatalogs)
                  <div class="table-actions-inline"><a href="{{ route('admin.catalogs.edit', $catalog) }}"
                class="btn btn-secondary btn-sm">Edit</a></div>@else<span class="entity-subtitle">View
                      only</span>@endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="empty-state">
        <h3 class="panel-title">No catalogs found</h3>
        <p class="panel-copy">Create a catalog before assigning categories or tenants to it.</p>
      </div>
    @endif
  </section>
</main>