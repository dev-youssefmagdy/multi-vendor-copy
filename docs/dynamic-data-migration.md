# Dynamic Data Migration — Tenant Panel

> **Purpose:** Replace every piece of static, hardcoded, or "dummy" data in the tenant admin panel
> (`/admin/*`) with live backend values. This document is organised as numbered sub-agent prompts
> to be executed sequentially. Each prompt is self-contained, lists the files to touch, the exact
> changes required, and a quick verification block.
>
> **Invariants (apply in every prompt)**
> - Do NOT modify any CSS classes, HTML structure, or visual layout.
> - Do NOT remove any section or element from any page.
> - Do NOT touch storefront views, central admin views, or shared `app.js` / `app.css`.
> - Every controller already has a matching route — only add data to controllers that are missing it.
> - Inline `<script type="application/json">` JSON islands are allowed (they are non-executable).
> - No inline executable `<script>` tags and no `style="…"` attributes for static values.
> - Every button / action that opens a modal or fires a request must wire to the same endpoint /
>   approach already used by the "old design" for that action (see prompt 00 README for the endpoint
>   map).

---

## How below_market / market comparison is calculated

The database does NOT have a "market average price" column. Use the following formula on any
**central product** snapshot (accessed via `TenantPanelRepository::centralProductSnapshot()`):

```
cost        = central_product.cost_price   (what the tenant pays, including delivery)
market_ref  = central_product.base_price   (the reference/retail price)

If cost > 0 AND market_ref > cost:
    raw_pct = (market_ref - cost) / market_ref * 100
    low_pct = floor(raw_pct * 0.85)           // lower bound of the range
    high_pct = ceil(raw_pct * 1.15)           // upper bound of the range
    below_market = low_pct . '% – ' . high_pct . '%'
Else:
    below_market = null  (do not show the "Cheaper than market" chip/line)
```

For the **Product (tenant)** model (no central snapshot):
```
cost = product.cost_price  (if set)
```

For the opportunity card gauges:
```
score_pct       = min(100, max(0, (int) $raw_pct))         // 0–100
profit_pct      = min(100, max(0, (int) ($raw_pct * 0.5))) // rough proxy
competition_pct = 25   // static "Low" = low gauge fill
status_pct      = 75   // "Hot" is always at 75%

score           = (string) $score_pct
profit          = '+' . round($raw_pct * 0.3, 0) . '%'
competition_level = 'Low'
status          = 'Hot'
```

These values are intentional approximations. If the business later adds a real `market_price`
column to `products`, replace the formula without changing the Blade keys.

---

## Sub-agent prompt A — Dashboard Controller: add missing variables

**Files to change:**
- `app/Http/Controllers/Tenant/Panel/Insights/DashboardController.php`
- `app/Repositories/Tenant/TenantPanelRepository.php` (add helper methods)
- `resources/views/tenant/pages/dashboard/_hero.blade.php`
- `resources/views/tenant/pages/dashboard/_performance.blade.php`
- `resources/views/tenant/pages/dashboard/_pay-alert.blade.php`
- `resources/views/tenant/pages/dashboard/_opportunities.blade.php`
- `resources/views/tenant/pages/dashboard/_new-in.blade.php`
- `resources/views/tenant/pages/dashboard/_partner.blade.php`
- `resources/views/tenant/pages/dashboard/_ads.blade.php`

### A1. Repository additions

Add the following public methods to `TenantPanelRepository`:

```php
/** Return the N most-recent central products added to this tenant's catalog. */
public function newInProducts(int $limit = 6): Collection
{
    return Product::query()
        ->with(['translations.language', 'files'])
        ->orderByDesc('created_at')
        ->limit($limit)
        ->get();
}

/** Return the N products with the highest calculated opportunity score. */
public function opportunityProducts(int $limit = 6): Collection
{
    return Product::query()
        ->with(['translations.language', 'files'])
        ->whereNotNull('cost_price')
        ->whereColumn('cost_price', '<', 'default_price')
        ->orderByRaw('(default_price - cost_price) / default_price DESC')
        ->limit($limit)
        ->get();
}

/** Count of vendor purchases (VendorSettlement) in Pending/Processing status for this tenant. */
public function pendingVendorPurchaseCount(): int
{
    // VendorSettlement lives on the central DB — use tenancy()->central()
    return (int) tenancy()->central(fn() =>
        \App\Models\VendorSettlement::query()
            ->where('tenant_id', tenant('id'))
            ->whereIn('status', ['pending', 'processing'])
            ->count()
    );
}
```

Add a private helper `buildOpportunityArray(Product $product): array` that maps a Product model
into the `$opp` array shape expected by `_opportunity-card.blade.php`:

