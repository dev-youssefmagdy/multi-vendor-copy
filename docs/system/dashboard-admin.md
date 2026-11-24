# Dashboard #1: Super Admin / Platform Administration

> Part of [COMPLETE_SYSTEM_DOCUMENTATION.md](../../COMPLETE_SYSTEM_DOCUMENTATION.md) §6. Every route, handler, form field and validation rule for this panel is listed in the generated [module-inventory.md §Super Admin panel](module-inventory.md#super-admin-panel-172-routes). This file explains what each module does and how it connects to the rest of the system. Evidence tags: **[V]** verified in source, **[I]** inferred, **[R]** recommendation.

## 1. Overview

| Item | Value |
|---|---|
| Purpose | Platform-owner control of the central catalog, stock, shipping, orders/fulfilment, tenants and plans, money flows, affiliates, content, settings and staff |
| Users | platform staff (`admins` table, central DB) |
| URL | `{central-domain}/admin` → `admin.dashboard`; login `/admin/login` (`admin.login`, Livewire `Admin\Auth\LoginPage`: email, password, remember) |
| Auth | session guard `admin`; middleware `AdminAuth` (redirects to `admin.login`) + `AdminPermission:{key,…}` (any-of) per route [V] |
| Technology | Livewire 4 full-page components `app/Livewire/Admin/**` (122 routes) + a few controllers (`app/Http/Controllers/Admin/*`: products form, edit requests, badge sorting, branches form, tenant editor, impersonation, receipts, language, mobile app, notification endpoints) [V] |
| Layout | admin layout under `resources/views/layouts/` + `components/admin-*`; left sidebar from `App\Helpers\AdminNavigation::visibleSections()`; top bar with breadcrumb (`routeInfo()`: section › group › page), notification bell (`components/admin-notification-bell.blade.php`, Reverb channel `admin.notifications`), dark/light toggle (`[data-theme]` tokens in `resources/css/app.css`) [V] |
| Error handling | JSON 404/403/419 for `admin*` AJAX requests (`bootstrap/app.php`) [V] |

## 2. Sidebar and navigation (complete)

All 80 route names referenced below resolve to registered routes [V]. Items are hidden when the user lacks the permission. A group is hidden when all its children are hidden.

| Section › Group | Item | Route | Permission | Status |
|---|---|---|---|---|
| Overview | Dashboard | `admin.dashboard` | dashboard.view | implemented |
| Commerce › Product Management | Products List | `admin.products.index` | catalog.products.view | implemented |
| | Categories List | `admin.categories.index` | catalog.categories.view | implemented |
| | Variations List | `admin.variations.index` | catalog.variations.view | implemented |
| | Product Labels | `admin.labels.index` | catalog.labels.view | implemented |
| | New In / Best Selling / Featured / Recommended / Trending Now Products | `admin.badges.{new-in,best-selling,featured,recommended,trending-now}` | catalog.badges.manage | implemented (badge lists with sort) |
| | Product Edit Requests | `admin.products.edit-requests` | catalog.product-edit-requests.manage | implemented |
| | Product Requests | `admin.product-requests.index` | catalog.product-requests.view | implemented |
| Commerce › Orders | Orders List | `admin.orders.index` | sales.orders.view | implemented (fan-out, see §5) |
| | Orders Report | `admin.orders.report` | sales.orders.report.view | implemented |
| | Return Requests | `admin.orders.returns.index` | sales.orders.view | implemented |
| | Refunds | `admin.orders.refunds.index` | sales.orders.view | implemented |
| | Return Analytics | `admin.orders.returns.analytics` | sales.orders.view | implemented (+ export) |
| Commerce › Shipping Management | In Delivery Orders | `admin.shipping.in-delivery` | shipping.delivery.view | implemented |
| | Fixed Shipping Costs | `admin.shipping.fixed-costs` | shipping.delivery.view | implemented |
| | Delivery Charges | `admin.shipping.delivery-charges` | shipping.delivery.view | implemented (shipping zones) |
| | Branches | `admin.branches.index` | branches.view | implemented |
| | Shipping Settings | `admin.shipping.settings` | shipping.settings.view | implemented |
| | Default Shipping Days | `admin.shipping.shipping-days.index` | shipping.settings.view | implemented |
| | Delivery Popup Days | `admin.shipping.delivery-popup.index` | shipping.settings.view | implemented |
| Commerce › Store | Coupons | `admin.store.coupons.index` | store.coupons.manage | implemented |
| | Flash Sales | `admin.store.flash-sales.index` | store.flash-sales.manage | implemented |
| | Tenant Sync | `admin.store.tenant-sync.index` | store.sync.manage | implemented; **being retired** by the dedup plan |
| Commerce › Manufacturing | Manufacturing Requests | `admin.manufacturing.index` | manufacturing.view | implemented |
| Commerce › Brand Requests | Brand Requests | `admin.brand-requests.index` | brand-requests.view | implemented |
| Commerce | Payment Logs | `admin.payment-logs.index` | billing.payment-logs.view | implemented |
| Commerce | Customers | `admin.customers.index` | sales.customers.view | implemented (fan-out) |
| Commerce | Notifications | `admin.notifications.index` | dashboard.view | implemented |
| Commerce | Support | `admin.support.index` | support.tickets.view | implemented |
| Commerce › Plans | Registered Users (tenants) | `admin.plans.users` | plans.tenants.view | implemented |
| | Pending Registrations | `admin.plans.pending-registrations` | plans.pending-registrations.view | implemented |
| | Tenant Change Requests | `admin.plans.tenant-change-requests` | plans.tenant-change-requests.manage | implemented |
| | Plans | `admin.plans.index` | plans.packages.view | implemented |
| Commerce | Compliance | `admin.compliance.index` | compliance.tenants.view | implemented |
| Commerce › Wallet Management | Tenants Ledger | `admin.wallets.index` | billing.wallets.view | implemented (fan-out) |
| | Finance Reports | `admin.finance.reports` | billing.reports.view | implemented |
| | Vendor Settlements | `admin.vendor-settlements.index` | billing.vendor-settlements.view | implemented |
| | Tenant Payouts | `admin.finance.payouts.index` | billing.payouts.manage | implemented |
| | Transactions | `admin.wallet-transactions.index` | billing.transactions.view | implemented (fan-out) |
| | Invoices | `admin.invoices.index` | billing.invoices.view | implemented |
| Commerce › Affiliates | Affiliate Accounts / Conversions / Payouts / Reports / Promo Codes | `admin.affiliates.{index,conversions,payouts,reports,coupons}` | affiliates.manage | implemented |
| Content › Blog Management | Categories, Posts | `admin.blog.categories`, `admin.blog.posts` | content.blog-*.view | implemented (central marketing blog) |
| Content › Domains | Domain Requests, DNS Records | `admin.domains.requests`, `admin.domains.dns-records` | domains.*.view | implemented (manual) |
| Content | Static Pages | `admin.pages.index` | content.pages.view | implemented |
| Content | FAQs | `admin.faqs.index` | content.faqs.view | implemented |
| Content | Newsletter | `admin.newsletter.index` | content.newsletter.view | implemented (list/delete only) |
| System › Settings | General, Home Variants, Template Control, Blade Theme Queue, Payment Gateways, Payment Gateway Limits, Email Templates, Email Configuration, Social Login, AI Settings, Currencies, Languages, Translation Key Access, Language Purchases, Translation Files, Countries, Maintenance Mode, Return Policy, Default Banners | `admin.settings.*` | see the generated inventory | implemented |
| System › Admins Management | Admins List, Role & Permissions | `admin.admins.index`, `admin.admins.roles-permissions` | admins.*.view | implemented |
| System › Clear Cache | Tenants Cache, Main Cache | `admin.cache.tenants`, `admin.cache.main` | system.cache.manage | implemented |
| System | Developer Docs | `admin.dev-docs.index` | system.dev-docs.view | implemented (`Livewire\Admin\Docs\DocsPage`) |

Routes not in the sidebar: AI logo limit (`admin.settings.ai-logo-limit`, permission `settings.ai-logo-limits.manage`), template parts (`admin.settings.template-parts*`), the impersonation endpoint, the order receipt, the tenant mobile-app update, and `GET /admin/products-run` (dev tool, no permission, see §6).

## 3. Modules

The 20-point template is applied per module. To avoid repeating the generated inventory, "Fields / rules" points to it where the list is long.

### 3.1 Dashboard
- **Purpose:** KPI overview. `Livewire\Admin\Dashboard` → `DashboardRepository::stats()` (tenants, packages, products, orders, revenue), `revenueSeries()` (chart), `latestPayments()` (5 latest `payment_logs`) [V].
- **Data:** orders and revenue come through `TenantAdminAggregateService` (loops all tenant DBs) [V]. Charts use Chart.js [I].
- **Debt:** cost grows linearly with tenants (P1). **[R]** Read from `order_facts`.

### 3.2 Product Management (central catalog)
- **Business purpose:** the platform's sellable catalog. Every tenant resells these products. Stock is central (per branch).
- **Pages and actions:**
  - **Products List** `Livewire\Admin\Product\ProductsList`:
    - filters: status, category, delivery scope, stock, deleted (soft deletes), **image search** (upload an image → `ImageSearchService` → Python embeddings);
    - sorting by column; delete / restore;
    - "price list" modal showing per-country pricing.
  - **Add/Edit Product** (`admin.products.create|edit`, `Admin\ProductController` + Livewire `AddEditProduct`, 592 lines):
    - sku (unique), slug, status (`draft|published|archived`), delivery scope (`all_zones|selected_zones|digital`), base / sale / cost price, stock, min stock, manage stock, taxable, factory, weight (grams);
    - assigned countries / all countries, categories, shipping zones, badges, label;
    - per-locale translations (name, description…), variants (option pairs from variations, per-variant images), primary image + gallery (reorder, remove);
    - **assign to all tenants or to selected tenants** (`product_tenant_assignments`, "tenant-owned" exclusivity) [V].
  - **Sort Products** (`SortProducts`, drag and drop).
  - **Edit Requests** (`ProductEditRequestController@index`, approve/reject vendor-proposed changes stored in `product_edit_requests`).
- **Categories:** `CategoriesList`, `AddEditCategory` (parent path, status, featured, translations, thumbnail), `SortCategories`, `CategoryProducts` (order products in a category).
- **Variations:** `VariationsList`, `AddEditVariation` (translations + options, e.g. Color: Red, Blue).
- **Labels:** `LabelsList`, `AddEditLabel` (text, translations, icon from `ProductLabelIcons`, active, sort). Toggle active.
- **Badges:** five fixed badge lists (new-in, best-selling, featured, recommended, trending-now) via `BadgeProductsController` + `BadgeSortController`. Products are assigned and ordered per badge, or all products in a category at once. Each badge feeds a storefront home section [V].
- **Tables:** `products`, `product_variants`, `variations`, `variation_options`, `product_variation`, `product_variant_option`, `categories`, `category_product`, `product_badges`, `product_badge_product`, `product_labels`, `product_label_product`, `product_country`, `product_shipping_zone`, `product_tenant_assignments`, `product_image_embeddings`, `files`, `translations`.
- **Side effects:**
  - observers (`CentralCatalogSyncObserver`) push changes to tenants in `sync` mode (`SyncCentralProductToTenantJob`, price/stock/weight jobs on `tenant-sync`); in `central` mode tenants read live;
  - `NotifyTenantsForProductCategories` notifies tenants that sell the category;
  - `EmbedProductImagesJob` computes image embeddings;
  - `ConvertImagesToAvifJob` converts images.
- **Rules:**
  - central variants and products use SoftDeletes, so order history keeps its references (dedup plan B1);
  - out-of-stock products are archived after N days (`products:archive-out-of-stock`, `outOfStockArchiveDays` in General Settings).
- **Debt:** sync jobs vs live reads coexist; a 592-line Livewire form. **[R]** A Catalog module with commands (CreateProduct, UpdatePricing) and events.

### 3.3 Product Requests (vendor → platform sourcing)
- `ProductRequest\RequestsList` (status filter, archive), `RequestDetail` (reply with files, status, priority, live refresh). Statuses: `pending, reviewing, in_production, completed, rejected, archived`.
- Real-time chat: `ProductRequestMessageSent` / `ProductRequestStatusChanged` on `admin.product-request.{id}` and `tenant.{id}.product-requests`. Files are served via `ProductRequestFileController`.
- Tables: `product_requests`, `product_request_messages` (central; note the model lacks `CentralConnection`, so it is read from central context only [V]).

### 3.4 Orders, Returns, Refunds
- **Orders List** (`Order\OrdersList`): filters status, payment status, gateway, search; export (CSV via `OrderRepository::export`). Data comes from `TenantAdminAggregateService::orders()`, **all tenants loaded into memory** [V].
- **Order Detail** (`/admin/orders/{tenantId}/{orderNumber}`, `OrderDetailPage`):
  - update tracking number; create return (reason ≥ 10 chars);
  - **cancel** (two-step modal; reason from `CancellationReason::options(Admin)`, note required when "other");
  - **refund** (amount ≤ refundable, reason); retry, complete (reference) and reject refunds.
  - Own-product orders are read-only (`OrderFulfillmentService::assertPlatformManageable`).
  - Receipt / shipping label: `OrderReceiptController`.
- **Orders Report** (`OrdersReportPage` → `OrderRepository::reportOverview()`: monthly series, per-tenant totals).
- **Returns** (`OrderReturnsList`, `OrderReturnDetailPage`, 490 lines): notes, approve (note), reject (reason), request more info, inspect (result `passed|partial|failed`, notes, **restock** flag), refund (amount), ship exchange (tracking), complete or reject the refund. Transitions are enforced by `ReturnStatus::allowedTransitions()`.
- **Refunds** (`RefundsList`): filters tenant, status, source (`cancellation|return|manual`), date range.
- **Return Analytics** (`ReturnAnalyticsPage`): per-tenant filter, export.
- **Tables:** tenant `orders`, `order_items`, `order_activities`; central `return_requests`, `return_request_media`, `return_request_notes`, `refunds`, `order_attachments`.
- **Rules:** all money via `OrderPricing` / `OrderProfitCalculator` / `RefundCalculator` (see `docs/ORDER_FORMULAS.md`). Cancelled and refunded orders produce 0 platform/vendor figures.

### 3.5 Shipping Management and Branches
- **In Delivery** (`InDeliveryOrdersList` + `InDeliveryOrderDetailPage`) is the platform fulfilment queue:
  - filter by shipping status; change order status (modal);
  - tracking number, upload/delete attachments (`order_attachments`), create return.
  - Own-product orders are excluded [V].
- **Fixed Shipping Costs** (`FixedShippingCostsList`, `AddEditFixedShippingCost`): per country, `price_per_gram`, currency, active. This is the "fixed shipping" component added to every catalog line price (`SyncProductFixedShippingCosts`, `SyncFixedShippingCostsToTenantsJob`).
- **Delivery Charges** (`DeliveryChargesList`, `AddEditShippingZone`): shipping zones (code, currency, status, translations) with weight-band **rates** (`shipping_zone_rates`), used for `orders.shipping_charge`.
- **Branches** (`BranchesList`, `AddEditBranch` via `AddEditBranchController`):
  - warehouse fields: name, code, phone, email, address, city, country, default, active;
  - per-branch shipping zones and rates; default free-shipping weight;
  - stock per branch in `branch_products`.
- **Shipping Settings** (`ShippingSettingsPage`): default shipping days; **shipping stops** (date ranges with a reason, e.g. holidays) in `shipping_stops`.
- **Default Shipping Days** (`ShippingDaysPage`): per-country min/max days (`shipping_day_rules`).
- **Delivery Popup Days** (`DeliveryPopupPage`): per country, day number + percentage (`delivery_popup_days`), used by a storefront delivery estimate popup [I].

### 3.6 Store: central Coupons, Flash Sales, Tenant Sync
- **Coupons** (`CouponsPage`): code, name, type (`fixed|percentage`), value, minimum spend, start/end, active, **country scope** (`central_coupon_country`). Central coupons apply on all tenant storefronts and are attributed `coupon_source = central` [V].
- **Flash Sales** (`FlashSalesPage`): product picker (search, paginate), discount %, dates, active, countries (`central_flash_sales`, `_product`, `_country`); `discount_source = central`.
- **Tenant Sync** (`TenantSyncPage`): pick sections × tenants → dispatch sync. Legacy of the copy model.

### 3.7 Manufacturing and Brand Requests
- Lists filtered by status and tenant, with a status modal.
- The detail page has status, admin notes, a **chat** (`ManufacturingMessageSent` / `BrandRequestMessageSent`), and **payment requests** (label, amount, currency, notes; create/cancel). The vendor pays them through gateway callback routes.
- Statuses:
  - manufacturing: `pending, under_review, in_production, completed, cancelled, rejected`;
  - brand: `pending, under_review, approved, completed, cancelled, rejected`;
  - payment requests: `pending, paid, cancelled`.

### 3.8 Payment Logs, Customers, Notifications, Support
- **Payment Logs** (`PaymentLogsList`, `PaymentLogDetailPage`): central `payment_logs` (subscriptions, registrations, add-ons), filtered by status and gateway. A status change to Refunded triggers `PaymentLogAffiliateObserver`, which reverses the affiliate commission.
- **Customers** (`CentralCustomersList`): all tenants' customers (fan-out), with filters status and "new only".
- **Notifications** (`NotificationsPage` + JSON endpoints `admin.notifications.{latest,open,read,read-all}`): filter by type, mark read, delete.
- **Support** (`TicketsList`, `TicketDetail`): tenant tickets with replies, status and priority, live via `admin.support.{ticketId}`.

### 3.9 Plans and Tenants
- **Plans** (`PlansList`, `AddEditPackage`):
  - status, term, price, trial days, icon, translations, feature rows (label/value);
  - limits: categories count or all categories, `products_limit`, `banners_limit`, `languages_limit`, `orders_per_month_limit`, `ai_calls_limit`, `image_searches_limit` (`-1` = unlimited), plus the `aiTranslationEnabled` flag;
  - enforced in tenants by `PlanLimitService::canPerform()` [V].
- **Registered Users = tenants** (`RegisteredUsersList`: filters status, package, has logo; delete tenant, download logo, export).
- **Add/Edit Tenant** (`TenantEditorController` + `AddEditTenant`):
  - fields: name, slug, email, phone, status, categories, primary language, package, trial end, domain, **profit percentage** (default markup), affiliate commission type/value, password, logo;
  - **assign package** modal (`AssignPackageModal`: package, gateway, amount, status, reference, paid at) for manual billing;
  - mobile app settings (`TenantMobileAppController`).
- **Impersonate:** `admin.plans.users.impersonate` → a 90 s single-use token → the tenant panel logged in as Store Owner. **No audit trail [V].**
- **Pending Registrations:** `pending_registrations` with filters status and package; delete.
- **Tenant Change Requests:** approve or reject (with an admin note) a vendor's request to change countries or categories. Approval dispatches `ResyncTenantChangeRequestJob` on queue `tenant-catalog-sync` (**not consumed by the documented worker**) [V].
- **Side effects of tenant creation:** see `COMPLETE_SYSTEM_DOCUMENTATION.md` §13.1 (DB provisioning, domain, owner, billing, affiliate attribution, mails).

### 3.10 Compliance
- `ComplianceOverviewList` (filter by status) and `ComplianceDetailPage` (mark verified / needs action, with a note).
- The vendor submits business identity data (business name, store name, country/city, phone, email, owner name, ID number, DOB, company, registration number) in **Settings → Compliance Center** and must accept the compliance terms (`tenant.compliance.accept`).
- Fields are stored on the tenant: `compliance_accepted_at`, `compliance_reviewed_at` [V].

### 3.11 Wallet Management (finance)
- **Tenants Ledger** (`WalletsList` / `WalletDetailPage`):
  - per tenant, computed from each tenant's orders: subtotal, discount, shipping, tax, platform profit, vendor profit, vendor gateway fee;
  - "mark paid" for an order settlement (amount, method, reference, note) [V docblock].
- **Finance Reports** (`FinanceReportsPage`, tabs).
- **Vendor Settlements** (`VendorSettlementsPage`): `vendor_settlements` (vendor → platform payments), filters gateway, tenant, status.
- **Tenant Payouts** (`PayoutsIndexPage`, `TenantPayoutPage`, `TenantPayoutEditPage`):
  - platform → vendor payouts: invoice number, method, bank or gateway, transaction reference, amount, status, note, paid at;
  - optional email (`TenantPayoutMail`); cancel.
- **Transactions** (`TransactionsList`): tenant `transactions` (fan-out) with filters direction and source, plus a detail modal.
- **Invoices** (`InvoicesList`, `InvoiceDetailPage`): central `invoices` (`draft|due|paid|cancelled`).
- **Debt:** the ledger is derived, not a double-entry journal; reconciliation depends on recomputation from orders. **[R]** A double-entry ledger module.

### 3.12 Affiliates (admin side)
Full lifecycle in [dashboard-affiliate.md](dashboard-affiliate.md).
- **Accounts** (`AffiliatesListPage`):
  - edit name, commission type/value, status (`pending|active|suspended`); type filter (external/tenant; tenant email not editable);
  - **pay out** modal (amount, reference, notes, attachment; prefilled from a payout request) and reject requests;
  - **Program Settings** modal: cookie days, default commission type/value, minimum payout, stored in `app_settings` via `AffiliateSettings`.
- **Conversions** (`ConversionsListPage`): status filter, **reverse** a conversion.
- **Payouts** (`PayoutsListPage`): requests with a details modal (payee profile, stats), mark paid (amount, reference, notes, receipt) or reject (note).
- **Reports** (`AffiliateReportsPage`): aggregate stats.
- **Promo Codes** (`AffiliateCouponsPage`): code, affiliate, discount type/value (for the merchant's subscription price), commission value, start/end, minimum spend, active.

### 3.13 Content
- **Blog:** `BlogCategoriesList`/`AddEditBlogCategory` (status, translations), `BlogPostsList`/`AddEditBlogPost` (category, status, published at, translations, image; publish/archive). Rendered on the **central marketing site** `/blog`, `/blog/{slug}`.
- **Static Pages:** `StaticPagesList`/`AddEditStaticPage` (status, `is_compliance` flag, translations; activate/archive). Rendered at `/pages/{slug}`, `/terms`, `/privacy`. Compliance pages are shown at registration.
- **FAQs:** `FaqsList`/`AddEditFaq` (status, translations) → `/faqs`.
- **Newsletter:** `NewsletterSubscribersList` (status filter, delete). No campaign sending exists [V].
- **Domains:**
  - `DomainRequestsList`: tenant, domain (unique), status `pending|connected|removed`, requested/verified dates; mark connected/pending/removed.
  - `DnsRecordsList`: global DNS record templates (type, name, value, TTL 60–86400, priority, description, required) shown to vendors for custom-domain setup.
  - Connection and SSL are **manual** [I].

### 3.14 System Settings (19 pages)
| Page | Component | What it controls |
|---|---|---|
| General | `GeneralSettingsPage` | app name, support email, default currency/language, marketplace name, header/footer logo, favicon, out-of-stock archive days, social URLs |
| Home Variants | `HomeVariantsPage` | per theme: variant key, name, description, ordered sections (toggle + sort), view, default, active, preview image (`home_variants`) |
| Template Control | `TemplateControlPage` | storefront templates (themes): activate/deactivate, set default, edit metadata and preview, **country availability** (`template_country`) |
| Template Parts | `TemplatePartsPage`, `AddEditTemplatePart` | reusable header/footer/etc. parts per template (`template_parts`) |
| Blade Theme Queue | `BladeThemeQueuePage` | review vendor-uploaded Blade themes: approve / reject with a reason (`blade_themes`) |
| Payment Gateways | `PaymentGatewaysPage`, `AddEditPaymentGateway` | central gateways by type (`orders` / `vendor_payments`): code, mode test/live, sandbox, encrypted credentials, logo, fee % and fixed |
| Payment Gateway Limits | `PaymentGatewayLimitsPage` | sales thresholds for notifying and **blocking** a tenant's own gateway (`tenant.gateway.blocked` middleware, `TenantGatewayLimitService`) |
| Email Templates | `EmailTemplatesPage`, `AddEditEmailTemplate` | templates by action/type with translations; "sync to tenants" |
| Email Configuration | `EmailConfigurationPage` | mailer, host, port, username, password, encryption, from address/name, queue; send a test email |
| Social Login | `SocialLoginPage` | Google/Apple enabled, client id/secret (secret masked) |
| AI Settings | `AiSettingsPage` | API key, base URL, text/image/vision/search/embedding/translation models; test connection |
| AI logo limit | `AiLogoLimitPage` | per-tenant AI logo generation limit |
| Currencies | `CurrenciesPage`, `AddEditCurrency` | code, name, sign, conversion rate, default (rates also auto-updated daily) |
| Languages | `LanguagesPage`, `AddEditLanguage` | name, code, native name, direction, default, active, **free or paid (price)**, AI translation price, offers AI translation, auto-translate catalog, countries, flag image; retry translation; sort |
| Translation Key Access | `TranslationKeyAccessPage` | lock translation keys so vendors cannot override them |
| Language Purchases | `LanguagePurchasesPage` | tenant language purchases (gateway filter, details) |
| Translation Files | `TranslationsPage` | edit `lang/*` PHP/JSON resources per locale, show missing keys, create a file |
| Countries | `CountriesPage` | toggle a country as active for tenants, toggle free |
| Maintenance Mode | `MaintenanceModePage` | message, active, start/end (`maintenance_windows`) → `CheckMaintenanceWindow` |
| Return Policy | `ReturnPolicySettingsPage` | window days, non-returnable categories, fee, conditions, reasons that require a video |
| Default Banners | `DefaultBannersListPage` | platform default banners per country (URL, order, image, translations) |

### 3.15 Admins, Cache, Developer Docs
- **Admins** (`AdminsList`): create/edit (role, name, email, password, status), activate/deactivate, delete.
- **Roles & Permissions** (`RolesPermissionsList`): name plus permission checkboxes from `AdminPermissions::definitions()` (87 keys).
- **Clear Cache:** `MainCachePage` (central) / `TenantsCachePage` (all tenants), each behind a confirmation.
- **Developer Docs:** `DocsPage` (topic pages).

## 4. Shared admin behaviours [V]
- List pages extend `Livewire\Admin\Base\*` with a `search` property, filters, `WithPagination` and a `clearFilters()` action.
- Create/edit forms use modals (`showFormModal`) or full pages with **locale tabs** (`activeLocale`, `translations[locale][field]`).
- Destructive actions use a two-step `confirmDelete` / `request*` and then the action.
- Toasts and alerts come from SweetAlert2/toastr in `resources/js/app.js` [I].

## 5. Cross-module impacts
- Product, price, stock and shipping changes → tenant storefront prices (via sync jobs or live merged reads) and checkout totals.
- Coupons and flash sales → `platform_profit` (central discounts reduce the platform share).
- Plan limits → vendor panel actions (`PlanLimitService`) and the storefront (number of banners, languages).
- Payment-log refund → affiliate commission reversal.
- Template/country availability → which themes vendors can pick, per country.
- Gateway limits → can block tenant checkout (`CheckTenantGatewayBlocked`).

## 6. Admin-specific issues
| Issue | Evidence | Priority |
|---|---|---|
| Fan-out reporting for orders, customers, ledger, transactions and the dashboard | `TenantAdminAggregateService` | Critical |
| `GET /admin/products-run` dispatches a job with no permission check | `routes/central.php:326` | Medium |
| Coupon, flash-sale and default-banner `list/{countryId?}` routes are permission-less redirects (harmless) | route list | Low |
| No audit log of admin actions | no table | High |
| `CouponsIndexPage`, `FlashSalesIndexPage`, `DefaultBannersIndexPage`, `BadgeProductsList` are unreferenced | dead-code scan | Low |
