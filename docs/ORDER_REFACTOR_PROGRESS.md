# Order Refactor - Progress Log

> Resume instructions: read ORDER_FORMULAS.md + ORDER_REFACTOR_PLAN.md, find the first task below not marked DONE, continue.
> Always commit after each task and update this file before hitting usage limits.

| Task | Status | Commit | Notes |
|------|--------|--------|-------|
| T0 Docs | DONE | - | formulas, plan, progress |
| T1 Core (migrations, OrderPricing, calculator, models) | DONE | see git log | migrations 2026_10_19_000001/2 (tested on dump copy), `OrderPricing`, new `OrderProfitCalculator` API, Order/OrderItem models, Product/Variant `priceBreakdownForCountry` |
| T2 Order creation | DONE | see git log | New `App\Services\Tenant\Checkout\OrderComposer` (line() + create()) used by PlacesOrders and CheckoutPage; `StorefrontRepository::cartItems(?countryId)` rows now carry `line` (OrderPricing::item) and `subtotal` = line grand_total; `cartTotal(?countryId)` = items_grand_total; coupon via OrderPricing::couponDiscount; mixed carts split coupon by items_grand_total; storefrontPricing() adds cost/profit_amount/fixed_shipping keys |
| T3 Settlement & ledger | DONE | see git log | `OrderPayoutLedger` rewritten (inTenant, vendorOwnPlatformForTenant, platformOwnVendorForTenant, ordersOwingPlatform, ordersOwedByPlatform, recordVendorSettlement, recordPlatformPayout, release/reversePayoutsForTenant, clearVendorSettlements); `VendorPurchaseService::amountDue/previewBreakdown (keys amount,product_cost,shipping_cost=0,subtotal,gateway_fee,total)/settle(..., ?principal)/unsettle`; payout pages create/delete platform_payout transactions and store released orders in orders_snapshot; WalletDetail mark-paid uses settle path and no longer creates a TenantPayout |
| T4 Vendor panel | DONE | see git log | Tenant panel repo `orderFinancials` rewritten on calculator (new keys); details page per-item table + totals/settlement block; orders list columns vendor_profit/platform_profit/vendor_own_platform/platform_own_vendor + Pay platform; vendor-purchases page = collected_by=vendor & platform_profit>0 (paid via order_transactions). Open: VendorPurchaseService::previewBreakdown + VendorSettlementPaymentController (T3) still use old API/vendor_cost; PlacesOrders (T2). |
| T5 Central/admin panel | DONE | see git log | financials keys: subtotal, items_fixed_shipping, items_discount, items_tax, items_grand_total, shipping_charge, coupon_discount, tax, grand_total, net_amount, platform_profit, vendor_profit, vendor_own_platform, platform_own_vendor, vendor_settlements_paid, platform_payouts_paid. Ledger entry/totals: platform_profit_total, vendor_profit_total, vendor_own_platform_total, platform_own_vendor_total, platform/vendor_share_due_*_total, fixed_shipping_total; legacy aliases tenant_owes_central/central_owes_tenant/net_payable_to_tenant kept for T3 pages; central_profit_total removed. Settlements read from VendorSettlement / payout_released (no order_transactions). Unsettled/recent ledger order rows renamed. VendorSettlementsPage header 'Remaining Owner Profit' left for T3 |
| T6 Storefront & themes | DONE | see git log | order-status (ecommet/elora/souqify/starter-kit) now show items subtotal, fixed shipping, flash-sale discount, tax, shipping, coupon, grand total + per-line fixed shipping/discount/line grand_total; invoice (+StorefrontInvoiceController payload), TemplateMailService items table, OrderController API (new fields), help docs. Profile lists/tracking already use grand_total/price (unchanged). Cart/checkout summaries are owned by T2 (not touched). |
| T7 QA | DONE | see git log (Order refactor T7) | in-process smoke tests of tenant, central and storefront pages; 2 fixes; see "QA results" |
| T8 Summary | TODO | | |

## Decisions / assumptions
* Item `grand_total` = sub_total + fixed_shipping - discount + tax + shipping_fee; per-item `shipping_fee` stays 0 (shipping is order-level, as today).
* `fixed_shipping` is per line, not x qty.
* Partial refunds reduce vendor share only; void orders => all figures 0.

