# Prompt 12 — Cleanup & Verification

Run this only after prompts 01–11 have passed their own verification. All invariants in `prompt_00_README_tenant_panel_refactor.md` apply.

## 1. Pre-delete safety checks
Stop if any of these returns a result outside the paths being deleted:
```bash
grep -rn "App\\\\Livewire\\\\Tenant\\\\" app routes config bootstrap tests | grep -v "Livewire\\\\Tenant\\\\Storefront"
grep -rn "livewire\.tenant\.\|livewire/tenant/" app resources routes | grep -v "livewire.tenant.storefront\|livewire/tenant/storefront"
grep -rn "layouts\.tenant['\")]" app resources --include=*.php
grep -rn "@livewire('tenant\.\|<livewire:tenant\." resources/views | grep -v "tenant.storefront"
php artisan route:list --path=admin --json | grep -i "Livewire"      # must be empty
```
Known safe facts (audited):
- `app/Livewire/Admin/**` has its own `Admin\Base\ListPage` and `Admin\Concerns\HasCsvExport`, and does not depend on `App\Livewire\Tenant\Base` or `Concerns`.
- The storefront uses `App\Livewire\Tenant\Storefront\Concerns\*` (CartController, PlacesOrders), which lives under `Storefront/` and stays.
- `AppServiceProvider` only registers `Tenant\Storefront\ThemeKit\*` Livewire components.

## 2. Delete

**Livewire classes** — `app/Livewire/Tenant/**` except `app/Livewire/Tenant/Storefront/**`:
- `Analytics/`, `Auth/`, `Badge/`, `Base/`, `BrandRequest/`, `Category/`, `Concerns/`, `Customer/`, `Dashboard.php`, `Finance/`, `Help/`, `Manufacturing/`, `Notifications/`, `Onboarding/`, `Order/`, `Product/`, `ProductRequest/`, `Return/`, `Setting/`, `Store/`, `Support/`, `Widgets/`
- This includes the 7 dead, unrouted classes: `Product/AddEditOwnProduct`, `Store/AddEditThemePart`, `Store/ThemePartsPage`, `Customer/CustomerDetailPage`, `Setting/AccountSettingsPage`, `Badge/BadgesList`, `Badge/BadgeProductsList`.

**Livewire views** — `resources/views/livewire/tenant/**` except `resources/views/livewire/tenant/storefront/**`. This includes these dead views:
- `finance/vendor-purchase-settle-modal.blade.php` (no references)
- `store/theme-parts-page.blade.php`
- `store/add-edit-theme-part.blade.php`
- `setting/account-settings.blade.php`
- `product/add-edit-own-product.blade.php`
- `badge/badge-products-list.blade.php`
- `pages/list-page.blade.php`, `pages/content-page.blade.php`

**Old layout** — `resources/views/layouts/tenant.blade.php` and `resources/views/layouts/tenant/` (`header`, `sidebar`, `icon`).

**Old controller views** that moved under `resources/views/tenant/pages/`:
- delete `resources/views/tenant/badge/`, `resources/views/tenant/customer/`, `resources/views/tenant/product/`, `resources/views/tenant/setting/`
- **keep** `resources/views/tenant/storefront/invoice.blade.php`

**Old controllers** that moved into `App\Http\Controllers\Tenant\Panel\*` — delete each one only when grep finds zero references:
- `OwnProductController`
- `CustomerCreateController`
- `CustomerDetailController`
- `AccountSettingsController`
- `ComplianceCenterController`
- `BadgeProductsController`

**Global components that only the old tenant views used** — delete only after grep confirms no remaining users:
- `resources/views/components/tenant-notification-bell.blade.php`
- `resources/views/components/payment-method-badges.blade.php`
- **Do not delete any other global component.** The central admin uses them.

**Help article partials** — delete `resources/views/livewire/tenant/help/articles/*` (already copied to `tenant/pages/support/help/articles/` in prompt 08). Before deleting, confirm that `resources/views/admin/docs/**` does not include them.

