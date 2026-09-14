# Prompt 08 — Requests (Manufacturing, Brand, Product), Support Tickets, Notifications, Help Docs

Requires prompts 01–03 and 07 (the payment modal). Follow the **Standard Conversion Recipe** (prompt 00).
Controllers: `app/Http/Controllers/Tenant/Panel/Requests/` (manufacturing, brand, product requests) and `…/Panel/Support/` (tickets, notifications, help). Views: `resources/views/tenant/pages/{requests,support}/`.
Keep middleware exactly: `product-requests` has `catalog.products.manage`. The others have no extra permission (as today).

Common for all chat-style detail pages:
- The thread uses `x-tenant::chat`. The composer is an `x-tenant::form` with `success="none"`, and the JS appends the returned message.
- The server response `data.message` is the rendered message payload, so the sender sees it instantly.
- Realtime: `getEcho().private(<same channel>).listen(<same event>, …)` appends incoming messages. The same channel names and events as today (read each view's inline script):
  - `tenant.{id}.manufacturing.{requestId}`
  - `tenant.{id}.brand-requests.{requestId}`
  - `tenant.{id}.support` (the ticket page then refetches its thread)
  - product-request events as fired by `ProductRequestMessageSent`
- The Livewire `chat-scrolled` dispatch becomes `chat.scrollToBottom()`.
- Every `event(new …MessageSent(...))` and every `AdminNotificationService::notify(...)` call stays **in the same order**. Move them into small services: `ManufacturingRequestService`, `BrandRequestService`, `ProductRequestService` and `SupportTicketService` in `app/Services/Tenant/`. Each gets `sendMessage()`/`create()` methods holding the bodies moved from Livewire. This matters because messages are central models with tenant context.

---

## 1. Manufacturing — `tenant.manufacturing.*`

**List** (`ManufacturingRequestsList`)
- The query lives in the class (`ManufacturingRequest::query()…`, lines 39 and 134 — export). Move it to `TenantPanelRepository::queryManufacturingRequests(array $filters)`.
- Filters `search`, `status`. Columns Product, Qty, Status, Admin Notes, Submitted, Actions (View, Cancel).
- The header action "New request" links to `tenant.manufacturing.create` (replaces `redirectToCreate`).
- `cancelRequest(id)` → `POST manufacturing/{id}/cancel`. Port the guards (only when the status allows it). Confirm text as today. Toast "Request cancelled.".
- Export `GET manufacturing/export` (the same headers/rows).

**Create** (`AddManufacturingRequest` → `manufacturing/add-manufacturing-request.blade.php`)
- Form fields:
  - `product_name` required string max:255
  - `description` (the current rule)
  - `quantity` required integer 1–99999
  - `linked_product_id` nullable
- The product search/`loadMoreProducts`/`selectProduct`/`clearLinkedProduct` flow becomes `x-tenant::select2 ajax-url="{{ route('tenant.manufacturing.products.search') }}" allow-clear`. The new `GET manufacturing/products/search` returns `Select2Response` using the **same query** as `updatedProductSearch()` (move it to the repository).
- Selecting a product pre-fills `product_name` in JS when it is empty (the current `selectProduct` behaviour).
- `store` → the same persistence, then `success(<current message or 'Manufacturing request submitted.'>, redirect: route('tenant.manufacturing.index'))`. Plus `/validate`.

**Detail** (`ManufacturingRequestDetail` → `manufacturing/manufacturing-request-detail.blade.php`, 502 lines, 5 scripts, Stripe CDN)
- `show(int $id)`: port `mount()` (tenant scoping + 404).
- `sendMessage` → `POST manufacturing/{id}/messages`, rule `message` required string max:2000, fires `ManufacturingMessageSent` as today. Plus `/validate`.
- The payment-request table inside the detail page is `x-tenant::datatable mode="client"` (a small list). Each row's Pay button opens `x-tenant::payment.gateway-modal` with `payment_request_id`.
- `initiatePayment` → `POST manufacturing/{id}/pay`: rules `payment_request_id` required integer (plus ownership check) + `InlinePaymentRules`, stash tokens, then `redirect route('tenant.manufacturing-payment.charge', …)` with the same params.
  - Gateways: the same source as `ManufacturingRequestDetail::pageData()` (read it: vendor or central gateways).
- Remove all 5 inline scripts. Stripe loads via `payment-sdk-loader` only.

## 2. Brand requests — `tenant.brand-requests.*`
Identical structure to manufacturing:
- **List** (`BrandRequestsList` → `brand-request/list.blade.php`, has a `<table>`): filter `status`. Columns as in the view. Datatable.
- **Create** (`CreateBrandRequest`):
  - `title` required max:255, `description` required min:20 max:8000, `files` (the current upload rules)
  - `x-tenant::dropzone name="files"`
  - persist as `save()`, redirect to `tenant.brand-requests.index`
- **Detail** (`BrandRequestDetail` → `brand-request/detail.blade.php`, 499 lines, 5 scripts, has a `<table>`):
  - chat → `POST brand-requests/{id}/messages` (`BrandRequestMessageSent`)
  - payment → `POST brand-requests/{id}/pay` → `tenant.brand-request-payment.charge`
  - the payments table is a client-mode datatable

## 3. Product requests — `tenant.product-requests.*` (permission `catalog.products.manage`)
- **List** (`RequestsList` → `product-request/list.blade.php`, has a `<table>`): filter `status`. Datatable.
- **Create** (`CreateRequest`):
  - `title` required max:255, `description` required min:20 max:8000, `product_url` (the current rule), uploads (the current rules)
  - `submit()` body → `ProductRequestService::create()`, including `AdminNotificationService::notify(...)` and `event(new ProductRequestMessageSent(...))`
  - toast "Product request submitted successfully." + redirect to `tenant.product-requests.show`
- **Detail** (`RequestDetail`):
  - `sendReply` → `POST product-requests/{requestId}/replies`: `reply` required min:2 max:5000 + attachments (the current rules)
  - closed guard → 422 "This request is closed."
  - toast "Reply sent."
  - the status progress indicator is kept (read the view)

## 4. Support — `tenant.support.*`
- **List** (`TicketsList`, generic list-page):
  - Port the query from `pageData()` into `querySupportTickets()`. `SupportTicket` is central, scoped via `SupportTicket::forTenant(tenant key)`.
  - Columns Subject, Category, Priority, Status, Last Reply, Actions.
  - Unread tickets are highlighted (the current `rowClasses`) via DataTables `createdRow` with a `DT_RowClass` added in `setRowClass()`.
- **Create** (`CreateTicket`, ContentPage fields):
  - rules copied verbatim: `subject` required max:255; `category` in `SupportTicket::categoryOptions()` keys; `priority` in `priorityOptions()` keys; `body` required min:10 max:5000
  - `category` and `priority` use `x-tenant::select` with those options
  - `submit()` → `SupportTicketService::create()` (notify + `SupportTicketMessageSent`)
  - toast "Support ticket created successfully." + redirect to show
- **Detail** (`TicketDetail`):
  - reply → `POST support/{ticketId}/replies`, rule `reply` required min:2 max:5000
  - closed → 422 "This ticket is closed and can no longer receive replies."
  - toast "Reply sent."
  - Keep the `tenant_has_unread` reset that `mount()`/`refreshTicket()` performs, and also perform it on the thread refetch.
  - `refreshTicket` → `GET support/{ticketId}/thread` returns the rendered thread HTML (`fragment`) for the Echo listener.
- Sidebar unread badge: after viewing, emit nothing. The badge updates on the next page load, as today.

## 5. Notifications — `tenant.notifications.index` (`NotificationsPage` → `notifications/notifications-page.blade.php`)
- The list is not a table: it is a card list. Use `x-tenant::ajax-list` + `x-tenant::pagination mode="ajax"`, with `GET notifications/feed?page=` returning `fragment()`.
  - Port the query (`TenantNotification::query()…`, the current ordering and per-page).
  - Add a small filter (All / Unread) only if the view already has one; otherwise don't add it.
- `markAllRead()` → `POST notifications/read-all`, toast "All notifications marked as read.". Reload the list and set the bell badge to 0 (emit `tenant:notification-read-all`; the bell listens).
- `markRead(id)` → `PATCH notifications/{id}/read`, **silent** (no toast, matching today). Pass `{ toast:false }` in the http call. The server still returns a `message` for consistency. The JS marks the card read and emits `tenant:notification-read`.

## 6. Help docs — `tenant.help.index` (`Help\DocsPage` → `help/docs-page.blade.php` + `help/articles/*`)
- Articles are static Blade partials: `api-reference`, `compliance`, `getting-started`, `page-builder`, `themes`, `tracking-pixels` and `variable-reference`.
- `index(Request)` renders the docs shell with the article from `?article=<slug>`, defaulting to the class's current default and whitelisted against the class's article list (port that list).
- Sidebar links are **real links** (`?article=slug`), progressively enhanced by `pages/support/help.js`, which fetches `GET help/articles/{slug}` (`fragment`) and swaps the content with `history.pushState`. This keeps the previous bug fix intent (no full reloads) without Livewire's `wire:click="showArticle()"`.
- The `<style>` blocks in `docs-page.blade.php` and `articles/variable-reference.blade.php` move to `resources/css/tenant/pages/help.css`.
- Code samples use `x-tenant::copy`.
- Move the article partials to `resources/views/tenant/pages/support/help/articles/`, then **delete the old ones in prompt 12**. The central admin's `admin/docs` does not include them (verify with grep before deleting).

## 7. Routes to add
```
manufacturing: GET data · GET export · GET products/search · POST / (store) · POST validate
               POST {id}/cancel · POST {id}/messages (+/validate) · POST {id}/pay (+/validate)
brand-requests: GET data · POST / · POST validate · POST {id}/messages (+/validate) · POST {id}/pay (+/validate)
product-requests: GET data · POST / · POST validate · POST {requestId}/replies (+/validate)
support:       GET data · POST / · POST validate · POST {ticketId}/replies (+/validate) · GET {ticketId}/thread
notifications: GET notifications/feed · POST notifications/read-all · PATCH notifications/{id}/read
help:          GET help/articles/{slug}
```
- Keep the existing names for GET pages. New names follow `tenant.<resource>.<action>`.
- Constrain the wildcard ids with `whereNumber`, and declare static segments (`data`, `export`, `products/search`, `new`, `create`) **before** `{id}`.

## 8. Vite entries
```
'resources/js/tenant/pages/requests/manufacturing-index.js',
'resources/js/tenant/pages/requests/manufacturing-create.js',
'resources/js/tenant/pages/requests/manufacturing-show.js',
'resources/js/tenant/pages/requests/brand-index.js',
'resources/js/tenant/pages/requests/brand-create.js',
'resources/js/tenant/pages/requests/brand-show.js',
'resources/js/tenant/pages/requests/product-requests-index.js',
'resources/js/tenant/pages/requests/product-request-create.js',
'resources/js/tenant/pages/requests/product-request-show.js',
'resources/js/tenant/pages/support/tickets-index.js',
'resources/js/tenant/pages/support/ticket-create.js',
'resources/js/tenant/pages/support/ticket-show.js',
'resources/js/tenant/pages/support/notifications.js',
'resources/js/tenant/pages/support/help.js',
```

## 9. Verification
1. Send a chat message from the tenant → it appears instantly. An admin reply from the central panel → it appears via Reverb without a reload (for manufacturing, brand and product requests, and support).
2. The payment flow from manufacturing and brand request details reaches the charge route with the same session keys (prompt 07 check).
3. Closed ticket or request replies → an error toast plus the field-level message.
4. Notifications: mark one read (silent, the bell decrements), mark all read (toast, bell → 0), AJAX paging.
5. Help: every article opens by link and by in-page navigation. Back/forward works. Deep link `?article=themes` works.