```php
private function buildOpportunityArray(\App\Models\Tenant\Product $product): array
{
    $label       = $product->translationValue('name') ?? $product->slug ?? ('Product #' . $product->id);
    $description = \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->translationValue('description'))), 140);
    $imageUrl    = $product->primary_image_url;
    $cost        = (float) ($product->cost_price ?? $product->default_price ?? 0);
    $marketRef   = (float) ($product->default_price ?? 0);
    $rawPct      = ($cost > 0 && $marketRef > $cost) ? (($marketRef - $cost) / $marketRef * 100) : 0;
    $lowPct      = $rawPct > 0 ? (int) floor($rawPct * 0.85) : 0;
    $highPct     = $rawPct > 0 ? (int) ceil($rawPct * 1.15)  : 0;
    $belowMarket = $rawPct > 0 ? "{$lowPct}% – {$highPct}%" : null;
    $scorePct    = min(100, max(0, (int) $rawPct));
    $profitPct   = min(100, max(0, (int) ($rawPct * 0.5)));
    $markup      = $rawPct > 0 ? '+' . round($rawPct * 0.3, 0) . '%' : '+0%';
    $suggested   = $cost > 0 ? '$' . number_format($cost * 1.5, 2) : '—';

    return [
        'image'             => $imageUrl,
        'added'             => true,
        'category'          => $product->categories->first()?->translationValue('name') ?? 'General',
        'trend'             => 'rising',
        'competition'       => 'Low competition',
        'title'             => $label,
        'description'       => $description !== '' ? $description : 'No description yet.',
        'markets'           => ['sa' => 'KSA', 'gb' => 'UK', 'eg' => 'Egy', 'us' => 'USA', 'ae' => 'UAE', 'fr' => 'FRA', 'ma' => 'Mor'],
        'below_market'      => $belowMarket,
        'score'             => $scorePct > 0 ? (string) $scorePct : '—',
        'score_pct'         => $scorePct,
        'profit'            => $rawPct > 0 ? '+' . round($rawPct * 0.25, 0) . '%' : '+0%',
        'profit_pct'        => $profitPct,
        'competition_level' => 'Low',
        'competition_pct'   => 25,
        'status'            => 'Hot',
        'status_pct'        => 75,
        'cost'              => $cost > 0 ? '$' . number_format($cost, 2) : '—',
        'markup'            => $markup,
        'suggested_price'   => $suggested,
    ];
}
```

Add a public wrapper:

```php
public function opportunityProductCards(int $limit = 6): array
{
    return $this->opportunityProducts($limit)
        ->map(fn(\App\Models\Tenant\Product $p) => $this->buildOpportunityArray($p))
        ->all();
}

public function newInProductCards(int $limit = 6): array
{
    return $this->newInProducts($limit)->map(function (\App\Models\Tenant\Product $product) {
        $cost = (float) ($product->cost_price ?? $product->default_price ?? 0);
        $marketRef = (float) ($product->default_price ?? 0);
        $rawPct = ($cost > 0 && $marketRef > $cost) ? (($marketRef - $cost) / $marketRef * 100) : 0;
        $lowPct = $rawPct > 0 ? (int) floor($rawPct * 0.85) : 0;
        $highPct = $rawPct > 0 ? (int) ceil($rawPct * 1.15) : 0;
        $opp = $this->buildOpportunityArray($product);
        return [
            'image'       => $opp['image'],
            'title'       => $opp['title'],
            'description' => $opp['description'],
            'below_market'=> $rawPct > 0 ? "{$lowPct}% – {$highPct}%" : null,
            'cost'        => $opp['cost'],
        ];
    })->all();
}
```

### A2. DashboardController changes

In `DashboardController::index()`, after `$overview = $repository->dashboardOverview();`, add:

```php
$opportunities       = $repository->opportunityProductCards(6);
$newProducts         = $repository->newInProductCards(6);
$pendingPurchases    = $repository->pendingVendorPurchaseCount();
$activeProductsCount = \App\Models\Tenant\Product::query()->where('active', true)->count();
```

Pass these to the view alongside the existing variables:

```php
return view('tenant.pages.dashboard.index', [
    // ... existing variables ...
    'opportunities'       => $opportunities,
    'newProducts'         => $newProducts,
    'pendingPurchases'    => $pendingPurchases,
    'activeProductsCount' => $activeProductsCount,
]);
```

### A3. View changes

**`_hero.blade.php`:**
- Line: `<li>{!! $check !!} dummy active products</li>` →
  `<li>{!! $check !!} {{ number_format($activeProductsCount) }} active products</li>`
- Line: `<span class="db-power-chip-value is-warn">dummy</span>` (Inventory chip) →
  `<span class="db-power-chip-value {{ $activeProductsCount > 0 ? 'is-good' : 'is-warn' }}">{{ $activeProductsCount > 0 ? 'Ready' : 'Pending' }}</span>`

