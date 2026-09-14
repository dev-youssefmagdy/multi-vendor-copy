# Prompt 04 — Dashboard + Analytics (5 pages)

Requires prompts 01–03. Follow the **Standard Conversion Recipe** in `prompt_00_README_tenant_panel_refactor.md`.
These pages are read-only insight pages. The charts must render **identically** to today.

## Pages

| Route name (unchanged) | URI | Livewire source | Repository source |
|---|---|---|---|
| `tenant.dashboard` | `/admin/dashboard` | `Tenant\Dashboard` → `livewire/tenant/dashboard/index.blade.php` | `dashboardOverview()` |
| `tenant.analytics.orders` | `/admin/analytics/orders` | `Analytics\OrderAnalyticsPage` (ContentPage) | `orderAnalyticsOverview()` |
| `tenant.analytics.clv` | `/admin/analytics/customer-lifetime-value` | `Analytics\CustomerLifetimeValuePage` | `customerLifetimeOverview()` |
| `tenant.analytics.shipping` | `/admin/analytics/shipping` | `Analytics\ShippingAnalyticsPage` | `shippingAnalyticsOverview()` |
| `tenant.analytics.profitability` | `/admin/analytics/profitability` | `Analytics\ProductProfitabilityPage` | `productProfitabilityOverview()` + `paginateProductProfitabilityRows()` |

Middleware is unchanged: `tenant.permission:dashboard.view` for the dashboard, and `tenant.permission:analytics.view` for the analytics group.
Return analytics (`tenant.returns.analytics`) is in prompt 06 but reuses the shared pieces built here.

## 1. Shared chart engine — verbatim port (charts must look identical)
Today every insight page emits `<script id="dashboard-chart-data" type="application/json">` and fixed canvas ids (`revenueChart`, `donutChart`, `barChart`, `lineChart`, `radarChart`). `resources/js/app.js` (`getDashboardCharts`, `tooltipOptions`, `gridColor`, `chartColor`, `formatChartValue`, `buildLineDatasets`, `buildBarDatasets`, `buildRadarDatasets`, `buildCharts`, `destroyCharts`, `rebuildCharts`, `setPeriod`, lines ~724–1372) draws them.

- Create `resources/js/tenant/modules/insight-charts.js`. Port those functions **verbatim in behaviour**: same canvas ids, the same JSON island id `dashboard-chart-data`, the same colours from CSS vars, and the same period switch (`data-action="set-period"` → keep the delegated handler here).
  - Import `Chart from 'chart.js/auto'` (the `vendor-charts` chunk).
  - Export `mountInsightCharts()`, which builds the charts when any known canvas exists and rebuilds them on `tenant:theme-changed`.
- The JSON island stays (it is a non-executable `type="application/json"` script, allowed by invariant 5). Render it with `@json($chartPayload)`, not `{!! json_encode() !!}`.

## 2. Shared insight layout partial
Create `resources/views/tenant/pages/insights/_layout.blade.php`. It is a Blade port of `livewire/tenant/pages/content-page.blade.php`, limited to the parts insight pages use:
- page header (`x-tenant::page-header`)
- `contentIntro` callout (`x-tenant::alert type="info"` or the existing `.content-note` markup — keep the look)
- metric cards (`x-tenant::stats-grid` with `cardsGridClass`)
- `chartSections` (each card: title, description, `<canvas id>`, optional `legend`, optional `metrics`)
- `tableSections` → **each becomes `x-tenant::datatable`**
- the JSON island

Remove every form/`wire:` branch from the port (content-page also serves settings forms, which prompt 10 handles separately). Variables: `$title`, `$badge`, `$description`, `$contentIntro`, `$cards`, `$cardsGridClass`, `$chartPayload`, `$chartSections`, `$tables` (array of `{id, title, description, url, columns, order}`).

## 3. Controllers
`app/Http/Controllers/Tenant/Panel/Insights/`:

