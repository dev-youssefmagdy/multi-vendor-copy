# Runbook: move a tenant to the new central-read flow (dedup)

How to move a tenant from synced copies (`sync`) to live reads from central with overrides (`central`), section by
section. The `vendor` tenant was moved this way on 2026-11-24 (all 13 sections, 0 verify diffs).
Background: `docs/TENANT_CENTRAL_DEDUP_PLAN.md` (§5a, §6), `docs/DEDUP_ROLLOUT_QA_PLAN.md`.

## Which tenants still need it (2026-11-24)

| Tenant | Domain | Status | Start at |
|---|---|---|---|
| `6e70e6e5-49d5-40cf-b66c-675dedf2c8dd` | vendor.dokan-v2.loc | **done**, all 13 `central` | step 7 (cleanup, later) |
| `d2a5bdc4-a66d-47e1-bf84-7fdc5c62aecf` | vvv.dokan-v2.loc | 6 catalog sections `central`, 7 config `sync` | step 4, config sections only |
| `0fc05b74-b605-4fa7-8d79-dfab8c50bab1` | rrr.dokan-v2.loc | everything `sync` | step 1 |

Check any tenant's current state with:

```bash
php artisan tinker --execute='echo json_encode(App\Support\Catalog\CatalogSource::map(App\Models\Tenant::find("<TENANT_ID>")));'
php artisan catalog:sync-check --tenant=<TENANT_ID>
```

All commands below are idempotent and per tenant. Set `T=<TENANT_ID>` first. Work on **one tenant at a time**.

## 0. Once per server (deploy)

```bash
git pull && composer install --no-dev -o
php artisan migrate --force            # central
php artisan tenants:migrate --force    # every tenant DB (adds override tables, typed columns, markers)
php artisan config:cache && php artisan route:cache
php artisan queue:restart
```

Do **not** run `php artisan view:cache` as the shell user (it 500s the site); let the web user compile views.

## 1. Back up the tenant

```bash
mkdir -p storage/backups/dedup-rollout
mysqldump --single-transaction --routines --triggers "dokan_v2_$T" | gzip > storage/backups/dedup-rollout/$T-$(date +%Y%m%d-%H%M).sql.gz
mysqldump --single-transaction dokan_v2 tenants domains | gzip > storage/backups/dedup-rollout/central-tenants-$(date +%Y%m%d-%H%M).sql.gz
```

Check the dump ends with `-- Dump completed`. `storage/backups/` is not gitignored yet: never commit it.

## 2. Baseline health check

```bash
php artisan catalog:sync-check --tenant=$T
```

It must say **All gates passed**. If not, fix the drift first (usually `php artisan tenants:sync-data --tenant=$T --section=<section>`
while the section is still `sync`).

## 3. Typed references (phase 1, only for tenants never done)

```bash
php artisan catalog:backfill-refs --tenant=$T --dry-run
php artisan catalog:backfill-refs --tenant=$T
php artisan catalog:sync-check --tenant=$T --gate=null_refs:0 --gate=unmapped_refs:0
```

## 4. Migrate → verify → flip, per section, in this order

Order matters: products need badges/labels/coupons/flash sales/categories first. Config sections go last, starting with
`settings` and `email_templates`, which are the only ones where the vendor's own edits are kept as overrides.

| # | Dedup section (`catalog:dedup-migrate`) | Flag name (`CatalogSource`) | Note |
|---|---|---|---|
| 1 | `badges` (then `field_overrides`) | `badges` | run `field_overrides` right after badges |
| 2 | `labels` | `labels` | |
| 3 | `coupons` | `coupons` | check `code_collisions=0` in sync-check |
| 4 | `flash_sales` | `flash-sales` | |
| 5 | `categories` | `categories` | |
| 6 | `products` | `products` | then remap mobile carts (below) |
| 7 | `settings` | `settings` | |
| 8 | `email_templates` | `email-templates` | |
| 9 | `languages` | `languages` | |
| 10 | `currencies` | `currencies` | |
| 11 | `payment_gateways` | `payment-gateways` | |
| 12 | `themes` | `themes` | |
| 13 | `pages` | `pages` | |

