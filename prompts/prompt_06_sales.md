# Prompt 06 — Sales: Orders, Returns, Customers

Requires prompts 01–04 (return analytics reuses the prompt 04 insight engine). Follow the **Standard Conversion Recipe** (prompt 00).
Controllers: `app/Http/Controllers/Tenant/Panel/Sales/`. Views: `resources/views/tenant/pages/sales/`. JS: `resources/js/tenant/pages/sales/`.
Keep middleware exactly:
- orders index: `tenant.permission:sales.orders.view` + `tenant.setup:payment_gateway`
- order show: `sales.orders.view`
- returns and return-policy: `sales.returns.manage`
- customers: `sales.customers.manage`

---

## 1. Orders list — `tenant.orders.index` (`Order\OrdersList`)
- Stats (4 cards, identical to `pageData()`): Orders, Paid, Processing, Collected (currency), from `orderStats()`.
- Filters:
  - `search` ("Order UUID or customer")
  - `status` (select2 from `OrderStatus::cases()` → `label()`, "All")
- Columns: Order, Customer, Value, Commission, Status, Gateway, Placed At, Actions. Default order is Placed At desc.
- **Port each cell from the current `rows` HTML into `_cols` partials**:
  - Order: uuid + "N items · M units"
  - Customer: name/Guest + email/"No email"
  - Value: grand total + "x% off · ship y"
  - Commission: `OrderProfitCalculator::effectiveTenantProfitForOrder()` + "platform" `effectiveOwnerProfitForOrder()`
  - Status: `x-tenant::status-badge`
  - Gateway: headline(payment_method) + Paid/Unpaid
  - Placed At: `M d, Y H:i`
  - Actions: View
- `data()` → `DataTables::eloquent($repo->queryOrders($filters))`, where `queryOrders` = the public wrapper of `buildOrdersQuery` (it already eager-loads `customer`, `items.product`, `items.variant.*`, `paymentGateway` and has `withCount('items')`).
  - `orderColumn('value', 'grand_total $1')` and `orderColumn('placed_at', 'created_at $1')`.
  - Commission and Gateway are not orderable.
- `export()` streams the **same** `exportHeaders()` / `exportRows()` (12 columns) using `exportOrders($filters)`. File name `orders-Y-m-d.csv`.

## 2. Order detail — `tenant.orders.show` (`Order\OrderDetailPage` → `order/order-detail-page.blade.php` + `order/partials/order-details.blade.php`)
- `show(int $orderId)`: `Order::query()->findOrFail($orderId)` + the same eager loads the component uses. Render `tenant.pages.sales.orders.show`.
  - Port `order-details.blade.php`: kv panels, line items as `x-tenant::table` (breakdown), `x-tenant::json-tree` for payloads.
- `updateShippingStatus()` → `PATCH orders/{orderId}/shipping-status` → `updateShippingStatus(UpdateShippingStatusRequest)`.
  - Rule: `shipping_status` in the allowed enum values.
  - **Keep the three guards and messages exactly**:
    - "Shipping status can only be updated for your own products orders." (403 JSON via `PanelActionException(…, 403)`)
    - "Invalid shipping status selected." (422)
    - `OrderLifecycleService` exceptions → 422 with `$e->getMessage()`
  - Success toast: "Shipping status updated successfully.". The response `data` returns the new status label/badge HTML so the JS updates the panel without a reload.
- The form is `x-tenant::form` with `x-tenant::select name="shipping_status"` + submit, shown only when the current view shows it.

## 3. Returns list — `tenant.returns.index` (`Return\ReturnsList`)
- The query lives in the component (`ReturnRequest::query()->where('tenant_id', tenant('id'))->latest()` + search/status). `ReturnRequest` is a central model, so keep the connection behaviour identical. Move it to `TenantPanelRepository::queryReturns(array $filters)`.
- Filters: `search`, `status` (the current options). Columns: Order, Status, Reason, Refund, Date, Actions (View).

