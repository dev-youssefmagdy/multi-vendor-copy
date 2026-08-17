<main id="mn">
  <script id="dashboard-chart-data" type="application/json">{!! json_encode([
      'revenueLabels' => collect($series)->pluck('label')->values(),
      'revenueData' => collect($series)->pluck('revenue')->values(),
      'ordersData' => collect($series)->pluck('orders')->values(),
      'donutLabels' => ['Tenants', 'Products', 'Packages'],
      'donutData' => [$stats['tenants'], $stats['products'], $stats['packages']],
  ], JSON_THROW_ON_ERROR) !!}</script>

  <div class="page-head fu d0">
    <div>
      <h1 class="D page-title">Dashboard Overview</h1>
      <p class="page-copy">Marketplace activity aggregated from tenant orders, packages, tenants, and products.</p>
    </div>
  </div>

  <div class="g-stats section-gap">
    <div class="card fu d1 card-glow-cyan"><div class="stat-head"><div><div class="eyebrow">Plan Revenue</div><div class="D stat-value">${{ number_format($stats['revenue'], 2) }}</div></div><div class="mini-stat-dot dot-cyan"></div></div><p class="section-copy">Captured subscription payments in the central database.</p></div>
    <div class="card fu d2 card-glow-violet"><div class="stat-head"><div><div class="eyebrow">Tenants</div><div class="D stat-value">{{ number_format($stats['tenants']) }}</div></div><div class="mini-stat-dot dot-violet"></div></div><p class="section-copy">All tenant accounts across the marketplace.</p></div>
    <div class="card fu d3 card-glow-green"><div class="stat-head"><div><div class="eyebrow">Orders</div><div class="D stat-value">{{ number_format($stats['orders']) }}</div></div><div class="mini-stat-dot dot-green"></div></div><p class="section-copy">Aggregated directly from tenant databases.</p></div>
    <div class="card fu d4 card-glow-amber"><div class="stat-head"><div><div class="eyebrow">Products</div><div class="D stat-value">{{ number_format($stats['products']) }}</div></div><div class="mini-stat-dot dot-amber"></div></div><p class="section-copy">Central catalog products available to tenants.</p></div>
  </div>

  <div class="g-r2 section-gap">
    <div class="card fu d2">
      <div class="chart-head chart-head-wrap"><div><h3 class="D section-title">Revenue Overview</h3><p class="section-copy">Monthly subscription revenue and order volume.</p></div><div class="period-btns" id="periodBtns"><button type="button" class="pb p-act" data-action="set-period">Monthly</button></div></div>
      <canvas id="revenueChart"></canvas>
    </div>
    <div class="card fu d3">
      <div class="section-stack-lg"><h3 class="D section-title">Marketplace Mix</h3><p class="section-copy">Tenant, product, and package distribution.</p></div>
      <div class="donut-wrap"><canvas id="donutChart"></canvas></div>
      <div class="legend-list">
        <div class="legend-row"><div class="legend-meta"><span class="dot dot-cyan"></span><span class="text-t2">Tenants</span></div><span class="legend-value">{{ number_format($stats['tenants']) }}</span></div>
        <div class="legend-row"><div class="legend-meta"><span class="dot dot-violet"></span><span class="text-t2">Products</span></div><span class="legend-value">{{ number_format($stats['products']) }}</span></div>
        <div class="legend-row"><div class="legend-meta"><span class="dot dot-green"></span><span class="text-t2">Packages</span></div><span class="legend-value">{{ number_format($stats['packages']) }}</span></div>
      </div>
    </div>
  </div>

  <div class="g-r3 section-gap">
    <div class="card fu d2"><div class="panel-head"><div><h3 class="D panel-title">Orders Volume</h3><p class="panel-copy">Monthly order count</p></div></div><canvas id="barChart"></canvas></div>
    <div class="card fu d3"><div class="panel-head"><div><h3 class="D panel-title">Tenant Growth</h3><p class="panel-copy">Active tenant momentum</p></div></div><canvas id="lineChart"></canvas></div>
    <div class="card fu d4"><div class="panel-head"><div><h3 class="D panel-title">Performance</h3><p class="panel-copy">Operational score snapshot</p></div></div><canvas id="radarChart"></canvas></div>
  </div>

  <div class="card section-gap">
    <div class="transactions-head"><div><h3 class="D section-title">Latest Plan Payments</h3><p class="section-copy">Most recent subscription billing events.</p></div></div>
    <div class="tw">
      <table class="tb">
        <thead><tr><th>Tenant</th><th>Package</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          @forelse ($latestPayments as $payment)
            <tr>
              <td>{{ $payment->tenant?->name ?? 'Unknown tenant' }}</td>
              <td>{{ $payment->package?->name ?? 'No package' }}</td>
              <td>${{ number_format((float) $payment->amount, 2) }}</td>
              <td><span class="chip {{ $payment->status->value === 'paid' ? 'c-g' : ($payment->status->value === 'failed' ? 'c-r' : 'c-a') }}">{{ ucfirst($payment->status->value) }}</span></td>
              <td>{{ $payment->paid_at?->format('M d, Y H:i') ?? '-' }}</td>
            </tr>
          @empty
            <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No payment activity yet</div><p class="empty-state-copy">Plan payments will appear here as soon as the central billing tables receive records.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</main>
