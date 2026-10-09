# Blade Theme Starter Kit

Zip up this folder's **contents** (not the folder itself) and upload via
**Store → Blade Theme**. Every upload is queued for admin review — it will
not appear on your storefront until an admin approves it AND you activate
it from **Store → Themes**.

> **Tip:** If your OS wraps the files in a parent directory when you zip
> (e.g. macOS Finder "Compress", Windows right-click → "Send to Zip"), the
> platform will auto-detect and strip that wrapper — so either zip style works.

---

## Required Files

The upload is rejected if either of these is missing:

- `layout/app.blade.php`
- `pages/home/index.blade.php`

---

## Allowed File Types

`.blade.php`, `.css`, `.js`, `.png`, `.jpg`, `.jpeg`, `.gif`, `.svg`,
`.webp`, `.woff`, `.woff2`, `.md`, `.txt`, `.json`, `.ico`, `.webmanifest`

---

## Blocked Patterns (Upload Rejected)

Blade files may not contain any of the following — the upload is rejected
immediately with the matching pattern named in the error:

- Raw PHP tags: `<?php`, `<?=`
- Blade PHP directive: `@php`
- Shell/eval functions: `system(`, `exec(`, `shell_exec(`, `passthru(`,
  `eval(`, `proc_open(`, `popen(`, `assert(`, `call_user_func(`,
  `call_user_func_array(`
- Backtick shell operator: `` ` ``

This is a first-pass denylist only, not a full sandbox. Your theme is still
reviewed by a human admin before it can go live.

---

## Variables Available in `pages/home/index.blade.php`

These variables are passed by the platform and available in every home page
template and its `@include`-d section files:

| Variable | Type | Description |
|---|---|---|
| `$storeName` | `string` | Store display name |
| `$logoPath` | `string\|null` | Logo URL |
| `$footerText` | `string` | Footer body text (HTML) |
| `$footerCopyright` | `string` | Footer copyright line |
| `$socialLinks` | `array` | Social media links (key → URL) |
| `$rootCategories` | `Collection` | Root categories with nested children |
| `$categories` | `Collection` | All active categories (flat) |
| `$currentCurrency` | `object` | Current currency (`->code`, `->symbol`) |
| `$banners` | `Collection` | Active hero banners |
| `$flash_sales` | `Collection` | Active flash sales |
| `$new_arrivals` | `Collection` | New arrival products (up to 10) |
| `$recommended_products` | `Collection` | Recommended products (up to 10) |
| `$best_sellers` | `Collection` | Best-selling products (up to 10) |
| `$trending_products` | `Collection` | Trending-now products (up to 10) |
| `$featured_products` | `Collection` | Curated featured products (up to 10) |
| `$top_rated_products` | `Collection` | Top-rated products (up to 10) |

---

## File Structure

```
layout/
  app.blade.php          ← Main layout shell (required)
  scripts.blade.php      ← JS includes
  styles.blade.php       ← CSS includes

pages/
  home/
    index.blade.php      ← Home page entry point (required)
    sections/
      hero.blade.php
      trust_bar.blade.php
      flash_sale.blade.php
      browse_categories.blade.php
      new_arrivals.blade.php
      recommended_products.blade.php
      featured_products.blade.php
      top_rated.blade.php
      trending_now.blade.php
  product.blade.php
  category.blade.php
  cart.blade.php
  checkout.blade.php
  auth.blade.php
  profile.blade.php
  favorites.blade.php
  best-selling.blade.php
  new-in.blade.php
  order-status.blade.php
  order-tracking.blade.php
  order-return.blade.php
  page.blade.php
  404.blade.php

partials/
  header.blade.php
  footer.blade.php