**`_performance.blade.php`:**
- `<span class="t-trend t-trend-up">{!! $trendUp !!} dummy</span>` →
  Remove the `dummy` text. Replace with the actual trend:
  ```blade
  @php
      // Trend: compare current month vs prior month using the last 2 elements of the series
      $seriesData = collect($chartPayload['revenueDatasets'][0]['data'] ?? []);
      $last  = $seriesData->last() ?? 0;
      $prev  = $seriesData->slice(-2, 1)->first() ?? 0;
      $trendPct = $prev > 0 ? round((($last - $prev) / $prev) * 100, 1) : 0;
      $trendClass = $trendPct >= 0 ? 't-trend-up' : 't-trend-down';
      $trendLabel = ($trendPct >= 0 ? '+' : '') . $trendPct . '%';
  @endphp
  ```
  Then replace the hardcoded span:
  ```blade
  <span class="t-trend {{ $trendClass }}">{!! $trendUp !!} {{ $trendLabel }}</span>
  ```
  Place this block ONCE in the `@php` at the top of the `_performance.blade.php` section so the same
  trend data applies to all 4 KPI cards. Each KPI card uses the same `$trendLabel`/`$trendClass`
  (it is the overall revenue trend, identical to what the design shows).

**`_pay-alert.blade.php`:**
- `<h2 class="db-pay-title">dummy orders wait to pay</h2>` →
  ```blade
  <h2 class="db-pay-title">{{ $pendingPurchases }} {{ Str::plural('order', $pendingPurchases) }} wait{{ $pendingPurchases === 1 ? 's' : '' }} to pay</h2>
  ```
  Add `use Illuminate\Support\Str;` at the top of the file (or use the facade — it is auto-imported in Blade).
  If `$pendingPurchases === 0`, hide the section with `@if($pendingPurchases > 0) … @endif` wrapping the entire `<section>`.

**`_opportunities.blade.php`:**
- Remove the entire `$opportunities = $opportunities ?? [...]` dummy PHP block.
- Keep the variable guard `$opportunities = $opportunities ?? []` so the partial never crashes if
  the controller passes no variable (empty-store case).
- The surrounding HTML is unchanged — the `@foreach($opportunities as $opp)` loop already exists.
- The `Add to Flash Sale` and `Add to trending` buttons in `_opportunity-card.blade.php` need
  `data-product-id` attributes so JS can wire them:
  - In `_opportunity-card.blade.php`, add `data-product-id="{{ $opp['id'] ?? '' }}"` to both buttons.
  - Add `'id' => $product->id` to `buildOpportunityArray()` output.
  - The "Create your Ad" CTA at the bottom: when `$opp['added']` is true, set
    `data-modal-open="product-video-ad-modal" data-product-id="{{ $opp['id'] ?? '' }}"`.
  - When `$opp['added']` is false: set
    `data-action-url="{{ route('tenant.products.toggle-active', $opp['id'] ?? 0) }}"` and label
    "Add to store & create your Ad" (keeping existing class logic) so clicking it activates the
    product and then opens the video-ad modal.

**`_new-in.blade.php`:**
- Remove the entire dummy `$newProducts = $newProducts ?? [...]` block.
- Keep `$newProducts = $newProducts ?? []` guard.
- For each `$product` in the loop, `$product['below_market']` is now either a string like
  `"24% – 34%"` or `null`. Update the chip:
  ```blade
  @if(!empty($product['below_market']))
      <span class="db-opp-cheaper">
          <small>Cheaper than market by</small>
          <strong>{{ $product['below_market'] }}</strong>
      </span>
  @endif
  ```
- Replace `<strong>{{ $product['cost'] }}</strong>` — it is already using `$product['cost']`
  from the controller, so just ensure `null`/empty is handled: `{{ $product['cost'] ?? '—' }}`.

**`_partner.blade.php`:**
- Remove the dummy `$partner = $partner ?? [...]` block.
- The partner program uses **affiliate** data. Add a query to `DashboardController`:
  ```php
  // Affiliate data (if the affiliate module exists for this tenant)
  $partnerData = [
      'invite_link'     => url('/ref/' . (tenant('id') ?? 'store')),
      'visits'          => '—',
      'visits_trend'    => '—',
      'traders'         => '—',
      'active_traders'  => '—',
      'reward'          => '—',
      'reward_status'   => '—',
  ];
  ```
  Pass `$partnerData` to the view. The values show `—` because the partner affiliate module is
  not yet built on the tenant side (this is documented in the BACKEND TODO comments). This replaces
  "dummy" with a neutral dash that is honest about availability.
  > **Note:** When the affiliate module is wired up for tenants, update `$partnerData` from the
  > affiliate models. Do NOT add any new Livewire or Alpine logic.

**`_ads.blade.php`:**
- The `$ads` section shows social posts from existing products.
- Add to `DashboardController`:
  ```php
  $adsData = \App\Models\Tenant\Product::query()
      ->whereNotNull('social_posts')
      ->where('social_posts', '!=', '[]')
      ->orderByDesc('updated_at')
      ->limit(6)
      ->get()
      ->flatMap(fn($p) => collect($p->social_posts ?? []))
      ->take(6)
      ->map(fn($post) => [
          'image'    => $post['image_url'] ?? null,
          'video'    => null,
          'platform' => $post['platform'] ?? 'Social',
          'duration' => '30s',
          'title'    => $post['hook'] ?? 'View this post',
          'hook'     => $post['content'] ?? '',
          'markets'  => ['sa' => 'KSA', 'gb' => 'UK', 'eg' => 'Egy', 'us' => 'USA', 'ae' => 'UAE'],
      ])
      ->values()
      ->all();
  ```
  Pass `$adsData` as `$ads` to the view.
