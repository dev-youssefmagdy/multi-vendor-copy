# Registration → Onboarding: Split Provisioning Plan

Goal: after the user submits **Complete Registration**, they reach the onboarding wizard
(target countries → languages → theme → launch) in ~1–2 s instead of waiting for the whole
tenant to be built. Pages, fields, step order and stored data stay exactly the same.

## 1. Where the time goes today

`CompleteRegistrationPage::finalize()` → `WebsiteRegistrationService::finalize()` → `TenantService::save()`
run **inside the HTTP request**:

| # | Work in the request | Cost |
|---|---------------------|------|
| 1 | `event(TenantCreated)` → `CreateDatabase` + `MigrateDatabase` (JobPipeline, `shouldBeQueued(false)`), 108 tenant migrations | **very high (seconds–tens of s)** |
| 2 | `seedDefaultFooterPages()` | medium |
| 3 | Free plan: `syncTenantOwnerAdmin()` (init tenancy, roles, admin, appearance defaults, banners) | high |
| 4 | Paid plan: `createTenantBillingRecords()` (init tenancy, transaction + subscription) | medium |
| 5 | `TenantAffiliateService::forTenant`, affiliate conversion/commission | low–medium |
| 6 | SMTP mails (welcome, subscription activated ×2) + admin notification | medium (network) |
| 7 | `SyncProductFixedShippingCosts::dispatch()` | low (queued) |

Then everything else is queued, but as **one big undifferentiated pile** on `tenant-sync`:
`SyncTenantSectionsJob` fans out 12 sections (languages … products … coupons). The wizard needs only
`languages` + `themes` (steps 2/3) yet waits in line behind `products`. Under load one registrant's
product sync also delays the next registrant's setup (shared queue).

Additionally `StoreOnboardingWizard::render()` runs `tenancy()->initialize()` + 2 queries on **every**
Livewire round-trip and `mount()` hits the tenant DB — it requires the tenant DB to exist.

## 2. Target lifecycle

```
Submit (request, <2 s)               Queue `tenant-provision` (own worker)        Queue `tenant-sync`
───────────────────────              ─────────────────────────────────────        ───────────────────
validate                              ProvisionTenantJob                           SyncTenantSectionJob × N
tenant row + domains                    1 CreateDatabase + MigrateDatabase           (categories, products,
DomainRequest, TenantOwner              2 owner admin + roles + appearance*           pages, email-templates,
central PaymentLog                      3 paid: billing records                       badges, flash-sales,
affiliate account/conversion            4 core sync: languages, currencies,           coupons)  ── started right
pending → completed                         settings, payment-gateways, themes        after step 4, runs while
mark provision_status=pending           5 provision_status=ready                      user fills the wizard
dispatch ProvisionTenantJob  ───────►   6 dispatch phase-2 sync + notifications ───►  sync_status=ready
redirect → wizard                       SendTenantRegistrationNotificationsJob
```
`*` appearance defaults/banners need languages, so step 4 core sync runs before step 2's banners
(order inside the job: DB → languages → owner admin/appearance → rest).

Wizard:
* **Step 1 (countries)** is central-DB only → usable immediately (no tenant DB).
* **Steps 2/3 (languages/themes)** read the tenant DB → lazy-loaded once `provision_status=ready`;
  until then show a small "preparing your store…" state with `wire:poll.3s` (normally already ready by the time
  the user finishes step 1).
* **Launch (step 4)** is gated on `sync_status=ready` (catalog sync finished) so the store never opens half-empty;
  shows a polling "finalizing catalog…" note only when still running.

## 3. State (central `tenants.data` JSON — no migration, uses `Tenant::saveData`)

* `provision_status`: `pending | ready | failed` (missing ⇒ treated as `ready`, so existing tenants are unaffected)
* `sync_status`: `pending | ready | failed` (missing ⇒ `ready`)

## 4. Changes

1. `TenantService::save()` — new option `deferProvisioning` (default **false** so the admin panel flow is unchanged).
   When true: skip `TenantCreated` DB pipeline, footer-page seeding and the sync/owner-admin dispatches (the
   provisioning job does them).
2. `WebsiteRegistrationService::finalize()` — new `deferProvisioning` flag: keeps all central writes in the
   request; moves tenant-DB work (owner admin, billing records) and mails/admin notification to jobs.
   Extract the DB-touching bits into public methods reused by the job.
3. New `App\Jobs\ProvisionTenantJob` (queue `tenant-provision`, `tries=2`, `timeout=900`, idempotent,
   `failed()` → `provision_status=failed`).