```

---

## Order Pages: Cancel, Return, Exchange & Refund (optional)

These additions are **optional and backwards compatible**: no new required files, no
renamed variables. Themes uploaded before this change keep working unchanged — they just
won't show the new cancel / refund UI until you add the includes below.

### Shared partials you can `@include`

The platform ships ready-made, accessible (labels, focus handling, ESC closes dialogs) and
RTL-safe partials. Include them by name from your theme files:

| Partial | Use it in | What it renders |
|---|---|---|
| `livewire.tenant.storefront.partials.order-cancel-action` | `pages/order-status`, `pages/order-tracking`, `pages/profile` | "Cancel order" button + two-step modal (reason → "Are you sure…? This cannot be undone."). Shows "Already shipped — you can request a return after delivery" for shipped orders. Only shown to the order's owner while the store policy allows cancelling. |
| `livewire.tenant.storefront.partials.order-cancellation-summary` | `pages/order-status`, `pages/order-tracking` | Red "cancelled" banner (reason, note, date/time, who cancelled) or a violet "refunded" banner. Renders nothing for other orders. |
| `livewire.tenant.storefront.partials.order-refunds-summary` | `pages/order-status`, `pages/order-tracking` | One card per refund: reference, amount, method, status badge, requested / completed dates, rejection reason. Renders nothing when there are no refunds. |
| `livewire.tenant.storefront.partials.return-item-action` | inside the items loop of `pages/order-status` (needs `$item`) | "Request Return" (with units left), the latest return status (links to the request), or "Return after delivery" for shipped orders. Hidden for cancelled / refunded orders. |
| `livewire.tenant.storefront.partials.return-form-content` | `pages/order-return` | The complete return / exchange form. |

`order-cancel-action` options (pass as the include's second argument):

- `cancelPart`: `'all'` (default — notice + button + modal), `'trigger'`, `'notice'` or `'modal'`.
  In an order **list** (profile), include `'trigger'` per order and `'modal'` **once** outside the loop.
- `cancelSize`: `'full'` (default, full-width button) or `'compact'` (pill).
- `cancelDecision`: on the profile page pass `$cancelDecisions[$order->uuid] ?? null`.

```blade
{{-- pages/order-status.blade.php --}}
@include('livewire.tenant.storefront.partials.order-cancellation-summary')
@include('livewire.tenant.storefront.partials.order-refunds-summary')
@include('livewire.tenant.storefront.partials.order-cancel-action')

{{-- pages/profile.blade.php: inside @foreach ($orders as $order) --}}
@include('livewire.tenant.storefront.partials.order-cancel-action', [
    'cancelPart' => 'trigger',
    'cancelSize' => 'compact',
    'cancelDecision' => $cancelDecisions[$order->uuid] ?? null,
])
{{-- …and once, after the loop --}}
@include('livewire.tenant.storefront.partials.order-cancel-action', ['cancelPart' => 'modal'])
```

The cancel modal is driven by Livewire; toasts are sent as the `order-status-swal` (order page)
and `profile-swal` (profile page) browser events with `{ message, type }`.

### New optional variables

`pages/order-status.blade.php` (and `pages/order-tracking.blade.php` when shown from the order page):

| Variable | Type | Description |
|---|---|---|
| `$canCancel` | `bool` | The logged-in owner may cancel this order now |
| `$cancelDecision` | `CancellationDecision` | `->allowed`, `->code` (`shipped`, `delivered`, `processing_locked`, …), `->message`, `->suggestReturn` |
| `$cancelReasons` | `array` | value → label of the reasons a customer can pick |
| `$cancellation` | `array\|null` | `reason`, `reason_label`, `note`, `cancelled_at`, `cancelled_by`, `cancelled_by_label` (null unless cancelled) |
| `$refunds` | `array` | Refunds: `reference`, `amount`, `currency`, `method_label`, `status`, `status_label`, `status_color`, `requested_at`, `processed_at`, `rejection_reason` |
| `$paymentState` | `OrderPaymentStatus` | Paid / Unpaid / Partially refunded / Refunded (`->label()`, `->color()`) |
| `$returnItems` | `array` | order item id → `remaining`, `returnable`, `has_open_request`, `errors` (delivered orders only) |

`pages/profile.blade.php`: `$cancelDecisions` (order uuid → `CancellationDecision`) and `$cancelReasons`.

`pages/order-return.blade.php`: `$remaining`, `$returnMethods`, `$exchangeEnabled`,
`$exchangeOptions` (`id`, `label`, `price`, `stock`), `$selectedReason`, `$photosRequired`,
`$descriptionRequired`, `$refundEstimate` (`itemsAmount`, `shippingAmount`, `returnFee`, `amount`).

The order status can now also be `refunded` — give it a badge style if you map statuses yourself.
