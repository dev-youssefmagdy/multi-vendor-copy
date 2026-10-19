# Order & Calculation Refactor - Plan

Formulas: see [ORDER_FORMULAS.md](ORDER_FORMULAS.md). Progress / resume point: [ORDER_REFACTOR_PROGRESS.md](ORDER_REFACTOR_PROGRESS.md).
Baseline data: `backup/dokan_v2_e57e205f-...sql` (tenant DB; orders 1-4, order #4 = variant 1208, flash 10 %, fixed 200).

## Findings from the baseline
* `order_items` in the dump already has `cost`, `fixed_shipping` (no migration in repo) -> migrations must be `hasColumn`-guarded.
* Today `price` stored on the item = flash-discounted price **including** fixed shipping (181.98); `discount` = 20.22.
  New `price` = cost + profit only (2.20); fixed/discount/grand_total stored separately. Legacy rows get a backfill.
* `orders.owner_profit` / `vendor_cost` are used by ~35 files (calculator, ledger, wallets, invoices, reports, panels, docs views).
* Order creation exists twice: `PlacesOrders` (service/API) and `CheckoutPage::placeOrder` (Livewire) - both must change.
* Settlement: `VendorSettlement` (central; vendor->platform, per order) and `TenantPayout` + `orders.payout_released`
  (central->vendor). New tenant `transactions` rows (model=Order) become the per-order ledger.

## Tasks (each = one commit)
| # | Task | Owner |
|---|------|-------|
| T0 | Docs: formulas, plan, progress | lead |
| T1 | Migrations (orders/order_items/backfill) + `OrderPricing` + `OrderProfitCalculator` rewrite + models + unit tests | lead |
| T2 | Order creation: `PlacesOrders`, `CheckoutPage`, `CartPage` totals (store cost/profit/fixed/discount/tax/grand_total, platform/vendor profit) | dev-A |
| T3 | Settlement & ledger: `OrderPayoutLedger`, `VendorPurchaseService`, `VendorSettlementPaymentController`, payout pages -> transactions | dev-B |
| T4 | Vendor/tenant panel: orders list + details + finance/billing/vendor-purchases/dashboard; "Pay platform" button | dev-C |
| T5 | Central/admin panel: orders list/details, wallets, tenant ledger, finance reports, invoices, payment log, docs partials | dev-D |
| T6 | Storefront: order-status, profile, order-tracking, invoice, mails, API in all themes (elora, ecommet, souqify, starter kit) | dev-E |
| T7 | QA: unit/feature tests, `php -l`, Blade compile, migrate dump into scratch DB, verify example numbers | qa |
| T8 | Final summary + docs update | lead |

## Rules for all agents
* Never read/write `owner_profit` or `vendor_cost` again; use `platform_profit` / `vendor_profit` and the calculator methods.
* Use `OrderPricing` for any price maths; no formulas inline in views/controllers.
* Commit only your own files (`git add <paths>`), message `Order refactor Tn: ...`.
* Update PROGRESS.md when a task finishes.