**`DashboardController`**
- `index()` → `view('tenant.pages.dashboard.index', …)`: port `livewire/tenant/dashboard/index.blade.php` 1:1, keeping `$formatCardValue` as `Metric::format()`.
- The three tables — **Latest Orders** (Order, Customer, Total, Gateway, Created), **Top Customers** (Customer, Orders, Total Spend, Last Order) and **Top Products** (Product, Units, Revenue, Gross Profit, Margin) — become `x-tenant::datatable mode="client" :paging="false" :searching="false"`. The rows come from `dashboardOverview()`, and cells use `_cols` partials that reproduce the current `<td>` content exactly.
- JS entry `resources/js/tenant/pages/dashboard/index.js` → `import { mountInsightCharts } from '@tenant/modules/insight-charts'; mountInsightCharts();`.

**`OrderAnalyticsController`, `CustomerLifetimeValueController`, `ShippingAnalyticsController`, `ProductProfitabilityController`**
- `index()`: build **exactly** the `pageMeta()` + `pageData()` arrays the Livewire class builds today (title, badge, description, `contentIntro`, `cardsGridClass`, `cards` via `Metric::cards`, `chartPayload`, `chartSections` including legends/metrics), minus the table rows. Render `tenant.pages.insights._layout`.
- One `data<Table>()` method per table section, e.g. `dataMonthly()` / `dataStatus()` for order analytics:
  - Source rows: the same overview array key the Livewire class maps today (`monthly_rows`, `status_rows`, `rows`, …) via `DataTables::collection(collect($overview['<key>']))`. Column formatting uses `editColumn` with the same number/currency formats (`'$' . number_format(x, 2)`, `M d, Y`, `N/A` fallbacks).
  - Default order = the current array order (`order: []`, no initial sort).
  - Cache the overview per request so `index()` and `data*()` don't diverge. For large tenants, wrap the overview call in `Cache::remember('tenant:'.tenant('id').':<page>:overview', 60, …)`, and use the same cached value in `index()` and `data*()`.
- **Product profitability** table ("Product, Real Price, Sell Price, Units, Revenue, Cost, Gross Profit, Margin, Status"): today it paginates `allProfitabilityRows()` in memory. Make `allProfitabilityRows()` public in `TenantPanelRepository` (or add a public `profitabilityRows(): array` wrapper) and feed `DataTables::collection()`. **Keep `paginateProductProfitabilityRows()`** for other callers. The Status column uses `x-tenant::status-badge` with the same label logic as the current row HTML.
- **CLV** table ("Customer, Orders, Paid Orders, Total Spend, Collected Spend, Average Order, Last Order"): the first column is a `_cols/customer.blade.php` partial (name + email, the `.entity-title` / `.entity-subtitle` markup).

## 4. Routes (in `routes/tenant_panel.php`, same middleware as the page)
```
GET /admin/dashboard                                     DashboardController@index               tenant.dashboard
GET /admin/analytics/orders                              OrderAnalyticsController@index          tenant.analytics.orders
GET /admin/analytics/orders/data/monthly                 …@dataMonthly                           tenant.analytics.orders.data.monthly
GET /admin/analytics/orders/data/status                  …@dataStatus                            tenant.analytics.orders.data.status
GET /admin/analytics/customer-lifetime-value             CustomerLifetimeValueController@index   tenant.analytics.clv
GET /admin/analytics/customer-lifetime-value/data        …@data                                  tenant.analytics.clv.data
GET /admin/analytics/shipping                            ShippingAnalyticsController@index       tenant.analytics.shipping
GET /admin/analytics/shipping/data/<section>             …@data<Section>                         tenant.analytics.shipping.data.<section>
GET /admin/analytics/profitability                       ProductProfitabilityController@index    tenant.analytics.profitability
GET /admin/analytics/profitability/data                  …@data                                  tenant.analytics.profitability.data
```
Create one data route per `tableSections` entry that the Livewire class defines today. Read each class and do not skip a section.

## 5. Vite entries
```
'resources/js/tenant/pages/dashboard/index.js',
'resources/js/tenant/pages/insights/index.js',   // shared by all 4 analytics pages + return analytics: mountInsightCharts()
```

## 6. Verification
1. A side-by-side screenshot (old vs new) of each page is visually identical: cards, charts, colours, legends, metrics and table content.
2. The theme toggle rebuilds the charts with the correct palette.
3. Every table sorts, pages and responds in responsive mode. Profitability and CLV page server-side through `DataTables::collection`.
4. No `livewire`, `app.js` or inline script requests on these pages (only the JSON island).
