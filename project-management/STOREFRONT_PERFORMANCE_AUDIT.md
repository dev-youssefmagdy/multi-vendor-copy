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