- In `_ads.blade.php`, remove the dummy `$ads = $ads ?? [...]` block. Keep `$ads = $ads ?? []` guard.
- If `$adsData` is empty (no social posts yet), show an empty-state inside the section:
  ```blade
  @if(empty($ads))
      <div class="empty-state-sm">No ads generated yet. <a href="{{ route('tenant.products.index') }}">Go to products</a> to generate social posts.</div>
  @else
      {{-- existing slider --}}
  @endif
  ```

### A4. Verification
1. Open `/admin/dashboard` — no "dummy" text anywhere on the page.
2. Hero section shows real active-product count.
3. Inventory chip shows "Ready" / "Pending" based on actual products.
4. Performance KPI cards show a real trend percentage instead of "dummy".
5. Pay-alert section is hidden when there are 0 pending vendor purchases; shows a real count otherwise.
6. Opportunities section shows real products with calculated percentages.
7. New-in section shows real recent products.
8. The browser console has zero JavaScript errors.

---

## Sub-agent prompt B — Today's Chances page: replace mock data

**File to change:**
- `resources/views/tenant/pages/todays-chances/index.blade.php`
- Add a new controller `app/Http/Controllers/Tenant/Panel/Catalog/TodayChancesController.php`
- Update `routes/tenant_panel.php`

### B1. Controller

```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class TodayChancesController extends PanelController
{
    public function index(Request $request, TenantPanelRepository $repository): View
    {
        $perPage = 12;
        $products = Product::query()
            ->with(['translations.language', 'files', 'categories.translations.language'])
            ->where('active', true)
            ->whereNotNull('cost_price')
            ->whereColumn('cost_price', '<', 'default_price')
            ->orderByRaw('(default_price - cost_price) / default_price DESC')
            ->paginate($perPage);

        $items = $products->getCollection()->map(
            fn(Product $p) => $repository->buildOpportunityArray($p)  // make buildOpportunityArray public
        );

        return view('tenant.pages.todays-chances.index', [
            'products' => $products,
            'items'    => $items,
        ]);
    }
}
```

Make `buildOpportunityArray` public in `TenantPanelRepository` (change `private` to `public`).

### B2. Route change in `routes/tenant_panel.php`

Change:
```php
Route::view('/todays-chances', 'tenant.pages.todays-chances.index')
    ->name('tenant.todays-chances');
```

To:
```php
Route::get('/todays-chances', [\App\Http\Controllers\Tenant\Panel\Catalog\TodayChancesController::class, 'index'])
    ->name('tenant.todays-chances');
```

### B3. View changes

In `resources/views/tenant/pages/todays-chances/index.blade.php`:

- Remove the entire `@php … $items = collect(range(...))->map(fn(...) => [...]) @endphp` block.
- The `@foreach($items as $opp)` and `@include('tenant.pages.dashboard._opportunity-card', ...)` loop remains unchanged.
- Replace the manual pagination block:

Replace:
```php
$total = 109;
$perPage = 12;
$lastPage = (int) ceil($total / $perPage);
$page = min(max(1, (int) request('page', 1)), $lastPage);
$from = ($page - 1) * $perPage;
$count = min($perPage, $total - $from);
$pages = ...
```
And the manual `<nav class="tc-pagination">` block with:
```blade
@php
    $page     = $products->currentPage();
    $lastPage = max(1, $products->lastPage());
    $count    = $products->count();
    $total    = $products->total();
    $pages    = collect(range(1, $lastPage))
        ->filter(fn ($n) => $n === 1 || $n === $lastPage || abs($n - $page) <= 1)
        ->values();
    $pageUrl  = fn (int $n) => $products->url($n);
@endphp
```

The `<nav class="tc-pagination">` HTML stays identical — it already uses `$page`, `$lastPage`,
`$count`, `$total`, `$pages`, and `$pageUrl`. Only the data source changes.

- If `$products->total() === 0`, show an empty state instead of the grid:
```blade
@if($products->total() === 0)
    <div class="pm-empty fu d1">
        <h3>No opportunities yet</h3>
        <p>Add products with cost prices set to see opportunities here.</p>
    </div>
@else
    <div class="tc-grid fu d1"> ... @foreach ... </div>
    <nav ...>...</nav>
@endif
```

### B4. Verification
1. `/admin/todays-chances` shows real products from the tenant catalog.
2. Pagination works with real data.
3. Cards show calculated percentages (not "dummy").
4. Empty state shows if no qualifying products.