* Settlement ledger = existing central Settlement Payments (`vendor_settlements`) + `orders.payout_released`. NO `order_transactions` table (removed on user request).
* Rounding: line discount is rounded to cents first, grand_total = components (docs example 181.935 -> 181.93 stored; order 183.13).
* Legacy backfill: orders with real line cost are re-expressed with the new formula (order #4 -> platform 202.96 / vendor -19.78); others keep historical split.

## New API cheat-sheet (use these; old owner/tenant methods are removed)
* `OrderPricing::unitPrice/item/order/couponDiscount` (app/Support/OrderPricing.php)
* `OrderProfitCalculator::platformProfitForOrder / vendorProfitForOrder / vendorOwnPlatformForOrder / platformOwnVendorForOrder / platformShareDueFromVendor / vendorShareDueFromPlatform / isFinanciallyVoid / netOrderTotal / collectedByVendor`
* Order accessors: `items_grand_total, items_fixed_shipping, items_discount, items_tax, discount_amount (=coupon_discount), grand_total, vendor_settlements_paid (from Settlement Payments), platform_payouts_paid (payout_released), vendor_total_with_fee, vendor_settled`
* Order columns: `platform_profit, vendor_profit, coupon_discount, coupon_source, collected_by`; OrderItem: `cost, profit_amount, price, sub_total, fixed_shipping, discount, discount_source, tax, shipping_fee, weight, grand_total`
* `Product/ProductVariant::priceBreakdownForCountry($countryId)` => cost, profit_amount, price, fixed_shipping

## QA results (T7)

Method: throwaway scripts (kept outside the repo) bootstrapped the app, initialised tenancy for tenant e57e205f (vendor.dokan-v2.loc),
logged in the first tenant AdminUser / central AdminUser / storefront customer and dispatched real GET requests through the HTTP kernel
(file cache redirected to a scratch dir). No phpunit, no migrate, no DB writes (order creation ran in a rolled-back transaction).

Tested clean (HTTP 200, no exceptions):
* Tenant panel: /admin/analytics (+ all 4 tabs and their data endpoints), dashboard, orders list + data + export (CSV body checked), order detail 1-4,
  vendor-purchases (+data, export, settle page, settle/breakdown), settlement-payments (+data), billing (+data, detail), payouts-received (+data), wallet,
  customers (list, data, export, show, payments data), refunds, returns, returns analytics, coupons, flash sales.
* Central admin: orders list, order detail (tenant order 2/3/4/TEST), receipt, shipping label, in-delivery detail, orders report, refunds/returns pages,
  wallets list + wallet detail (ledger modal partial), wallet transactions, finance reports, vendor settlements, payouts index/create, invoices list/show,
  payment-logs list/detail. Livewire::test mount+render of every OrdersList/OrdersReportPage/Wallet*/Finance*/PaymentLog*/Invoice* component.
  TenantLedgerService::ledger()/find()/totals()/forTenants(), MarketplaceInvoiceService snapshot + PDF payload + SimplePdfRenderer.
* Storefront (elora, ecommet, souqify): order-status (orders 2,3,4), tracking, return form, invoice HTML (order 4: items 2.20, fixed shipping 200, discount 20.22,
  line 181.98, shipping 1.20, grand 183.18), profile, cart; REST OrderController index/show (new fields present); TemplateMailService order items table + tokens.
* Settlement: OrderProfitCalculator / OrderPayoutLedger / VendorPurchaseService (amountDue, previewBreakdown, settle, unsettle) on order 4 and in-memory scenarios
  (platform-collected, released payout, central/vendor coupon, partial refund, cancelled) - no errors, invariants hold.
* Order creation (PlacesOrders -> OrderComposer, variant 1208, country 184, flash 10%, COD, rolled back): qty 1 -> cost 1.76, profit 0.44, price 2.20, fixed 200,
  discount 20.22, item grand 181.98, platform 201.76 + vendor -19.78 = 181.98 (shipping 0 in the script, order 4's 1.20 comes from the live zone); qty 2 -> price sub 4.40,
  discount 20.44, grand 183.96, platform 203.52 + vendor -19.56 = 183.96.
* `php -l` on all PHP changed since the refactor start; all Blade views compiled (view:cache into a scratch dir, so storage/framework/views untouched) and the
  compiled output linted.

Fixed:
* TenantPanelRepository::shippingAnalyticsOverview used `$this->orders` (null before first load) instead of `$this->orders()` -> 500 on /admin/analytics/shipping and its data endpoint.
* /vendor/{tenant}/orders/{uuid}/invoice (path-based tenancy) pointed to a non-existent StorefrontInvoiceController::download -> added it (delegates to show()).
* (Earlier, separate commit) analytics 500 from the removed `transactions` relation.

Not tested / notes:
* State-changing flows: marking a settlement paid (WalletDetailPage::confirmMarkPaid), TenantPayoutPage::save / payout release, gateway charge/callback controllers
  for vendor settlements, mixed (own + central) carts, coupon checkout, checkout with a central gateway (collected_by=platform) - only the underlying calculator/ledger
  services were exercised, not the full request. Test data only contains vendor-collected orders.
* Other tenants (joe, sss, preview) DBs are not migrated/seeded with orders; central aggregates only covered the one tenant with data.
* Storefront starter-kit theme and the API endpoints through real token auth (controller invoked directly with the customer set).
* Observation: ledger `settlements_received` sums VendorSettlement `total` (incl. shipping 1.20) while the ledger principal is `product_cost`.
