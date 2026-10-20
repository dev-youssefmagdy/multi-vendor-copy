# Tenant (vendor) affiliate — team plan & agent prompts

Goal: a tenant (vendor store) can act as an affiliate. Same engine as `docs/AFFILIATE_SYSTEM.md`, no rule changes.
Surfaces: vendor dashboard "Partner Program" banner + a new **Partner Program tab on `/finance/wallet`** (withdraw lives there).

Not an order/profit feature: **do NOT touch** `OrderPricing`, `OrderProfitCalculator`, `OrderPayoutLedger`, `OrderComposer`,
`vendor_settlements`, `TenantPayout` or `docs/ORDER_FORMULAS.md`. Partner balance is a completely separate ledger
(`affiliates.balance`), never mixed with vendor order money.

## Analysis (system analyst)
| Finding | Consequence |
|---|---|
| Affiliate data is in the **central DB**; vendor panel runs on the **tenant DB** (default connection switches) | `Affiliate*` models need `Stancl\Tenancy\Database\Concerns\CentralConnection`; service transactions must use the central connection |
| Dashboard banner `_partner.blade.php` + `DashboardController::$partnerData` is dummy (`—`, link `url('/ref/<id>')`) | Fill with real stats; link must be the **central** site `…/?ref=CODE` (central domain = `config('tenancy.central_domains.0')`, scheme from `app.url`) |
| `partner.js` invite button already mounted (share sheet → clipboard fallback) | Works once link is real; add `mailto` / WhatsApp fallback only if trivial |
| Withdraw button links to `/wallet`; wallet has no withdraw; `tenant.partner-program` is a placeholder route | Add a tab; make the nav route redirect to `wallet?tab=partner` |
| `affiliates` requires unique email/password, has a login panel | Tenant affiliates are **non-login** rows: `type='tenant'`, `tenant_id` unique, synthetic email `tenant-{id}@affiliates.invalid`, random password, status `active`; blocked from affiliate panel login |
| Self-referral check compares affiliate email to new tenant email | For tenant affiliates also compare the owning tenant's real email and `tenant_id` |
| Payout requests/payouts are handled by central admin (existing UI) | Reuse as-is; admin list shows a Type badge/filter; vendor only submits requests and views history |

Metric definitions (tenant-facing labels → data):
- **Link visits** = `affiliate_referrals` count
- **Registered traders** = referrals with `converted_at` not null (main number); sub-line "N paying" = conversions `approved|paid`
- **Available reward** = `affiliates.balance`; also total earned, total paid, pending requested, requestable (`AffiliateService::requestableAmount`), min payout (`AffiliateSettings::minPayout()`)
- Trend chips: show "+N this month" (referrals / registrations created in last 30 days) or "—" when 0.

## Shared contract (all agents code against this)
```
App\Services\TenantAffiliateService
  forTenant(string|Tenant $tenant): Affiliate          // firstOrCreate type=tenant, status=active, code via Affiliate::generateUniqueCode(), commission from AffiliateSettings defaults
  referralUrl(Affiliate $a): string                     // {scheme}://{central_domain}/?ref={code}
  stats(Affiliate $a): array  // visits, registered, paying, visits_30d, registered_30d, balance, total_earned, total_paid, pending_requested, requestable, min_payout (floats/ints, unformatted)
Affiliate: scopes/helpers  type ('external'|'tenant'), tenant_id, isTenant(); scopeExternal()
Routes (tenant_panel.php, inside finance group, middleware tenant.permission:finance.wallet.view, names prefixed tenant.finance.):
  GET  wallet/partner/conversions/data  -> wallet.partner.conversions.data   (datatable JSON)
  GET  wallet/partner/payouts/data      -> wallet.partner.payouts.data       (datatable JSON, payouts + requests)
  POST wallet/partner/withdraw          -> wallet.partner.withdraw           (JSON {ok,message}; uses AffiliateService::requestPayout)
  GET  wallet?tab=partner               -> wallet (tab key `partner`)
tenant.partner-program  -> redirect to route('tenant.finance.wallet', ['tab' => 'partner'])
```
Tenant-context rule: every query on `Affiliate*` models must hit the central DB (CentralConnection trait); in `AffiliateService`
use `DB::connection(config('tenancy.database.central_connection'))->transaction(...)`.

## Prompts