---

## Sub-agent prompt C — Product Cards: replace dummy market data

**Files to change:**
- `resources/views/tenant/pages/catalog/products/_card.blade.php`
- `resources/views/tenant/pages/catalog/_mock-cards.blade.php`
- `app/Http/Controllers/Tenant/Panel/Catalog/ProductsListController.php`

### C1. `_card.blade.php`

This file already uses `$product->default_price` for the cost. Add the below-market calculation:

At the top `@php` block, after `$price = (float)($product->default_price ?? 0);` add:

```php
$costPrice  = (float) ($product->cost_price ?? $central['cost_price'] ?? 0);
$marketRef  = $costPrice > 0 ? max($costPrice, $price) : $price;
$rawPct     = ($costPrice > 0 && $marketRef > $costPrice)
    ? (($marketRef - $costPrice) / $marketRef * 100)
    : 0;
$belowLow   = $rawPct > 0 ? (int) floor($rawPct * 0.85) : 0;
$belowHigh  = $rawPct > 0 ? (int) ceil($rawPct * 1.15) : 0;
$belowMarket = $rawPct > 0 ? "{$belowLow}% – {$belowHigh}%" : null;
```

Replace the `.db-opp-cheaper` chip block:
```blade
@if($belowMarket)
    <span class="db-opp-cheaper">
        <small>Cheaper than market by</small>
        <strong>{{ $belowMarket }}</strong>
    </span>
@endif
```

In the `.db-opp-stats` section, replace the "Average Cheaper than market by" stat:
```blade
<div class="db-opp-stat">
    <span class="db-opp-stat-label">Average Cheaper than market by</span>
    <strong>{{ $belowMarket ?? '—' }}</strong>
    <small>Through global stores</small>
</div>
```

The "Cost to your customer's door" stat already uses `${{ number_format($price, 2) }}` — keep it.

The `$central` variable is already passed from `ProductsListController` and `ProductController`.
Add `'cost_price'` to the `mapCentralProductSnapshot` return array in `TenantPanelRepository`
(it is already there — `'cost_price' => $product->cost_price !== null ? (float) $product->cost_price : null`).

### C2. `_mock-cards.blade.php`

This file is shown **only when there are zero products** (fallback). Replace the hardcoded
Unsplash URLs and "dummy" values with a friendly empty-state message. The mock cards are
misleading (they show fake products from Unsplash). Replace the entire content with:

```blade
{{-- No products yet: guide the user to add some. --}}
<div class="pm-empty fu d1" style="grid-column: 1 / -1; padding: 3rem 1rem; text-align: center;">
    <h3>No products yet</h3>
    <p>Add your first product from the central catalog to see it here.</p>
    <a href="{{ route('tenant.products.create') }}" class="btn btn-primary" style="margin-top:1rem;">Add product</a>
</div>
```

Keep the `<nav class="tc-pagination">` block but wrap it in `@if($total > 0) … @endif`
so it disappears when there are no products.

> **Design note:** The mock-cards file existed purely for design preview and should never show
> fake Unsplash products to real users.

### C3. `ProductsListController`

Ensure the controller already passes `$centralSnapshots` (it does — `centralProductSnapshots()`
is called on line 39). No change needed unless the `cost_price` key is missing from
`mapCentralProductSnapshot`. If it is missing, add it (it is present per the repository audit).

### C4. Verification
1. Product cards on `/admin/products` show real below-market percentages when cost_price is set.
2. Cards where cost_price is 0 / null show `—` for "Average Cheaper" and no `.db-opp-cheaper` chip.
3. Mock-cards file shows a styled empty state when there are no products.

---

## Sub-agent prompt D — Own Products index: replace dummy stats

**File to change:**
- `resources/views/tenant/pages/catalog/own-products/index.blade.php`
- `app/Http/Controllers/Tenant/Panel/Catalog/OwnProductsListController.php` (or the existing own-products controller)

### D1. Find the own-products index controller

Run: `grep -rn "own-products.index\|OwnProductsList" /var/www/multi-vendor/app/Http/Controllers/`

Pass `$ownProductStats` to the view:

```php
$ownProductStats = [
    'total'    => $products->total(),     // LengthAwarePaginator already available
    'active'   => \App\Models\Tenant\Product::query()
                     ->where('is_own_product', true)->where('active', true)->count(),
    'featured' => \App\Models\Tenant\Product::query()
                     ->where('is_own_product', true)->where('featured', true)->count(),
];
```

### D2. View changes

In the `.op-stats` section, replace:
- `<strong class="op-stat-value">dummy</strong>` (Products) → `<strong class="op-stat-value">{{ number_format($ownProductStats['total']) }}</strong>`
- Second `dummy` (Active) → `<strong class="op-stat-value">{{ number_format($ownProductStats['active']) }}</strong>`
- Third `dummy` (Featured) → `<strong class="op-stat-value">{{ number_format($ownProductStats['featured']) }}</strong>`

