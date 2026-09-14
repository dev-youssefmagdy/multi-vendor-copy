# Tenant Panel Refactor — Livewire ➜ Controller + Blade + JS/AJAX

Repo: `https://github.com/dev-youssefmagdy/multi-vendor-copy.git` — branch **main** (audited at `7e1a8f6 "Fixes"`).
Scope: **everything under `/admin` on the tenant domain** (`routes/tenant.php` → `Route::prefix('admin')` group).
Out of scope: storefront, central admin, affiliate, owner panels, Figma redesign (current look is kept).

Apply the prompts **in order**. Each one is self-contained and ends with its own verification block.
Do not start a prompt until the previous prompt's verification passes.

| # | File | What it does |
|---|------|--------------|
| 01 | `prompt_01_foundation.md` | Packages, Vite entries, tenant CSS/JS core, HTTP/toastr/confirm/form engine, JSON-aware middleware, base controller, response helper, route file split |
| 02 | `prompt_02_components.md` | The full `x-tenant::*` component library (every input, select2, datatable, filters card, pagination, modal, …) + their JS modules |
| 03 | `prompt_03_layout_shell_auth.md` | Layout without Livewire, sidebar/header/widgets as Blade+JS, notification bell, onboarding tour, tenant login page |
| 04 | `prompt_04_dashboard_analytics.md` | Dashboard + 4 analytics pages |
| 05 | `prompt_05_catalog.md` | Products, own products, edit requests, sorting, categories, badges, image search |
| 06 | `prompt_06_sales.md` | Orders, returns (list/detail/analytics), customers |
| 07 | `prompt_07_finance_payments.md` | Wallet, billing, vendor purchases/settle, settlement payments, payouts, buy languages + shared payment modal |
| 08 | `prompt_08_requests_support_help.md` | Manufacturing, brand requests, product requests, support tickets, notifications, help docs |
| 09 | `prompt_09_store.md` | Themes, pages, coupons, flash sales, banners, appearance + logo builder, blade theme, page builder, home variants |
| 10 | `prompt_10_settings.md` | All 20 settings pages |
| 11 | `prompt_11_onboarding.md` | Onboarding page (tour + setup tabs) |
| 12 | `prompt_12_cleanup_verification.md` | Delete tenant Livewire classes/views, grep gates, route coverage, build checks |

---

## GLOBAL INVARIANTS — apply in every prompt

1. **Never break shared code.**
   - `resources/css/app.css` and `resources/js/app.js` are shared with the central admin, affiliate, owner and auth layouts. The tenant panel gets its own entries: `resources/css/tenant/app.css` and `resources/js/tenant/app.js`. Do **not** remove anything from `app.js`. The only permitted `app.css` edit is the font self-hosting in prompt 01.
   - Global Blade components in `resources/views/components/*.blade.php` (`x-input`, `x-select`, `x-modal`, `x-table`, `x-pagination`, `x-editor`, `x-dropzone`, `x-btn`, `x-card`, …) are used by 30+ central-admin Livewire views. **Do not modify or delete them.** New components live in the `x-tenant::` namespace.
   - `resources/views/livewire/auth/panel-login.blade.php` is shared by 4 panels. Do not touch it.
   - `resources/views/livewire/tenant/storefront/**` and `app/Livewire/Tenant/Storefront/**` belong to the storefront. **Never delete or edit them.**
   - The Livewire package stays installed (storefront + central admin use it). Only the tenant panel stops using it.
2. **Keep every existing GET URI and route name unchanged.** `TenantNavigation`, payment controllers, emails, notifications and redirects depend on them. New routes are additive:
   - `<name>.data` — DataTables JSON
   - `<name>.export` — CSV
   - `<name>.validate` — validation-only
   - action names: `.store`, `.update`, `.destroy`, `.toggle-active`, …
3. **Keep every permission / setup middleware.** Any new route inherits the exact middleware of the page it serves (`tenant.permission:*`, `tenant.setup:*`). The `.data`, `.export`, `.validate` and action routes sit inside the same group as the page.
4. **Behaviour parity.** Every public Livewire action method becomes an HTTP endpoint with identical business logic. Port logic by **moving calls to the existing repositories/services**, not by rewriting it:
   - `TenantPanelRepository`, `TenantPanelService`, `PlanLimitService`, `OrderLifecycleService`, `ReturnRequestService`, `VendorPurchaseService`, `LanguagePurchaseService`, `AiTranslationPurchaseService`, `BladeThemeService`, `DnsRecordService`, `PaymentReadinessService`, `SocialPostService`, `PriceFinderService`, `FlashSaleMediaService`, `TenantCategoryMediaService`, `TenantTranslationService`, `AdminNotificationService`, `PaymentManager`, …
   - Plan-limit checks (`canPerform()` / `errorMessage()` / `incrementCounter()`) must stay on the same actions.
   - If logic currently lives inline in a Livewire method (not in a service), move it into the matching service as a new public method. Do not leave it duplicated in a controller.
