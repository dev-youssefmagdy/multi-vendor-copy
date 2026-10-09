# Cancel · Return · Exchange · Refund — Business Analysis & Execution Plan

> Run the agent prompts in **Part C** one by one, in order. Each phase depends on the previous
> one. Every agent must finish its **Definition of Done** (including its tests passing) before
> the next one starts.
>
> **Global rules for every agent**
> - Do **not** touch `resources/views/tenant/pages/finance/vendor-settle/show.blade.php` (that's the user's unrelated in-progress work).
> - Do **not** `git commit` or push.
> - Match the surrounding code style: PHP 8.3, `declare(strict_types=1)` where the neighbouring file uses it,
>   enums with `label()`, services in `app/Services`, thin controllers / Livewire components.
> - All customer- and vendor-facing strings go through `__()`. Add keys to `lang/ar.json` and `lang/fr.json`
>   (and `lang/en.json` if the file lists keys explicitly).
> - PHPUnit 12: use `#[Test]` / `#[DataProvider]` **attributes**, not docblock annotations (the docblock
>   `@dataProvider` in `PanelMutationsContractTest` is ignored by PHPUnit 12 — that's a pre-existing bug, don't copy it).
> - Tenancy: when code running in the central context needs tenant data, use `$tenant->run(fn () => ...)`
>   (it restores the previous context). Do **not** add new `tenancy()->initialize()` calls that leave
>   tenancy switched on.
> - Run `php artisan test --filter=<YourTests>` for your phase plus `php -l` on touched files, and
>   `./vendor/bin/pint --dirty` at the end of your phase.

---

## Part A — Current-state analysis (as of 2026-10-08)

### A.1 Architecture facts that drive the design
| Concern | Where it lives |
|---|---|
| Orders, order items, order activities, customers, products, stock | **Tenant DB** (`App\Models\Tenant\*`) — one DB per vendor store |
| Return requests, media, notes | **Central DB** (`App\Models\ReturnRequest*`, `CentralConnection`) — keyed by `tenant_id` + `order_number` (= order `uuid`) |
| Vendor ("Vendor") | Tenant panel (`routes/tenant_panel.php`, `app/Http/Controllers/Tenant/Panel/**`), guard `tenant` |
| Platform admin ("Admin") | Central admin (`routes/central.php`, `app/Livewire/Admin/**`), guard `admin` |
| Customer | Storefront Livewire (`app/Livewire/Tenant/Storefront/**`), guard `storefront`; JSON API `routes/api.php` |
| Payments | `App\PaymentGateway\PaymentManager` → gateways implementing `refund(string $transactionId, float $amount, string $currency, array $context)` (Stripe, PayPal, Tap, Paymob, Moyasar, MyFatoorah, etc.; others throw `PaymentException::notSupported`) |
| Stock | `App\Services\Tenant\StockService::decrementForOrder()` — called **only when an online payment is confirmed** (`PaymentController`). COD orders never decrement stock. Decrements tenant variant + product and central variant + product (`manage_stock`), bumps central `sold_count`. |
| Order lifecycle | `App\Services\Tenant\OrderLifecycleService` (activities, mails, tenant/admin notifications) |
| Return policy | `App\Services\ReturnPolicyService` (admin policy for central-catalog products, tenant policy for own products, per-product override) |
| Mail templates | `App\Services\Mail\TemplateMailService` already has `TenantOrderCancelled`, `TenantRefundProcessed`, `TenantReturnUpdate` actions |
| Storefront themes | `ecommet`, `elora`, `souqify` — order/return pages delegate to the **shared partials** in `resources/views/livewire/tenant/storefront/partials/` (`return-item-action`, `return-form-content`, `return-detail-content`). Vendor-uploaded blade themes only *require* `layout/app` + `pages/home/index`; the starter kit lives in `resources/blade-theme-starter-kit/`. |

### A.2 Gaps vs. the requirement ("the system is currently inactive")
1. **Cancel order**
   - Only `POST /api/.../orders/{uuid}/cancel` exists: it sets `status=cancelled` and does nothing else. There's no reason, no note, no confirmation, no activity log, no stock restore, no refund, no notifications.
   - No storefront UI to cancel. No vendor/admin cancel action.
   - `OrderLifecycleService::updateOrderStatus(Cancelled)` sends a *"refund processed"* email even though no refund ever happens, which is wrong.
   - `PaymentController::charge()` still lets a customer pay for a cancelled order (`where('paid', false)` only). A gateway success callback for a cancelled order would flip it to `Processing`.
2. **Status-based eligibility**: there's no single rule source. `Refunded` doesn't exist in `OrderStatus`.
3. **Return**
   - Missing **quantity**, **order item link**, **return method**, **customer notes**, **inspection** step and **resolution** (refund vs exchange).
   - `ReturnRequestService` has **no state machine**: `markRefunded()` works from `Pending`, `approve()` from `Rejected`, and so on.
   - Returned items are never restocked.
   - Return eligibility doesn't check the order status (a cancelled order could have a return), and photos are always mandatory (the spec says *"if necessary"*).
4. **Exchange** doesn't exist at all (no replacement selection, no stock check).
5. **Refund** has no entity. `return_requests.refund_amount` is just a number: there's no amount breakdown, payment method, fee, approver, request/execution dates or transaction status, and the gateway is never called. Order payment state never becomes `refunded`. A double refund can't be prevented.
6. **Notifications/visibility**: the customer can't see the cancellation reason/date or the refund status. The vendor and admin have no refunds view.

---

## Part B — Target design (business rules + technical spec)

