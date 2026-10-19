# Order Refactor - Progress Log

> Resume instructions: read ORDER_FORMULAS.md + ORDER_REFACTOR_PLAN.md, find the first task below not marked DONE, continue.
> Always commit after each task and update this file before hitting usage limits.

| Task | Status | Commit | Notes |
|------|--------|--------|-------|
| T0 Docs | DONE | - | formulas, plan, progress |
| T1 Core (migrations, OrderPricing, calculator, models) | TODO | | |
| T2 Order creation | TODO | | |
| T3 Settlement & ledger | TODO | | |
| T4 Vendor panel | TODO | | |
| T5 Central/admin panel | TODO | | |
| T6 Storefront & themes | TODO | | |
| T7 QA | TODO | | |
| T8 Summary | TODO | | |

## Decisions / assumptions
* Item `grand_total` = sub_total + fixed_shipping - discount + tax + shipping_fee; per-item `shipping_fee` stays 0 (shipping is order-level, as today).
* `fixed_shipping` is per line, not x qty.
* Partial refunds reduce vendor share only; void orders => all figures 0.