4. New `App\Jobs\SendTenantRegistrationNotificationsJob` (welcome + subscription mails + admin notice).
5. `SyncTenantSectionJob` — when the last phase-2 section completes set `sync_status=ready`
   (tracked through a Bus batch on `tenant-sync`; `allowFailures` → `failed` flag).
6. `CompleteRegistrationPage::finalize()` — call service with `deferProvisioning: true`; unchanged redirect.
7. `StoreOnboardingWizard` — no tenancy in `mount()`/`render()` for step 1; lazy tenant loading for steps 2/3;
   `checkProvisioning()` poll; launch gate. Blade gets only the small loading/poll states.
8. Ops: add `tenant-provision` worker to `installation_into_server.sh`, `docs/06-queue-scheduler.md`
   and the `composer dev` queue command (so provisioning is never stuck behind product syncs).

## 5. Non-goals / unchanged

Page routes, field names, validation, step order, saved data (`category_ids`, `country_ids`, `TenantCountry`,
languages, theme), admin-panel tenant creation, the sync services themselves.

## 6. Risks & mitigations

* **Queue worker not running** → registration would stall. Mitigation: documented worker, wizard shows status,
  `failed` state offers retry (re-dispatches the job).
* **Job retried** → idempotent: DB creation skipped if tenant DB exists; all sync writers are already upsert-style.
* **Existing tenants** → missing flags ⇒ `ready`; zero behaviour change.

## 7. Implementation status

Implemented: `TenantService::save(..., deferProvisioning)`, `WebsiteRegistrationService::finalize(..., deferProvisioning)`,
`ProvisionTenantJob`, `SendTenantRegistrationNotificationsJob`, `MarkTenantSyncReadyJob`,
`CompleteRegistrationPage` (defers), `StoreOnboardingWizard` + blade (lazy tenant data, waiting states, launch gate),
worker queue order `tenant-provision,tenant-sync,default` (systemd unit, composer dev, install script).
Deviation from §4: the failed state shows a support message instead of a retry button (a retry would need the
owner password, which is deliberately not stored); re-dispatch from `php artisan queue:retry`.
Phase 2 is a Bus **chain** (categories → products order) rather than a batch (no `job_batches` table).
**Deploy note:** restart the queue worker after deploying; run with an existing worker on `tenant-provision`.

## 8. Performance pass (2026-10-24)

Measured on the dev box (MySQL with `sync_binlog=1`, so every DDL fsyncs):

| Step | Before | After |
|------|--------|-------|
| Tenant DB build (108 migrations → schema dump) | 26–105 s | 3.5 s |
| `themes` sync (3 templates × 198 countries) | 1 802 queries / 5.9 s | ~15 queries / <0.2 s |
| `currencies` (155 rows) | 2 queries per row | 1 bulk upsert |
| `settings` (+ title translations) | ~4 queries per row | upsert + 1 delete + 1 insert |
| Phase 1 total (wizard steps 2/3 unlocked) | ~32–110 s | **3.8 s** |
| Whole flow incl. products/profit chain | — | ~22 s |

* **Schema dump** — `database/schema/tenant-schema.sql`, built by `php artisan tenants:schema-dump` (scratch DB →
  migrate → dump + rows seeded by migrations: `settings`, `product_badges`). Laravel's migrator loads it automatically
  when a tenant DB has no migrations yet (also through stancl `tenants:migrate`), then runs only migrations newer than
  the dump — a stale dump is still correct, just slower. **Re-run the command after adding tenant migrations.**
  Needs the `mysql` client on the worker host (installed with MySQL server).
* **Bulk writes** — currencies, settings, theme countries, appearance defaults use `upsert`/`insertOrIgnore`;
  existing rows are prefetched once (themes, payment gateways); banner translation languages are mapped once.
  Bulk writes skip model events, so each section bumps its `CacheVersion` tag once instead of once per row.
* **Wizard step 1** — country validation is one `whereIn` count (was one `exists` query per country) and
  `tenant_countries` are written with one insert inside a transaction.
* **Profit in the chain** — `ApplyTenantProfitPercentageJob` now runs inside the phase-2 chain right after `products`,
  so `sync_status=ready` (Launch) implies final prices. Its standalone queue `tenant-profit` had no worker at all; it is
  now in the systemd unit, `composer dev` and the install script.
* **Progress** — chained section jobs record `sync_completed_section`; the Launch step shows a progress bar and a
  failure message when `sync_status=failed` (it used to spin forever).
* **`Tenant::saveData`** locks the tenant row for its read-modify-write: every attribute lives in one `data` JSON
  column, so the wizard and queue jobs writing at the same time could drop each other's keys.
