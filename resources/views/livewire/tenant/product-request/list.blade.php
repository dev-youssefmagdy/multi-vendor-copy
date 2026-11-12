<main id="mn">
  <div class="page-head fu d0">
    <div>
      <div class="page-title-row">
        <h1 class="D page-title">{{ __('Product Requests') }}</h1>
        <span class="page-badge">{{ __('Catalog') }}</span>
      </div>
      <p class="page-copy">{{ __('Track the status of your product requests to the Neozena team.') }}</p>
    </div>
    <a href="{{ route('tenant.product-requests.create') }}" class="btn btn-primary">{{ __('+ New Request') }}</a>
  </div>

  <div class="g-stats3 section-gap">
    <div class="card card-glow-cyan fu d1">
      <div class="stat-head">
        <div>
          <div class="eyebrow">{{ __('Total Requests') }}</div>
          <div class="D stat-value">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-icon">
          <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
            <rect x="9" y="3" width="6" height="4" rx="1" />
          </svg>
        </div>
      </div>
      <p class="stat-sub">{{ __('All product requests you\'ve submitted.') }}</p>
    </div>
    <div class="card card-glow-green fu d2">
      <div class="stat-head">
        <div>
          <div class="eyebrow">{{ __('Open') }}</div>
          <div class="D stat-value">{{ $stats['open'] }}</div>
        </div>
        <div class="mini-stat-dot dot-green"></div>
      </div>
      <p class="stat-sub">{{ __('Requests currently in progress.') }}</p>
    </div>
    <div class="card {{ $stats['unread'] ? 'card-glow-amber' : '' }} fu d3">
      <div class="stat-head">
        <div>
          <div class="eyebrow">{{ __('Unread Replies') }}</div>
          <div class="D stat-value">{{ $stats['unread'] }}</div>
        </div>
        <div class="mini-stat-dot dot-amber"></div>
      </div>
      <p class="stat-sub">{{ __('Awaiting your review.') }}</p>
    </div>
  </div>

  <details class="card filters-card fu d4 section-gap">
    <summary class="filters-summary">
      <div>
        <div class="panel-title">{{ __('Filters') }}</div>
        <p class="panel-copy">{{ __('Narrow requests by status.') }}</p>
      </div>
      <div class="filters-summary-meta">
        <svg class="filters-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </div>
    </summary>
    <div class="filters-grid">
      <div>
        <label class="field-label">{{ __('Status') }}</label>
        <x-select wire:model.live="statusFilter">
          <option value="">{{ __('All Statuses') }}</option>
          @foreach ($statusOptions as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
          @endforeach
        </x-select>
      </div>
    </div>
  </details>

  <div class="card fu d5 table-card-shell">
    <div class="table-header-shell">
      <div>
        <h3 class="panel-title">{{ __('Your Requests') }}</h3>
        <p class="panel-copy">{{ __('Sorted by most recent activity.') }}</p>
      </div>
    </div>

    <x-table :headers="[__('Request'), __('Status'), __('Last Update'), __('Actions')]">
      @forelse ($records as $req)
        <tr wire:key="pr-{{ $req->id }}">
          <td>
            <div class="entity-title">
              {{ $req->title }}
              @if ($req->tenant_has_unread)
                <span class="badge badge-amber" style="margin-left:6px;">{{ __('New Reply') }}</span>
              @endif
            </div>
            <div class="entity-subtitle">{{ __(':code · Submitted :diffForHumans', ['code' => $req->code, 'diffForHumans' => $req->created_at->diffForHumans()]) }}</div>
          </td>
          <td><span class="{{ $req->status->badgeClass() }}">{{ __($req->status->label()) }}</span></td>
          <td><span class="entity-subtitle">{{ $req->last_reply_at?->diffForHumans() ?? '—' }}</span></td>
          <td>
            <a href="{{ route('tenant.product-requests.show', $req->id) }}" class="btn btn-secondary btn-sm">
              <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:4px;vertical-align:-1px;">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              {{ __('View') }}
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="4">
            <div class="empty-state">
              <div class="empty-state-title">{{ __('No product requests yet') }}</div>
              <p class="empty-state-copy">{{ __('Submit a request when you\'d like a new product added to the catalog.') }}</p>
              <a href="{{ route('tenant.product-requests.create') }}" class="btn btn-primary btn-sm" style="margin-top:10px;">{{ __('+ New Request') }}</a>
            </div>
          </td>
        </tr>
      @endforelse
    </x-table>

    <div class="table-footer-shell">
      <div class="panel-copy">
        {{ __('Showing :firstItem to :lastItem of :total requests.', ['firstItem' => $records->firstItem() ?? 0, 'lastItem' => $records->lastItem() ?? 0, 'total' => $records->total()]) }}
      </div>
      <div class="pagination-shell">
        <x-pagination :paginator="$records" />
      </div>
    </div>
  </div>
</main>