### B.1 Glossary / actors
- **Customer**: storefront user who owns the order.
- **Vendor**: tenant panel admin (permission `sales.orders.manage` for orders, `sales.returns.manage` for returns/refunds).
- **Admin**: central platform admin (`sales.orders.manage`).
- **System**: automated actions (gateway webhooks, auto-refund).

### B.2 Order status model
`App\Enums\OrderStatus` keeps all existing cases and **adds `Refunded = 'refunded'`**.

"Confirmed / Processing" in the requirement = our `Processing` (an online payment is confirmed or a COD order is confirmed by the vendor).
`Completed` is treated like `Delivered`. `Rejected` (vendor refused the order) is treated like `Cancelled`.

`App\Enums\OrderPaymentStatus` **adds `PartiallyRefunded = 'partially_refunded'`** (`Refunded` already exists).

New tenant `orders` columns (tenant migration):
| column | type | purpose |
|---|---|---|
| `cancelled_at` | timestamp null | when it was cancelled |
| `cancellation_reason` | string(50) null | `CancellationReason` enum value |
| `cancellation_note` | text null | optional free text (required when reason = `other`) |
| `cancelled_by_type` | string(20) null | `customer` / `vendor` / `admin` / `system` |
| `cancelled_by_id` | unsignedBigInteger null | actor id in its own guard |
| `stock_deducted_at` | timestamp null | set by `StockService::decrementForOrder()`. **Backfill**: `paid = 1` → `updated_at` |
| `stock_restored_at` | timestamp null | set when stock is given back on cancel (idempotency guard) |
| `refunded_amount` | decimal(12,2) default 0 | running total of **completed** refunds |
| `refunded_at` | timestamp null | when the order became fully refunded |

### B.3 Cancellation — business rules

**B.3.1 Eligibility matrix** (`App\Services\Orders\OrderCancellationPolicy::evaluate(Order, CancellationActor): CancellationDecision`, a pure, unit-testable class)

| Order status | Customer | Vendor | Admin | Message when blocked |
|---|---|---|---|---|
| Pending | ✅ | ✅ | ✅ | — |
| Processing | ✅ **if policy allows** (`order_policy.cancellation_allow_processing` = true **and**, if `cancellation_window_hours` > 0, within N hours of order placement) | ✅ | ✅ | "This order is already being prepared and can no longer be cancelled. Please contact the store." |
| Shipped | ❌ → Return | ❌ (must go through delivery/return) | ❌ | "This order has already been shipped. Once it's delivered you can request a return." |
| Delivered / Completed | ❌ → Return | ❌ | ❌ | "This order was delivered — please request a return instead." |
| Cancelled / Rejected | ❌ | ❌ | ❌ | "This order is already cancelled." |
| Refunded | ❌ | ❌ | ❌ | "This order has already been refunded." |

`CancellationDecision` = `{ allowed: bool, code: string, message: string, suggestReturn: bool }`.

**B.3.2 Reasons** (`App\Enums\CancellationReason`, each with `label()` and `audience()`):
- Customer: `changed_mind`, `ordered_by_mistake`, `found_better_price`, `delivery_too_long`, `wrong_address_or_details`, `payment_issue`, `duplicate_order`, `other`
- Vendor/Admin: `out_of_stock`, `unable_to_fulfil`, `suspected_fraud`, `customer_request`, `pricing_error`, `other`

Validation: the reason is **required** and must be valid for the actor's audience. The note is optional (max 1000), but **required when the reason is `other`**.

**B.3.3 Cancellation transaction** (`App\Services\Orders\OrderCancellationService::cancel(Order $order, CancellationActor $actor, CancellationReason $reason, ?string $note): Order`)
1. `DB::transaction` + `lockForUpdate()` on the order row. Re-evaluate the policy inside the lock (to stop double submits); throw `OrderActionException` (domain exception with a user-safe message) if not allowed.
2. Update the order: `status=Cancelled`, `cancelled_at=now()`, reason, note, `cancelled_by_*`.
3. Activity: title `Order cancelled`, description `Cancelled by {actor label}. Reason: {reason label}. {note}`.
4. **Stock**: if `stock_deducted_at !== null && stock_restored_at === null` → `StockService::restoreForOrder($order)` (an exact mirror of decrement: tenant variant + product, central variant + product when `manage_stock`, `sold_count -= qty` floored at 0, then dispatch `SyncCentralProductStockToTenantsJob`) → set `stock_restored_at`. If stock was never deducted (unpaid / COD), nothing is restored.
5. **Refund**: if `paid = true` → `RefundService::requestForCancellation($order, $actor)` creates a `Refund` for `grand_total − refunded_amount` with fee 0 and `source=cancellation`. If the tenant policy `order_policy.auto_refund_on_cancel` (default **true**) is on and the gateway supports refunds, it executes immediately (after commit). Otherwise it stays `pending` for the vendor/admin to process.
6. **Notifications** (after commit): tenant notification (vendor), admin notification, customer email `TenantOrderCancelled` (with `{{cancellation_reason}}` = reason label + note). Remove the incorrect "refund processed" email from `updateOrderStatus()` for cancellations; the refund email is sent by `RefundService` only when a refund actually completes.
7. **Blocking conflicting actions**
   - `OrderLifecycleService::updateShippingStatus()` already rejects Cancelled/Rejected. Extend it to also reject `Refunded`.
   - Route `OrderShippingStatus::Cancelled` through `OrderCancellationService` (actor = vendor, reason = `unable_to_fulfil`) instead of a bare status flip, so stock and refund stay consistent.
   - `OrderLifecycleService::updateOrderStatus()` to `Cancelled` must delegate to `OrderCancellationService`.
   - `PaymentController::charge()`: refuse cancelled/rejected/refunded orders.
   - `PaymentController::success()` / webhook: if the payment confirmation arrives for an order that's **already cancelled**, record the payment (`paid=true`, payment_details), **don't** change the status, and immediately create + execute a cancellation refund. Log an activity "Payment received after cancellation — refund initiated".
