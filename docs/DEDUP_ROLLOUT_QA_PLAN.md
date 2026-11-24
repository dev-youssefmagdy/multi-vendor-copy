# Dedup rollout: audit + QA + `vendor` tenant cut-over plan

Branch: `refactor/tenant-central-dedup-plan` · Date: 2026-11-24 · Owner: PM/BA
Companion of `docs/TENANT_CENTRAL_DEDUP_PLAN.md` (phases -1 to 8 are marked done there).

## 1. Baseline (checked 2026-11-24)

| Tenant | Domain | Catalog sections (6) | Config sections (7) | Cleanup |
|---|---|---|---|---|
| `6e70e6e5-…` **vendor** | vendor.dokan-v2.loc | `central`, migrate + verify done, 0 diffs | **`sync`** (never migrated) | not run |
| `d2a5bdc4-…` vvv | vvv.dokan-v2.loc | `central` | `sync` | not run |
| `0fc05b74-…` rrr | rrr.dokan-v2.loc | `sync` | `sync` | not run |

`catalog:sync-check --tenant=vendor`: all gates pass, refs clean (null/unmapped/dangling = 0).
Migrations: central + vendor tenant have nothing pending.
E2E: suite exists (`tests/e2e`, projects `admin`/`sync`/`central`) but only targets the throwaway tenants
`e2e-sync`/`e2e-central`, which do not exist right now; `.env.e2e` is missing; nothing tests a real tenant.

**Gap:** "vendor is on the new flow" is only half true: config sections (languages, currencies, settings,
payment-gateways, themes, email-templates, pages) still read synced copies.

## 2. Work, one teammate at a time (each starts from the previous report)

1. **Senior Backend: code audit.** Anything the plan says is done but is not: sync entry points (§2b) still
   writing for `central` sections, readers not switched, TODO/fixme, open B-P8 bugs, flagged follow-ups.
   Fix small gaps; list bigger ones.
2. **PM (me): cut `vendor` over.** For the 7 config sections: `catalog:dedup-migrate <section> --dry-run`
   → migrate → verify (0 diffs) → flip `CatalogSource` to `central`. Re-run `catalog:sync-check`. Cleanup
   (`catalog:dedup-cleanup`, deletes copies) is dry-run only, real run needs the product owner's go.
3. **Senior QA: Playwright.** Create `.env.e2e`, run the full suite in parallel (`fullyParallel`, 4+ workers).
   Add a `vendor` project: real tenant, non-destructive (central admin impersonates into the panel; storefront
   browse, product page, cart, coupon, checkout page render; no order placed on the real tenant). Order
   lifecycle and money stay covered by `e2e-central`.
4. **Problem solver: failures.** Root-cause every red test (app bug vs test bug), fix, re-run until green.
5. **Senior Frontend: panels + themes.** Vendor panel screens and storefront themes on the new flow: console
   errors, 500s, missing translations / currencies / gateways / pages after the config flip. Card rule: change
   only what is broken, never move card elements.
6. **PM: runbook** `docs/TENANT_DEDUP_ROLLOUT_RUNBOOK.md`: steps to move every other tenant.

## 3. Done when

* `vendor` has all 13 sections `central`, sync-check gates pass, verify diffs 0.
* Full E2E suite green (admin / sync / central / vendor), run in parallel.
* Runbook written; progress logged in `TENANT_CENTRAL_DEDUP_PLAN.md` §12.

## 4. Results

### Step 1: Backend audit (done)
* Fixed (uncommitted): `ProfilePage` reviews keyed by merged key in central mode (central product already rated
  still showed "Leave a Review"); `EmailTemplateController` resync on a vendor-own template now returns 422.
* No open §2b sync entry point; no harmful raw config reads (4 id/code lookups, kept by identity rows).
* Low, left: sync-check pages metric `tenant_only_field` false positive (`content` vs `body`,
  `CatalogSyncCheck.php:665`); "platform managed" badges missing on config panel screens; E2E gaps (settlements,
  payouts, mobile API, ecommet/souqify themes).
* Parallel tests: ParaTest was missing → installed `brianium/paratest ^7.20` (dev) so `php artisan test --parallel` works.
* `storage/backups/` is **not gitignored**: dedup backups could be committed by accident.