**Stale references to clean up**
- Remove any leftover `use App\Livewire\Tenant\…;` import in `routes/tenant_panel.php` and `routes/tenant.php` (except the storefront ones).
- Remove every reference to `tenant.onboarding.onboarding-tour` and `tenant.widgets.account-setup-progress`.
- In `resources/views/admin/docs/_partials/architecture.blade.php`, the row that describes `resources/views/livewire/tenant/` should now point to `resources/views/tenant/pages/` and `resources/views/tenant/components/`.
- `resources/js/app.js` keeps its Livewire hooks, because the central admin still uses them. **Do not edit it.**

**Refresh caches**
```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan view:cache
php artisan route:cache
php artisan config:cache
```

## 3. Grep gates — every command must return nothing
```bash
P="resources/views/tenant"

grep -rnE "wire:|@livewire|<livewire:|livewire(Styles|Scripts)" $P
grep -rnE "x-data|x-show|x-model|x-on:|@click|x-cloak|x-transition|x-text|x-bind" $P
grep -rnP "<script(?![^>]*type=\"application/json\")" $P
grep -rn "<style" $P
grep -rnE "https?://(cdn\.|unpkg|cdnjs|fonts\.(googleapis|gstatic)|api\.dicebear|code\.jquery|jsdelivr)" \
     $P resources/css/tenant resources/js/tenant resources/css/app.css
# Only payment-sdk-loader.js may match the next one:
grep -rnE "https?://" resources/js/tenant | grep -vE "js\.stripe\.com|js(test)?\.authorize\.net|2pay-js\.2checkout\.com|localhost"
grep -rn "'rows' =>" app/Http/Controllers/Tenant/Panel          # no HTML-string row building
grep -rn "Livewire" app/Http/Controllers/Tenant/Panel app/Services/Tenant --include=*.php | grep -v Storefront
```

Review these by hand rather than expecting an empty result:
- `grep -rn "->rawColumns(" app/Http/Controllers/Tenant/Panel`. Every raw column must be rendered by a Blade partial (auto-escaped) and never concatenated in PHP.
- `grep -rnE 'style="[^"]*(margin|padding|display|color|font|border|background|grid|gap)' $P`. Static styling must live in CSS. Only genuinely dynamic values are allowed inline, such as `--p:` progress or a user-chosen colour.

## 4. Coverage tests

### 4.1 `tests/Feature/Tenant/PanelRouteCoverageTest.php`
- Seed a tenant that has setup complete, an active theme, a payment gateway, a language, and an admin holding every permission.
- For every link and child in `TenantNavigation::visibleSections()`, GET the route and assert 200, or a documented redirect (for example, pages → payment gateways when no gateway exists).
- For every route whose name ends in `.data`, send a GET with `Accept: application/json` and assert the JSON has the keys `draw`, `recordsTotal`, `recordsFiltered`, `data`.
- For every route whose name ends in `.export`, assert a `text/csv` response that starts with the UTF-8 BOM.

### 4.2 `tests/Feature/Tenant/PanelMutationsContractTest.php`
- Iterate the routes in `routes/tenant_panel.php` that use POST, PUT, PATCH or DELETE. Exclude login, logout, compliance, the gateway `charge`/`success`/`cancel`/`webhook` routes, and `*.validate`.
- Cover at least one route per controller:
  - with a valid payload (built from factories), assert 2xx JSON with a non-empty `message`;
  - with an empty payload, assert 422 with `errors`, or 2xx for endpoints that take no input.

### 4.3 `tests/Feature/Tenant/ValidateRoutesTest.php`
- Every `*.validate` route returns `{valid:true}` for a valid payload and 422 for an empty one.
- Every `*.validate` route carries the `throttle:tenant-validate` middleware.

### 4.4 Middleware contract
- An AJAX request without permission returns 403 JSON.
- An AJAX request while setup is incomplete returns 409 JSON with `redirect`.
- An unauthenticated AJAX request returns 401 JSON.
- A GET without the `Accept: application/json` header still redirects, as before.