Remove the `FOR DESIGN PURPOSE` and `BACKEND TODO` comments.

Also remove the conditional that shows `_mock-cards.blade.php` when `$products->total() === 0`.
Instead show:
```blade
@if($products->total() === 0)
    <div class="pm-empty fu d1">
        <h3>No own products yet</h3>
        <p>Create your first product using the button above.</p>
    </div>
@endif
```

### D3. Verification
1. Own products stats show real counts.
2. Empty state shows sensibly when no own products exist.

---

## Sub-agent prompt E — Opportunity card actions: wire "Add to Flash Sale" and "Add to trending"

**Files to change:**
- `resources/views/tenant/pages/dashboard/_opportunity-card.blade.php`
- `resources/js/tenant/pages/dashboard/opportunities.js` (or wherever the dashboard JS lives)
- Routes in `routes/tenant_panel.php`

### E1. Overview

The two buttons `Add to Flash Sale` and `Add to trending` in the opportunity card are currently
UI-only. They need to wire to existing functionality:

- **Add to Flash Sale:** opens the flash-sales management page or, if there is an existing route
  to add a product to a flash sale inline, calls it.
  - Use `href="{{ route('tenant.store.flash-sales.index') }}"` as a link (simplest approach).
  - OR, if a `POST store/flash-sales/{product}/quick-add` endpoint is feasible (a thin wrapper
    around the flash sale form), use `data-action-url`.

- **Add to trending / Featured:** the "trending" feature maps to the product's `featured` flag.
  - Wire the button to: `PATCH products/{product}/featured`
  - After success: show toastr "Product added to trending.", reload the dashboard opportunity cards.
  - Button changes state: `data-confirm="Mark this product as trending?"`.

Recommended implementation — add `data-*` attributes to both buttons in `_opportunity-card.blade.php`:

```blade
<div class="db-opp-actions">
    <a href="{{ route('tenant.store.flash-sales.index') }}" class="btn-tile">
        {!! $zap !!} Add to Flash Sale
    </a>
    @if(!empty($opp['id']))
        <button type="button" class="btn-tile"
            data-action-url="{{ route('tenant.products.toggle-featured', $opp['id']) }}"
            data-action-method="PATCH"
            data-confirm="Mark this product as trending?"
            data-success="reload-page">
            {!! $trendUp !!} Add to trending
        </button>
    @else
        <button type="button" class="btn-tile">{!! $trendUp !!} Add to trending</button>
    @endif
</div>
```

The "Create your Ad" CTA:
- When `$opp['added']` is `true`: open the video-ad modal.
  ```blade
  <button type="button" class="btn btn-lg db-opp-cta is-added"
      data-modal-open="product-video-ad-modal"
      data-product-id="{{ $opp['id'] ?? '' }}">
      {!! $idea !!} Create your Ad
  </button>
  ```
- When `$opp['added']` is `false` (product not yet in store): link to the add-product form.
  ```blade
  <a href="{{ route('tenant.products.create') }}" class="btn btn-primary btn-lg db-opp-cta">
      {!! $idea !!} Add to store &amp; create your Ad
  </a>
  ```

### E2. Verification
1. "Add to Flash Sale" button links to the flash sales page.
2. "Add to trending" button sends a PATCH request, shows a confirm dialog, and toasts on success.
3. "Create your Ad" opens the video-ad modal for added products.
4. No JS console errors.

---

## Sub-agent prompt F — Flash Sales / "Add to Flash Sale" quick-add endpoint (optional, if needed)

This prompt is OPTIONAL. If the UI decision in prompt E is to link to the flash-sales page
rather than an inline modal, skip this prompt.

**Files to change:**
- `app/Http/Controllers/Tenant/Panel/Store/FlashSalesController.php`
- `routes/tenant_panel.php`

### F1. Endpoint

```
POST store/flash-sales/quick-add
```

Body: `product_id` (integer, required). Checks:
1. Product belongs to this tenant.
2. No active flash sale already exists for this product in any country.
3. Creates a FlashSale record with `active = true`, `start_date = now()`,
   `end_date = now() + 7 days`, `discount_percentage = 10` (default).

Response: `success('Product added to Flash Sale.')`.

### F2. View change

In `_opportunity-card.blade.php`, replace the Flash Sale button with:
```blade
<button type="button" class="btn-tile"
    data-action-url="{{ route('tenant.store.flash-sales.quick-add') }}"
    data-action-method="POST"
    data-payload='{"product_id": {{ $opp['id'] ?? 0 }} }'
    data-success="toast">
    {!! $zap !!} Add to Flash Sale
</button>
```

---

## Sub-agent prompt G — Product modals: ensure all modal actions are wired

**Files to check/change:**
- `resources/views/tenant/pages/catalog/products/_modals/video-ad.blade.php`
- `resources/views/tenant/pages/catalog/products/_modals/social.blade.php`
- `resources/views/tenant/pages/catalog/products/_modals/price-finder.blade.php`
- `resources/views/tenant/pages/catalog/products/_modals/share.blade.php`
- `resources/views/tenant/pages/catalog/products/_modals/price-list.blade.php`

