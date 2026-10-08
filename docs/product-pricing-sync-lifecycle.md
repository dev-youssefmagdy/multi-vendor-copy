# Product pricing & sync lifecycle (central -> tenant -> tenant edit)

Goal: one data shape and one formula for every path that writes tenant prices, so a
tenant edit, a central edit, a shipping-rule change and a profit-% change all
produce identical JSON.

## 1. Canonical tenant data shape

Tenant `products` and `product_variants` (per-tenant DB):

| Column | Product | Variant | Meaning |
|---|---|---|---|
| base cost | central `sale_price` (fallback `base_price`), read live | `real_price` (mirror of central variant `price`) | what the tenant pays |
| `fixed_shipping_costs` | mirror of central product JSON `{country_id: cost}` | none (read from `centralVariant`, fallback to product's) | per-country shipping |
| `profit` | `{key: {profit_type, profit_value, total_profit}}` | same | **the source of truth the vendor controls** |
| `price` / `sell_price` | `{key: number}` | same | derived cache |
| `default_price` / `default_sell_price` | `price.default` | `sell_price.default` | derived cache |

`key` = `"default"` plus one entry per country with an active central `FixedShippingCost` rule.

Formula (only one, `TenantPricingService::compute`):

```
profitAmt  = type == fixed ? round(value, 2) : round(base * value / 100, 2)
price[key] = round(base + profitAmt + shipping[key], 2)      # shipping["default"] = 0
profit[key]= {profit_type, profit_value, total_profit: profitAmt}
```

The storefront does NOT read the cache: `Tenant\Product/ProductVariant::finalPriceForCountry()`
recomputes from `real_price` + `profit` + central shipping. Consequences:
- `profit = null` means 0% markup on the storefront.
- `real_price = 0` means a 0.00 price on the storefront.
- the cache is only used by filters/sorting, so it must always be re-derived from the three inputs.

## 2. Lifecycle

### 2.1 Central admin add/edit product (`AddEditProduct::save` -> `ProductService::save`)
1. Product + variants saved on central (`base_price`, `sale_price`, variant `price`, `weight_grams`, stock).
2. `CentralCatalogSyncObserver` reacts per model:
   - `weight_grams` changed -> `SyncProductFixedShippingCosts` (rebuild central `fixed_shipping_costs`) -> `SyncFixedShippingCostsToTenantsJob`.
   - price changed (product `base_price|sale_price`, variant `price`) -> `SyncCentralProductPriceToTenantsJob`.
   - variant stock changed -> `syncProductVariant` -> `SyncCentralProductToTenantJob`.
   - status changed -> flips `central_visible` on tenants.
3. `AddEditProduct::save` also calls `syncProduct()` -> `SyncCentralProductToTenantJob` -> `TenantCatalogSyncService::syncProductToTenant` for every tenant (create/update tenant product + variants).

### 2.2 Tenant catalog sync (`syncProducts` full sync, `syncProductToTenant` single)
- NEW tenant product/variant: seed `profit` for every key with the tenant `profit_percentage` (percentage), compute `price/sell_price` + `default_*`, copy `fixed_shipping_costs`.
- EXISTING: keep the vendor's `profit` config, re-derive `price/sell_price/default_*` from current base + shipping (so central cost changes apply). `profit = null` (legacy) is seeded like new.
- Variant `real_price` always mirrors central variant `price`.
- Orphans: variants with `central_product_variant_id IS NULL` on a central-linked, non-own product are deleted (they are corruption, see 4).

### 2.3 Price job (`SyncCentralProductPriceToTenantsJob`)
Central price changed -> for matching tenant products: re-derive product prices, **refresh variant `real_price` from the central variant `price`**, re-derive variant prices. Keeps the vendor's `profit` config; keys = union of existing keys and canonical keys (so a new shipping country gets a row).

### 2.4 Shipping job (`SyncFixedShippingCostsToTenantsJob`)
Central `fixed_shipping_costs` rebuilt -> copy to tenant product, re-derive product + variant prices (variant uses its own central costs, fallback product costs). Same key-union rule.

### 2.5 Profit % job (`ApplyTenantProfitPercentageJob` -> `ProductPriceCalculationService`)
Tenant changes global profit % -> overwrites every row with that percentage (explicit "reset all"). Already canonical shape.

### 2.6 Price-list modal (`ProductPriceListService::save`)
Vendor edits per-country profit type/value -> same formula via `TenantPricingService`.

### 2.7 Tenant product edit form (`ProductController::save` -> `TenantPanelService::saveProduct`)
Form fields: product "Sale Price" (default), per variant "Sell Price" (default), categories, badges, active, featured, translations.
Rules for **central-linked** products:
- Variants are matched by `id` / `central_product_variant_id`; **never created blank, never deleted** by the form (central sync owns that). Unmatched rows are skipped.
- `real_price`, `central_product_variant_id`, `title`, `sku`, `weight_grams` are not taken from the request (central/sync owned). `title/sku/weight` are only written when the key is present.
- Price: if the submitted default equals the stored default (2 decimals) -> **no price/profit change**. If it differs -> vendor override: `profit.default = {fixed, value = max(0, submitted - base)}`, other country rows keep their config, then `TenantPricingService::compute` rebuilds `price/sell_price/default_*/profit` for all keys.
- New central-linked product from the form: seed like sync (2.2), then apply the override rule if the posted default differs.
Own products (no central link) keep the legacy scalar behaviour (`{default: x}`, no profit).

## 3. Components

- NEW `App\Services\Tenant\TenantPricingService`: `shippingMap`, `seedProfit`, `compute`, `withDefaultOverride`, `forCatalog`, `activeCountryKeys`.
- Changed: `TenantPanelService::saveProduct`, `_variants-table.blade.php`, `TenantCatalogSyncService` (2 spots + orphan cleanup), `SyncCentralProductPriceToTenantsJob`, `SyncFixedShippingCostsToTenantsJob`, `ProductPriceListService`, `Tenant\ProductVariant::fixedShippingCostForCountry`.

## 4. Root causes found (tenant 'vendor', product 149)

1. `_variants-table.blade.php` rendered `id`, `central_product_variant_id`, `real_price`, `active` as raw `name="variants.0.id"`. PHP rewrites dots to underscores, so only `sell_price` (rendered via `x-tenant::input`, which emits `variants[0][sell_price]`) reached the server.
2. `saveProduct` then created a blank variant per row (no central link, `real_price = 0`, `profit = null`, no title/stock) and deleted the real ones. Storefront: price 0.00 and variant stock 0 -> "out of stock" and 404.
3. Because the blank rows lose `central_product_variant_id`, the next form load cannot match them, so every save repeats the damage (29 orphan rows found across products 1, 2, 145, 149, 150).
4. Even with ids fixed, a scalar `sell_price` collapsed `{52,150,184,default}` to `{default}`.
5. Sync-side drift: `default_price/default_sell_price` overwritten with `central*(1+pct)` while `price` was preserved; `profit` left null for new products; price job never refreshed variant `real_price`; jobs only produced keys present in the old `profit` JSON.

## 5. Verification

- Re-save product 149 with only a category change: variant ids/central links/`real_price`/`profit`/`sell_price` JSON unchanged.
- Change default sell price of a variant: only `profit.default` (fixed) and price keys change; other country profit rows intact.
- Change central price / shipping / weight: tenant JSON keeps `{52,150,184,default}` with `total_profit`.
- `php artisan test --filter=TenantPricingServiceTest` (formula, key union, override rule).
- Verified on tenant `vendor` product 149: `syncProductToTenant` replaced the 4 blank variants with variants linked to central 1262-1265; simulated form saves (unchanged / one variant price edited) kept ids, `real_price`, per-country `sell_price` and `profit`; price and shipping jobs are idempotent.
- Existing corrupted tenants heal on the next products sync (orphan variants deleted, blank `profit` seeded).