5. **No inline `<script>` or `<style>` in any tenant panel Blade file.** Every page with behaviour gets a Vite entry `resources/js/tenant/pages/<section>/<page>.js`. It is registered **explicitly** in `vite.config.js` and loaded with `@push('tenant-vite') @vite('…') @endpush`. Page-specific CSS goes in `resources/css/tenant/pages/<page>.css`, imported from the page JS entry.
   - Server data is passed to JS through `data-*` attributes or `<script type="application/json" id="…">` (JSON island). That is the only allowed `<script>` tag, and it must never be executable.
   - No `style="…"` attributes for anything beyond a genuinely dynamic value (e.g. a progress width or a user-chosen colour). Static styling goes in CSS.
6. **No CDN / external assets.** Everything comes from npm and is bundled by Vite into `public/build` (hashed, minified, code-split). Fonts use `@fontsource/*`. Avatars are local initials. TinyMCE is self-hosted.
   - **The single exception is payment SDKs:** `https://js.stripe.com/v3/`, `https://js.authorize.net/v1/Accept.js` (and `jstest.` for sandbox), `https://2pay-js.2checkout.com/v1/2pay.js`. PCI DSS and the providers' terms forbid self-hosting them. They are injected on demand by `resources/js/tenant/modules/payment-sdk-loader.js`, never by a `<script src>` tag in Blade.
7. **Every POST/PUT/PATCH/DELETE shows a toastr.** Success uses the server `message`. Error covers 422 (summary), 403, 404, 409, 419 (session expired → reload), 429, and 5xx. The HTTP client does this globally, so pages never forget. Every mutating endpoint **must** return a `message`.
8. **Every form uses validate + submit.**
   - `POST …/validate` runs the same `FormRequest` and returns `{valid:true}` or 422. It is called on field blur/change, debounced, touched fields only.
   - The submit endpoint runs the `FormRequest` again and persists.
   - The rules live **once**, in the FormRequest class.
9. **Every record list is a Yajra DataTable** (`x-tenant::datatable`, server-side). This includes lists that currently live inside modals or detail pages. Fixed breakdown tables that are not record lists (order line items, invoice totals, price breakdowns) use the static `x-tenant::table`. Each prompt names which tables go where.
10. **Escape everything.** DataTables cells that render HTML are produced by Blade partials (auto-escaped). `rawColumns` may only list columns rendered by those partials. Never concatenate user data into HTML strings in PHP. This replaces the current `ListPage` `rows` string-building pattern.
11. **Code quality.**
    - `declare(strict_types=1);` in new PHP files.
    - Typed properties and return types. Constructor-injected dependencies.
    - Thin controllers: HTTP only, delegating to services.
    - FormRequests for all input.
    - Enums for statuses (reuse existing enums).
    - No N+1: eager-load what `editColumn` touches.
    - Pint-clean PHP. ES modules with no globals except the documented `window.Tenant` debug handle.

---

## STANDARD CONVERSION RECIPE (referenced by prompts 04–11)

For each Livewire page `App\Livewire\Tenant\<Section>\<Page>`:

**A. Read first.**
- `pageMeta()`, `pageData()`, `mount()`, every public property and every public method.
- Its view(s) and partials. The generic `livewire.tenant.pages.list-page` / `content-page` count as its view if `pageView()` isn't overridden.
- Write down: stats cards, filter fields, table headers, row actions, modals, uploads, redirects, toasts (`$this->toast()`), confirms (`$this->confirmAction()`), dispatched events.

**B. Controller** at `app/Http/Controllers/Tenant/Panel/<Section>/<Page>Controller.php`, extending `App\Http\Controllers\Tenant\Panel\PanelController` (prompt 01). It has:
- `index()` or `show()` → the view, with **everything except row data**: stats, filter option lists, meta (title/badge/description), permission flags.
- `data(Request $request)` → the DataTable JSON (see D), when the page lists records.
- One method per mutating Livewire method, using the exact names from the prompt's endpoint map.
- `validate<Action>(<Action>Request $request): JsonResponse { return $this->validFormResponse(); }` for every form.
- `export(Request $request)` → a streamed CSV (see F), when the Livewire class had `$exportable = true`.

**C. FormRequest** at `app/Http/Requests/Tenant/Panel/<Section>/<Action>Request.php`, extending `TenantFormRequest` (prompt 01).
- Copy the rules **verbatim** from the Livewire `validate()` / `#[Validate]` / `rules()` calls, renaming camelCase property names to the HTML field names (snake_case, or bracket arrays for nested fields). Put the same key mapping in `attributes()` so messages read naturally.
- `authorize()` returns true: route middleware already enforces permissions.