### G1. Audit each modal

Each modal is opened via `data-modal-open="<id>" data-product-id="<id>"`. The JS in
`resources/js/tenant/pages/catalog/products-index.js` (or similar) handles loading the modal
content via `GET products/{product}/social`, `GET products/{product}/ai-price`, etc.

Run: `grep -n "FOR DESIGN PURPOSE\|dummy\|BACKEND TODO" resources/views/tenant/pages/catalog/products/_modals/*.blade.php`

Fix any remaining dummy values in each modal by ensuring the JS properly fills them from the
API response. For example:

- **video-ad modal:** If it has dummy/placeholder content, add a note that this is populated by
  the JS from `GET products/{product}/social` (video ad content comes from social posts).
- **price-finder modal:** Ensure the `data-product-id` attribute is passed so JS can load
  `GET products/{product}/ai-price`.
- **share modal:** Ensure share URL is populated from `GET products/{product}/share`.

### G2. Verification
- Open each modal in the browser.
- Social modal shows real posts (or empty state).
- AI price modal shows real data (or a "fetch" prompt).
- Share modal shows real product URL.
- No "dummy" text visible.

---

## Sub-agent prompt H — All remaining pages with FOR DESIGN PURPOSE comments

Run this grep to find every remaining file with dummy data after prompts A–G:

```bash
grep -rn "dummy\|FOR DESIGN PURPOSE\|BACKEND TODO" \
  /var/www/multi-vendor/resources/views/tenant/pages/ \
  --include="*.blade.php" \
  -l
```

For each file found:
1. Read the file to understand what data is needed.
2. Find the controller that renders that view (via `route:list`).
3. Add the missing variable to the controller (the data is usually already in `TenantPanelRepository`).
4. Replace `dummy` in the view with `{{ $variable ?? '—' }}`.

Common patterns:

| File | Dummy data | Source |
|---|---|---|
| `settings/admins/index.blade.php` | Any static counts | Controller already passes data from DataTable |
| `settings/roles-permissions/index.blade.php` | Static text | Read the controller |
| `sales/orders/index.blade.php` | Any inline dummy | DataTable is already wired; check for any hardcoded non-DataTable values |
| `sales/customers/index.blade.php` | Stats | `customerStats()` in repository |
| `finance/billing/index.blade.php` | Stats | `billingStats()` in repository |
| `finance/payouts/index.blade.php` | Stats | `payoutStats()` in repository |
| `requests/_list.blade.php` | Static item counts | DataTable rows are dynamic |

### H1. Verification

```bash
grep -rn "dummy" /var/www/multi-vendor/resources/views/tenant/pages/ --include="*.blade.php"
```

This command must return zero results (except inside HTML comments or `<!-- FOR DESIGN -->` blocks
that have already been removed or that are gating a future feature clearly labelled as such).

---

## Sub-agent prompt I — Fix console / network errors after data wiring

After all data is wired, test every major page and fix any console or network errors:

### I1. Pages to test

1. `/admin/dashboard` — check all sections, charts, opportunity cards
2. `/admin/todays-chances` — real products, pagination
3. `/admin/products` — cards, image search, all modals
4. `/admin/products/create` — form fields
5. `/admin/own-products` — stats, grid
6. `/admin/analytics/orders` — charts, tables
7. `/admin/analytics/customer-lifetime-value` — charts, table
8. `/admin/analytics/shipping` — charts
9. `/admin/analytics/profitability` — table
10. `/admin/orders` — DataTable, stats
11. `/admin/orders/{id}` — detail page
12. `/admin/returns` — DataTable
13. `/admin/returns/{id}` — detail, actions
14. `/admin/customers` — DataTable, stats
15. `/admin/finance/wallet` — stats, tables, payment modal
16. `/admin/finance/billing` — stats, table
17. `/admin/finance/payouts` — table
18. `/admin/manufacturing` — table, create form
19. `/admin/brand-requests` — table, create form
20. `/admin/product-requests` — table, create form
21. `/admin/support/tickets` — table
22. `/admin/store/themes` — card grid, actions
23. `/admin/store/coupons` — country cards
24. `/admin/store/flash-sales` — country cards
25. `/admin/settings/tracking` — form
26. `/admin/settings/admins` — table
27. `/admin/settings/payment-gateways` — table
28. `/admin/onboarding` — tour and setup tabs

### I2. Common errors to watch for

- **419 CSRF on AJAX:** Ensure all `POST/PUT/PATCH/DELETE` requests include `X-CSRF-TOKEN`.
- **404 on DataTable data URLs:** Verify the `.data` route exists in `routes/tenant_panel.php`.
- **Missing JS variables:** If a Blade `@json()` island is missing, the page JS may throw.
- **Null image URLs:** Wrap all `style="background-image:url('...')"` with a null check in Blade
  (`@if($url) style="background-image:url('{{ $url }}')" @endif`).
