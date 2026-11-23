# Order Formulas & Calculations (single source of truth)

All order money in the platform (storefront, vendor/tenant panel, central/admin panel, invoices, API)
MUST follow these formulas. Implementation lives in `App\Support\OrderPricing` (pure maths) and
`App\Support\OrderProfitCalculator` (per-order profit / settlement figures).

## 1. Customer-side totals

```
price               = cost + profit_amount                      (per unit; profit = percentage | fixed)
sub_total           = price * qty
item_grand_total    = sub_total + fixed_shipping - discount + tax + shipping_fee   (shipping_fee is 0 per item; shipping lives on the order)
discount            = (sub_total + fixed_shipping) * flash_sale_pct / 100          (0 when no active flash sale)
items_grand_total   = SUM(item_grand_total)
order_grand_total   = items_grand_total + order.shipping_charge - order.coupon_discount
```

* `fixed_shipping` ("fixed cost price") is **per order line, not multiplied by qty**.
* **Customer-facing display:** storefront order pages (all themes + starter kit) never show `fixed_shipping` as its own row; "Items subtotal" = `subtotal + items_fixed_shipping` (per line: `sub_total + fixed_shipping`). Stored columns and formulas are unchanged. Invoice and order emails follow the same rule (unit price = (sub_total + fixed_shipping) / qty).
* **Storefront price filter** (min/max on category, search, best-selling, new-in...) compares against the *displayed* unit price, i.e. `price + fixed_shipping` with any flash-sale discount applied (`Product::storefrontPricing()['current_price']` x currency rate), not the bare sell price. Implemented in `StorefrontRepository::productIdsWithinDisplayedPrice()` (PHP, so the maths is not duplicated in SQL). Price *sorting* still orders by the SQL sell price.
* `shipping_charge` comes from the existing weight/zone logic, computed from **catalog lines only**.
* **Own products ship free (2026-11-07):** a product with `is_own_product = true` (vendor-created, no `central_product_id`) contributes 0 shipping:
  `fixed_shipping = 0`, weight 0, excluded from the zone/rate weight sum and from every shipping-availability check ("select a country", "not available to your country").
  An own-only cart therefore checks out with `shipping_charge = 0` and no shipping error; a mixed cart pays the zone rate for the catalog lines' weight only
  (and mixed carts still split into two orders; the own companion order carries `shipping_charge = 0`). `is_tenant_owned` (central product assigned to one tenant) is a catalog product, NOT own, for shipping.
  Predicate: `OrderComposer::isOwnProduct()` / `OrderComposer::isShippableCartItem()`; used by `PlacesOrders`, `CheckoutPage`, `CartPage`, `CalculatesFreeShipping` (mobile cart).
