# Project instructions

## Orders, prices and profit calculations - READ FIRST
Before changing ANYTHING related to orders, carts, checkout, prices, discounts/coupons/flash sales, shipping charges,
taxes, platform/vendor profit, settlements, payouts, invoices, order pages (vendor panel, central/admin panel,
storefront themes) or the `orders` / `order_items` tables:

1. Read `docs/ORDER_FORMULAS.md` (the single source of truth for every formula and column).
2. Skim `docs/ORDER_REFACTOR_PROGRESS.md` (decisions, API cheat-sheet, open follow-ups).
3. Implement through `App\Support\OrderPricing` / `OrderProfitCalculator` / `OrderPayoutLedger` / `OrderComposer`;
   do not duplicate formulas inline.
4. If a formula, column or rule changes, update `docs/ORDER_FORMULAS.md` (and the progress doc) in the same commit.
