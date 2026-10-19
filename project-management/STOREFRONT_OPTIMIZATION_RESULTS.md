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