8. Split checkout (`order_group_uuid`): each order is cancelled independently. The refund amount is that order's own `grand_total` (a partial refund of the shared gateway transaction).

**B.3.4 Customer visibility**: the order page shows a red "Cancelled" banner with the **reason label**, the **note**, the **cancellation date/time** and who cancelled it ("You" / "The store" / "Support"), plus a refund status card if a refund exists.

### B.4 Refund — entity & rules

**Central table `refunds`** (central DB, so admin can see every refund across tenants, like `return_requests`):
| column | type | notes |
|---|---|---|
| `id` | pk | |
| `reference` | string unique | `RF-{YYYYMMDD}-{random6}`, shown to the customer |
| `tenant_id` | string(36) idx | |
| `order_number` | string idx | order uuid |
| `return_request_id` | fk null → return_requests | set for return refunds |
| `source` | string(20) | `RefundSource`: `cancellation` / `return` / `manual` |
| `reason` | string(255) | human reason (cancellation reason label / return reason label / free text) |
| `currency` | string(3) default `USD` | payments are charged in USD (see `PaymentController::charge`) |
| `items_amount` | decimal(12,2) | value of the refunded goods |
| `shipping_amount` | decimal(12,2) default 0 | shipping refunded |
| `return_fee` | decimal(12,2) default 0 | deducted fee |
| `amount` | decimal(12,2) | **final refunded amount** = items + shipping − fee (≥ 0) |
| `payment_method` | string null | original `orders.payment_method` (e.g. `stripe`, `cod`) |
| `gateway` | string null | gateway code used to pay |
| `original_transaction_id` | string null | from `orders.payment_details.transaction_id` |
| `refund_method` | string(20) | `RefundMethod`: `original_payment` / `manual` (bank transfer / cash / store credit, used for COD or unsupported gateways) |
| `gateway_refund_id` | string null | id returned by the gateway |
| `status` | string(20) idx | `RefundStatus`: `pending` → `processing` → `completed`, or `failed`, or `rejected` |
| `failure_reason` | text null | |
| `requested_by_type` / `requested_by_id` | | who created it |
| `approved_by_type` / `approved_by_id` / `approved_by_name` | | **who approved** (name is denormalised for display) |
| `requested_at` | timestamp | **request date** |
| `approved_at` | timestamp null | |
| `processed_at` | timestamp null | **execution date** (completed or failed) |
| `notes` | text null | internal |
| `meta` | json null | raw gateway response, item breakdown |
| timestamps | | |

**Rules (`App\Services\Refunds\RefundService`)**
- **No double refund**: `sum(amount where status in pending/processing/completed) + newAmount ≤ order.grand_total` (rounded to cents). Refused if the order status is `Refunded`. Row-level lock on the order inside the tenant transaction.
- Unpaid orders (`paid=false`) can't be refunded.
- `execute(Refund)`:
  - `refund_method=original_payment`: call `PaymentManager::gateway($gateway)->refund($transactionId, $amount, $currency, ['order_id' => ...])` **inside the tenant context**.
    - Success → `completed` + `gateway_refund_id` + `processed_at`.
    - `PaymentException::notSupported` or another failure → `failed` with `failure_reason`; the vendor/admin can then **retry** or **complete manually** (`refund_method=manual`, with an optional external reference saved in `meta.manual_reference`).
  - `refund_method=manual`: only completes through an explicit `markCompletedManually()` by the vendor/admin.
