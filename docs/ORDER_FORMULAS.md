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
* `shipping_charge` comes from the existing weight/zone logic (unchanged).
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
