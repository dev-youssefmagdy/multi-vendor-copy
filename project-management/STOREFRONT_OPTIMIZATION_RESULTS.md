# Storefront Optimization Results

## Changes
- `app/Repositories/Tenant/StorefrontRepository.php`: new `hydrateProductCards()` batch-loads `productRelations()` for lists coming from `HomeProductService` (featured, trending, new-in/best-selling via `paginateFromService`, recommended). Reuses relations across overlapping lists within the request (memo) and sets the `variant->product` inverse to avoid lazy loads.
- `app/Livewire/Tenant/Storefront/HomePage.php`: section list resolved first; `buyTogetherProducts` only queried when `shop_by_category` is on the page, with complete eager loads.
- `app/Eloquent/Relations/CachedBelongsTo.php`: identity map partitioned by tenant key; skips the DB query when everything is cached.
- `tests/Feature/Tenant/StorefrontPerformanceTest.php`: two-tenant tests (bounded queries + relations loaded, identity-map cross-tenant, list-cache cross-tenant, invalidation on product change).

No migrations, no new cache keys, no markup/style changes. Cache structure/TTL/invalidation unchanged (see audit).

## Measured (vendor, GET /, in-process, warm, same data)
| | queries | repeated | total ms | sql ms | bytes |
|---|---|---|---|---|---|
| before | 442 | 392 | 164-177 | 50-57 | 454,828 |
| after | 89 | 27 | 110-117 | 25-30 | 454,828 (identical) |

Cold run: 480 -> 103 queries, 307 -> ~243 ms. Timings are from a local, single machine with few repetitions; treat as indicative. Peak memory was not improved (68-80 MB, noisy). No cold/warm run was done for other tenants/themes.

## Tests (run with 4 GB cap: `ulimit -v 4194304`, `php -d memory_limit=4G`)
- `StorefrontPerformanceTest`: 4/4 pass (one run hit a flaky file-cache write `ENOENT` in the shared `storage/framework/cache`; passed on rerun alone).
- `StockRestoreTest`: fails **with and without** these changes (pre-existing, order totals; unrelated).
- Rest of the suite and a rendered-HTML diff beyond byte length were not run.

## Rollback
`git checkout -- app/Eloquent/Relations/CachedBelongsTo.php app/Livewire/Tenant/Storefront/HomePage.php app/Repositories/Tenant/StorefrontRepository.php` and delete the new test. No data/schema changes.

## Remaining bottlenecks
~89 queries still: 3 batches of eager loads (distinct lists), repeated tenancy init via `central()`, currency/language lookups, per-request theme/variant/colour lookups. Other themes and pages unprofiled.

## All themes x variants (preview tenant, `/preview?theme=&homepage_variant=`, 615 products, 50 categories)
Each combo in a fresh PHP process, 2nd (warm) request, before = commit 9f128ef5 versions of the 3 files, after = current. Preview mode bypasses `cacheRemember`, so these are cache-cold for the version-tagged caches.

| theme variant | queries | ms | HTML bytes |
|---|---|---|---|
| ecommet v1 | 683 -> 143 | 323 -> 223 | identical |
| elora v1 | 1561 -> 296 | 530 -> 339 | +252 (random buy-together picks) |
| elora v2 | 1267 -> 238 | 441 -> 267 | identical |
| elora v3 | 594 -> 137 | 329 -> 230 | identical |
| elora v4 | 594 -> 139 | 330 -> 224 | identical |
| elora v5 | 1382 -> 198 | 461 -> 249 | identical |
| elora v6 | 765 -> 140 | 319 -> 245 | identical |
| souqify v1 | 297 -> 132 | 252 -> 239 | identical |
| souqify v2 | 519 -> 129 | 293 -> 239 | identical |
| souqify v3 | 517 -> 129 | 263 -> 217 | identical |
| souqify v4 | HTTP 500 both | - | pre-existing: `translationValue()` on null in `themes/souqify/pages/home-v4/sections/hero.blade.php` (preview data) |
| souqify v5 | 532 -> 125 | 287 -> 212 | identical |
| souqify v6 | 554 -> 125 | 282 -> 226 | identical |

Not covered: the two uploaded `custom` blade themes (served via `StorefrontHomeController`, a different code path), and the real `vendor` tenant on themes other than elora.
