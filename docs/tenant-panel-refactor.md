# Tenant Panel Refactor — Livewire ➜ Controller + Blade + JS

This document is the deliverable of prompt 12 (`prompts/prompt_12_cleanup_verification.md`). It
describes the architecture that resulted from prompts 01–11, which replaced every Livewire
component under the tenant `/admin` panel (`routes/tenant.php` → `Route::prefix('admin')`) with
plain controllers, Blade views and vanilla JS/AJAX. Everything else — the storefront, the central
admin, the affiliate panel, the owner panel, and `resources/js/app.js` / `resources/css/app.css` —
is unaffected and keeps using Livewire.

## 1. Architecture

### Routes
- `routes/tenant.php` still owns the tenant-domain route group (`PreventAccessFromCentralDomains`,
  `InitializeTenancyByDomain`, storefront routes, auth). It now only registers Storefront Livewire
  components (`App\Livewire\Tenant\Storefront\ThemeKit\*`).
- `routes/tenant_panel.php` (included from `tenant.php`) holds the entire `/admin` panel: it's
  grouped by feature area (Dashboard, Catalog, Sales, Finance, Requests, Support, Store, Settings,
  Onboarding, Insights). Every page group carries the same middleware the old Livewire route did —
  `tenant.permission:<key>` and `tenant.setup:<key>` — and the `.data`, `.export`, `.validate` and
  mutating routes for that page sit inside the same group, so permission/setup enforcement is
  identical to before.
- Route names are unchanged from the Livewire era (`tenant.<section>.<page>`); only the underlying
  action changed from a Livewire component class to `[Controller::class, 'method']`.

### Controllers and FormRequests
- `app/Http/Controllers/Tenant/Panel/<Section>/<Page>Controller.php`, all extending
  `App\Http\Controllers\Tenant\Panel\PanelController`. Controllers are thin: `index()`/`show()`
  return a Blade view with everything except row data (stats, filter option lists, page meta,
  `$columns`); `data()` returns a Yajra DataTables JSON response; one method per mutating action
  (`store`, `update`, `destroy`, `toggle-active`, …); `validate<Action>()` runs the matching
  FormRequest and returns `{valid:true}`; `export()` streams CSV for pages that had
  `$exportable = true` in Livewire.
- `app/Http/Requests/Tenant/Panel/<Section>/<Action>Request.php`, all extending
  `App\Http\Requests\Tenant\Panel\TenantFormRequest`. Validation rules live exactly once, in the
  FormRequest — both the `.validate` endpoint and the real submit endpoint run the same class.
- Business logic was **moved**, not rewritten: repositories/services such as
  `TenantPanelRepository`, `TenantPanelService`, `PlanLimitService`, `OrderLifecycleService`,
  `ReturnRequestService`, `VendorPurchaseService`, `LanguagePurchaseService`,
  `AiTranslationPurchaseService`, `BladeThemeService`, `DnsRecordService`,
  `PaymentReadinessService`, `SocialPostService`, `PriceFinderService`, `FlashSaleMediaService`,
  `TenantCategoryMediaService`, `TenantTranslationService`, `AdminNotificationService`,
  `PaymentManager` keep the exact same method contracts the Livewire classes called into.

### Support helpers
`app/Support/Tenant/*` holds panel-wide value objects and helpers used by controllers and views:
- `TenantNavigation` — the sidebar/section link tree (`visibleSections()`), permission-filtered.
- `TableColumn` — a small value object (`label`, `orderable`, `searchable`, …) so DataTables column
  definitions live in PHP next to the query that feeds them, instead of in Blade or JS.
- `Metric` — builds the `stats-grid` card arrays (`Metric::cards([...])`) consistently.

### Blade components
`resources/views/components/tenant/*.blade.php`, namespaced as `x-tenant::*` (registered separately
from the shared `x-*` components used by the central admin, which are never touched):
`x-tenant::page-header`, `x-tenant::stats-grid`, `x-tenant::filters-card`, `x-tenant::datatable`,
`x-tenant::table` (static/breakdown tables), `x-tenant::modal`, `x-tenant::form`, every input/select
variant, `x-tenant::dropdown-item`, pagination, and the select2/date/color-picker wrappers.

### JS layout
`resources/js/tenant/`:
- `app.js` — the tenant panel's own Vite entry (parallel to, and independent of, the shared
  `resources/js/app.js`). Small: it only wires up `core` + global `events`, and declares no
  vendor library statically (verified in §5 below).
- `core/` — the HTTP client (fetch wrapper with the global toastr/419/403/409 handling), the
  form engine (validate-on-blur + submit), confirm dialogs (SweetAlert2), and the declarative
  `data-*` attribute wiring (modals, toggles, confirms) shared by every page.
- `components/` — JS behind the `x-tenant::*` components (datatable init, select2 init, filters
  card URL sync, pagination).
