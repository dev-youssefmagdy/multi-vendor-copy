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
