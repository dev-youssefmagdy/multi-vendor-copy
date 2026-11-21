# Tenant ↔ Central de-duplication plan

Branch: `refactor/tenant-central-dedup-plan`  ·  Status: **DRAFT v1** (2026-11-21)  ·  Owner: PM/BA

> Read together with `docs/ORDER_FORMULAS.md`, `docs/ORDER_REFACTOR_PROGRESS.md` and
> `docs/product-pricing-sync-lifecycle.md`. Any change here that touches orders, prices, coupons, flash
> sales or profit MUST go through `OrderPricing` / `OrderProfitCalculator` / `OrderPayoutLedger` /
> `OrderComposer` and update `ORDER_FORMULAS.md` in the same commit.

---

## 0. Design review before coding (2026-11-21)

Coding is paused until this section is signed off. Partial Phase -1/0 work exists uncommitted on the branch
(sync fixes, 3 tenant migrations, `app/Support/Catalog`, `app/Models/Tenant/Catalog`); it is kept and reviewed, not run.

### 0.1 The biggest decision: how tenant code reads merged data

`Tenant\Product` alone is referenced from **94 files** (storefront, panel, checkout, mobile API, central reports) and
has 33 public methods; themes (starter kit + vendor-uploaded) read its properties directly.

| | **A. Repositories + DTO + typed columns** (draft v1) | **B. Tenant SQL views + one id space** (recommended) |
|---|---|---|
| How | New `*Catalog` repositories UNION central + tenant; every reader switches to them; every FK gets `*_type`. | Tenant tables become **views with today's names** (`products`, `product_variants`, `categories`, `category_product`, `translations`, …) = central rows JOIN override tables UNION ALL tenant-owned rows. Tenant-owned rows live in `own_*` tables with ids ≥ 1,000,000,000; central rows keep their **central id**. View exposes a `source` column (`central`/`tenant`). |
| Reads (94 files, themes) | rewrite almost all | **unchanged** (same table, same columns, same model) |
| Writes | repositories | routed: own rows → `own_*` table, central rows → override tables (`Tenant\Product::save()` routes by `source`; mass updates go through a service). Only the panel save paths + sync change. |
| Ids | composite (`c:123`) everywhere: Livewire, carts, routes, JSON configs | one integer stays unambiguous; carts/favorites/JSON configs only need the one-time re-key |
| `product_type` on orders | required everywhere | still stored on `order_items` (and returns) as you asked, for reporting/clarity, but not needed for correctness |
| Effort / risk | very high, every screen touched | medium; concentrated in migrations, views, write routing, sync removal |
| Risks | — | (1) view performance → MySQL 8.0.46 supports derived-condition pushdown into UNION (≥ 8.0.29), must be proven with `EXPLAIN` on home/category/search queries; (2) views hard-code the central DB name → dumps restored on another server break, every central migration touching a viewed column must re-create views in all tenants (`catalog:views:refresh` in the deploy script); (3) views are not updatable (UNION) → any stray `Product::query()->update()` must be found and routed (guard test). |

**Recommendation was B; the product owner chose A (§0.3).** B delivers the same result (no copies, central read live, explicit source/type) with
a fraction of the code churn, and keeps vendor themes working without remediation. A's repositories are still used
behind B for the write side only.

### 0.2 Other findings that change the plan

1. **Price sort/filter needs a stored per-tenant number.** Doing the profit maths in SQL would duplicate
   `TenantPricingService` (forbidden by CLAUDE.md). So override tables keep `default_price`/`default_sell_price`
   as a **sort cache**, refreshed by one small job on central price/shipping or tenant profit changes
   (an `UPDATE` per tenant, no row copying). This is the only central→tenant write left.
2. **Central variants are hard-deleted** (`ProductService::syncVariants()` → `$variant->delete()`, no `SoftDeletes`).
   With live reads, order history, carts and overrides lose their variant. → add `SoftDeletes` to central
   `ProductVariant` (and categories, coupons, flash sales, badges, labels if they hard-delete) **before** cutover.
   Variants are updated in place by id otherwise, so override keys stay stable.