* **Own products are priced from the vendor's form (2026-11-11):** for `is_own_product` without `central_product_id`
  `cost = 0`, `profit_amount = sell price`, `fixed_shipping = 0`, where sell price = the variant's `sell_price` (country key, then `default`)
  for variant products, else `sale_price` when > 0, else the base price (`price` JSON country key, then `default`, then `default_price`).
  Same price in every country (only currency conversion differs); central cost, `profit` rows and `FixedShippingCost` never apply
  (they used to: the storefront showed only the weight-based fixed shipping, e.g. $20 in Morocco and $0.00 where none is configured).
  A no-variant product sold at its Sale Price shows the base price as the strikethrough. Code: `Product::ownSellPrice()` /
  `ownPriceBreakdownForCountry()`, used by `Product::priceBreakdownForCountry()`, `storefrontPricing()` and own `ProductVariant::priceBreakdownForCountry()`.
  Country targeting ("sold in" = `products.allowed_country_ids`) hides an own product from visitors of other countries (listings, search, home sections,
  flash-sale strips, product page 404) via `Product::scopeAvailableInCountry()` / `isAvailableInCountry()`; for catalog products the column stays a ranking preference only.
  **Sold only there (2026-11-15):** checkout refuses an order whose shipping country is not in an own product's list
  (`OrderComposer::itemsNotSoldInCountry()`, called by `CheckoutPage::placeOrder()` and `PlacesOrders::place()`, message lists the products).
  Visitor country = `CustomerCountryResolver` (selected/saved address first, then the session country); the session country comes from
  `CountryDetectorService`, which now re-reads Cloudflare `CF-IPCountry` on every request (a VPN / network change is picked up immediately;
  `?country=XX` stays a session-wide QA override).
  **Visibility = detected country, strict (2026-11-15):** listings, home sections, flash-sale strips and the product page use
  `StorefrontRepository::visibilityCountryId()` = the DETECTED country (CountryDetectorService; mobile API without detection falls back
  to the app's session country), not the saved address or a manual country pick. A product with a country list is shown ONLY when that
  country is in the list; when the country is unknown it is hidden as well (`scopeAvailableInCountry(null)` / `isAvailableInCountry(null)`).
  Products without a list are visible everywhere. Pricing/shipping still use `customerCountryId()` (address first).
  **List values (2026-11-16):** `NULL` = "Select all countries" (visible everywhere), `[ids]` = only those countries, `[]` = nothing
  ticked (hidden everywhere, also left out of the sitemap). The same `availableInCountry(visibilityCountryId())` filter now also guards
  every add-to-cart entry point (`CartController::add()` + the Livewire quick-add actions), favorites and the sidebar variant-filter counts,
  so a hidden product can't be put in the cart from a stale page either.
  **Vendor preview (2026-11-16):** `?preview_country=XX` renders the storefront as XX for that request only (dashboard "Live Store
  Preview" country selector); `ApplyStorefrontSessionContext` restores the session's country/language/currency afterwards, unlike the
  session-wide `?country=XX` QA override. `StorefrontRepository::forCountry($country)` resolves theme / edition / colors / banners /
  visibility as a visitor from `$country` (used by `App\Support\Tenant\StorefrontCountryShortcuts`).
* **Own-products orders are merchant-fulfilled (2026-11-08):** no stored column; discriminator = `Tenant\Order::isOwnProductsOrder()` (has items and every item's product is `is_own_product`; the companion order of a mixed cart and own-only carts). Platform (central admin) treats them read-only: excluded from the Delivery Queue / its counters, and status, tracking, attachments, cancel, refund, create-return, shipping label and receipt are rejected server-side (`OrderFulfillmentService::assertPlatformManageable`, Livewire pages, `OrderReceiptController`). Shipping stays on the catalog order only (`shipping_charge = 0` on the own order), so shipping/settlement lines come only from the catalog order. Formulas unchanged.
* `tax` is 0 today; whatever is calculated per item is summed into `order.tax`.

### Worked example (storefront, UK, USD)
Variant "New hot sale 65 pieces cost", qty 1, flash sale 10 %, no coupon.

```
cost                = 1.72
profit_amount       = 1.72 * 25 / 100 = 0.43
price               = 2.15
sub_total           = 2.15 * 1 = 2.15
fixed_shipping      = 200
discount            = (2.15 + 200) * 10 / 100 = 20.215
item_grand_total    = 2.15 + 200 - 20.215 + 0 = 181.935
shipping (by weight)= 1.2
order_grand_total   = 181.935 + 1.2 - 0 = 183.135
```

(DB sample order #4 uses cost 1.76 -> price 2.20 -> discount 20.22 -> item total 181.98.)

## 2. Platform / vendor split (stored on `orders`)

```
platform_profit =  SUM( cost*qty + fixed_shipping - discount            [only if the flash sale is CENTRAL's, else 0] )
                 + order.shipping_charge + order.tax
                 - order.coupon_discount                                [only if the coupon is CENTRAL's, else 0]

vendor_profit   =  SUM( profit_amount*qty - discount                    [only if the flash sale is the VENDOR's, else 0] )
                 - order.coupon_discount                                [only if the coupon is the VENDOR's, else 0]
```

Invariant: `platform_profit + vendor_profit = order_grand_total` (verified by unit tests).

* `order_items.discount_source` = `central` | `vendor` | `null`   (flash-sale owner)
* `orders.coupon_source`        = `central` | `vendor` | `null`   (coupon owner)
* `orders.collected_by`         = `vendor` | `platform`           (who received the customer's money:
  tenant gateway / COD => `vendor`; central gateway => `platform`)

## 3. Settlement (running balances)

```
vendor_own_platform = platform_profit - SUM(Settlement Payments paid for the order)   -- only when collected_by = vendor
platform_own_vendor = vendor_profit   - (vendor_profit if payout_released else 0)      -- only when collected_by = platform
```

* "Settlement Payments" = the existing central `vendor_settlements` records (`VendorSettlement`, shown in the vendor panel
  at Finance > Settlement Payments). The paid principal is `product_cost` (gateway fee is separate). No extra transactions table.
* Pay-central page (`VendorPurchaseService::previewBreakdown`) splits the amount due for display only: `shipping_cost = min(order.shipping_charge, due)`,
  `product_cost = due - shipping_cost` (cost + fixed shipping - central discounts + tax - central coupon). `amount` (the recorded principal) stays the full due.
* Platform -> vendor money is tracked by `orders.payout_released` / `TenantPayout`.
* Cancelled / rejected / fully-refunded orders produce 0 for all figures. A partial refund reduces the
  vendor's share only (platform_profit unchanged).
* **Return refund (2026-11-23):** refundable value of `qty` of a line = `(line.grand_total - coupon_discount * line.grand_total / items_grand_total) * qty / line.qty`
  (`OrderPricing::returnLineValue`, used by `RefundCalculator::forReturn`), so Σ lines + shipping = order grand_total, the same invariant `forCancellation` uses.
  Previously it read the legacy `sub_total x (1 - discount_percentage + tax_percentage)`, which ignored fixed shipping, flash discount, item tax and the coupon_discount column.
* Vendor panel and central/admin panel show the **same** numbers; the vendor panel has a "Pay platform"
  action that reuses the existing order-details settlement flow.

## 4. Schema

`orders`: DROP `owner_profit`, `vendor_cost`; ADD `vendor_profit`, `platform_profit`, `coupon_discount`,
`coupon_source`, `collected_by`, `coupon_code`, `coupon_type`, `catalog_coupon_id` (phase 3: snapshot + typed ref, nullable).

`order_items` (column order): `id, order_id, product_id, product_variant_id, qty, cost, profit_amount, price,
sub_total, fixed_shipping, discount, discount_source, tax, shipping_fee, weight, grand_total, timestamps`.

`order_items.product_variant_id` FK is `ON DELETE SET NULL` (2026-11-21, dedup plan B1): deleting a catalog variant never
deletes order lines; the line keeps `product_id` and its money snapshot. Readers must tolerate a null variant.

`order_items` typed catalog references (2026-11-23, dedup plan phase 1), appended after `updated_at`:
`product_type`, `catalog_product_id`, `variant_type`, `catalog_variant_id` (all NULL-able; index
`(product_type, catalog_product_id)` and `(variant_type, catalog_variant_id)`).
* `*_type` = `central` | `tenant`; `catalog_*_id` = the **central** id when `central`, the **tenant** id when `tenant`.
* Rule (`App\Support\Catalog\CatalogRefResolver`): product = `central` iff `central_product_id` is set and the product is not
  `is_own_product` (`is_tenant_owned` stays `central`); variant = `central` iff `central_product_variant_id` is set and its product is
  not own. Written by `OrderComposer::create()` (one batch lookup per order, `OrderComposer::lineRefs()`), by the `OrderItem` save
  hook for any other writer, and for old rows by `php artisan catalog:backfill-refs`.
* Identity only: no formula reads them, money columns are untouched, readers still use `product_id` / `product_variant_id` until
* **Phase 5 (2026-11-27, `catalog_source.products = central`):** a line of a CENTRAL product has `product_id` / `product_variant_id` = NULL (migration `2026_11_27_000002`: `order_items.product_id` nullable) and is identified only by the typed pair (`OrderComposer` -> `ProductCatalog::lineColumns()`); own products keep their tenant ids plus the pair. `OrderItem::product()` / `variant()` resolve through the pair (merged key) in central mode. **No formula, money column or snapshot changed**: the unit price still comes from `Product::storefrontPricing()` -> `OrderPricing::unitPrice()` on the merged row's `profit` / `fixed_shipping_costs` / base price, which `ProductCatalog::productsSql()` builds with the same precedence and rounding as `TenantPricingService::forCatalog()` (parity-tested: `CatalogPhase5ParityTest`). Stock moves for such lines go through `StockService::moveMergedLineStock()` (tenant `stock_override` if set, then central stock / `sold_count` exactly as before).
  the products cutover (dedup plan phase 5). When the variant FK sets `product_variant_id` to NULL the pair keeps the history.
* NULL pair = not backfilled yet, or the tenant row was already gone at backfill time (reported by `catalog:sync-check --section=refs`).

`orders` coupon identity (2026-11-25, dedup plan phase 3), appended: `coupon_code` (snapshot of the code at checkout),
`coupon_type` (`central` | `tenant`) + `catalog_coupon_id` (central id when central, tenant id when tenant; index
`orders_coupon_ref_index`). Written by `OrderComposer::create()` (`OrderComposer::couponRef()`), old rows by
`php artisan catalog:dedup-migrate coupons`.
* `discount_id` stays the TENANT coupon id; it is NULL for a central coupon once the tenant's `catalog_source.coupons` is
  `central` (no tenant row exists). Readers of the code use `Order::appliedCouponCode()` (snapshot, else the row).
* Ownership rules are unchanged, only where they are read from: `orders.coupon_source` = `central` iff the coupon is
  central's (`CouponCatalog::orderSource()`: merged coupon `source`, or `central_coupon_id` on a sync copy);
  `order_items.discount_source` = `central` iff the flash sale is central's (`FlashSaleCatalog::discountSource()`: merged
  row `source`, or `central_flash_sale_id` on a sync copy). Same values in sync and central mode for the same cart.
* Identity only: no formula reads these columns; money snapshots are never recomputed from live central data.
* Per-tenant coupon usage (D4) = `CouponCatalog::usageCount()` = the tenant's orders by (`coupon_type`, `catalog_coupon_id`),
  the two orders of a mixed cart counting once. No usage limit exists (none existed before either).

## 5. Where the formulas live in code (keep in sync with this file)

| Concern | Code |
|---|---|
| All price / total / profit-split maths | `App\Support\OrderPricing` (`unitPrice`, `item`, `order`, `couponDiscount`) |
| Per-order platform/vendor figures, void/refund rules | `App\Support\OrderProfitCalculator` |
| Paid / remaining ledger helpers | `App\Support\OrderPayoutLedger` (reads central `vendor_settlements` = Settlement Payments, and `orders.payout_released`) |
| Cost / profit / fixed-shipping split of a unit price | `Product` / `ProductVariant::priceBreakdownForCountry()` |
| Shipping eligibility (own = no shipping) | `OrderComposer::isOwnProduct()` / `isShippableCartItem()` |
| Building lines + persisting orders (checkout, API) | `App\Services\Tenant\Checkout\OrderComposer` (used by `PlacesOrders` and `CheckoutPage`) |
| Coupon lookup (storefront, checkout, mobile, panel) and order coupon ref | `App\Repositories\Tenant\Catalog\CouponCatalog` (sync mode = the old `Tenant\Coupon` queries); `OrderComposer::couponRef()` |
| Flash-sale discount per product (relation, SQL sort/filter expression, home strips) | `Product::flashSales()` / `App\Repositories\Tenant\Catalog\FlashSaleCatalog` (central mode) |
| Typed catalog refs of order lines (`product_type` / `catalog_product_id`, `variant_type` / `catalog_variant_id`) | `OrderComposer::lineRefs()` → `App\Support\Catalog\CatalogRefResolver`; `HasCatalogRefs` on `OrderItem` |
| Cart / checkout totals | `StorefrontRepository::cartItems()/cartTotal()` - priced with the **storefront (session) country**, identical in cart and checkout; the delivery-address country only selects the shipping zone fee |
| Vendor -> platform payment | `VendorPurchaseService` + `VendorSettlementPaymentController` (creates a `VendorSettlement` record) |

Rules: never re-implement these formulas in controllers/views/SQL (SQL revenue sums use the stored `order_items.grand_total`);
never use the removed `owner_profit` / `vendor_cost` columns; any change to a formula MUST update this file in the same commit.