- `modules/` — cross-cutting modules such as `payment-sdk-loader.js` (see exception 1 below).
- `pages/<section>/<page>.js` — one small entry per page, imported only where that page needs
  bespoke behaviour beyond the declarative `data-*` wiring. Registered explicitly as a Vite input
  in `vite.config.js` and loaded from the page's Blade view with
  `@push('tenant-vite') @vite('resources/js/tenant/pages/<section>/<page>.js') @endpush`.

### Vendor chunks
Heavy libraries (jQuery, DataTables + its Bootstrap5/Responsive plugins, select2, toastr, TinyMCE,
flatpickr, chart.js, intl-tel-input, SortableJS) are never statically imported by `app.js` — they
are pulled in only by the page entries that need them and/or lazy-loaded, so Vite code-splits them
into separate chunks (`vendor-jquery-*.js`, `dataTables.dataTables-*.js`, `select2.full-*.js`,
`toastr-*.js`, `tinymce-*.js`, `vendor-flatpickr-*.js`, `vendor-charts-*.js`,
`vendor-phone-*.js` for intl-tel-input, `vendor-sortable-*.js`). `resources/js/tenant/app.js`'s
manifest entry only lists `core` and `events` as static imports — none of those vendor chunks.

## 2. The JSON contract

Every AJAX endpoint in the tenant panel returns one of these shapes, and the shared HTTP client
(`resources/js/tenant/core/http.js`) understands all three without per-page boilerplate:

- **Success**: `{success: true, message: string, data?: mixed, redirect?: string}`. The client
  shows `message` as a success toast; if `redirect` is present it navigates there after the toast.
- **Validation failure (422)**: `{message: string, errors: {field: string[]}}` — the standard
  Laravel validation shape. The form engine walks `errors`, focuses the first invalid field,
  reveals the locale tab if the error is on a hidden-locale field, and renders inline messages.
- **Other failure**: `{success: false, message: string, redirect?: string, toast_type?: 'error'|'warning'}`.
  The client shows an error/warning toast with `message`; 401/403/409/419/429/5xx are additionally
  intercepted globally (unauthenticated → redirect to login, no permission → 403 toast, setup
  incomplete → 409 with `redirect`, session expired (419) → toast + reload, rate limited → 429
  toast).

## 3. How to add a new page