3. **Tenant isolation becomes a new risk.** Central tables hold data for *other* tenants (`product_tenant_assignments`,
   tenant-targeted coupons/flash sales, edit requests). Every view/repository must filter by the current tenant;
   QA adds cross-tenant leak tests (tenant A must never see tenant B's assigned product or coupon).
4. **Stock is already live** (`StockService::availableStock()` reads central for catalog products), so no change and
   no new risk there; confirms the approach works.
5. **Central DB becomes a hard runtime dependency for every storefront request.** It already is (tenancy resolves
   `tenants`/`domains` centrally), so availability doesn't get worse; load does. Indexes (§4.3) and a per-tenant
   cache with a central version stamp are mandatory, not optional.
6. **Old URLs and links break when ids change** (panel `/products/{id}/edit`, notification links, bookmarks).
   Keep `legacy_id` in the views for one release and redirect legacy ids.
7. **Edit requests and field overrides (§4.2a)** fit B: the `translations` view = tenant field overrides +
   tenant-only languages UNION central translations (mapped by language **code**) where no override exists.
8. **One id-space migration instead of per-section re-keys.** Re-key once per tenant (central-linked rows → central
   id, own rows → id + 1e9) across all references (§5, §5a), behind a maintenance window per tenant, then switch the
   sections to views one by one.
9. Phase order (with A): see §6. Central soft deletes are added in phase 0; the field-override approve/reset
   change (§4.2a) ships with phase 2.

### 0.3 Decisions (signed off 2026-11-21)

| # | Decision |
|---|---|
| Q1 | **Architecture A: repositories + DTO + typed `*_type` columns.** Option B (views) rejected. Consequences: all readers of the synced tenant models (≈94 files for `Tenant\Product` alone) move to the `*Catalog` repositories phase by phase; composite `CatalogRef` keys (`c:123` / `t:45`) in Livewire, carts, routes and JSON configs; the theme contract is kept by a `CatalogProduct` DTO exposing today's property/method names, and the theme validation rule applies to any theme that uses raw ids. §0.2-8 (single id-space re-key) does not apply; references are re-keyed per section via typed columns (§5, §5a). |
| Q2 | Keep the per-tenant **sort-price cache** in the override tables, refreshed by one light job (§0.2-1). |
| Q3 | Add **soft deletes** to central `product_variants` and any other central catalog table that hard-deletes, before any cutover (§0.2-2). New phase 0 item. |
| Q4 | Finish, review and ship the **Phase -1 hotfixes** first, as their own change. |

Still open: D6 (coupon code collision), D8 (fields vendors may request).

## 1. Goal

Stop copying central data into every tenant DB. A tenant keeps **only**:

1. rows the tenant owns (own products, own coupons, own flash sales, own badges/labels…), and
2. a **slim override row** for central items where the vendor really changes something (profit, active,
   featured, sort order, custom translations, country list…).

Everything else is read **live from central** and merged with the tenant rows at query time.

Expected wins: no sync lag / drift (root cause of the bugs in `product-pricing-sync-lifecycle.md` §4),
much smaller tenant DBs, faster onboarding (no initial catalog copy), one source of truth.

## 2. Key facts this plan relies on

| Fact | Consequence |
|---|---|
| Central DB `dokan_v2` and tenant DBs `dokan_v2_<id>` live on the **same MySQL server** and the tenant connection uses the **same user** (`root`, `MySQLDatabaseManager`, not the permission-controlled one). | A tenant query can `JOIN dokan_v2.products` directly → real SQL merge (UNION / LEFT JOIN overrides) with pagination, sorting and filtering in the DB. |
| Constraint: this only works while every tenant DB sits on the central server and the DB user can read both. | Write it down as an architectural invariant (§9). Moving tenants to another server or switching to per-tenant DB users would require a fallback (API/cache read). |
| `TenantCatalogSyncService` syncs 13 sections: languages, currencies, settings, payment-gateways, themes, email-templates, pages, categories, products (+variants, `category_product`), badges, labels, flash-sales, coupons. Plus observers/jobs: `CentralCatalogSyncObserver`, `SyncCentralProductToTenantJob`, `SyncCentralProductPriceToTenantsJob`, `SyncFixedShippingCostsToTenantsJob`, `ApplyTenantProfitPercentageJob`. | Each section is cut over independently behind a flag, then its sync code is deleted. |
| Tenant rows already carry links: `central_product_id`, `central_product_variant_id`, `central_category_id`, `central_flash_sale_id`, `central_coupon_id`, `central_product_label_id`, `central_language_id`, `central_currency_id`, `central_theme_id`, `central_payment_gateway_id`, `central_email_template_id`. | Data migration is mechanical: tenant id → central id via these columns. |
| The tenant-owned truth for a central product is the `profit` JSON (+ active/featured/order/badges/categories/country list/translations). `price`, `sell_price`, `default_*` are **derived caches** used only for SQL filter/sort. Storefront already recomputes price from central cost + `profit` + central shipping. | Prices move to read-time computation via `TenantPricingService`; only `profit` is stored per tenant. |
| A tenant only "has" the central products it adopted (initial catalog, "New in products" button, `ProductTenantAssignment`). | The **override row doubles as the adoption list**: a central product is in the tenant's catalog iff an override row exists. |
| `order_items.discount_source` and `orders.coupon_source` (central/vendor) already exist. | Adding `product_type` / `variant_type` (central/tenant) follows the existing pattern. |

## 2a. Bugs the current sync causes today (fix first: Phase -1)

| # | Bug | Evidence | Fix |
|---|---|---|---|
| B1 | **Order history loss.** `order_items.product_variant_id` is `cascadeOnDelete()`; `deleteStaleCatalogRows()` deletes tenant variants that disappeared from central, and their order lines go with them. | `create_tenant_panel_tables.php:127` | Tenant migration: drop the FK and re-add it as `nullOnDelete` (or no FK). Lines keep their money snapshot. |
| B2 | Tenant toggles are overwritten on every sync: `languages.is_active`, `currencies.is_active/is_default` (in the upsert update list), `categories.active/featured`, email template `subject/body`, and the `category_product` set (detach to central's set). | `TenantCatalogSyncService` | Remove these columns from the update lists (insert-only), stop detaching tenant category links. |
| B3 | Tenant-owned rows are deleted: tenant-created pages (`whereNotIn slug`) and tenant badges, including the seeded featured/recommended/new-in/best-selling/trending badges (`whereNotIn text`). | `TenantCatalogSyncService` | Only delete rows that were created by sync (have a central link / `source = central`). |
| B4 | Badges have no `central_*_id`; they are matched by `text`, so renaming a central badge duplicates it. | `product_badges` schema | Add `central_product_badge_id`, backfill by text once. |

These are independent of the de-duplication and can ship on their own.

## 2b. Every catalog-sync entry point (must be cut per section, nothing may keep writing copies)

`TenantCatalogSyncService` is reached from many places. A section is only "de-duplicated" when **every** row
below skips it for tenants whose `catalog_source.<section> = central` (one guard inside the service:
`runSection()` / `syncProductToTenant()` / `syncNewProductsForTenant()` return 0 for such sections, plus the
callers below stop dispatching), and is deleted in phase 7.

| Entry point | Trigger | Sections | Fate |
|---|---|---|---|
| `ProvisionTenantJob` (+ `SyncTenantSectionJob` chain, `MarkTenantSyncReadyJob`) | registration / onboarding | core sections atomically, then catalog sections in order; sets `tenants.data.sync_status` (`ready` unlocks Launch in `StoreOnboardingWizard`, `failed` shows catalog error) | new tenants skip central-mode sections; adoption = insert override rows for the initial catalog (fast); `sync_status` becomes `ready` once core + adoption are done; removed in phase 7 for catalog sections |
| `TenantService::save()` → `SyncTenantSectionsJob` | admin creates/edits a tenant (new: all sections; edit: all but coupons/flash-sales, e.g. `category_ids` change) | many | edit → only "adopt products for new categories" (override inserts) + cache bump |
| `Admin\Store\TenantSyncPage` → `SyncTenantSectionsJob` → `SyncTenantSectionJob` | admin manual "sync sections" page | chosen | hide/disable central-mode sections per tenant; page becomes the **sync-check** page (below) |
| `tenants:sync-data {--tenant} {--section=*}` | CLI / ops | chosen | skip central-mode sections with a notice; removed in phase 7 |
| `ResyncTenantChangeRequestJob` | approved `TenantChangeRequest` (store settings change) | given | check which sections; skip central-mode ones |
| `LanguagePurchaseService::completePurchase()` | tenant buys a language | `languages` | stays until phase 6 (languages = KEEP slim) |
| `CentralCatalogTenantSyncService` (fan-out to all/matching tenants via `TenantCategoryMatchService`) | `CentralCatalogSyncObserver` on central `Category`, `Product`, `ProductVariant`, `Variation`; `CentralCouponObserver`; `CentralFlashSaleObserver`; `CentralSharedTenantSyncObserver` on `Currency`, `EmailTemplate`, `Language`, `PaymentGateway`, `StaticPage`, `Template`; `CentralTemplatePartObserver`; `ProductFileObserver` | per model | replaced by a **global cache-version bump** (+ the sort-price refresh job for price/shipping changes, §0.3-Q2); per-tenant fan-out removed section by section |
| `SyncCentralProductToTenantJob`, `SyncCentralProductPriceToTenantsJob`, `SyncFixedShippingCostsToTenantsJob` | central product / price / weight change | products | price + shipping jobs shrink to the sort-price cache refresh; product job removed |
| `ApplyTenantProfitPercentageJob` → `ProductPriceCalculationService` | tenant changes global profit % | products | writes override `profit` + sort cache only |
| `ProductsListController` → `syncNewProductsForTenant()` / dashboard "New in products" (`TenantPanelRepository` ~L3029); admin "assign to tenant" (`syncProductToAssignedTenants`) | vendor adopts new central products / admin assigns | products | becomes "insert override rows" (adoption), no copy |
| `EmailTemplateController::resync` / `AddEditEmailTemplate::resyncFromCentral` | vendor "re-sync from central" | email template (one) | becomes "delete override row" in phase 6 |
| Scheduled `products:translate-new-schedule` (weekly, `TranslateNewProductsCommand`) | AI-translates new central products into tenant AI languages | writes **tenant `translations`** | must write to `tenant_field_overrides` / tenant-only-language rows keyed by **central id** from phase 5 on (§4.2, §5a) |
| Scheduled `currencies:update-rates` (daily), `products:archive-out-of-stock` (daily) | central writes → observers → fan-out | currencies / products | fan-out replaced by cache bump; no tenant writes |
| `products:backfill-country-ids` | one-off backfill | products | obsolete after phase 5 |

### 2b.1 Sync check (health / parity), needed before and during the migration

New read-only command `catalog:sync-check {--tenant=*} {--section=*} {--json}` (and the same report on
`Admin\Store\TenantSyncPage`), per tenant and section:

* **Before cutover (now):** drift between central and the tenant copy: missing / extra / stale rows (central-linked
  rows whose central row is gone or soft-deleted), wrong `central_visible`, variants without central link (the
  orphan corruption of `product-pricing-sync-lifecycle.md` §4), translations equal / different / tenant-only,
  `has_custom_translations` without an approved edit request, duplicate badges by text, `sync_status` ≠ ready,
  last sync time and failures. Gives the baseline for §5a dry-runs and shows how much legacy data each tenant has.
* **During migration:** for each section: mode (`sync` / `central`), migrate / verify / cleanup status from
  `catalog_dedup_runs`, shadow-read diff count (must be 0 to flip), and "still written by sync?" (must be no
  for `central` sections: detects any entry point above that was missed).
* **After cutover:** override rows pointing at missing/soft-deleted central rows, typed references
  (`order_items`, favorites, carts…) that resolve to nothing, sort-price cache older than the central price.
* Exit code ≠ 0 when a gate fails, so it can run in CI/deploy and as a daily scheduled report.

Lands in **phase 0** (drift part) and grows with each phase.

## 3. Core design

### 3.1 Source discriminator

Every merged entity is identified by **(`source`, `id`)** where `source ∈ {central, tenant}`.

* DB columns: `<entity>_type` `ENUM('central','tenant')` next to every FK that can point to either side
  (`order_items.product_type`, `order_items.variant_type`, `favorites.product_type`, pivots…).
* UI / route / cart key: string `"c:123"` / `"t:45"` (helper `CatalogRef::make/parse`). Slugs stay the
  public URL; on slug collision the tenant row wins (vendor's own data) and the central one gets `-c` suffix
  at resolve time (decision D3).

### 3.2 Reading central from the tenant connection

* `App\Support\Catalog\CentralTable::name('products')` → `` `dokan_v2`.`products` `` (DB name from the
  central connection config, never hard-coded).
* Read-only Eloquent models `App\Models\Tenant\Catalog\Central*` (e.g. `CentralCatalogProduct`) that use the
  **tenant** connection with `$table = 'dokan_v2.products'`, so they can be joined/eager-loaded with
  tenant tables in one query. Saving/deleting them throws (`ReadOnlyCentralModel` trait).
* A guard test asserts no tenant code writes to `dokan_v2.*`.

### 3.3 Merged read layer (one per entity)

`App\Repositories\Tenant\Catalog\{Product,Category,Badge,Label,FlashSale,Coupon}Catalog`:

```
merged(product) = central products JOIN tenant overrides (adopted, not hidden by central status)
                  UNION ALL
                  tenant own products
```

* Returns a uniform DTO / model (`CatalogProduct`) exposing **the same properties** today's
  `Tenant\Product` exposes (`name`, `slug`, `price`, `files`, `variants`, `storefrontPricing()`,
  `priceBreakdownForCountry()`…) so Blade themes keep working (theme contract, §7).
* Override values win over central values (`COALESCE(o.active, …)`), tenant translations win over central
  translations.
* Central status draft/archived/soft-deleted = hidden (replaces the synced `central_visible` column).
* Per-request + short Redis cache for hot lists (home sections, menus), keyed by tenant + locale + country.

### 3.4 Writes

* Vendor edits on a central item write **only** the override row (never central).
* Vendor edits on own items write the tenant table as today.
* Central admin edits write central only — **no fan-out jobs** (tenants see the change on next read; caches
  are invalidated by a central-side version stamp `catalog_version` instead of per-tenant jobs).

## 4. Per-entity verdict (confirmed by the DB designer review, 2026-11-21)

Legend: **OVERRIDE** = slim tenant table keyed by the central id (NULL column = inherit central) ·
**DROP** = central copies removed, read central directly · **KEEP** = tenant data.
In every case the tenant table itself stays for **tenant-created** rows (own products, own coupons…).

| Tenant table | Central source / link | What the tenant really owns | Verdict | Tenant references to it |
|---|---|---|---|---|
| `products` | `products` / `central_product_id` | `active`, `featured`, `order_number`, `needs_ai_translation`, `profit` JSON (per-country), `weight_grams`, `return_*`, `social_posts`, `ai_price_data`, `has_custom_translations`, `orders_count`. Derived every sync (not owned): `price`, `default_price`, `fixed_shipping_costs`, `central_visible`, `is_tenant_owned`, `slug`, `allowed_country_ids` | **OVERRIDE** `tenant_product_overrides`; KEEP `products` for own products | `order_items`, `favorites`, `product_rates`, `category_product`, `flash_sales.product_id`, `flash_sale_product`, `product_badge_product`, `product_label_product`, `mobile_carts.items` JSON, session cart, **central** `return_requests.product_id` (stores tenant id) |
| `product_variants` | `product_variants` / `central_product_variant_id` | `margin_percentage`, `active`, `profit` JSON, `weight_grams`, `thumbnail_path`. Derived: `real_price`, `stock`, `option_ids`, `sell_price`, `default_sell_price` | **OVERRIDE** `tenant_product_variant_overrides`; KEEP for own | `order_items.product_variant_id` (B1), carts |
| `category_product` | central `category_product` | `sort_order` only (the set is central) | **DROP** central pairs; slim `tenant_category_product_sort(category_type, category_id, product_type, product_id, sort_order)`; local rows for own products | — |
| `categories` | `categories` / `central_category_id` | `order_number`, `active`, `featured`, thumb file, panel translations; `parent_id` is a remapped central parent | **OVERRIDE** `tenant_category_overrides`; KEEP tenant-created | `category_product`, `categories.parent_id`, page-builder/banner JSON configs |
| `translations` (product/variant/category/page/setting morphs) | central `translations` (central `language_id`) | product fields when `has_custom_translations`; languages central lacks (AI-translated tenant-only) | **OVERRIDE**: keep only custom fields + tenant-only languages; fallback to central by language **code** | `translations.language_id` |
| `files` | — (product/variant images already read through `centralProduct`) | category thumbs, own-product images | **KEEP** (already tenant-only) | — |
| `languages` | `languages` / `central_language_id` (free + purchased) | `is_default`, `is_active`, `translation_status`, token counters, `image_file_id`, `sort_order` | **KEEP slim** (many local CASCADE FKs: `translations`, `translation_overrides`) | `customers.language` (code) |
| `currencies` | `currencies` / `central_currency_id` | `is_active`, `is_default` | **DROP** + override `(central_currency_id, is_active, is_default)` | none (code-based) |
| `settings` | `app_settings` (key → name) | `value` (already insert-only = override pattern) | **KEEP as override**: delete seeded rows equal to central + synthetic title translations | — |
| `payment_gateways` | `payment_gateways` / `central_payment_gateway_id` | `required_values` (encrypted credentials), `mode`, `use_own`, connection, monitoring | **OVERRIDE** (credentials are tenant secrets) | `orders.payment_gateway_id` → needs `_type` or stays a local id |
| `themes` + `theme_country` | `templates` / `central_theme_id`, `template_country` | `themes.is_active`, `theme_country.is_enabled` | **OVERRIDE** `tenant_theme_state`, `tenant_theme_country` — invasive (PK change) | `tenant_page_sections`, `tenant_home_variants`, `tenant_theme_colors` (CASCADE on `theme_id`) |
| `email_templates` (+ translations) | `email_templates` (Tenant type) / `central_email_template_id` | subject/body edits (today clobbered, B2) | **OVERRIDE** `(central_email_template_id, locale, subject, body, is_active)` | — |
| `pages` | `static_pages` (by slug) | `active`; tenant-created pages | **DROP** central copies; KEEP tenant pages with `source` | — (slug) |
| `coupons` + `coupon_country` | `central_coupons` / `central_coupon_id` | nothing for central coupons (country set = intersection with tenant countries, computable); tenant own coupons | **DROP** central copies; KEEP tenant | orders store code + `coupon_source`; `OrderComposer` uses `central_coupon_id !== null` → switch to `source`. Codes may collide (both unique) → D6 |
| `flash_sales` + `flash_sale_product` / `flash_sale_country` | `central_flash_sales` / `central_flash_sale_id` | nothing for central sales; tenant own sales | **DROP** central copies; KEEP tenant | `discount_source` decided by `source` |
| `product_badges` + `product_badge_product` | `product_badges` (by text, B4) / pivot with `country_id` | per-country pivot `sort_order` (`SortBadgeProducts`), tenant badges, manual attaches | **OVERRIDE** sort table `(badge_type, badge_id, product_type, product_id, country_id, sort_order)`; KEEP tenant badges | — |
| `product_labels` + pivot | `product_labels` / `central_product_label_id` | nothing | **DROP** | — |

### 4.1 Override table schema

```sql
tenant_product_overrides (
  central_product_id BIGINT UNSIGNED PRIMARY KEY,
  carried TINYINT NOT NULL DEFAULT 1,           -- adoption flag (replaces "tenant row exists")
  active TINYINT NULL, featured TINYINT NULL, order_number INT NULL,
  weight_grams INT NULL, profit JSON NULL,      -- per-country {profit_type, profit_value, total_profit}
  price JSON NULL, default_price DECIMAL(12,2) NULL,   -- materialised cache for SQL sort/filter
  has_custom_translations TINYINT NOT NULL DEFAULT 0, needs_ai_translation TINYINT NOT NULL DEFAULT 0,
  ai_translated_at TIMESTAMP NULL, return_* ..., social_posts JSON NULL, ai_price_data JSON NULL,
  orders_count INT NOT NULL DEFAULT 0, timestamps,
  INDEX (carried, active, featured, order_number), INDEX (default_price)
);
tenant_product_variant_overrides (
  central_product_variant_id BIGINT UNSIGNED PRIMARY KEY, central_product_id BIGINT UNSIGNED,
  active TINYINT NULL, margin_percentage DECIMAL NULL, weight_grams INT NULL, profit JSON NULL,
  thumbnail_path VARCHAR NULL, sell_price JSON NULL, default_sell_price DECIMAL(12,2) NULL, timestamps,
  INDEX (central_product_id, active, default_sell_price)
);
tenant_category_overrides (central_category_id PK, active NULL, featured NULL, order_number NULL, thumb_file_id NULL);
```

NULL = inherit central. Prices stay a formula: `TenantPricingService::forCatalog(central base, central shipping,
override.profit, tenant.profit_percentage)`; the materialised `price`/`default_*` columns are refreshed by events
(central price/shipping change, profit change), never by a full copy.

### 4.2 Translations

`HasTranslations` resolution order: (1) tenant `translations` row (`translatable_type = 'central_product'` keyed by
central id) only when `has_custom_translations` or the language is tenant-only; (2) central `translations`, joined
by `languages.code` (ids differ between DBs). Variant titles and option names come straight from central.

### 4.2a Vendor edit requests → field-level overrides (only the approved changes)

**Today:** the vendor edits a central product's name/description in the tenant panel
(`ProductController::save` / `Livewire\Tenant\Product\AddEditProduct` → `TenantPanelService::submitProductEditRequest`).
The diff is stored centrally in `product_edit_requests` (`requested_translations` = changed fields, `current_translations`
= previous values, `product_id` = **tenant** product id). The admin approves in `Admin\Product\ProductEditRequestsList::approve()`,
which writes the product's **full** translation set into the tenant and sets `has_custom_translations = true`.
From then on the sync skips **all 6 translated fields in all locales** (`TenantCatalogSyncService` ~L1177 / L1705),
so one approved Arabic name freezes every later central update of description, meta, other locales… forever.
`resetToCentral()` restores `current_translations` (the values at request time, not central's current ones).

**Target rule:** a tenant keeps **only the fields that were changed and approved**, starting from the approval moment.
Every other field of the same product (other fields, other locales) keeps following central live.

```sql
tenant_field_overrides (
  id BIGINT UNSIGNED PK,
  entity_type VARCHAR(40) NOT NULL,        -- central_product | central_variant | central_category | …
  entity_id BIGINT UNSIGNED NOT NULL,      -- central id
  field VARCHAR(64) NOT NULL,              -- name | description | meta_description | … (whitelisted per entity)
  locale VARCHAR(10) NULL,                 -- NULL for non-translatable fields
  value LONGTEXT NULL,
  central_value_at_approval LONGTEXT NULL, -- to detect "central changed since" (drift badge for admin/vendor)
  edit_request_id BIGINT UNSIGNED NULL,    -- central product_edit_requests.id
  approved_at TIMESTAMP NOT NULL, approved_by BIGINT UNSIGNED NULL, timestamps,
  UNIQUE (entity_type, entity_id, field, locale)
);
```

* **Read:** `value = override ?? central current value` per (field, locale). `has_custom_translations` becomes
  derived (exists override) and is dropped in phase 7.
* **Approve:** upsert one row per changed (field, locale) from the request diff (`changedFields()` already computes it);
  nothing else is copied. A later approved request only touches its own fields.
* **Reset to central:** delete the override rows (per request, per field, or all) → the live central value shows
  again, including central edits made after the request.
* **Central edits a field that is overridden:** override still wins; `central_value_at_approval ≠ current` shows a
  "central updated" hint so admin/vendor can reset.
* **Extensible to "anything":** the request payload becomes `requested_changes = {field: {locale|_: value}}` with a
  per-entity whitelist (`EditableCentralFields`), so non-translation fields (images, short description, meta, variant
  title…) use the same table without schema changes. Price/profit/active/featured/sort are **not** requests – the
  vendor sets them directly in the override tables (§4.1).
* **Schema:** `product_edit_requests` gets `central_product_id` (+ `product_type`); backfill from the tenant link.
* **Data migration:** for each tenant product with `has_custom_translations = true`, create override rows from its
  **approved** edit requests (latest per field/locale wins); where none exist, diff tenant vs central translations
  and keep only the differing (field, locale) pairs. Pending requests survive the cutover (they are re-keyed to the
  central id).
* Lands in **phase 5** (products), but the approve/reset code switches to field rows as soon as the table exists so
  the "freeze all" behaviour stops early (can ship with phase 2).

### 4.3 Indexes (central)

`translations(translatable_type, translatable_id, language_id, field)`, `product_variants(product_id, status, price)`,
`products(status, deleted_at, id)`, `product_tenant_assignments(tenant_id, product_id)`,
`central_flash_sale_product(central_flash_sale_id, product_id)`, `product_badge_product(country_id, product_badge_id, sort_order)`.
Tenant side: override PKs are central ids → `eq_ref` joins. Charset/collation must be identical in every DB, otherwise
cross-schema joins can't use indexes. Verify every merged query with `EXPLAIN`.

## 5. Orders and other references

| Table | New columns | Backfill |
|---|---|---|
| `order_items` | `product_type`, `variant_type` (enum, NOT NULL default `tenant`), `legacy_product_id`, `legacy_variant_id` | rows whose tenant product has `central_product_id` → `product_type='central'`, `product_id = central_product_id` (same for variants); own → `tenant`. Snapshot money columns untouched (formulas unchanged). |
| `favorites`, `product_rates`, `mobile_carts`, session cart keys | `product_type` (+ `variant_type` for carts) | same mapping; session carts: accept old int ids for one release, translate via legacy map. |
| pivots (`category_product`, badge/label/flash-sale pivots) | `product_type` (+ `<x>_type`) | same mapping. |
| **central** `return_requests` | `product_type` | today stores the tenant product id → map per tenant. |
| `orders.payment_gateway_id` | `payment_gateway_type` (only if gateways become override-only) | decided in phase 6. |

Cross-DB foreign keys are impossible, so referential integrity becomes application-level: central products must
only ever be **soft-deleted** while orders reference them, and `order_items` must snapshot `name`/`sku`/price
(central history is not versioned). Composite unique keys become `(…, x_type, x_id)`.

`OrderComposer` resolves lines through `ProductCatalog::find(CatalogRef)`; `isOwnProduct()` becomes
`ref.source === tenant`. **No formula changes** — only where the inputs are read from. If any rule text in
`ORDER_FORMULAS.md` names a column (e.g. `is_own_product`, `central_product_id`), update it in the same commit.

## 5a. Existing data in ALL tenant DBs (legacy rows already written by the sync)

Every tenant DB already holds copies, derived values and tenant ids pointing at copies. Switching reads is not
enough: each section's legacy data must be **classified, converted, verified, then removed**, in every tenant DB
(active, suspended and pending ones), per section, idempotently.

### 5a.1 Inventory of legacy data per tenant DB

| Legacy data (tenant DB unless noted) | Classify | Action |
|---|---|---|
| `translations` rows, `translatable_type` = `App\Models\Tenant\{Product,ProductVariant,Category,Coupon,Page,Setting}` of **central-linked** rows (no morph map → full class names; `language_id` = tenant language id) | (a) equal to central (same field, same language **code**) → copy · (b) differs and backed by an approved edit request → custom · (c) differs, no request (old manual/AI edits) → custom · (d) language central lacks (tenant-only, AI-translated) → tenant-owned | (a) delete in phase 7 · (b)/(c) → `tenant_field_overrides` (§4.2a), only the differing (field, locale) pairs · (d) keep, re-key to central id + `entity_type`. Rows of **own** products/coupons/pages and `Banner` stay untouched. |
| `products` / `product_variants` central-linked rows | owned columns vs derived (§4) | owned columns → `tenant_product_overrides` / `tenant_product_variant_overrides` (one row per adopted product = adoption), derived dropped. `has_custom_translations` → field rows (above). |
| `categories`, `currencies`, `languages`, `payment_gateways`, `themes`, `theme_country`, `email_templates` (+ `email_template_translations`), `settings`, `pages` | owned toggles/values vs copy | owned → override tables; rows equal to central → delete in phase 7. Settings rows whose `value` = central default → delete. |
| central-linked `coupons`, `flash_sales`, `product_labels`, central `product_badges` (+ `coupon_country`, `flash_sale_country`, `flash_sale_product`) | pure copies | delete in phase 7 (nothing tenant-owned except sort order/country already handled in §4). |
| pivots `category_product`, `product_badge_product`, `product_label_product`, `flash_sale_product` | link to copies | rewrite to typed rows (`*_type`, central ids); keep only tenant-chosen sort/links. |
| `files` rows of central-linked entities (if any) | copy vs tenant upload | tenant uploads (category thumbs, variant `thumbnail_path`) → override columns; copies → delete. |
| `order_items`, `favorites`, `product_rates`, `mobile_carts.items` JSON, session carts | references to tenant copy ids | §5 backfill → `*_type = central` + central id, keep `legacy_*_id`. |
| **JSON configs** that embed ids: `tenant_page_sections` / home variants / banners (product, category, badge ids in section settings), menus, links in `TenantNotification` data | references to tenant copy ids | rewrite to `CatalogRef` strings (`c:123` / `t:45`); unknown ids logged, not dropped. |
| **Central** tables holding tenant copy ids: `return_requests.product_id`, `product_edit_requests.product_id`, admin notifications / `NotificationLinks` URLs, analytics/reports | references | add `product_type` / `central_product_id`, backfill per tenant via the tenant's `central_product_id` map. |
| Caches (`CacheVersion` keys, Redis lists, sitemaps, search/embedding indexes keyed by tenant ids) | derived | bump version / rebuild after each section cutover. |

### 5a.2 Tooling

* One artisan command per section: `catalog:dedup-migrate {section} {--tenant=*} {--dry-run} {--verify} {--cleanup}`,
  run tenant by tenant (queued, one job per tenant), chunked, resumable.
* **Dry-run** writes a per-tenant report (rows per class: copy / custom / tenant-owned / orphan / unmapped) to
  `storage/app/dedup-reports/<section>/<tenant>.json` and changes nothing.
* **Migrate** step only **adds** data (override rows, typed columns, re-keyed translations) – the old rows stay, so
  the flag can still flip back to `sync`.
* **Verify** = shadow-read diff (old sync tables vs new merged read) for every tenant: ids, names per locale, price per
  country, badges, flash-sale discount, stock. Must be 0 diffs before the tenant's flag flips to `central`.
* **Cleanup** (phase 7 only) deletes copies after a `mysqldump` of the affected tables per tenant
  (`storage/backups/dedup/<tenant>/<section>.sql.gz`), never in the same run as migrate.
* Progress table `catalog_dedup_runs` (central): tenant, section, step, counts, status, error, timestamps → admin page
  shows which tenants are migrated / verified / cleaned.
* Tenants created after a section is live start on `central` directly (no legacy data) – onboarding stops copying.
* Orphans (tenant rows whose `central_*_id` no longer exists centrally, or translations for missing languages) are
  reported, never silently deleted; order-referenced orphans keep their order snapshot.

## 6. Phases (each = own PR, own feature flag, own go/no-go)

Flag: `tenants.catalog_source` JSON per section (`{"badges":"central", …}`), default `sync`. Reads go through
the new layer when the section is `central`; sync of that section is skipped for that tenant.

| # | Phase | Owner | Deliverables |
|---|---|---|---|
| -1 | **Hotfixes** (§2a) | Backend + DB | B1 FK → `nullOnDelete`; B2/B3 stop clobbering tenant toggles and deleting tenant pages/badges; B4 badge central id. Ships alone. |
| 0 | **Foundations** | Backend + DB | central soft deletes for variants and other hard-deleted catalog tables (§0.3-Q3); `CentralTable`, read-only central model base, `CatalogRef`, `catalog_source` flag + helper, guard test (no writes to central from tenant), architecture invariant doc, `catalog:sync-check` drift report (§2b.1), central guard in `TenantCatalogSyncService` that skips `central`-mode sections (§2b). No behaviour change. |
| 1 | **Typed references** | DB + Backend | add `*_type` (+ `legacy_*`) columns to order_items, favorites, product_rates, mobile_carts, pivots; default `tenant`; backfill command (idempotent, per tenant, dry-run). Readers still use tenant rows. |
| 2 | **Badges + labels** | Backend + Frontend | merged repository, tenant panel list (central read-only + own editable), storefront badge rendering, drop badge/label sync for flagged tenants. |
| 3 | **Flash sales + coupons** | Backend + QA | merged repos, `OrderComposer`/`OrderPricing` read via repo, `discount_source`/`coupon_source` unchanged; usage counters. |
| 4 | **Categories** | Backend + Frontend | merged categories + overrides, typed `category_product`. |
| 5 | **Products + variants** (largest) | All | override tables, `ProductCatalog` UNION read, read-time pricing (`TenantPricingService`), `sort_price` cache, adoption = override insert, tenant panel product list/edit (central fields read-only), storefront + mobile API through `CatalogProduct`, files/translations from central, delete product sync + price/shipping/stock fan-out jobs. |
| 6 | **Config entities** (languages, currencies, payment gateways, themes, email templates, pages, settings defs) | Backend + Frontend | per-entity override tables where tenant customises; delete remaining sync sections. |
| 7 | **Cleanup** | DB + QA | after N days on `central` for all tenants: delete central-linked tenant rows, drop `central_*` link columns & `central_visible`, delete `TenantCatalogSyncService` sections + jobs + commands, drop flag. |

Every phase 2–6 runs the §5a sequence for its section on **all** tenants: dry-run → migrate → verify → flip flag →
(phase 7) cleanup.

Rollback per phase: flip the tenant's section back to `sync` and run that section's sync (tenant rows are not
deleted until phase 7, so rollback is instant).

## 7. Frontend / theme contract

* Themes (starter kit + vendor-uploaded) receive `CatalogProduct` objects exposing today's property/method
  names → no theme change required. If any theme uses a raw `->id` for cart/favorite/links, switch to
  `->ref` and run the theme validation rule (validate starter kit + vendor themes, disable incompatible ones,
  force vendor-admin remediation).
* Livewire: every `wire:click="add({{ $product->id }})"`, cart key, favorite toggle and quick-add uses
  `CatalogRef` strings.
* Tenant panel: central items show central fields read-only (name/description/images/cost/stock) with an
  "Override" area (profit/price, active, featured, sort, categories, badges, countries, custom translation);
  own items keep full edit.

## 8. QA / QC gates (summary — detailed test plan to be filled by QA, §11)

* Per phase: feature tests for the merged repo (central only, tenant only, both, override wins, central
  draft hides, slug collision), sync-skip test, order totals invariant
  `platform_profit + vendor_profit = order_grand_total` unchanged for central and own lines.
* Backfill: dry-run counts per tenant, re-runnable, verified by comparing old vs new reads for every tenant
  (shadow-read diff command: render list via sync tables and via central layer, diff ids/prices).
* Manual QC: tenant panel product/flash-sale/coupon/badge pages, storefront (each theme), cart → checkout
  → order (central + own + mixed cart), mobile API, central admin order/return analytics.
* Go/no-go: shadow-read diff = 0 on staging copy of all tenants; full test suite green; p95 page time not
  worse than baseline on home/category/product pages.

## 9. Architecture invariants (new)

1. Tenant code never writes to `dokan_v2.*`.
2. All tenant DBs share the central MySQL server and a user with SELECT on `dokan_v2`. If this ever changes,
   the merged repositories must switch to a cached/API read before tenants move.
3. Every FK that can point to central or tenant carries a `*_type` column.
4. Money snapshots on `order_items` / `orders` are never recomputed from live central data.
5. The central DB name is resolved from `config('tenancy.database.central_connection')`, never hard-coded
   (`dokan_v2_testing*` in tests).
6. Central catalog rows referenced by tenants are soft-deleted only.
7. All central reads go through one repository per entity that can degrade to an app-side merge (two queries +
   merge by id) if invariant 2 ever breaks.
8. Cache versioning (`CacheVersion`) bumps on **central** writes for every tenant (global version key / tag).

## 10. Decisions / open questions

| # | Question | Proposed |
|---|---|---|
| D1 | Cross-DB JOIN vs separate queries + PHP merge | **JOIN/UNION in SQL** (pagination/sort/filter stay in DB). |
| D2 | Keep a derived `sort_price` per tenant? | **Yes**, in override tables, recomputed on profit change and by a cheap central-price listener (single UPDATE per tenant DB, no row copy). |
| D3 | Slug collision central vs own | own wins; central resolved with suffix. |
| D4 | Central coupon/flash-sale usage limits | per tenant counters in tenant DB. |
| D5 | Phase 6 config entities | decide per entity after phase 5; may stay as-is if override ≈ full row. |
| D6 | Central vs tenant coupon code collision | central wins at checkout? or block tenant from creating a code that exists centrally → **block on create + tenant wins for legacy collisions**. Needs product-owner sign-off. |
| D8 | Which fields may vendors request on central items? | Today: name + description (translations). Target: whitelist per entity in `EditableCentralFields`; product owner to confirm the list. Only approved (field, locale) pairs are stored (§4.2a). |
| D7 | Typed columns vs offsetting tenant ids (`AUTO_INCREMENT = 1e9`, one BIGINT stays unambiguous) | **Typed columns** (explicit, queryable); offset rejected as it hides the source. |

## 11. Team inputs (appended as each teammate reports, sequentially)

1. Database Designer — **done** (2026-11-21): per-table inventory §4, override schema §4.1, translations §4.2,
   indexes §4.3, hotfix bugs §2a (B1 verified in migration source), invariants 5–8.
2. Senior Backend Developer — **Phase -1 done** (2026-11-21, uncommitted): B1–B4 + R1–R3 implemented and tested (see §12).
   Phase 0 paused by PM after a first draft (`app/Support/Catalog/*`, `app/Models/Tenant/Catalog/*`: CentralTable,
   CatalogRef, CatalogSource, read-only central model/builder, CentralWriteGuard; not wired, no tests yet).
   Flag storage decision: `catalog_source` key in central `tenants.data` (stancl VirtualColumn), no migration.
3. Senior Frontend Developer — **Phase -1 UI review done** (2026-11-21, uncommitted): see §12 "Frontend review — Phase -1".
4. Senior QA + QC — **Phase -1 done** (2026-11-21): **GO with conditions** (see §12 "QA — Phase -1").
   * Tests: `CatalogSyncTenantOwnershipTest` 8/8, `CatalogSyncPanelUiTest` 3/3, new `CatalogSyncMigrationsTest` 4/4
     (each Phase -1 migration run twice + down/up for 2–5; B1 with orphan lines; backfills for pages/badges/categories/
     category_product; R1 hidden-parent product + category saves through the real services). Regression filters: 20 failures
     in 39 feature tests + 20/455 unit failures, all in code Phase -1 does not touch (RefundCalculator, StorefrontProductPricing,
     variations.slug, admin roles/blog, OrderCancellation/StockRestore totals, PanelMutationsContract data provider,
     ValidateRoutes querying `products.central_visible` on central) → pre-existing, tracked separately, not a Phase -1 blocker.
   * Migration QC: idempotent (guarded by `hasColumn`/FK inspection); central unreachable → column added, backfill skipped with a
     warning and **not retried** on the next `tenants:migrate` (pages/badges/category links self-heal on next sync; categories:
     legacy `active = 0` on centrally unpublished rows stays 0 → re-run the backfill by hand if the log shows the warning).
     Lock time: `ADD COLUMN` is INSTANT on MySQL 8.0.46; `order_items` FK re-add (FOREIGN_KEY_CHECKS=1) is a COPY rebuild
     of `order_items` (writes blocked for the rebuild) → run off-peak. `down()`: 2–5 drop the columns (data loss of
     `source`/`central_visible` only, re-derivable); 1 is a deliberate no-op.
   * Accepted: categories backfill sets `active = 1` on centrally unpublished rows. Before Phase -1 the sync overwrote
     `active` with central's status on every run, so a vendor "off" on such a row could not have survived a republish
     anyway; the reset reproduces old behaviour, it does not newly override vendors. PO to confirm.
   * Deploy order + manual QC + rollback: §12 "QA — Phase -1".

## 12. Progress log

| Date | Phase | Change |
|---|---|---|
| 2026-11-21 | — | Plan drafted. |
| 2026-11-21 | -1 | PM review of the paused hotfix code: B1 migration, pages `source`, badge central id, language/currency/email-template insert-only OK. **Must fix before shipping:** (R1) categories: `active = tenant toggle AND central published` is written back into `active`, so a central unpublish→republish leaves the category disabled forever → add tenant `categories.central_visible` like products, leave `active` to the vendor; (R2) `category_product` is now never detached, so central re-categorisation never propagates → add `category_product.source` (central/tenant), backfill (links matching central's current set = central, others = tenant), detach only `source = central` links central dropped; (R3) email template "re-sync from central" on one template resets **all** templates → scope `resetEmailTemplatesFromCentral()` to the clicked template; (R4) no tests yet. |
| 2026-11-21 | -1 | **Hotfixes implemented** (uncommitted). Tenant migrations `2026_11_21_000001..5`: B1 `order_items.product_variant_id` FK → `SET NULL` (idempotent, `product_id` has no FK); B3 `pages.source`; B4 `product_badges.central_product_badge_id`; R1 `categories.central_visible`; R2 `category_product.source` — backfills read central via `CentralTable` (name from config). `TenantCatalogSyncService`: language `is_active`/currency `is_active,is_default`/email template subject,body,is_active + translations insert-only (central may still switch a language off); categories `active/featured` insert-only, sync writes `central_visible` (global scope `centralVisible` on `Tenant\Category`, panel pickers filter too, product save keeps hidden-category links); `category_product` detaches only `source=central`; pages/badges deleted only when sync-owned; badges matched by central id, text fallback, renames propagate; `resetEmailTemplateFromCentral($tenant, $template)` resets the clicked template only. Sync code degrades safely (no deletes) until a tenant DB is migrated. Tests: `tests/Feature/Tenant/CatalogSyncTenantOwnershipTest.php`. Known limits: a vendor-removed central link is re-attached by the next sync (no tombstone until phase 4); page translations/`active` still overwritten for central pages. **Deploy: run `tenants:migrate` with the code.** |
| 2026-11-21 | -1 | **Frontend review — Phase -1** (uncommitted). Centrally hidden categories follow hidden products: gone from panel lists/pickers, edit/products URLs 404; storefront (menus, home sections, category page → 404, product breadcrumb, `?category_id=` filters, sitemap, web/mobile API) all read through Eloquent so the `centralVisible` scope hides them without crashes (theme `$primaryCategory`/ancestors are null-safe). **Fixed regressions:** (1) `categoryTreeOptions()` dropped visible children of a hidden parent → product form / badge bulk-assign silently unlinked them on save; orphans now listed as roots; (2) category edit form lost a hidden parent (select2 had no option → saved as Root) → `categoryOptions($keepId)` keeps it, labelled "(Hidden by platform)"; list Parent column shows "Hidden by platform" instead of "Root"; (3) panel product-list category filter keeps orphans (`orWhereDoesntHave('parent')`). Email template reset confirm now says only this template (+ active status) is reset; button hidden for templates without a central link. Pages list: "Platform" badge for `source = central`. Badges: route key `text` regenerated from current text, so renamed badges resolve (old bookmarked URLs 404). **Themes:** no Phase -1 change alters a property starter-kit/uploaded/built-in themes read (scanned all uploaded `theme/views` + starter kit). Lang keys added to live `lang/{en,ar,fr}.json` (gitignored). Tests: `tests/Feature/Tenant/CatalogSyncPanelUiTest.php` (3). |
| 2026-11-21 | -1 | **QA — Phase -1** (uncommitted). Verdict **GO** if: staging tenant passes manual QC below and `tenants:migrate` log has no `backfill skipped` / `FK not re-added` warnings (else fix per tenant before the next sync). **Deploy:** (1) DB snapshot of tenant DBs (or at least `order_items`, `categories`, `category_product`, `pages`, `product_badges`); (2) deploy code (safe before migrate: sync degrades to no deletes / attach-only, scope guarded); (3) `php artisan tenants:migrate` off-peak, grep log for warnings; (4) `php artisan queue:restart` (workers cache `Category::hasCentralVisibleColumn()` per process); (5) one manual sync on a staging/test tenant (`syncForTenant` all sections) and re-check. **Manual QC:** panel — language/currency toggles + default currency survive a sync; category active/featured survive; centrally unpublished category gone from list/pickers/filter, child still pickable, child edit shows "(Hidden by platform)" parent and saves without losing it; product save keeps hidden-category links; vendor page survives sync and central pages show "Platform"; vendor + seeded badges (featured/recommended/new-in/best-selling/trending-now) survive, badge pages resolve; email template edit survives sync, "Re-sync from Central" resets only that template (button absent on own templates). Storefront — menus/home/category page/breadcrumb/filters without the hidden category (its URL → `/not-found`, see note), new-in/best-selling sections still populated. Orders — delete a variant (central drop + sync) on an order line: order detail (panel, central admin, storefront account, invoice/PDF, returns) renders with null variant, totals unchanged, `platform_profit + vendor_profit = grand_total`. **Rollback:** redeploy previous code (new columns are ignored by old code; old sync resumes clobbering, as before); keep migrations (B1 FK must stay SET NULL); only if required `tenants:rollback --step=5`-style per-migration down for 2–5 (1 is a no-op). **Notes:** `/categories/<missing-or-hidden-slug>` → 302 to `/not-found` (200, soft 404) is pre-existing (`bootstrap/app.php` HttpException renderer, HEAD) — follow-up for SEO. Central badge rename of a built-in text (`new-in` …) renames the tenant row so `pbd.text = 'new-in'` lookups miss; before Phase -1 the same rename created a new row and deleted the old one with its product links → not a regression, and central has no rename UI. Follow-ups (Low): badge `active` still overwritten by sync; `resync` endpoint returns success for templates without a central link; migrations 2–5 don't retry a skipped backfill. Tests: `tests/Feature/Tenant/CatalogSyncMigrationsTest.php` (4). |