### Step 2: `vendor` cut-over (done 2026-11-24)
* Backup: `storage/backups/dedup-rollout/vendor-<id>-20261124-1540.sql.gz` + central `tenants`/`domains`.
* 7 config sections: dry-run (0 stale / 0 orphan) → migrate → verify **0 diffs** → `CatalogSource::set(central)`.
* `vendor` now has all 13 sections `central`; `catalog:sync-check` all gates pass; storefront/panel HTTP 200.
* Cleanup (`catalog:dedup-cleanup`) NOT run: waits for product-owner go after the soak period.
* Gap found: nothing outside `e2e:prepare` sets `central`, so **new tenants still start on `sync`** (onboarding
  should default every section to `central`).

### Step 3: QA Playwright (done)
* New `vendor` project (real tenant, non-destructive, panel via central-admin impersonation; 59 tests),
  `e2e:vendor-probe` (read-only fixture discovery), `.env.e2e` (E2E_* also read from `.env`).
* Parallel run (6 workers, 1.4 min): admin 8/1 fail · sync 29/0 (+1 skip by design) · central 30/0 · vendor 50/9.
* All 10 failures are app/data bugs: (1) **E2E fixtures leaked into vendor** (8 `e2e-*` products adopted, "E2E Gadgets"
  category, `E2E10` coupon live during runs); (2) `BadgesDedup` compares absolute sort_order (1- vs 0-based) → spurious
  sort overrides freeze central reorders; (3) flatpickr chunk export error (coupons/flash-sales panels, pre-existing);
  (4) dashboard country-picker null `querySelector` (pre-existing); (5) `free-shipping-icon` `<svg height="auto">`.
* PHPUnit (33 dedup files, 8 parallel shards): shards 2/3/6/8 green; shard 4: 3 errors in
  `LanguageCatalogTranslationTest` (missing `countries` table in shard DB, `App\Models\Catalog` class missing).

### Step 4: Problem solver (running)

## 5. Scope change (product owner, 2026-11-24): sync removed entirely

The product owner will `migrate:fresh` central, re-import the products and delete every existing tenant. Therefore:

* **No sync mode any more.** Every tenant reads central live from registration; only what the tenant changes is
  stored, as an override, and read override-first (e.g. product name = approved override if present, else central).
* **No legacy migration**: `catalog:dedup-migrate` / verify / cleanup / backfill-refs exist only for copies that will
  never exist again → removed with the sync code.
* **Adoption row kept** (PM decision): which central products a tenant sells = one thin `tenant_product_overrides` row
  (id + `adopted_at`, every override column NULL = inherit). It carries no copied data; it is what makes "New in
  products", admin "assign to tenant" and vendor remove/hide work. Overrides fill its columns only when changed.
* Config sections (languages, currencies, gateways, themes, email templates, pages, settings) keep a bare identity row
  per central row (local FKs) with values overlaid live; only `central_overridden` rows hold tenant values.

### Step 5: Backend — remove sync (after step 4)
1. `CatalogSource`: every section `central`, always (drop the `sync` mode; keep the helper returning central or remove
   it with all callers).
2. Provisioning (`ProvisionTenantJob`, onboarding wizard, `TenantService::save`): no copy; config identity rows +
   initial adoption rows only; `sync_status` ready.
3. Delete: `TenantCatalogSyncService` copy sections, `SyncTenantSection(s)Job`, `SyncCentralProduct*ToTenant(s)Job`,
   `SyncFixedShippingCostsToTenantsJob`, fan-out in central observers (→ cache-version bump only), `tenants:sync-data`,
   Tenant Sync admin page, dedup migrate/verify/cleanup/backfill tooling, `catalog:sync-check` copy-drift parts.
4. Remove the `sync` branches from every reader/writer (repositories, models, panel, storefront, mobile API, orders).
5. Migrations: fresh tenant schema without copy-only tables/columns where safe (`central_*` link columns, copy tables).
6. Every command / job / controller / scheduled task re-checked on the central-only flow.

**Update (PO, 2026-11-24): no `migrate:fresh`.** Central data stays; only the tenant records are removed. `once.sh` must:
delete tenants through the model (drops tenant DB, domains, storage), purge central rows of deleted tenants
(`product_tenant_assignments`, `product_edit_requests`, `catalog_dedup_runs`, tenant countries, change requests…),
drop + refill the tenant DB pool (`tenants:pool-fill`) and refresh `tenants:schema-dump` on the new schema, and make sure
central E2E fixtures stay invisible to real tenants.

### Step 6: QA — fresh-install E2E
Real registration of a new tenant through the UI → panel + storefront + order lifecycle, all on central-only, parallel.
`sync` project removed from Playwright.
