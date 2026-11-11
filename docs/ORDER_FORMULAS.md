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
* Vendor panel and central/admin panel show the **same** numbers; the vendor panel has a "Pay platform"
  action that reuses the existing order-details settlement flow.

## 4. Schema

`orders`: DROP `owner_profit`, `vendor_cost`; ADD `vendor_profit`, `platform_profit`, `coupon_discount`,
`coupon_source`, `collected_by`.

`order_items` (column order): `id, order_id, product_id, product_variant_id, qty, cost, profit_amount, price,
sub_total, fixed_shipping, discount, discount_source, tax, shipping_fee, weight, grand_total, timestamps`.

## 5. Where the formulas live in code (keep in sync with this file)

| Concern | Code |
|---|---|
| All price / total / profit-split maths | `App\Support\OrderPricing` (`unitPrice`, `item`, `order`, `couponDiscount`) |
| Per-order platform/vendor figures, void/refund rules | `App\Support\OrderProfitCalculator` |
| Paid / remaining ledger helpers | `App\Support\OrderPayoutLedger` (reads central `vendor_settlements` = Settlement Payments, and `orders.payout_released`) |
| Cost / profit / fixed-shipping split of a unit price | `Product` / `ProductVariant::priceBreakdownForCountry()` |
| Shipping eligibility (own = no shipping) | `OrderComposer::isOwnProduct()` / `isShippableCartItem()` |
| Building lines + persisting orders (checkout, API) | `App\Services\Tenant\Checkout\OrderComposer` (used by `PlacesOrders` and `CheckoutPage`) |
| Cart / checkout totals | `StorefrontRepository::cartItems()/cartTotal()` - priced with the **storefront (session) country**, identical in cart and checkout; the delivery-address country only selects the shipping zone fee |
| Vendor -> platform payment | `VendorPurchaseService` + `VendorSettlementPaymentController` (creates a `VendorSettlement` record) |

Rules: never re-implement these formulas in controllers/views/SQL (SQL revenue sums use the stored `order_items.grand_total`);
never use the removed `owner_profit` / `vendor_cost` columns; any change to a formula MUST update this file in the same commit.
