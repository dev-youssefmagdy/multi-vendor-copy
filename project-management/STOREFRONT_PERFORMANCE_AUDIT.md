# Storefront Performance Audit

Date: 2026-10-19. Stack: Laravel 13, stancl/tenancy 3.10 (domain identification, DB-per-tenant), Livewire 4, MySQL 8.0, `CACHE_STORE=file`, session `file`.

## Request lifecycle (traced)
`web` -> `InitializeTenancyByDomain` -> `PreventAccessFromCentralDomains` -> `tenant.storefront.context` (country/lang/currency/theme/variant/colors, ApplyStorefrontSessionContext) -> `identify.tenant.theme` -> `tenant.gateway.blocked` -> `preview.template` -> `blade.theme.home` (custom uploaded blade theme short-circuit) -> `store.launch.gate` -> Livewire `HomePage` -> theme view `themes/{slug}/pages/home/*` with sections chosen by `HomeVariantResolver` + `TenantPageSection`.

Existing caching (already good): `StorefrontRepository::cacheRemember` and `HomeProductService` use file-cache keys `storefront:{tenantId}:...:v{versions}`, invalidated by `CacheVersionObserver` (bumps per-tenant `cv:{tenant}:{Model}`). Keys are tenant-isolated.

## Method / limitations (measured, not estimated)
In-process requests through the HTTP kernel (`Request::create`, Host header), `QueryExecuted` listener, 3 runs each, local dev data (vendor tenant: 151 products, 6 categories, 2 banners, theme `elora`). Local MySQL => SQL time is tiny; **query count and PHP time are the meaningful signals**. No production data, no slow-query log, no Telescope session were used. Tenant `joe` returned 500 in CLI runs from a file-cache permission error (files owned by www-data) -> not benchmarked; `sss` is a "Store Coming Soon" stub (1.5 KB). Only the `elora` home variant on tenant `vendor` has real measurements; other themes (`ecommet`, `souqify`) and custom blade themes were **not** profiled.

## Baseline (vendor, GET /)
| run | total | sql | queries | repeated | peak mem | bytes |
|---|---|---|---|---|---|---|
| cold | 307 ms | 65 ms | 480 | 403 | 60 MB | 456,477 |
| warm | 164-177 ms | 50-57 ms | 442 | 392 | 68-70 MB | ~454.8k |

Top offenders (warm): 66x translations + 66x `languages where ?=?`, 33x variants, 33x flash_sales, 66x `avg(stars)`, 31x central `products` + `files`, 27x variants by id, ~30x `count(product_rates)` from views.

## Findings (ranked)
1. **N+1 on cached product lists (HIGH, fixed).** `HomeProductService` caches bare `Product` rows; card rendering lazy-loaded variants/translations/rates/flash sales/central product per card (~330 queries).
2. **`CachedBelongsTo` no-op `where 1=0` queries (MED, fixed)**: one useless query per eager-load batch.
3. **`CachedBelongsTo` identity map not tenant-scoped (isolation risk, fixed).** Static map keyed by class+id; in queue workers/Octane `flushCache()` runs only on app termination, so tenant B could receive tenant A's `Language #1`.
4. **`buyTogetherProducts` (`inRandomOrder`) computed for every theme (LOW, fixed)** though only elora's `shop_by_category` section uses it; its eager loads were also incomplete.
5. `tenancy()->central()` re-initialises tenancy on every call (repeated `INFORMATION_SCHEMA` checks, ~4/request). Not changed: framework-level, needs design decision.
6. `HomePage` re-resolves currency/language (`currencies where code`, x4) across middleware + repo. Small; not changed.
7. Indexes: no slow query seen (slowest 2.5 ms on 151 rows). **No index proposed - no evidence.** Re-check `products(active, created_at)` / `product_rates(product_id)` on a large catalog with EXPLAIN.
8. Product cache TTL 3600s without a `FlashSale`/`ProductRate` tag: acceptable because relations are now loaded fresh per request, so cached rows hold no price/rating state.

## Not done / follow-ups
Profile elora/ecommet/souqify variants and category/product pages; large-catalog test; HomePage still fetches data for sections that may be disabled (only buy-together gated); memoise `tenancy()->central()` lookups; consider `Cache::driver('file')` -> redis only if measured file-cache IO matters.

## Index review (backup dump `dokan_v2_e57e205f-...sql`, loaded into a throw-away scratch DB, since dropped)
The dump itself is small (151 products, 4 orders), so it was scaled to 154,624 products / 65,536 orders (random `created_at`/`orders_count`) in the scratch DB only, then `EXPLAIN ANALYZE` before/after.

| Query | Before | After | Index |
|---|---|---|---|
| `products where active=1 order by created_at desc limit 10` (full scan + filesort) | 124 ms | 0.06 ms | `products(active, created_at)` |
| `... order by orders_count desc, created_at desc limit 10` | 123 ms | 0.06 ms | `products(active, orders_count, created_at)` |
| same + out-of-stock `NOT EXISTS` filter | 286 ms | 4 ms | `products(active, created_at)` |
| `orders where created_at >= now()-3d / 30d` (trending, best-selling) | 43 ms | 0.05 / 0.35 ms | `orders(created_at)` |

Migration: `database/migrations/tenant/2026_10_19_000003_add_storefront_listing_indexes.php` (idempotent, reversible; tested up/up/down/up on the scratch DB; NOT yet run on any real tenant DB - run `php artisan tenants:migrate`).

Reviewed and intentionally not indexed: `categories`, `banners`, `flash_sales` (tens of rows); `translations`, `files`, `product_variants`, `product_rates`, `product_badge_product`, `category_product`, `flash_sale_product`, `order_items` (lookups already use FK / composite indexes, filesort only on tiny sets). `orders(customer_id, created_at)` would drop a per-customer filesort - negligible. `pbp_badge_id_index` is redundant with the `pbp_unique` prefix (harmless, left). Keyword search (`translations.value LIKE '%kw%'`) cannot use a B-tree; a FULLTEXT index would need a query change - out of scope.

## Index review - central DB (`backup/dokan_v2.sql`, loaded into a scratch DB, since dropped)
Storefront requests touch the central DB for: tenant/domain resolution, `maintenance_windows` (latest row, every request), `blade_themes` (by tenant), `home_variants` (theme_slug/key/is_default), `countries`/`currencies`/`languages`, `fixed_shipping_costs`, and the central `products`/`product_variants`/`files`/`translations` behind each tenant product. All are already indexed for those predicates (`domains.domain` unique, `home_variants(theme_slug,key)` unique, `products.slug/sku` unique, `product_variants.product_id`, `files(model_type,model_id)`, `translations(translatable_type,translatable_id)`, `countries.iso2` unique, `fixed_shipping_costs(country_id,is_active)`). Largest tables are 14.8k files / 9.2k translations; `maintenance_windows` has 1 row, `blade_themes` 2. **No central index recommended** - no query showed a scan on a table that can grow. Per-request central queries are better addressed by caching/memoising (e.g. the latest maintenance window) than by indexes.