### Agent 1 — Senior backend dev (files: migrations, models, services, central admin/affiliate-panel pages, config)
1. Migration: `affiliates` add `type` string(20) default `external` (index), `tenant_id` string nullable unique. Backfill none.
2. Add `CentralConnection` trait to `Affiliate`, `AffiliateReferral`, `AffiliateConversion`, `AffiliateCoupon`, `AffiliatePayout`, `AffiliatePayoutRequest`; switch `AffiliateService` `DB::transaction` → central connection. Add `type`,`tenant_id` to fillable, `isTenant()`, `scopeExternal()`.
3. Create `App\Services\TenantAffiliateService` per the contract. `forTenant` must be race-safe (unique `tenant_id`, catch duplicate and re-read).
4. Self-referral: in `AffiliateService::isSelfReferral` also treat as self when `$affiliate->tenant_id === $tenantId` or the owning tenant's email equals the new tenant's email.
5. Block tenant affiliates from the affiliate panel (`Affiliate\Auth\LoginPage` must reject `type=tenant`; `AffiliateCouponsPage`/lists unaffected). Admin `AffiliatesListPage`: show a "Type" badge (Tenant/External + tenant id), add a type filter; the Edit modal must not allow editing a tenant affiliate's email.
6. Do NOT build any vendor-panel UI. Update `docs/AFFILIATE_SYSTEM.md` (tenant type section). Run `php artisan test tests/Feature/Affiliate` — must stay green.

### Agent 2 — Senior full-stack dev (files: tenant panel controllers/routes/views/js/css only)
1. `DashboardController`: replace dummy `$partnerData` with `TenantAffiliateService` (`forTenant(tenant('id'))`, `referralUrl`, `stats`) — keys unchanged (`invite_link, visits, visits_trend, traders, active_traders, reward, reward_status`), numbers formatted (`number_format`, `$` for reward). Keep banner markup; "Withdraw" → `route('tenant.finance.wallet', ['tab'=>'partner'])`. `active_traders` text e.g. "3 paying"; `reward_status` e.g. "$ pending requested" or "Ready to withdraw".
2. Replace the `tenant.partner-program` placeholder route with a redirect to the wallet partner tab.
3. `WalletController::index`: pass `tab` (`subscriptions` default | `partner`) and partner stats. In `wallet/index.blade.php` wrap content in `<x-tenant::tabs>` (look at `sales/customers/show.blade.php` for usage): tab 1 "Subscriptions & Balance" (existing content untouched), tab 2 "Partner Program". Honour `?tab=partner` as active.
4. Partner tab (new partial `wallet/_partner.blade.php`): stats grid (Available reward, Total earned, Total paid, Pending requests, Link visits, Registered traders), referral link box with Copy (`data-tenant-copy`), **Withdraw** button opening a `x-tenant::modal` with an amount field (hint: requestable + minimum) posting to `wallet.partner.withdraw` (use the existing tenant form/ajax helpers; show validation errors from `InvalidArgumentException` messages), two datatables: Conversions (merchant/tenant id masked, package, sale, commission, status, date) and Payouts & requests (type, amount, status, reference, date; attachment link). Add the two `*Data` + `withdraw` actions in a **new** `PartnerWalletController` (not WalletController) with a FormRequest; return `DataTables::of(...)` over central-connection queries scoped to the tenant's affiliate id only (never accept an affiliate id from the request).
5. Routes per contract. Reuse existing CSS tokens; add minimal CSS only in `resources/css/tenant/pages/` if needed. Run `npm run build` only if assets changed.
6. Do not edit models/services/migrations (Agent 1 owns them); if a contract method is missing, stub-call it per contract.

### Agent 3 — QA tester (runs after 1 and 2; files: tests only, plus bug-fix reports)
1. Feature tests in `tests/Feature/Affiliate/TenantAffiliateTest.php`: `forTenant` idempotent + unique; stats math (visits, registered, paying, balance); `referralUrl` uses central domain; tenant affiliate cannot log in to affiliate panel; self-referral (same tenant email) → rejected; full flow click → signup → paid approve credits tenant affiliate balance; withdraw request validations (below min, above requestable, success, duplicate pending cap); partner data endpoints only return the current tenant's rows (isolation test with two tenant affiliates); wallet page `?tab=partner` renders.
2. Run `php artisan test tests/Feature/Affiliate` and the existing wallet/dashboard tests (note pre-existing failure: `RegisterPageTest` "3 matches expected 5"). `php artisan route:list --name=wallet.partner`; `php artisan view:cache && php artisan view:clear`.
3. Review checklist: no order-formula code touched; no cross-tenant leakage; CSRF on withdraw; money never negative; central-connection used everywhere in tenant context. Report defects with file:line; fix only test code.

## Done criteria
Dashboard banner shows real numbers + central referral link; Copy/Invite work; Withdraw opens wallet partner tab and a request lands in central admin "Affiliates → Pay Out" (prefilled); partner balance independent from vendor balances; all affiliate tests green.