- On `completed`:
  - `orders.refunded_amount += amount`.
  - If `refunded_amount >= grand_total` → order `status=Refunded` (only if the order isn't `Cancelled`; a cancelled order stays `Cancelled`, and its payment state shows refunded) and `refunded_at=now()`.
  - Order activity "Refund completed — {amount} ({reference})".
  - Customer email `TenantRefundProcessed`, plus tenant and admin notifications.
  - Linked return request → `Refunded` (see B.5).
- `reject(Refund, reason)`: only from `pending`/`failed`. It's recorded with a reason and visible to the customer.
- **Derived payment status** (`Order::paymentState()`, used by the panel, admin aggregate and storefront): `refunded_amount ≥ grand_total` → `Refunded`; `refunded_amount > 0` → `PartiallyRefunded`; otherwise the existing logic.
- **Financial impact**: orders whose status is `Cancelled`/`Refunded` and paid amounts that were refunded must be excluded from tenant payouts, owner profit, the vendor settlement "amount due" and revenue KPIs. The Phase 2 agent must audit `TenantLedgerService`, `OrderProfitCalculator`, `TenantAdminAggregateService`, `TenantPanelRepository::orderStats()` and the vendor-settlement queries, and subtract `refunded_amount` / exclude cancelled orders wherever "collected" money is summed.

**Refundable amount calculator** (`App\Services\Refunds\RefundCalculator`, pure)
- `forCancellation(Order)`: items = subtotal − order discount + tax, shipping = shipping charge, fee = 0 → `amount = grand_total − refunded_amount`.
- `forReturn(Order, OrderItem, int $qty, ReturnReason $reason, float $policyFee)`:
  - unit net = (`item.sub_total` − `item.discount` + `item.tax`) / `item.qty`, minus the item's prorated share of the order-level discount (`discount_percentage`), plus the prorated order-level tax.
  - items_amount = unit net × qty (round 2).
  - shipping_amount = 0 by default. If the reason is **seller fault** (`defective`, `wrong_item`, `not_as_described`) **and** the whole order is being returned, refund the shipping too.
  - return_fee = policy fee, **waived for seller-fault reasons**.
  - amount = max(0, items + shipping − fee), capped at the remaining refundable amount on the order.
- The reviewer may **lower** the amount (partial refund after inspection) but never raise it above the calculated maximum.

### B.5 Return — workflow & rules

**New/changed columns on `return_requests`** (central migration):
`type` (`ReturnType`: `return` / `exchange`, default `return`), `order_item_id` (unsignedBigInteger null), `quantity` (unsigned int default 1), `return_method` (`ReturnMethod`: `courier_pickup` / `drop_off` / `ship_back`), `customer_note` (text null), `inspection_result` (`InspectionResult`: `passed` / `partial` / `failed`, null), `inspection_notes` (text null), `inspected_at`, `received_at`, `restocked_at` (timestamps null), `replacement_product_variant_id` (null), `replacement_quantity` (null), `exchange_tracking_number` (null), `exchange_shipped_at`, `exchange_completed_at`, `cancelled_at` (customer withdrew), `reviewed_by_type` (string null, so a *vendor* reviewer can be recorded as well as `reviewed_by_admin_id`), `reviewed_by_id`.

**Status machine** (`App\Enums\ReturnStatus`; add `Inspected`, `ExchangeShipped`, `Exchanged`, `Cancelled`; keep the existing values). Add `allowedTransitions()` / `canTransitionTo()` and make **every** `ReturnRequestService` mutator assert it (throwing a domain exception with a friendly message):

```
Pending ─────────────┬─> AwaitingMerchantReview ─┐   (admin forwards to vendor)
   │  ▲              │                            │
   │  └── AwaitingInfo <──────────────────────────┤   (reviewer asks for info; customer reply → Pending)
   ├─> Approved ──> ItemReceived ──> Inspected ──┬─> Refunded            (type=return, or exchange fallback)
   │                                              ├─> ExchangeShipped ──> Exchanged   (type=exchange)
   │                                              └─> Rejected            (inspection failed)
   ├─> Rejected
   └─> Cancelled (customer withdraws: only from Pending / AwaitingInfo / AwaitingMerchantReview)
Refunded / Exchanged / Rejected / Cancelled ──> Closed (optional archival)
```

**Creation rules** (`ReturnRequestService::create`, plus `ReturnRequestValidationService` for inline pre-checks; both must enforce the same rules):
1. The order belongs to the customer and its status is `Delivered` or `Completed` (otherwise: Shipped → "You can request a return once the order is delivered"; Cancelled/Refunded → not eligible).
2. Within the policy window (existing logic) and the product is returnable (existing logic).
3. `order_item_id` belongs to the order. `1 ≤ quantity ≤ item.qty − (qty already in returns that aren't Rejected/Cancelled for that item)`.
4. Only **one open** request per order item at a time.
5. Reason required. Description is required (min 10 chars) for seller-fault reasons and optional otherwise (max 2000).
6. **Photos**: required (≥ 1) for seller-fault reasons (`ReturnReason::requiresPhotos()`), optional otherwise. Video rule stays as it is (policy-driven).
7. `return_method` required. `customer_note` optional (max 1000).
8. `type=exchange` → see B.6.
9. After creation: a system note on the request, a tenant + admin notification, a customer email (`TenantReturnUpdate`), and an order activity "Return requested for {qty} × {product}".

**Transition side-effects**
- `approve` (vendor or admin; records reviewer type/id): customer email with return instructions per `return_method`. For an exchange, **re-verify and reserve replacement stock** (B.6).
- `reject(reason)`: reason required, visible to the customer. For an exchange, release any reserved replacement stock.
- `markItemReceived`: sets `received_at`.
- `inspect(result, notes, restock: bool)`: sets the inspection fields.
  - `passed`/`partial` + restock → `StockService::restockItem(variant/product, quantity)` (tenant + central when `manage_stock`), once only (`restocked_at`).
  - `failed` → the request can only go to `Rejected` (with the inspection notes as the reason) — or the reviewer may still issue a partial refund.
- From `Inspected`:
  - `type=return` → `issueRefund(amount ≤ max)` creates a `Refund` (source `return`, linked) through `RefundService`, and executes it per B.4. When the refund **completes**, the request moves to `Refunded`. While the refund is pending/failed the request stays `Inspected`, and the UI shows the refund status.
  - `type=exchange` → `markExchangeShipped(tracking)` → `ExchangeShipped`, then `markExchangeCompleted()` → `Exchanged`. If the replacement can't be fulfilled, the reviewer can switch to a refund (`convertToRefund`), which releases the reserved stock and follows the return path.
- `cancelByCustomer`: allowed from Pending / AwaitingInfo / AwaitingMerchantReview.
- Every transition: a customer-visible system note, a notification fan-out (existing `notify()`), and an order activity in the tenant DB.

### B.6 Exchange — rules
- Same product only. The replacement is a **different active variant of the same product** (size/colour). If the product has no other variants, exchange is not offered (the UI hides the option).
- **Price parity**: the replacement variant's effective unit price must equal the original line's unit price. Otherwise the request is refused with "This option has a different price — please return the item and place a new order." This avoids charging or paying price differences.
- **Stock check** at request time **and** again at approval, using the same rules as `ChecksCartStock::checkProductStock` (null stock = unlimited for own products; central `manage_stock`). Error: "Only N left of {variant}".
- **Reservation**: on approval, decrement the replacement variant stock by `replacement_quantity` (tenant + central, mirroring `StockService`). Release it (increment back) on reject / convert-to-refund / customer cancel after approval.
- `replacement_quantity` = `quantity`.
- The returned item is restocked on inspection (B.5), like a normal return.

### B.7 Policy settings (tenant, `settings` table group `order_policy`; managed on the existing **Settings → Return Policy** page as a new "Cancellation & Refunds" card)
| key | type | default |
|---|---|---|
| `cancellation_allow_processing` | bool | `true` |
| `cancellation_window_hours` | int (0 = no limit) | `0` |
| `auto_refund_on_cancel` | bool | `true` |
| `exchange_enabled` | bool | `true` |
| `restock_returned_items` | bool | `true` (default for the inspection "restock" checkbox) |

Read through one `App\Services\Orders\OrderPolicyService` (cached per request), with defaults when a setting is missing.

### B.8 Surfaces (UI) — what each actor sees

**Customer (storefront, all 3 themes plus the blade-theme starter kit)**
- Order page: a **"Cancel order"** button when the policy allows it. It opens a modal with a required reason `<select>`, an optional note `<textarea>` (required when "Other"), and then a **confirmation step** ("Are you sure you want to cancel order #…? This cannot be undone.") with Confirm and Back buttons. On success, a toast and the page re-renders with the cancelled banner.
- For Shipped orders: an info note "Already shipped — you can request a return after delivery".
- A cancelled banner (reason, note, date, who cancelled). A **refund card**: reference, amount, method, status badge (Pending / Processing / Completed / Failed / Rejected), requested and completed dates.
- Return form (extended): quantity stepper (max = remaining), resolution radio **Refund / Exchange** (Exchange only if enabled and other same-price variants exist), replacement variant select showing live stock, return method radio, reason, description, photos (the required marker follows the reason), video, additional notes, and an **estimated refund preview** (calculator).
- Return detail: a timeline of the status machine, type, qty, method, replacement, inspection result (customer-friendly), refund card, exchange tracking number, and a "Withdraw request" button while allowed.
- Implementation constraint: keep all new markup inside **shared partials** under `resources/views/livewire/tenant/storefront/partials/`. New partials are `order-cancel-action.blade.php`, `order-cancellation-summary.blade.php` and `order-refunds-summary.blade.php`, included from each theme's existing `order-status.blade.php`. **Don't** add required files to the blade-theme contract. Per the blade-theme rule: update `resources/blade-theme-starter-kit/pages/order-status.blade.php` (+ README) to show how to include the new partials. The change is additive and non-breaking, so vendor themes don't need to be disabled; document the new optional variables in the README.
- API (`routes/api.php`):
  - `POST /orders/{uuid}/cancel` now requires `reason` (plus optional `note`) and uses the service, returning 422 with the policy message.
  - `GET /orders/{uuid}` adds `can_cancel`, a `cancellation{reason, reason_label, note, cancelled_at, cancelled_by}` block and `refunds[]`.
  - New `GET /orders/cancellation-reasons`.

**Vendor (tenant panel)**
- Order show: a "Cancel order" action (vendor reasons modal, policy-guarded); a cancellation panel; a **Refunds panel** listing the order's refunds with Retry, Mark completed manually (reference) and Reject actions; and a "Refund remaining" manual refund (amount ≤ remaining, reason required) for paid orders.
- Returns show: shows type, qty, method, notes, replacement variant with live stock, inspection form (result, notes, restock checkbox), Issue refund (prefilled with the calculator max, editable down), Exchange shipped (tracking), Exchange completed, and Convert to refund. Buttons appear **only** when the transition is allowed.
- New **Sales → Refunds** list (DataTable + status filter + stat cards: total refunded, pending, failed) at `/refunds` (`tenant.refunds.index` / `.data`), permission `sales.returns.manage`. Add it to the panel navigation next to Returns.
- Orders list: status filter includes `Refunded`, and the payment column shows Refunded / Partially refunded.
- Settings → Return Policy: the new "Cancellation & Refunds" card (B.7).

**Admin (central)**
- Order detail (`OrderDetailPage`): cancellation info, a refunds panel (same actions as the vendor's), and a Cancel order action (admin reasons).
- Return detail (`OrderReturnDetailPage`): the same new transitions as the vendor, recorded as an admin reviewer.
- New **Orders → Refunds** list page (`/orders/refunds`, Livewire, filters tenant/status/source, CSV export via `HasCsvExport`). Add it to the sidebar.
- `TenantAdminAggregateService::mapPaymentStatus()` reflects refunded / partially refunded. The order status donut includes Refunded.

### B.9 Notifications matrix
| Event | Customer email | Vendor (tenant notification) | Admin (admin notification) |
|---|---|---|---|
| Order cancelled (any actor) | `TenantOrderCancelled` (reason, date) | ✅ | ✅ |
| Refund created (pending) | — (visible on the order page) | ✅ | ✅ |
| Refund completed | `TenantRefundProcessed` (amount, reference, method) | ✅ | ✅ |
| Refund failed | — | ✅ (action needed) | ✅ |
| Return created / each transition | `TenantReturnUpdate` (status + message) | ✅ | ✅ |
| Exchange shipped | `TenantReturnUpdate` with tracking | ✅ | ✅ |

### B.10 Out of scope (recorded as recommendations, not implemented)
- COD orders don't reserve or decrement stock at placement, which allows overselling. That's a separate inventory decision. The cancel/return logic stays correct either way because restores are tracked with `stock_deducted_at` / `stock_restored_at`.
- Store credit / wallet refunds, price-difference exchanges, cross-product exchanges, and courier pickup booking integrations.

---

## Part C — Agent execution prompts (run sequentially)

### Phase 1 — Foundation: enums, migrations, models, stock restore, policy service
```
You are a senior Laravel engineer. Read RETURN_EXCHANGE_REFUND_PLAN.md fully (Parts A and B, plus the global rules at the top). Implement ONLY Phase 1:

1. Enums in app/Enums (with label(); color() where a status badge needs it):
   - OrderStatus: add Refunded.
   - OrderPaymentStatus: add PartiallyRefunded.
   - New CancellationReason (with audience(): 'customer'|'staff'|'both', plus static forCustomer()/forStaff()).
   - New CancellationActor (customer, vendor, admin, system), RefundStatus, RefundSource, RefundMethod, ReturnType, ReturnMethod, InspectionResult.
   - ReturnStatus: add Inspected, ExchangeShipped, Exchanged, Cancelled. Add allowedTransitions(), canTransitionTo(self), and update isOpen()/color()/label().
   - ReturnReason: add isSellerFault() and requiresPhotos().
   Grep for every `match` on OrderStatus / ReturnStatus / OrderPaymentStatus across app/ and resources/views and make them exhaustive (match throws UnhandledMatchError on new cases). This includes TemplateMailService, status-badge components, theme order-status pages, OrderRepository and TenantAdminAggregateService.
2. Migrations:
   - Tenant (database/migrations/tenant): the orders columns in B.2, including the backfill of stock_deducted_at for paid orders.
   - Central (database/migrations): the `refunds` table (B.4) and the return_requests columns (B.5).
   Add sensible indexes. All migrations must be reversible.
3. Models:
   - Tenant Order: fillable + casts for the new columns; add `order_group_uuid` to fillable if it's missing; relations/helpers isCancelled(), paymentState(), remainingRefundable().
   - New central App\Models\Refund (CentralConnection) with casts to the enums and a scope forOrder($tenantId, $orderNumber).
   - ReturnRequest: fillable/casts/relations (refunds()).
4. StockService:
   - decrementForOrder() sets orders.stock_deducted_at and becomes a no-op if it's already set (idempotent).
   - Add restoreForOrder(Order) (exact mirror, idempotent via stock_restored_at, sold_count floored at 0, dispatch the sync job).
   - Add restockItem(OrderItem|variant/product, int qty) and reserveVariant()/releaseVariant() for exchanges.
   Refactor the shared SQL into private helpers. Keep the GREATEST(0, …) semantics for decrements.
5. App\Services\Orders\OrderPolicyService (B.7) with defaults.
6. Tests:
   - Unit tests for ReturnStatus transitions, CancellationReason audiences and ReturnReason helpers.
   - Feature tests (reuse tests/Feature/Tenant/Concerns/SetsUpTenantPanel; put a new reusable trait tests/Feature/Tenant/Concerns/BuildsOrders.php in that folder that creates a customer, an own product with a variant + stock, and an order with items in a given status/paid state) proving decrement → restore round-trips stock exactly and is idempotent.
   Note: tenant bootstrapping costs ~45s per test, so consolidate scenarios into few test methods.
7. Run the migrations against the test DB via the tests, run your tests and pint. Report the files changed and the test output.
```

### Phase 2 — Refund engine
```
Read RETURN_EXCHANGE_REFUND_PLAN.md (global rules, B.4, B.9). Phase 1 is done: inspect the enums/models/migrations it created before you start. Implement ONLY Phase 2:

1. App\Services\Refunds\RefundCalculator (pure): forCancellation() and forReturn(), exactly per B.4.
2. App\Services\Refunds\RefundService:
   - create(...) (generic, with the no-double-refund guard under an order row lock in the tenant DB)
   - requestForCancellation(Order, CancellationActor)
   - requestForReturn(ReturnRequest, float $amount, actor)
   - execute(Refund), retry(Refund), markCompletedManually(Refund, actor, ?reference), reject(Refund, actor, reason)
   - private complete() and fail()
   It must work from BOTH the tenant context and the central context: wrap tenant work in $tenant->run(). Gateway calls go through App\PaymentGateway\PaymentManager inside the tenant context. Catch PaymentException and \Throwable → failed with a safe failure_reason (log the raw error). Notifications and emails go out after commit (B.9), reusing TemplateMailService::sendTenantRefundProcessed (extend its placeholders with amount/reference/method if useful) and the Tenant/Admin notification services.
3. Order payment-state propagation (refunded_amount, refunded_at, status Refunded rules, activity entries).
4. Financial audit (B.4 "Financial impact"): find every place that sums paid/collected revenue, owner profit, tenant payout or vendor settlement (TenantLedgerService, OrderProfitCalculator, TenantAdminAggregateService incl. mapPaymentStatus, TenantPanelRepository stats, VendorSettlement services, OrderRepository report). Make cancelled orders and refunded amounts excluded/subtracted consistently. List each change with a one-line justification in your report.
5. Tests:
   - Unit: RefundCalculator — many cases incl. order discount proration, seller-fault fee waiver, caps.
   - Feature, with a fake gateway bound in the container (implement PaymentGatewayInterface, configurable success/failure/notSupported):
     full refund → order Refunded + refunded_amount; partial refunds; double-refund blocked; gateway failure → failed → retry succeeds; manual completion; reject; unpaid order refusal.
   Run the tests and pint; report.
```

### Phase 3 — Cancellation flow (backend + API)
```
Read RETURN_EXCHANGE_REFUND_PLAN.md (global rules, B.3, B.9). Phases 1–2 are done; read their services first. Implement ONLY Phase 3 (backend + API, no Blade UI):

1. App\Services\Orders\OrderCancellationPolicy (pure evaluate() → CancellationDecision value object) implementing the B.3.1 matrix incl. processing window/policy flags.
2. App\Services\Orders\OrderCancellationService::cancel() implementing B.3.3 steps 1–6 exactly (lock, update, activity, stock restore, refund request + auto execute after commit, notifications). Create App\Exceptions\OrderActionException (user-safe message, 422).
3. Integrations (B.3.3 step 7): OrderLifecycleService (updateOrderStatus / updateShippingStatus delegate cancellations; drop the wrong refund email; block Refunded), PaymentController charge() guard, success() + webhook late-payment-after-cancel handling, OrdersController (tenant panel) shipping-status Cancelled path.
4. Endpoints:
   - API OrderController::cancel (reason/note validation via a FormRequest, policy message on 422); show() payload additions; GET cancellation-reasons route.
   - Tenant panel: POST /orders/{id}/cancel (+ /validate, matching the existing *.validate convention in routes/tenant_panel.php) and refund actions POST /refunds/{id}/retry|complete|reject and POST /orders/{id}/refunds (manual refund), all JSON via PanelController success()/failure(), permission sales.orders.manage / sales.returns.manage.
   Only backend here; Phase 6 builds the views.
5. Tests:
   - Unit: a policy-matrix DataProvider covering every OrderStatus × actor × policy flag.
   - Feature:
     - customer cancels a Pending unpaid order (no refund, no stock change)
     - customer cancels a Processing paid order (stock restored, refund auto-completed via the fake gateway, order shows refunded payment)
     - Shipped → 422 with the return message
     - double cancel → 422
     - reason required + note required for 'other'
     - vendor cancel path
     - shipping-status Cancelled path goes through the service
     - charge() refuses a cancelled order
     - payment-after-cancel triggers a refund
     - notifications recorded (Mail::fake / notification tables)
   Run and report.
```

### Phase 4 — Return & Exchange backend
```
Read RETURN_EXCHANGE_REFUND_PLAN.md (global rules, B.5, B.6, B.9). Phases 1–3 are done; read their code first. Implement ONLY Phase 4 (backend):

1. Refactor ReturnRequestService:
   - Every mutator asserts ReturnStatus::canTransitionTo and records the reviewer type/id (vendor vs admin). Change the signatures to take an actor (CancellationActor or a small ReviewerContext value object) instead of ?int $adminId, and update ALL callers (tenant ReturnController, admin OrderReturnDetailPage, storefront components).
   - Add the new operations: inspect, issueRefund (via RefundService), markExchangeShipped, markExchangeCompleted, convertToRefund, cancelByCustomer.
   - Hook the RefundService completion → request Refunded.
   - Replace deliveredAt()/policy calls that call tenancy()->initialize() without restoring with $tenant->run() (or restore the previous tenant), without breaking existing callers.
2. Creation rules B.5 1–9 in create() AND ReturnRequestValidationService (shared private rule methods; no duplicated logic drift): quantity/remaining qty, order status, open-request uniqueness, photos-if-necessary, return_method, customer_note, type exchange rules B.6 (same product, other active variant, price parity, stock check), and the order activity in the tenant DB.
3. Exchange reservation/release via the StockService helpers (approve / reject / convert / cancel).
4. Restock on inspection (once, restocked_at, respecting restock flag/default policy).
5. Add an endpoint-free helper `ReturnRequestService::availableActions(ReturnRequest, actorType): array` used by all UIs to decide which buttons to show.
6. Tests (feature, fake gateway):
   - Full return lifecycle: Pending → Approved → ItemReceived → Inspected(passed, restock) → refund completes → Refunded. Assert the stock increments, the refund row fields (amount breakdown, approver, dates, status), the order refunded_amount/partially refunded, and the notes/activities.
   - Rejection path; AwaitingInfo loop.
   - Illegal transition throws.
   - Quantity limits across two requests.
   - Exchange: unavailable stock refused; price mismatch refused; approve reserves; reject releases; shipped → Exchanged.
   - Customer withdraw.
   - Non-delivered order refused.
   Run and report.
```

### Phase 5 — Storefront UI (customer) + themes + translations
```
Read RETURN_EXCHANGE_REFUND_PLAN.md (global rules, B.3.4, B.8 Customer, B.10). Also read the memory rule about blade theme structure changes in the plan (B.8 constraint). Phases 1–4 are done.

Implement ONLY Phase 5:
1. OrderStatusPage (Livewire):
   - cancel modal state (step 1 reason/note → step 2 confirm), cancelOrder() using OrderCancellationService with CancellationActor::Customer, friendly errors via the existing 'order-status-swal' event
   - pass canCancel / cancelDecision, cancellation summary, refunds, and the order-level paymentState to the views.
2. New shared partials in resources/views/livewire/tenant/storefront/partials/: order-cancel-action, order-cancellation-summary, order-refunds-summary. Include them from ecommet, elora and souqify pages/order-status.blade.php (and order-tracking if it shows the status). Match each theme's visual language (inspect its existing markup; ecommet uses #FF4D00 accents, etc.). They must be accessible (labels, focus, ESC closes the modal) and RTL-safe (use logical spacing such as ms-/me- or the theme's existing convention).
3. RequestReturnForm + return-form-content partial: quantity, type (refund/exchange + replacement variant with live stock), return method, conditional photo requirement, customer_note, estimated refund preview from RefundCalculator.
4. ReturnDetailPage + return-detail-content: timeline, type/qty/method/replacement/inspection result/exchange tracking, refunds card, withdraw button (cancelByCustomer).
5. return-item-action partial: respect remaining qty and the open-request rule; show "Return after delivery" for shipped orders; hide for cancelled/refunded orders.
6. Blade-theme starter kit: update pages/order-status.blade.php and pages/order-return.blade.php + README per the rule (additive, document the new optional variables, no new required files). Verify BladeThemeService::REQUIRED_FILES is unchanged.
7. Translations: every new __() key goes into lang/ar.json and lang/fr.json with proper Arabic/French translations.
8. Tests: Livewire feature tests (Livewire::test against the components inside a tenant context) for:
   - the cancel flow (reason validation, confirm step required, success re-render shows the reason and date)
   - return form submission incl. exchange
   - withdraw
   - views render for all three themes without errors for orders in each status (Pending, Processing, Shipped, Delivered, Cancelled with refund, Refunded).
   Run and report.
```

### Phase 6 — Vendor panel UI
```
Read RETURN_EXCHANGE_REFUND_PLAN.md (global rules, B.7, B.8 Vendor). Phases 1–5 are done; Phase 3 created the panel endpoints. Study the existing panel conventions first: resources/views/tenant/pages/sales/orders/show.blade.php, returns/show.blade.php, PanelController JSON responses, *.validate routes, Metric::cards, TableColumn, and the panel JS form helpers used by returns/show.

Implement ONLY Phase 6:
1. Order show: cancel action + modal (staff reasons, note), cancellation panel, refunds panel (retry / complete manually with reference / reject with reason / manual refund ≤ remaining), payment state badge. Hide shipping-status controls for cancelled/refunded orders.
2. Returns show: render new fields and drive the action buttons from ReturnRequestService::availableActions(): inspect form, issue refund (prefilled max, editable down, server enforces the cap), exchange shipped (tracking), exchange completed, convert to refund. Add backend routes + controller methods + FormRequests (+ /validate twins) for any return actions not yet exposed.
3. Sales → Refunds list page (controller, DataTable data endpoint, _cols partials, stats) + navigation entry + permission.
4. Orders list: Refunded filter + payment state column.
5. Settings → Return Policy page: the "Cancellation & Refunds" card bound to the order_policy settings (validate + update).
6. Add the new panel routes to tests/Feature/Tenant/PanelRouteCoverageTest / ValidateRoutesTest expectations if those tests enumerate routes. Write feature tests hitting each new panel endpoint (happy path + validation + permission) and asserting the show pages render for orders/returns in every relevant status. Run and report.
```

### Phase 7 — Central admin UI
```
Read RETURN_EXCHANGE_REFUND_PLAN.md (global rules, B.8 Admin). Phases 1–6 are done. Study app/Livewire/Admin/Order/* and their views, and InteractsWithAdminUi / HasCsvExport.

Implement ONLY Phase 7:
1. OrderDetailPage: cancellation info, admin cancel action (staff reasons → OrderCancellationService with CancellationActor::Admin, run inside $tenant->run()), refunds panel with the same actions as the vendor's.
2. OrderReturnDetailPage: new transitions via ReturnRequestService::availableActions(), as an admin reviewer.
3. New Livewire RefundsList page at /orders/refunds (route name orders.refunds.index, registered BEFORE the /{tenantId}/{orderNumber} catch-all), filters (tenant, status, source, date range), stats, CSV export, link to the order and return. Add a sidebar entry with the existing permission scheme.
4. TenantAdminAggregateService / OrderRepository: refunded and partially refunded payment states, Refunded in the status options and donut, cancelled_orders count unaffected.
5. Feature tests: admin cancel, admin refund actions, return transitions as admin, the refunds list renders + filters + CSV export. Run and report.
```

### Phase 8 — End-to-end lifecycle verification & hardening
```
Read RETURN_EXCHANGE_REFUND_PLAN.md entirely. All phases are implemented. You are the QA lead + reviewer.

1. Write tests/Feature/Tenant/OrderAfterSalesLifecycleTest.php covering the full requirement end to end against real services (fake gateway only). Each scenario asserts the DB state for order, items stock (tenant + central where relevant), refunds row (every B.4 field: amount, reason, payment method, fee, approver, requested/processed dates, status), return request, activities, notifications and emails:
   a) Pending COD order → customer cancels with reason + note → Cancelled, no refund, no stock change, the customer page shows the reason + date.
   b) Paid Processing order → customer cancels → stock restored exactly once, refund completed, payment state refunded, vendor + admin notified.
   c) Processing with policy cancellation_allow_processing=false → customer blocked, vendor allowed.
   d) Shipped → cancel blocked with the return message; Delivered → cancel blocked; Cancelled → blocked; Refunded → blocked and refund blocked.
   e) Delivered paid order with 3 units → return 2 (defective, photos, courier_pickup) → approve → received → inspect passed + restock → refund = calculator amount with fee waived → order PartiallyRefunded; then return the last 1 (changed_mind, fee applied) → order Refunded; a 4th return is refused (no qty left).
   f) Exchange happy path + stock-unavailable path + price-mismatch path.
   g) Gateway failure → refund failed → vendor retry → completed; manual completion path for COD.
2. Run the WHOLE suite (`php artisan test`). For any failure, decide whether it's caused by this work (fix it) or pre-existing (list it with evidence, e.g. the PHPUnit 12 @dataProvider issue).
3. Review the diff (`git diff --stat` + read the key files) for: missing transaction/lock, tenancy context leaks, N+1 queries in the lists, unescaped output in DataTable columns, authorization (customer can only act on own orders/returns; tenant only on its tenant_id), and missing translations (compare the new __() keys against lang/ar.json and lang/fr.json). Fix what you find.
4. Run pint on the dirty files. Produce a final report: the lifecycle matrix (requirement item → implemented where → test that proves it), the files changed, the full test results, and the known limitations (B.10).
```