**D. DataTable.**
- In `TenantPanelRepository`, expose the existing protected `build<X>Query(array $filters)` as `public function query<X>(array $filters): Builder`. Keep `paginate<X>()` / `export<X>()` untouched (other callers).
- Where no builder exists (the query lives in the Livewire class), move it into the repository as `query<X>()`.
- In the controller:
  ```php
  public function data(Request $request): JsonResponse
  {
      $filters = $this->filters($request, ['search', 'status']); // whitelisted keys
      return DataTables::eloquent($this->repo->queryOrders($filters))
          ->addIndexColumn()
          ->editColumn('uuid',       fn (Order $o) => view('tenant.pages.sales.orders._cols.order', ['order' => $o])->render())
          ->editColumn('status',     fn (Order $o) => view('tenant::components.status-badge', ['status' => $o->status])->render())
          ->editColumn('created_at', fn (Order $o) => $o->created_at?->format('M d, Y H:i'))
          ->addColumn('actions',     fn (Order $o) => view('tenant.pages.sales.orders._cols.actions', ['order' => $o])->render())
          ->filterColumn(...)  // only when the column's search differs from the builder's search
          ->orderColumn(...)   // for computed columns
          ->rawColumns(['uuid', 'status', 'actions'])
          ->toJson();
  }
  ```
- **Global search** goes through the repository filter (`filters[search]`). Disable DataTables' own per-column search (`searchable: false` on computed columns) so there's one search path.
- **Default order** matches the current builder's order (usually `latest()`). Computed columns are `orderable: false` unless an `orderColumn` is written.
- **Aggregated / array data** (analytics months, CLV, product profitability) uses `DataTables::collection($rows)` from the existing repository methods, or `DataTables::of($queryBuilder)` for DB-level aggregates.
- Column cells port the **exact content** of the current `rows` HTML strings, but as Blade partials in `resources/views/tenant/pages/<section>/<page>/_cols/*.blade.php`.

**E. View** at `resources/views/tenant/pages/<section>/<page>.blade.php`:
```blade
@extends('tenant.layouts.app')
@section('title', 'Orders')
@section('content')
  <x-tenant::page-header title="Orders" badge="Sales" description="…">
    <x-slot:actions> … </x-slot:actions>
  </x-tenant::page-header>
  <x-tenant::stats-grid :stats="$stats" />
  <x-tenant::filters-card target="orders-table"> … inputs … </x-tenant::filters-card>
  <x-tenant::datatable id="orders-table" :url="route('tenant.orders.data')" :columns="$columns" … />
@endsection
@push('tenant-vite') @vite('resources/js/tenant/pages/sales/orders-index.js') @endpush
```
Column definitions are built in the controller as `$columns` (a `TableColumn` value-object array, prompt 02). This keeps titles, orderability and widths in PHP next to the query that feeds them.

**F. Export.** `export()` streams the **same** `exportHeaders()` / `exportRows()` as the Livewire class, via `$this->streamCsv($fileName, $headers, $rowsIterable)` (prompt 01; UTF-8 BOM, `lazy()` cursor). The Export button is a plain `<a>` carrying the current filter query string, and the filters-card JS keeps its `href` in sync.

**G. Modals.**
- A create/edit modal becomes `x-tenant::modal` + `x-tenant::form`.
- **Edit:** the JS calls `GET <resource>/{id}` → `{data:{…}}` (a `show` JSON endpoint), then `form.fill(data)`.
- **Submit:** `POST` (create) or `PUT` (update), chosen by the form's `data-mode`. On success: close the modal, reload the DataTable, show the toastr.
- **Confirms** (`confirmAction`) become `data-confirm="…"` on the action button. The core JS asks via SweetAlert2, then sends the request.

**H. Redirects.** A Livewire `$this->redirect(url)` after an action becomes JSON `{message, redirect}`. The server flashes `status` so the next page shows the toast. The JS navigates.

**I. Realtime.** Echo listeners that called `$wire.$refresh()` now call the page's `reload()` function (DataTable reload or fragment refetch).

**J. JS entry** at `resources/js/tenant/pages/<section>/<page>.js`. It imports from `@tenant/core` only what it needs. It holds no business logic beyond UI wiring. Declarative `data-*` attributes handle the common cases (forms, confirms, modals, toggles), so most entries are small.

**K. Route.** Replace `Route::get(..., LivewireClass::class)` with `[Controller::class, 'index']`. Keep the name. Add the new routes next to it in `routes/tenant_panel.php` (prompt 01).

**L. Delete nothing in prompts 04–11.** Deletion happens only in prompt 12, after every page is verified.

---

## Definition of done (whole project)

- `grep` gates from prompt 12 all return zero.
- `npm run build` succeeds, with no warnings about unresolved imports.
- `php artisan route:list --path=admin` shows no `App\Livewire\Tenant\*` action (storefront excluded).
- Every item in `TenantNavigation::visibleSections()` opens, lists, filters, paginates, exports, creates, edits and deletes with toastr feedback. Tested in dark and light theme, and at 375px and ≥1280px widths.
- The browser Network tab shows zero requests to third-party hosts, except payment SDKs on payment modals and Reverb websockets.