- **Empty `$opp['id']`:** In `_opportunity-card.blade.php`, always check `!empty($opp['id'])`
  before using `route('tenant.products.toggle-featured', $opp['id'])`.

### I3. Network-level checks

Open DevTools → Network → XHR:
- DataTable calls must return JSON `{data:[...], recordsTotal:N, recordsFiltered:N}`.
- Modal load calls must return `{success:true, data:{...}}`.
- Toggle/action calls must return `{success:true, message:'...'}`.

No 500 errors. No 422 errors on initial page load. No external HTTP requests except:
- Reverb websocket (`ws://` or `wss://`)
- Payment SDKs on payment-modal pages only

---

## Sub-agent prompt J — Product card buttons: connect "Add to Flash Sale" from product list page

**File:** `resources/views/tenant/pages/catalog/products/_card.blade.php`

The product tools section is currently commented out (see the `{{-- Product tools ... --}}`
comment block). The `Create your Ad` button is already wired via `data-modal-open`. The flash
sale and trending buttons are accessible from the opportunity cards (dashboard/today's chances).

For the product list page cards, the tools are restored via the commented block. Uncomment and
wire them:

```blade
{{-- Uncomment to restore product tools --}}
<div class="pm-card-tools">
    <div class="pm-card-toggles">
        <x-tenant::status-toggle
            :checked="$product->active"
            :action-url="route('tenant.products.toggle-active', $product)"
            on-label="Active"
            off-label="Inactive"
            title="Toggle active"
            data-success="reload-page"
        />
        <x-tenant::status-toggle
            :checked="$product->featured"
            :action-url="route('tenant.products.toggle-featured', $product)"
            on-label="Featured"
            off-label="Not featured"
            title="Toggle featured"
            data-success="reload-page"
        />
    </div>
    <div class="pm-card-actions">
        <a href="{{ route('tenant.products.edit', $product) }}" class="t-icon-btn" title="Edit product" aria-label="Edit {{ $label }}">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </a>
        <x-tenant::dropdown align="end">
            <x-tenant::dropdown-item data-modal-open="product-social-modal" data-product-id="{{ $product->id }}">
                {{ $hasSocial ? 'View / regenerate social posts' : 'Generate social media posts' }}
            </x-tenant::dropdown-item>
            <x-tenant::dropdown-item data-modal-open="product-price-finder-modal" data-product-id="{{ $product->id }}">
                {{ $hasPriceData ? 'View AI price data' : 'Fetch AI price data' }}
            </x-tenant::dropdown-item>
            <x-tenant::dropdown-item data-modal-open="product-share-modal" data-product-id="{{ $product->id }}">Share</x-tenant::dropdown-item>
            <x-tenant::dropdown-item data-modal-open="product-price-list-modal" data-product-id="{{ $product->id }}">Price list</x-tenant::dropdown-item>
        </x-tenant::dropdown>
    </div>
</div>
```

This ensures the product cards on `/admin/products` have the same actions as before the
new card design was introduced (parity with old design approach). Do NOT change the `is-added`
logic or the "Create your Ad" CTA at the bottom.

---

## Execution Order

Run sub-agent prompts in this order:
1. **A** — Dashboard controller (foundation for B, E, G)
2. **B** — Today's Chances (depends on A's `buildOpportunityArray`)
3. **C** — Product cards (independent)
4. **D** — Own Products stats (independent)
5. **E** — Opportunity card actions (depends on A's `$opp['id']`)
6. **G** — Product modals audit (independent)
7. **H** — All remaining dummy data sweep (depends on A–G being done first)
8. **I** — Console/network error sweep (after all data is wired)
9. **J** — Product card tools restore (after C)

Prompt **F** (flash-sale quick-add) is optional and may be run after E.

---

## Data formulas reference (quick card)

| Shown as | Formula | Source field |
|---|---|---|
| `$94.00` cost | `$product->default_price` (tenant) or `$central['current_price']` | Tenant `Product.default_price` |
| `24% – 34%` below market | `floor(raw*0.85)% – ceil(raw*1.15)%` where `raw=(ref-cost)/ref*100` | `Product.cost_price` vs `default_price` |
| `96` opp score | `min(100, (int) raw_pct)` | Calculated |
| `+25%` est. profit | `+round(raw*0.25,0)%` | Calculated |
| `Low` competition | Always "Low" (static label, market data not in DB) | Static |
| `Hot 🔥` status | Always "Hot" (static for opportunity products) | Static |
| `+50%` recommended markup | `+round(raw*0.3,0)%` | Calculated |
| Suggested price | `cost * 1.5` formatted as `$X.XX` | Calculated |
| Active products count | `Product::where('active',true)->count()` | DB |
| Pending vendor purchases | `VendorSettlement::where('tenant_id',...)->whereIn('status',['pending','processing'])->count()` | Central DB |
