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
| T7 QA | TODO | | |
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