## 4. Return detail — `tenant.returns.show` (`Return\ReturnDetailPage` → `return/return-detail-page.blade.php`)
- `show(int $id)`: port `mount()` exactly (404 when not this tenant's; load `$order` by `order_number` uuid). Also port `authorizeReturn()` (the `abort(403)` at line ~153) into a private controller guard used by every action.
- Actions → endpoints (`ReturnRequestService` calls unchanged):

| Livewire | Endpoint | Request rules | Toast |
|---|---|---|---|
| `approve()` | `POST returns/{id}/approve` | — | "Return request approved." |
| `reject()` | `POST returns/{id}/reject` | `reject_reason` required string max:2000 | "Return request rejected." |
| `requestMoreInfo()` | `POST returns/{id}/request-info` | `info_message` required string max:2000 | "Requested more information from the customer." |
| `markItemReceived()` | `POST returns/{id}/received` | — | "Item marked as received." |
| `markRefunded()` | `POST returns/{id}/refunded` | `refund_amount` required numeric min:0 | "Return marked as refunded." |
| `addNote()` | `POST returns/{id}/notes` | `note_text` required string max:2000 | "Note added." |

- Each form action gets its own `/validate` sibling route.
- Modals (`showRejectModal`, `showInfoModal`, `showRefundModal`) become `x-tenant::modal` + `x-tenant::form success="reload-page"`.
- Approve and Received are `data-action-url` buttons with confirms.
- The notes/timeline thread uses `x-tenant::chat` (read the view and keep the same data).
- Button visibility per status must match the current `@if` conditions exactly.

## 5. Return analytics — `tenant.returns.analytics` (`Return\ReturnAnalyticsPage` → `return/return-analytics.blade.php`)
- Move the queries (`ReturnRequest::query()->where('tenant_id', …)` …) into `TenantPanelRepository::returnAnalyticsOverview(): array`, returning the same cards, chart payload and monthly rows.
- `ReturnAnalyticsController@index` renders `tenant.pages.insights._layout` (prompt 04) with the same content. The table (Month, Return Requests) is `x-tenant::datatable` via `DataTables::collection` at `GET returns/analytics/data` (`tenant.returns.analytics.data`).
- Reuse the Vite entry `pages/insights/index.js`.
- **Route order:** `returns/analytics` and `returns/analytics/data` stay **before** `returns/{id}` (they already are).

## 6. Customers list — `tenant.customers.index` (`Customer\CustomersList`)
- Stats from `customerStats()` (the same cards). Filters: `search`, `status`.
- Columns: ID, Customer, Contact, Orders, Lifetime Value, Last Order, Status, Actions (View, Delete).
- `data()` via `queryCustomers()`, the public wrapper of the builder behind `paginateCustomers`.
- Delete → `DELETE customers/{customerId}` → `destroy`. Use `$service->deleteModel(Customer::findOrFail(...))`, the same confirm title/text as `confirmDelete()`, and the toast "Customer deleted successfully.".
- Export → `GET customers/export` (`tenant.customers.export`), with the same headers/rows as the Livewire `exportRows()`.

## 7. Customer create — `tenant.customers.create` / `store` (`CustomerCreateController` + `tenant/customer/customer-create.blade.php`, uses Alpine)
- Move the controller to `Sales\CustomerCreateController`. Extract its inline `$request->validate` rules into `StoreCustomerRequest`.
- Add `POST customers/validate` (`tenant.customers.validate`).
- `store` returns `success('Customer created successfully.' (current text), redirect: route('tenant.customers.show', $customer->id))`.
- Rewrite the view with components (`phone`, `select2` country → city with `data-depends-on`, `password toggle`, `switch`) and remove the Alpine. Switch it to `tenant.layouts.app`.

## 8. Customer detail — `tenant.customers.show` (`CustomerDetailController` + `tenant/customer/customer-detail.blade.php`, 15 Alpine directives)
- Move the controller to `Sales\CustomerDetailController`. Rewrite the view with `tenant.layouts.app` and components.
- **Tabs:** `x-tenant::tabs mode="link"` keyed exactly like `?tab=` today (read the tab keys in the view; `activeTab` defaults to `profile`). Panels render server-side. Alternatively use `mode="hash"` with all panels rendered, but keep `?tab=` deep links working either way.
- **Profile form:**
  - `x-tenant::form method="PUT" action="{{ route('tenant.customers.update', $customerId) }}" validate="{{ route('tenant.customers.validate.update', $customerId) }}" success="none"`
  - The rules are extracted from `updateProfile()` into `UpdateCustomerProfileRequest`.
  - Response `success('Customer profile updated.' (current text))`.
  - The country→city select2 AJAX uses the existing `tenant.cities.by-country` (return `Select2Response`-compatible JSON, or add `?format=select2`; keep the old JSON shape for other callers).
  - The phone uses `x-tenant::phone` (replaces the "object" phone sanitising hack at the source, but keep the controller guard).
  - The Alpine password-generate/show block (line ~113) becomes `x-tenant::input type=password toggle` + a "Generate" button in `pages/sales/customer-detail.js`.
- **Orders tab:**
  - The order accordion (Order Info, Financials, Line Items `details-table`) stays as a detail list. Line items use `x-tenant::table`.
  - The **Payment History** `x-table` (Order, Gateway, Amount, Status, Date) becomes `x-tenant::datatable` at `GET customers/{customerId}/payments/data` (`tenant.customers.payments.data`), querying the customer's orders with the same fields.
- **Addresses tab:**
  - The saved-addresses list stays cards.
  - The Alpine modal becomes `x-tenant::modal id="address-modal"`:
    - Add opens it empty.
    - Edit uses `data-modal-fill-url="{{ route('tenant.customers.addresses.show', [$customerId, $address->id]) }}"` (new `GET` JSON), with `data-modal-action` / `data-modal-method="PUT"`.
  - `storeAddress`/`updateAddress` return JSON `success(...)` with `success="close-modal reload-page"`.
  - Rules move to `SaveCustomerAddressRequest`, with `…/addresses/validate`.
  - `destroyAddress` → `data-action-url` DELETE + confirm, toast from the server.
  - Keep all existing route names (`tenant.customers.update`, `…addresses.store|update|destroy`, `tenant.cities.by-country`).
- Toggle active (if present in the view) → `PATCH customers/{customerId}/active`.

## 9. Routes to add
```
orders:     GET orders/data · GET orders/export · PATCH orders/{orderId}/shipping-status · POST orders/{orderId}/shipping-status/validate
returns:    GET returns/data · GET returns/analytics/data · POST returns/{id}/{approve|reject|request-info|received|refunded|notes} (+ /validate for reject, request-info, refunded, notes)
customers:  GET customers/data · GET customers/export · POST customers/validate · DELETE customers/{customerId}
            POST customers/{customerId}/validate (name: tenant.customers.validate.update) · PATCH customers/{customerId}/active
            GET customers/{customerId}/payments/data · GET customers/{customerId}/addresses/{addressId} (show)
            POST customers/{customerId}/addresses/validate
```
- Wildcard order: `customers/data`, `customers/export` and `customers/create` go before `customers/{customerId}`. `orders/data` and `orders/export` go before `orders/{orderId}`.
- Constrain ids with `->whereNumber('orderId')` / `->whereNumber('customerId')` / `->whereNumber('id')`.

## 10. Vite entries
```
'resources/js/tenant/pages/sales/orders-index.js',
'resources/js/tenant/pages/sales/order-show.js',
'resources/js/tenant/pages/sales/returns-index.js',
'resources/js/tenant/pages/sales/return-show.js',
'resources/js/tenant/pages/sales/customers-index.js',
'resources/js/tenant/pages/sales/customer-create.js',
'resources/js/tenant/pages/sales/customer-detail.js',
```

## 11. Verification
1. Orders: filters (URL-synced), sorting, export honouring the filters, the detail shipping-status update with each guard message.
2. Returns: every action per status, validation errors inside the modals, notes appear, the analytics chart and table render.
3. Customers: create with blur validation, profile update, address add/edit/delete in the modal, the payment history table, tab deep links (`?tab=addresses`). No Alpine left: `grep -n "x-data\|@click\|x-show" resources/views/tenant/pages/sales -r` returns empty.
