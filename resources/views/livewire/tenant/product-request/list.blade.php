<main id="mn">
  <div class="page-head fu d0">
    <div>
      <div class="page-title-row">
        <h1 class="D page-title">Product Requests</h1>
        <span class="page-badge">Catalog</span>
      </div>
      <p class="page-copy">Track the status of your product requests to the Neozena team.</p>
    </div>
    <a href="{{ route('tenant.product-requests.create') }}" class="btn btn-primary">+ New Request</a>
  </div>

  <div class="g-stats3 section-gap">
    <div class="card stat-card fu d1">
      <span class="dot dot-cyan"></span>
      <div class="stat-val">{{ $stats['total'] }}</div>
      <div class="stat-label">Total Requests</div>
    </div>
    <div class="card stat-card fu d2">
      <span class="dot dot-green"></span>
      <div class="stat-val">{{ $stats['open'] }}</div>
      <div class="stat-label">Open</div>
    </div>
    <div class="card stat-card fu d3 {{ $stats['unread'] ? 'card-glow-amber' : '' }}">
      <span class="dot dot-amber"></span>
      <div class="stat-val">{{ $stats['unread'] }}</div>
      <div class="stat-label">Unread Replies</div>
    </div>
  </div>

  <div class="card fu d4" style="padding:16px 20px;display:flex;gap:12px;align-items:center;">
    <x-select wire:model.live="statusFilter" class="field-control" style="max-width:200px;">
      <option value="">All Statuses</option>
      @foreach ($statusOptions as $val => $label)
        <option value="{{ $val }}">{{ $label }}</option>
      @endforeach
    </x-select>
  </div>

  <div class="card fu d5">
    <table class="panel-table">
      <thead>
        <tr>
          <th>Request</th>
          <th>Status</th>
          <th>Last Update</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($records as $req)
          <tr>
            <td>
              <div class="entity-title">
                {{ $req->title }}
                @if ($req->tenant_has_unread)
                  <span class="badge badge-amber" style="margin-left:6px;">New Reply</span>
                @endif
              </div>
              <div class="entity-subtitle">#{{ $req->id }} &middot; Submitted {{ $req->created_at->diffForHumans() }}</div>
            </td>
            <td><span class="badge {{ $req->status->badgeClass() }}">{{ $req->status->label() }}</span></td>
            <td class="entity-subtitle">{{ $req->last_reply_at?->diffForHumans() ?? '—' }}</td>
            <td><a href="{{ route('tenant.product-requests.show', $req->id) }}" class="btn btn-secondary btn-sm">View</a></td>
          </tr>
        @empty
          <tr><td colspan="4" class="panel-empty">No product requests yet. <a href="{{ route('tenant.product-requests.create') }}">Create one</a>.</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="pagination-wrap">{{ $records->links() }}</div>
  </div>
</main>