For each row:

```bash
S=<dedup section>; F=<flag name>
php artisan catalog:dedup-migrate $S --tenant=$T --dry-run   # report: storage/app/dedup-reports/$S/$T.json
php artisan catalog:dedup-migrate $S --tenant=$T             # additive only, old rows stay
php artisan catalog:dedup-migrate $S --tenant=$T --verify    # must print "Verify: 0 diffs."
php artisan tinker --execute='App\Support\Catalog\CatalogSource::set(App\Models\Tenant::find("'$T'"), "'$F'", "central");'
```

Stop rules:
* Dry-run shows `stale` > 0 or `orphan` > 0 → read the JSON report before migrating. For `field_overrides`, stale pairs
  need `--accept-stale` (they then follow central); only use it once the vendor agrees.
* Verify shows any diff → **do not flip**. Investigate the report; the section stays on `sync`.
* Migrate status `failed` → read the error column, fix, re-run (it is resumable).

After `products` is flipped:

```bash
php artisan catalog:dedup-migrate products --tenant=$T --remap-carts --dry-run
php artisan catalog:dedup-migrate products --tenant=$T --remap-carts
```

## 5. After all sections are flipped

```bash
php artisan cache:clear
php artisan catalog:sync-check --tenant=$T          # every section "central", "All gates passed"
php artisan queue:restart
```

Smoke test (or run the Playwright `vendor` project pointed at this tenant, see §8):

* Storefront: home, `/ar` (RTL), a category, search, a product page with variants, add to cart, apply a coupon,
  open checkout (HTTP 200, no error page).
* Vendor panel: products list (central + own), product edit opens, categories, badges, flash sales, coupons, orders,
  languages, currencies, payment gateways, themes, email templates, pages, settings.
* Check `storage/logs/laravel.log` for new errors.

## 6. Rollback (any time before cleanup)

Copies are not deleted until step 7, so rollback is instant per section:

```bash
php artisan tinker --execute='App\Support\Catalog\CatalogSource::set(App\Models\Tenant::find("'$T'"), "<flag>", "sync");'
php artisan tenants:sync-data --tenant=$T --section=<flag>
# products only, before the products rollback:
php artisan catalog:dedup-migrate products --tenant=$T --remap-carts --reverse
php artisan cache:clear
```

Full restore: `gunzip < storage/backups/dedup-rollout/<file>.sql.gz | mysql "dokan_v2_$T"`.

## 7. Cleanup (phase 7, deletes the old copies, needs product-owner go)

Only after the tenant has run on `central` for the agreed soak period with no incidents.

```bash
php artisan catalog:dedup-cleanup-all --tenant=$T --with-config          # dry-run: gates + what would be deleted
php artisan catalog:dedup-cleanup-all --tenant=$T --with-config --execute # real delete, with a verified JSON backup
```

Per section instead: `php artisan catalog:dedup-cleanup <section> --tenant=$T --dry-run`, then `... --backup`.
Restore one section: `php artisan catalog:dedup-cleanup <section> --tenant=$T --restore=<backup dir>`.
After cleanup, rollback means a restore first, then the flag flip in step 6.

## 8. Testing with Playwright

```bash
cp .env.e2e.example .env.e2e     # set E2E_PASSWORD, E2E_WORKERS
npx playwright test              # admin + sync + central + vendor projects, in parallel
npx playwright test --project=vendor
npm run e2e:report
php artisan test --parallel      # PHPUnit (ParaTest installed)
```

The `vendor` project is non-destructive: no orders, no saves. It opens the panel through central-admin impersonation.
To point it at another tenant, see the variables at the top of `tests/e2e/vendor/`.

## 9. New tenants

Tenants created after the rollout should start on `central` for every section, with no copies. Until onboarding does that
by default, run steps 4–5 right after provisioning (`sync_status = ready`).