### 4.5 Vite manifest check (`scripts/check-tenant-vite.sh`, run in CI after `npm run build`)
```bash
grep -rhoE "@vite\('resources/js/tenant/pages/[^']+'\)" resources/views/tenant \
  | sed -E "s/@vite\('([^']+)'\)/\1/" | sort -u > /tmp/used.txt
grep -oE "'resources/js/tenant/pages/[^']+'" vite.config.js | tr -d "'" | sort -u > /tmp/declared.txt
comm -23 /tmp/used.txt /tmp/declared.txt   # used but not declared → must be empty
comm -13 /tmp/used.txt /tmp/declared.txt   # declared but unused → must be empty
while read f; do grep -q "\"$f\"" public/build/manifest.json || echo "MISSING IN MANIFEST: $f"; done < /tmp/used.txt
```

## 5. Build and performance
- `npm run build` finishes with no warnings, apart from the known TinyMCE chunk-size warning.
- **Bundle split.** The chunk behind `resources/js/tenant/app.js` must not contain jQuery, DataTables, select2, toastr, TinyMCE, flatpickr, chart.js, intl-tel-input or SortableJS. Each of those must show up only under `dynamicImports` for the entry in `manifest.json`. Check it with:
  ```bash
  node -e "const m=require('./public/build/manifest.json');console.log(m['resources/js/tenant/app.js'])"
  ```
- **Lighthouse** on `/admin/orders` (desktop, logged in):
  - no third-party requests at all;
  - no render-blocking fonts;
  - CLS below 0.05 (the theme cookie removes the flash);
  - the first DataTables request fires within 300 ms of `DOMContentLoaded`.

## 6. Manual QA matrix
Test every page reachable from `TenantNavigation` under five conditions: dark theme, light theme, 375 px width, at least 1280 px width, and an RTL locale. On each page, check the items below that apply.
- **Lists:** load, filter (the filters survive a reload through the URL), sort, page, page length, quick search, and export where the page has one.
- **Forms:** blur validation on touched fields only, a toast on submit, inline 422 errors, the first invalid field focused, and the locale tab revealed when the error sits in a hidden locale.
- **Modals:** open, pre-filled on edit, save, close with ESC, return focus to the opener, and select2 dropdowns inside the modal.
- **Deletes and toggles:** the confirm dialog, then a toast; a failed toggle reverts the switch.
- **Session expiry:** leave the page idle past the session lifetime; the next action shows the 419 toast and reloads the page.
- **Permissions:** a user without the permission gets a 403 toast on AJAX and a redirect with a flash on GET.
- **Realtime:**
  - the notification bell (Reverb auth goes to `/tenant/broadcasting/auth`);
  - the support, manufacturing, brand request and product request chats;
  - the setup widget, which polls only while the tab is visible.
- **Payments:**
  - gateways: Stripe inline, Authorize.Net inline (sandbox), 2Checkout inline, and one redirect gateway;
  - start points: wallet renew/upgrade, buy languages, languages manage, AI translation, manufacturing, brand request, vendor settle;
  - after returning from the gateway, the landing page shows the correct toast.
- **Other panels:** spot-check the central admin, affiliate, owner panel and every storefront theme. They should look and work exactly as before (they still use Livewire and `app.css`/`app.js`).

## 7. Deliverable
Write `docs/tenant-panel-refactor.md`, covering:
- **Architecture:** `routes/tenant_panel.php`, `Tenant\Panel\*` controllers, `Tenant\Panel\*` FormRequests, the `app/Support/Tenant/*` helpers, `x-tenant::*` components, the `resources/js/tenant/{core,components,modules,pages}` layout, and the vendor chunks.
- **The JSON contract:** success `{success,message,data,redirect}`, validation `{message,errors}`, failure `{success:false,message,redirect,toast_type}`.
- **How to add a new page:** the recipe from prompt 00, with one worked example (Coupons).
- **Documented exceptions:**
  1. Payment SDK CDNs (PCI).
  2. Drag-sortable lists that are not DataTables: the sort pages, banners and page-builder sections.
  3. Breakdown tables that use `x-tenant::table`: order line items, invoice and settlement breakdowns, and the price-list and price-finder variant tables.
  4. JSON islands (`type="application/json"`) as the only kind of `<script>` allowed in Blade.
- **Deleted files:** list every Livewire class and view removed, for the changelog.
