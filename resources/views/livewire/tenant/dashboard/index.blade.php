<main id="mn">
  @php
    $cards = $overview['cards'] ?? [];
    $statusRows = $overview['status_rows'] ?? [];
    $latestOrders = $overview['latest_orders'] ?? [];
    $topCustomers = $overview['top_customers'] ?? [];
    $topProducts = $overview['top_products'] ?? [];

    $formatCardValue = function (array $card): string {
      $value = (float) ($card['value'] ?? 0);

      return match ($card['format'] ?? null) {
        'currency' => '$' . number_format($value, 2),
        'percent' => number_format($value, 2) . '%',
        default => fmod($value, 1.0) === 0.0 ? number_format($value, 0) : number_format($value, 2),
      };
    };
  @endphp

  <script id="dashboard-chart-data"
    type="application/json">{!! json_encode($overview['chart_payload'] ?? [], JSON_THROW_ON_ERROR) !!}</script>

  <div class="page-head fu d0">
    <div>
      <div class="page-title-row">
        <h1 class="D page-title">Vendor Dashboard</h1>
        <span class="page-badge">Tenant Workspace</span>
      </div>
      <p class="page-copy">Track store performance, tenant billing health, customer growth, shipping capture, and
        product profitability from the current tenant database.</p>
    </div>
  </div>

  <div class="g-stats section-gap">
    @foreach ($cards as $card)
      <div class="card {{ $card['glow'] ?? '' }}">
        <div class="stat-head">
          <div>
            <div class="eyebrow">{{ $card['label'] }}</div>
            <div class="D stat-value">{{ $formatCardValue($card) }}</div>
          </div>
          <div class="mini-stat-dot {{ $card['dot'] ?? 'dot-cyan' }}"></div>
        </div>
        <p class="panel-copy">{{ $card['caption'] }}</p>
      </div>
    @endforeach
  </div>

  <div class="g-r2 section-gap">
    <section class="card fu d2">
      <div class="chart-head chart-head-wrap">
        <div>
          <h3 class="D section-title">Sales vs Collection</h3>
          <p class="section-copy">Gross sales and collected sales by month for the tenant.</p>
        </div>
      </div>
      <canvas id="revenueChart"></canvas>
    </section>

    <section class="card fu d3">
      <div class="section-stack-lg">
        <h3 class="D section-title">Order Status Mix</h3>
        <p class="section-copy">Current distribution of tenant order statuses.</p>
      </div>
      <div class="donut-wrap"><canvas id="donutChart"></canvas></div>
      <div class="legend-list">
        @foreach ($statusRows as $index => $row)
          <div class="legend-row">
            <div class="legend-meta">
              <span class="dot {{ ['dot-cyan', 'dot-violet', 'dot-green', 'dot-amber'][$index % 4] }}"></span>
              <span class="text-t2">{{ $row['label'] }}</span>
            </div>
            <span class="legend-value">{{ number_format($row['count']) }}</span>
          </div>
        @endforeach
      </div>
    </section>
  </div>

  <div class="g-r3 section-gap">
    <section class="card fu d2">
      <div class="panel-head">
        <div>
          <h3 class="D panel-title">Shipping Capture</h3>
          <p class="panel-copy">Monthly shipping revenue captured from tenant orders.</p>
        </div>
      </div>
      <canvas id="barChart"></canvas>
    </section>

    <section class="card fu d3">
      <div class="panel-head">
        <div>
          <h3 class="D panel-title">Customer Momentum</h3>
          <p class="panel-copy">New customers compared with repeat buying activity.</p>
        </div>
      </div>
      <canvas id="lineChart"></canvas>
    </section>

    <section class="card fu d4">
      <div class="panel-head">
        <div>
          <h3 class="D panel-title">Performance Radar</h3>
          <p class="panel-copy">Operational score snapshot across sales, catalog, and subscriptions.</p>
        </div>
      </div>
      <canvas id="radarChart"></canvas>
    </section>
  </div>

  <div class="g-r2 section-gap">
    <section class="card table-card-shell">
      <div class="table-header-shell">
        <div>
          <h3 class="panel-title">Latest Orders</h3>
          <p class="panel-copy">Most recent order activity in the tenant storefront.</p>
        </div>
      </div>
      <x-table :headers="['Order', 'Customer', 'Total', 'Gateway', 'Created']">
        @forelse ($latestOrders as $order)
          <tr>
            <td>{{ $order->uuid }}</td>
            <td>{{ $order->customer?->full_name ?? 'Guest' }}</td>
            <td>${{ number_format((float) $order->grand_total, 2) }}</td>
            <td>
              {{ $order->paymentGateway?->name ?? str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString() }}
            </td>
            <td>{{ $order->created_at?->format('M d, Y H:i') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5">
              <div class="empty-state">
                <div class="empty-state-title">No order activity yet</div>
                <p class="empty-state-copy">Tenant order data will appear here as soon as storefront orders are created.
                </p>
              </div>
            </td>
          </tr>
        @endforelse
      </x-table>
    </section>

    <section class="card table-card-shell">
      <div class="table-header-shell">
        <div>
          <h3 class="panel-title">Top Customers</h3>
          <p class="panel-copy">Highest lifetime spend customers in the current tenant.</p>
        </div>
      </div>
      <x-table :headers="['Customer', 'Orders', 'Total Spend', 'Last Order']">
        @forelse ($topCustomers as $row)
          <tr>
            <td>
              <div class="entity-title">{{ $row['name'] }}</div>
              <div class="entity-subtitle">{{ $row['email'] }}</div>
            </td>
            <td>{{ $row['orders'] }}</td>
            <td>${{ number_format((float) $row['total'], 2) }}</td>
            <td>{{ optional($row['last_order'])->format('M d, Y') ?? 'N/A' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4">
              <div class="empty-state">
                <div class="empty-state-title">No customer order history yet</div>
                <p class="empty-state-copy">Top customers appear once the tenant has customer orders.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </x-table>
    </section>
  </div>

  <section class="card table-card-shell section-gap">
    <div class="table-header-shell">
      <div>
        <h3 class="panel-title">Top Products</h3>
        <p class="panel-copy">Most profitable tracked items based on realized revenue and cost.</p>
      </div>
    </div>
    <x-table :headers="['Product', 'Units', 'Revenue', 'Gross Profit', 'Margin']">
      @forelse ($topProducts as $row)
        <tr>
          <td>{{ $row['name'] }}</td>
          <td>{{ $row['sold_qty'] }}</td>
          <td>${{ number_format((float) $row['revenue'], 2) }}</td>
          <td>${{ number_format((float) $row['profit'], 2) }}</td>
          <td>{{ number_format((float) $row['margin'], 2) }}%</td>
        </tr>
      @empty
        <tr>
          <td colspan="5">
            <div class="empty-state">
              <div class="empty-state-title">No profitability data yet</div>
              <p class="empty-state-copy">Product profitability will populate as soon as tenant orders include tracked
                items.</p>
            </div>
          </td>
        </tr>
      @endforelse
    </x-table>
  </section>
</main>