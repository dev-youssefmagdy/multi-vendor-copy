# Dashboard #4: Public Storefront (tenant website)

> Part of [COMPLETE_SYSTEM_DOCUMENTATION.md](../../COMPLETE_SYSTEM_DOCUMENTATION.md) §9–10. All routes are listed in [routes-inventory.md §4–5](routes-inventory.md). Component fields and actions are in [module-inventory.md §Tenant storefront](module-inventory.md#tenant-storefront-domain-50-routes). Tags: **[V]** verified, **[I]** inferred (read from Blade/CSS, not rendered), **[R]** recommendation.

## 1. Routing and tenant resolution [V]

| Access mode | Example | File | Tenancy |
|---|---|---|---|
| Subdomain | `{slug}.{central-domain}` (created at registration by `WebsiteRegistrationService::uniqueTenantDomain()`) | `routes/tenant.php` | `InitializeTenancyByDomain` (`domains` table) + `PreventAccessFromCentralDomains` |
| Custom domain | `shop.example.com` (vendor adds it under Settings → Domains; admin marks it connected) | same | same |
| Path mirror | `{central}/vendor/{slug}/…` | `routes/tenant_path.php` | `InitializeTenancyBySlug` (`tenants.data->slug`); Livewire updates go to `POST /s/{slug}/livewire/update`; `/vendor/{slug}/admin*` redirects to the real panel domain |
| Theme preview | `{central}/preview/*` | `routes/central.php` | `InitializeTenancyForPreview`, `ForcePreviewTemplate` |

**Storefront middleware stack** (in order, domain mode):

| Middleware | What it does |
|---|---|
| `tenant.storefront.context` (`ApplyStorefrontSessionContext`) | country, currency and language in the session; `?country=XX` QA override; `?preview_country=XX` one-request preview |
| `identify.tenant.theme` (`IdentifyTenantTheme`) | prepends the tenant's live Blade theme view path |
| `tenant.gateway.blocked` (`CheckTenantGatewayBlocked`) | the platform blocks a tenant whose own-gateway sales exceed admin limits |
| `preview.template` (`ForcePreviewTemplate`) | theme preview mode |
| `blade.theme.home` (`ServeBladeThemeHome`) | serves the uploaded theme's home page |
| `store.launch.gate` (`StoreLaunchGate`) | shows `errors.store-not-launched` until the store is launched or all setup steps are done |

Globally, `CheckMaintenanceWindow` shows the maintenance page.

**Unknown host** → redirect to the central site with "This store does not exist." **404/500 on a tenant host** → redirect to `tenant.storefront.not-found`. Unknown paths hit `Route::fallback(NotFoundPage)`.

## 2. Rendering architecture [V]

```mermaid
flowchart LR
    R[Route → Livewire full-page component<br/>App\Livewire\Tenant\Storefront\*] --> TS{TemplateStrategy for tenant theme}
    TS -->|ecommet| E[themes.ecommet.layout.app + pages.*]
    TS -->|elora| EL[themes.elora.layout.app + pages.* / home-vN]
    TS -->|souqify| SQ[themes.souqify.layout.app + pages.* / home-vN]
    TS -->|uploaded| UB[UploadedBladeTemplateStrategy → storage/app/tenants/{id}/theme/views]
    R --> REPO[StorefrontRepository / ProductCatalog / CouponCatalog / FlashSaleCatalog]
    REPO --> CDB[(central catalog)] & TDB[(tenant DB)]
    VC[View composer registered in TenancyServiceProvider] --> TS
```

- `App\Contracts\TemplateStrategy`: `slug()`, `name()`, `previewPath()`, `layout()`, `pageView($page)`, `supportedPages()`. Implementations live in `app/Services/Tenant/Templates/*`.
- Theme choice: `themes` / `theme_country` (per-country theme), home layout `HomeVariantResolver::resolveFor(theme, country)` → `home_variants` (sections + colors + view), and per-tenant section order and visibility (`tenant_page_sections`).
- Reusable Livewire widgets: `Layout\CartDropdown`, `Layout\CartManager` (`addWithVariant`, `addMany`), `Layout\LocaleSwitcher` (language, currency and country tabs), `ThemeKit\AddToCartButton`, `ThemeKit\CartIcon`, `SupportRequestForm`.
- Concerns shared by pages:
  - `HasStorefrontLayout`, `InteractsWithStorefrontUi` (mobile menu);
  - `ChecksCartStock`, `CalculatesFreeShipping` (free-shipping progress banner, `FreeShippingBanner`);
  - `ManagesOrderCancellation`, `ProvidesFavoriteDisplayData`.
- Data source: `App\Repositories\Tenant\StorefrontRepository` (2,391 lines, 61 public methods) plus the catalog repositories, cached with `STOREFRONT_CACHE_*` TTLs and `CacheVersion` stamps.
- JS: `resources/js/storefront-cart.js`, `resources/js/{elora,souqify,…}/*.js` (header, footer, home, category, cart per variant), `image-search.js`, `storefront/video-pip.js`; Swiper carousels; translations injected by `App\Support\StorefrontJsTranslations`.

## 3. Global storefront behaviours [V]

| Behaviour | Implementation |
|---|---|
| Country detection | `CountryDetectorService` (Cloudflare `CF-IPCountry` on every request; session fallback); `visibilityCountryId()` = **detected** country (strict) |
| Product visibility | `Product::scopeAvailableInCountry()`: `allowed_country_ids` NULL = everywhere, `[]` = nowhere, list = only there. Applied to listings, search, home sections, flash strips, product page (404), cart add, favorites |
| Pricing | `Product::storefrontPricing()` → `OrderPricing::unitPrice()` with the session country; displayed price = sell price + fixed shipping − flash discount, converted by currency rate; **charged in USD** |
| Currency and language | `LocaleSwitcher`; languages are the tenant's enabled languages (paid ones purchased); RTL via `dir="rtl"` and Arabic fonts |
| Cart | session `storefront_cart` (`product_id`, `variant_id`, `qty`); `CartController` add/remove/update (JSON, CSRF); stock checks |
| Coupon | session `storefront_coupon`; minimum-spend check |
| Auth | guard `storefront` (`customers` table); Google and Apple OAuth |
| Tracking pixels | Meta, TikTok, Snapchat, GA4 IDs from vendor settings; `ProductPage::$viewContentTracked` fires ViewContent once [I] |
| SEO | `App\Helpers\SeoHelper`, per-product meta fields (meta keywords and description in AI translation fields), `/sitemap.xml` (excludes products hidden everywhere), `/robots.txt` |
| Image search | `POST /search/image` → `ImageSearchController@storefront` → Python embeddings; plan limit `image_searches_limit` |
| Maintenance / not launched | `CheckMaintenanceWindow`, `StoreLaunchGate` |

## 4. Page catalog

Each entry gives: URL and route · auth · purpose · data · interactions · layout notes (from the Elora theme; Souqify and Ecommet follow the same structure with their own markup). Page templates: `resources/views/themes/{theme}/pages/{page}.blade.php`.

### 4.0 Common layout (all pages) [I from `themes/elora/layout/app.blade.php`, `partials/header*.blade.php`, `partials/footer*.blade.php`]
- **Header** (6 variants in Elora, `header`, `header-v2…v6`):
  - logo (image or text logo built in Appearance → logo builder);
  - category navigation from the tenant's categories (`tenant.storefront.category`);
  - quick links (New In, Best Selling);
  - **search box with autocomplete** (`/search/autocomplete`) and an **image-search** button;
  - locale switcher (language / currency / country);
  - favorites, account (login or profile), cart icon with dropdown (`CartDropdown`);
  - mobile hamburger (`toggleMobileMenu`), plus a mobile home header (`partials/mobile-home-header`).
  - Top-header links are configurable (Appearance → top header links). An optional promo banner (title, subtitle, CTA, desktop and mobile images).
- **Footer** (6 variants): footer logo (text or image per locale, colors, shape, font, width), vendor CMS pages (seeded footer pages), social links (`social_links`), and payment method icons (`PaymentMethodIcons`) [I].
- **Account sidebar** (`partials/account-sidebar`) on account pages.
- **Responsive:** breakpoints 1279/1023/767/639/479 px; separate mobile headers and cards; Swiper carousels switch slides-per-view by width [I].

### 4.1 Home: `/` · `tenant.home` · public
- **Component:** `HomePage` (load-more per section: new-in, best-selling, flash, top-rated, recommended; tabbed products via `showProductTab` and `/api/home/tabbed-products`; quick add-to-cart). A vendor Blade theme can replace it (`ServeBladeThemeHome`).
- **Sections (configurable, from `SectionRegistry`; order and visibility per tenant in the Page Builder):**

| Theme | Home sections (keys) |
|---|---|
| Ecommet | hero_banner, trust_bar, flash_deals, new_year_sale (category pills + product grid), flash_sale_strip, best_selling, promo_banner |
| Elora (v1–v6) | hero_banner, trust_bar, category_carousel, tabbed_products, category_swiper, flash_sale_strip, trending_now, category_strip, best_seller, new_arrivals, shop_by_category, recommended_for_you, promo_banner (+ category_circles in templates) |
| Souqify (v1–v6) | hero, trust_bar, trending_this_week, browse_categories, flash_sale, top_products, hero_banners, new_arrivals, explore_products, featured_products, hot_deals, special_offers, top_rated_products, recommended_products, promo_banner |

- **Content sources:**
  - hero/banners: tenant `banners` with country scope, falling back to admin `default_banners`;
  - product sections: badges (new-in, best-selling, featured, recommended, trending-now) from admin badge lists and tenant badge overrides;
  - flash sales: central + vendor;
  - categories: tenant categories;
  - trust bar: static icons/text in the theme [I];
  - promo banner: Appearance settings.
- **Hardcoded vs dynamic:** section *markup* and the trust-bar copy live in the theme files; section *data*, order and visibility are dynamic; colors come from `tenant_theme_colors` + home-variant colors.

### 4.2 Listing pages
| Page | URL / route | Component | Filters and behaviour |
|---|---|---|---|
| Best selling | `/best-selling` · `tenant.storefront.best-selling` | `BestSellingPage` | period `days`, category, infinite load (`loadMore`) |
| Search results | `/search?q=` · `tenant.storefront.search` (same component, `isSearchRoute`) | `BestSellingPage` | keyword, category, **image-search results** (`imageSearchId`) |
| Full star (top rated) | `/full-star` | `FullStarPage` | search, category |
| New in | `/new-in` | `NewInPage` | search, category |
| Offers | `/offers` | `OffersPage` | sort, category, flash-only, minimum discount, load more |
| Category / all categories | `/categories/{slug?}` · `tenant.storefront.category` | `CategoryPage` | keyword, sort, availability, product flag (badges), on sale, ratings, section, **price min/max (displayed price incl. fixed shipping)**, **variation options** (e.g. color/size with counts), apply/clear, load more; JSON variant `/categories-products/{slug?}` |
- **Product card** (`pages/_product-card`, `_category-product-card`, theme partials):
  - image(s), label/badge ribbon, name, current price + strikethrough, rating stars, favorite heart, quick add-to-cart.
  - **Memory rule:** never reposition the badge, ribbon, cart or heart elements when fixing cards.

### 4.3 Product details: `/products/{slug}` · `tenant.storefront.product` · public
- **Component** `ProductPage`: `selectedVariantId`, `qty`, `addToCart()`, reviews modal (`openReviewsModal`, `loadMoreReviews`), mobile menu.
- **Displays:**
  - gallery (primary + gallery images, AVIF), translated name, description, price breakdown for the visitor's country, flash-sale countdown/discount [I];
  - variant selector (options from `variations`), stock state, label, badges, ratings summary, reviews (`product_rates`), estimated delivery (`shipping_day_rules`, `delivery_popup_days`) [I], return policy summary [I];
  - related products [I].
- **Rules:** 404 when not available in the visitor's country; add-to-cart validates stock and country.

### 4.4 Cart: `/cart` · `tenant.storefront.cart` · public (session)
- `CartPage`: line list (image, name, variant, unit price incl. fixed shipping, qty stepper `updateQty`, remove), coupon apply/remove (`couponCode`, minimum-spend error), totals (items subtotal, discount, shipping estimate), free-shipping progress, proceed to checkout.
- Cart endpoints: `POST /cart/add|remove|update`.

### 4.5 Checkout: `/checkout` · `tenant.storefront.checkout` · login required (redirects guests to `/account/login?redirect=…`)
- `CheckoutPage` (976 lines):
  - shipping and billing address selection from saved addresses or a **new address modal** (full name, email, phone, city, state, country);
  - line edits; coupon;
  - payment method selection: COD or the tenant's active gateways. Inline card fields for Stripe, Authorize.Net and 2Checkout use SDKs loaded on demand; other gateways redirect;
  - `placeOrder()`.
- **Validation:** address fields; country availability for own products; stock; shipping availability for catalog products ("select a country", "not available to your country").
- **Result:**
  - creates 1 or 2 orders (catalog + own companion; same `order_group_uuid`);
  - online payment → `/checkout/payment/{gateway}/{orderUuid}` → gateway → `/success` or `/cancel`;
  - COD → `/orders/{uuid}/status`.

### 4.6 Order pages
| Page | URL / route | Auth | Content |
|---|---|---|---|
| Order status (thank-you / details) | `/orders/{uuid}/status` · `tenant.storefront.order-status` | public by UUID | `OrderStatusPage`: details vs tracking mode, items, totals (customer display rule: items subtotal includes fixed shipping), **review** a product (stars, comment), **reorder**, **cancel** (2-step reason + note), invoice link |
| Order tracking | `/orders/{uuid}/tracking` · `tenant.storefront.order-tracking` | public by UUID | `OrderTrackingPage`: status timeline from `order_activities`, tracking number |
| Invoice | `/orders/{uuid}/invoice` · `tenant.storefront.order-invoice` | customer | `StorefrontInvoiceController@show` (standalone printable view `resources/views/tenant/storefront/invoice.blade.php` with its own styles and Google Fonts) |
| Request return | `/orders/{uuid}/return` · `tenant.storefront.order-return` | customer | `RequestReturnForm`: order item, reason (`ReturnReason`), description, quantity stepper, type return/exchange, return method (courier pickup / drop-off / ship back), replacement variant (exchange), customer note, photos + video (size limits; video required for some reasons per policy) |
| Return detail | `/account/returns/{id}` · `tenant.storefront.return-detail` | customer | `ReturnDetailPage`: status, notes thread, reply with text, photos and video, **withdraw** request |

### 4.7 Customer account
| Page | URL / route | Content |
|---|---|---|
| Login / Register | `/account/login` · `tenant.storefront.login` (guest) | `AuthPage` with tabs: login (email, password) and register (name, email, phone, password, confirm); Google and Apple buttons (`/auth/google`, `/auth/apple`) |
| Account | `/profile` · `tenant.storefront.profile` | `ProfilePage` tabs: **orders** (status filter, reorder, cancel, review), **profile** (personal info), **wishlist**, **returns**; **addresses** CRUD in a modal (label, full name, phone, line 1, city, state, country, default); logout |
| Favorites | `/favorites` · `tenant.storefront.favorites` | `FavoritesPage` grid; toggle via `POST /favorites/toggle`; `GET /favorites/ids` to mark hearts |
| Logout | `POST /account/logout` | — |

### 4.8 Content and system pages
| Page | URL / route | Content |
|---|---|---|
| CMS page | `/pages/{slug}` · `tenant.storefront.page` | `PageView`: vendor page (translated title and body). Typical seeded footer pages cover about, contact, privacy, terms, shipping and returns [I: `pages:seed-footer-defaults`] |
| 404 | `/not-found`, fallback | `NotFoundPage` (themed) |
| Store not launched | any | `resources/views/errors/store-not-launched.blade.php` with an admin login link |
| Maintenance | any | maintenance view via `MaintenancePageContext` |
| App landing | `/app` | `AppLandingController` (standalone view `storefront/app-landing.blade.php`; store badges) |
| robots / sitemap | `/robots.txt`, `/sitemap.xml` | generated |
| Support request | embedded `SupportRequestForm` (type, name, email, message) | sends a request to the vendor [I] |

### 4.9 Not implemented [V]
Compare products · storefront blog · dedicated contact form route (only the embedded support form) · newsletter signup UI (the `subscribers` table and vendor page exist but the nav item is hidden) · gift cards · multi-address shipping · guest checkout (login is required) · product Q&A.

## 5. Storefront JSON / mobile APIs [V]
- `/api/v1/*` (`routes/api.php`): tenant identified by `Authorization: Bearer {tenant_id}`. Endpoints for categories, products, cart, coupon, checkout, orders, addresses, profile, reviews, favorites (session-guarded) via `App\Http\Controllers\Tenant\{CatalogController,CartController,CouponController,CheckoutController,OrderController,AddressController,ProfileController,ReviewController,FavoriteController}`. Documented for vendors in the panel help article "API reference".
- `/api/mobile/v1/*` (`routes/mobile.php`, 63 routes):
  - `X-Tenant` header, optional `X-Country`; response envelope middleware;
  - bearer customer tokens (`mobile_access_tokens`);
  - controllers `App\Http\Controllers\Mobile\*`: bootstrap, home, catalog, cart (`mobile_carts`), checkout, payment, orders extras, returns, pages, preferences (device tokens), resolve;
  - deep links via `/.well-known/*`.

## 6. Storefront issues and rebuild notes
| Issue | Priority |
|---|---|
| 3 themes × up to 6 hand-copied variants (header, footer, home, scripts, styles) → fixes must be repeated about 12 times; ~200 Vite inputs | Medium |
| Vendor Blade themes execute server-side (see master §18 S1) | Critical |
| `StorefrontRepository` god class; PHP-side price filtering | Medium |
| Session-only web cart (lost across devices), separate `mobile_carts` model | Low |
| **[R]** Rebuild the storefront as **section components** (one Blade component per section type with variant props and design tokens), a JSON page schema per tenant (sections, order, settings), and a safe template layer for vendor customization (no PHP) | — |
