# Order Refactor - Progress Log

> Resume instructions: read ORDER_FORMULAS.md + ORDER_REFACTOR_PLAN.md, find the first task below not marked DONE, continue.
> Always commit after each task and update this file before hitting usage limits.

| Task | Status | Commit | Notes |
|------|--------|--------|-------|
| T0 Docs | DONE | - | formulas, plan, progress |
| T1 Core (migrations, OrderPricing, calculator, models) | DONE | see git log | migrations 2026_10_19_000001/2 (tested on dump copy), `OrderPricing`, new `OrderProfitCalculator` API, `OrderTransaction`, Order/OrderItem models, Product/Variant `priceBreakdownForCountry` |
| T2 Order creation | TODO | | |
| T3 Settlement & ledger | TODO | | |
| T4 Vendor panel | TODO | | |
| T5 Central/admin panel | TODO | | |
| T6 Storefront & themes | DONE | see git log | order-status (ecommet/elora/souqify/starter-kit) now show items subtotal, fixed shipping, flash-sale discount, tax, shipping, coupon, grand total + per-line fixed shipping/discount/line grand_total; invoice (+StorefrontInvoiceController payload), TemplateMailService items table, OrderController API (new fields), help docs. Profile lists/tracking already use grand_total/price (unchanged). Cart/checkout summaries are owned by T2 (not touched). |
| T7 QA | TODO | | |
| T8 Summary | TODO | | |

## Decisions / assumptions
* Item `grand_total` = sub_total + fixed_shipping - discount + tax + shipping_fee; per-item `shipping_fee` stays 0 (shipping is order-level, as today).
* `fixed_shipping` is per line, not x qty.
* Partial refunds reduce vendor share only; void orders => all figures 0.

* Settlement ledger = dedicated tenant table `order_transactions` (NOT the wallet `transactions` table, whose observer syncs wallets).
* Rounding: line discount is rounded to cents first, grand_total = components (docs example 181.935 -> 181.93 stored; order 183.13).
* Legacy backfill: orders with real line cost are re-expressed with the new formula (order #4 -> platform 202.96 / vendor -19.78); others keep historical split.

## New API cheat-sheet (use these; old owner/tenant methods are removed)
* `OrderPricing::unitPrice/item/order/couponDiscount` (app/Support/OrderPricing.php)
* `OrderProfitCalculator::platformProfitForOrder / vendorProfitForOrder / vendorOwnPlatformForOrder / platformOwnVendorForOrder / platformShareDueFromVendor / vendorShareDueFromPlatform / isFinanciallyVoid / netOrderTotal / collectedByVendor`
* Order accessors: `items_grand_total, items_fixed_shipping, items_discount, items_tax, discount_amount (=coupon_discount), grand_total, vendor_settlements_paid, platform_payouts_paid, vendor_total_with_fee, vendor_settled`
* Order columns: `platform_profit, vendor_profit, coupon_discount, coupon_source, collected_by`; OrderItem: `cost, profit_amount, price, sub_total, fixed_shipping, discount, discount_source, tax, shipping_fee, weight, grand_total`
* `Product/ProductVariant::priceBreakdownForCountry($countryId)` => cost, profit_amount, price, fixed_shipping