Recipe (full version: prompt_00's "STANDARD CONVERSION RECIPE"):

1. **Route** — add the page's routes to the right group in `routes/tenant_panel.php`, inside the
   `tenant.permission:*` / `tenant.setup:*` group it belongs to: `index`/`show`, `data` (if it
   lists records), `store`/`update`/`destroy`/etc, `validate*`, `export` (if it needs CSV).
2. **FormRequest** — one class per form/action under `app/Http/Requests/Tenant/Panel/<Section>/`,
   extending `TenantFormRequest`, with `rules()` and `attributes()`.
3. **Controller** — under `app/Http/Controllers/Tenant/Panel/<Section>/`, extending
   `PanelController`, injecting the repository/service it needs.
4. **Repository query** — if the page lists records, expose (or add) a `query<X>(array $filters)`
   method on `TenantPanelRepository` returning a `Builder`, and wire it into `data()` via
   `DataTables::eloquent(...)`.
5. **View** — `resources/views/tenant/pages/<section>/<page>/index.blade.php` extending
   `tenant.layouts.app`, built from `x-tenant::*` components; DataTable cell partials go in
   `_cols/*.blade.php` (auto-escaped Blade, never string-concatenated HTML).
6. **JS entry** — `resources/js/tenant/pages/<section>/<page>.js`, registered as a Vite input in
   `vite.config.js`, loaded via `@push('tenant-vite') @vite(...) @endpush`. Most of the page's
   interactivity (forms, confirms, modals, toggles) needs no entry-specific code at all — it's
   handled by `data-*` attributes the core JS already understands.

### Worked example: Coupons (`store.coupons.*`)

Files: `app/Http/Controllers/Tenant/Panel/Store/CouponController.php`,
`app/Http/Requests/Tenant/Panel/Store/SaveCouponRequest.php`,
`resources/views/tenant/pages/store/coupons/{index,list}.blade.php` +
`_cols/actions.blade.php`, `resources/js/tenant/pages/store/coupons.js`.

- **Routes** (`routes/tenant_panel.php`, under `tenant.permission:store.coupons.manage`):
  `GET /coupons` (country picker, `coupons.index`), `GET /coupons/list/{countryId?}`
  (`coupons.list`), `GET /coupons/data/{countryId?}` (`coupons.data`),
  `GET /coupons/item/{coupon}` (`coupons.show`, used to prefill the edit modal),
  `POST /coupons` (`coupons.store`) + `POST /coupons/validate` (`coupons.validate`),
  `PUT /coupons/item/{coupon}` (`coupons.update`) + `POST /coupons/item/{coupon}/validate`
  (`coupons.validate.update`), `DELETE /coupons/item/{coupon}` (`coupons.destroy`).
- **FormRequest** (`SaveCouponRequest`): rules copied verbatim from the old Livewire validation —
  `code` unique (ignoring the current row on update via `Rule::unique(...)->ignore($couponId)`),
  `name_text`, `type` (`in:fixed,percentage`), `value`, `minimum_spend`, `start_date`, `end_date`
  (`after_or_equal:start_date`), `country_id`. `attributes()` maps `name_text` → `name`, etc., for
  natural error messages. Both `validateStore`/`validateUpdate` and `store`/`update` on the
  controller run this same class.
- **Controller**: `index()` shows a per-country picker card grid (coupon counts per
  `TenantCountry`); `list($countryId)` returns the actual page (stats via `Metric::cards()`,
  `$columns` via `TableColumn::make()`/`::actions()`); `data()` runs
  `DataTables::eloquent($this->repo->queryCoupons($countryId))`, formats `value` per `CouponType`,
  renders the `actions` column from a Blade partial and lists only `actions` in `rawColumns()`;
  `show()` returns the edit-modal JSON (`{data: {...}}`); `store`/`update`/`destroy` persist and
  return `{success, message}`.
- **View**: a country-picker index page plus a per-country list page built entirely from
  `x-tenant::page-header`, `x-tenant::stats-grid`, `x-tenant::datatable` and a create/edit
  `x-tenant::modal` + `x-tenant::form` — no bespoke markup beyond that.
- **JS**: `resources/js/tenant/pages/store/coupons.js` is two lines of comment — the modal
  create/edit/delete flow and the DataTable are fully declarative through `data-*` attributes
  (`data-modal-open`, `data-confirm`, `data-mode="create|edit"`), so no page-specific wiring is
  needed. This is the common case: most pages' JS entries are this small or don't exist at all.

## 4. Documented exceptions

The global "no CDN, no inline `<script>`/`<style>`" invariants (prompt_00 §5–6) have exactly four
carve-outs:

1. **Payment SDK CDNs (PCI DSS).** `https://js.stripe.com/v3/`,
   `https://js.authorize.net/v1/Accept.js` (and `jstest.` for sandbox), and
   `https://2pay-js.2checkout.com/v1/2pay.js` are injected on demand by
   `resources/js/tenant/modules/payment-sdk-loader.js` — never by a `<script src>` in Blade.
   These providers' terms and PCI DSS forbid self-hosting their tokenization scripts.
2. **Drag-sortable lists that are not DataTables.** SortableJS (`vendor-sortable-*.js`, dynamically
   imported) powers the sort pages (`Category`/`Product`/`Badge` sort pages) and other manually
   ordered lists (banners, page-builder sections) — these are small, fully in-memory lists where a
   DataTable would be the wrong tool.
3. **Breakdown tables using `x-tenant::table`.** Fixed, non-paginated tabular data — order line
   items, invoice and vendor-settlement breakdowns, and the price-list/price-finder variant
   tables — renders as a static `x-tenant::table`, not a DataTable, per prompt_00 invariant 9.
4. **JSON islands.** `<script type="application/json" id="...">` is the only `<script>` tag allowed
   in a tenant panel Blade file. It carries server data to JS (as an alternative to `data-*`
   attributes for larger payloads) and is never executable.

(`resources/views/tenant/storefront/invoice.blade.php` is a standalone printable/PDF document, not
a panel page under `x-tenant::*`/`tenant.layouts.app` — it keeps its own `<style>` block and Google
Fonts `<link>`s as it always has; it is explicitly out of scope for the panel's CDN/style rules and
was deliberately kept unmodified by prompt 12.)

## 5. Deleted files (changelog)

The following Livewire classes, views, layouts and controller views were removed once every page
that used them had a Blade+JS replacement and its own verification passed (prompt 12 §2). The
storefront (`App\Livewire\Tenant\Storefront\**`, `resources/views/livewire/tenant/storefront/**`)
and `resources/views/tenant/storefront/invoice.blade.php` were explicitly kept.

### Livewire classes (`app/Livewire/Tenant/**`)
`Analytics/{CustomerLifetimeValuePage,OrderAnalyticsPage,ProductProfitabilityPage,ShippingAnalyticsPage}`,
`Auth/LoginPage`, `Badge/{BadgeProductsList,BadgesList,SortBadgeProducts}`,
`Base/{ContentPage,ListPage,TenantPage}`,
`BrandRequest/{BrandRequestDetail,BrandRequestsList,CreateBrandRequest}`,
`Category/{AddEditCategory,CategoriesList,CategoryProducts,SortCategories}`,
`Concerns/InteractsWithTenantUi`, `Customer/{CustomerDetailPage,CustomersList}`, `Dashboard`,
`Finance/{BillingDetailPage,BillingPage,BuyLanguagePage,PayoutsReceivedPage,
SettlementPaymentsPage,VendorPurchasePage,VendorSettleOrderPage,WalletPage}`, `Help/DocsPage`,
`Manufacturing/{AddManufacturingRequest,ManufacturingRequestDetail,ManufacturingRequestsList}`,
`Notifications/NotificationsPage`, `Onboarding/{OnboardingPage,OnboardingTour}`,
`Order/{OrderDetailPage,OrdersList}`,
`Product/{AddEditOwnProduct,AddEditProduct,EditRequestsPage,OwnProductsList,ProductsList,SortProducts}`,
`ProductRequest/{CreateRequest,RequestDetail,RequestsList}`,
`Return/{ReturnAnalyticsPage,ReturnDetailPage,ReturnsList}`,
`Setting/{AccountSettingsPage,AddEditEmailTemplate,AdminsList,AiTranslationPage,
ComplianceCenterPage,CurrenciesPage,DomainsList,EmailTemplatesPage,GeneralSettingsPage,
LanguagesManagePage,LanguagesPage,MailConfigurationsPage,PaymentGatewaysPage,
PaymentReadinessPage,ReturnPolicyPage,RolesPermissionsList,SubscribersPage,
TrackingSettingsPage,TranslationsPage}`,
`Store/{AddEditPage,AddEditThemePart,AppearancePage,BannersIndexPage,BannersPage,
BladeThemePage,CouponsIndexPage,CouponsPage,CustomTemplatePage,FlashSalesIndexPage,
FlashSalesPage,HomeVariantsPage,PageBuilderPage,PagesList,TargetCountriesPage,
ThemePartsPage,ThemesPage}`,
`Support/{CreateTicket,TicketDetail,TicketsList}`, `Widgets/AccountSetupProgress`.

Includes the 7 dead, unrouted classes: `Product/AddEditOwnProduct`, `Store/AddEditThemePart`,
`Store/ThemePartsPage`, `Customer/CustomerDetailPage`, `Setting/AccountSettingsPage`,
`Badge/BadgesList`, `Badge/BadgeProductsList`.

### Livewire views (`resources/views/livewire/tenant/**`, excluding `storefront/**`)
`badge/{badge-products-list,sort-badge-products}`, `brand-request/{create,detail,list}`,
`category/{add-edit-category,category-products,sort-categories}`,
`customer/partials/customer-form`, `dashboard/index`,
`finance/{billing-detail-page,buy-language-page,vendor-purchase-settle-modal,
vendor-settle-order-page,wallet-page}`,
`help/{docs-page,articles/{api-reference,compliance,custom-template,custom-template-guide,
getting-started,page-builder,theme-apis,themes,tracking-pixels,variable-reference}}`,
`manufacturing/{add-manufacturing-request,manufacturing-request-detail}`,
`notifications/notifications-page`, `onboarding/{icons,page,tour}`,
`order/{order-detail-page,partials/order-details}`,
`pages/{content-page,list-page}`,
`product/{add-edit-own-product,add-edit-product,edit-requests-page,price-finder-modal,
price-list-modal,share-modal,social-posts-modal,sort-products}`,
`product-request/{create,detail,list}`,
`return/{return-analytics,return-detail-page}`,
`setting/{account-settings,add-edit-email-template,ai-translation-page,domains-list,
general-settings,languages-manage-page,partials/test-email-modal,payment-readiness,
tracking-settings,translations-page}`,
`store/{add-edit-page,add-edit-theme-part,appearance-page,banners-index-page,banners-page,
blade-theme-page,coupons-index-page,custom-template-page,flash-sales-index-page,
home-variants-page,page-builder-page,partials/flash-sale-form,partials/logo-builder,
target-countries-page,theme-parts-page,themes-page}`,
`support/{create-ticket,ticket-detail}`, `widgets/account-setup-progress`.

### Layouts
`resources/views/layouts/tenant.blade.php`, `resources/views/layouts/tenant/{header,sidebar,icon}.blade.php`.

### Old controller views (superseded by `resources/views/tenant/pages/**`)
`resources/views/tenant/badge/show.blade.php`,
`resources/views/tenant/customer/{customer-create,customer-detail}.blade.php`,
`resources/views/tenant/product/{add-edit-own-product,_variant-row}.blade.php`,
`resources/views/tenant/setting/{account-settings,compliance-center}.blade.php`.

### Global components (only the old tenant views used them)
`resources/views/components/tenant-notification-bell.blade.php`,
`resources/views/components/payment-method-badges.blade.php`.

`resources/js/app.js` and `resources/css/app.css` (shared with central admin/affiliate/owner/auth)
were **not** touched, per invariant 1.
