# 01 - Screen x Theme x Variant Matrix (P0 inventory)

Source of truth for P1 (backend), P2 (panel), P4 (screens), P5 (themes), P7 (QA).
Derived from: `resources/views/themes/{ecommet,elora,souqify}`, `app/Livewire/Tenant/Storefront/*`,
`App\Services\Tenant\HomeVariantResolver`, `App\Services\Tenant\PageBuilder\SectionRegistry`, `HomeVariant` seed
migrations, `routes/tenant.php`, `routes/api.php`, `resources/css/{theme}-{key}.css`.
Method: file lists via grep/find; one representative blade per pattern. Visual details of header/footer variants were
NOT read in full (marked "skin from partial"); P5 must open the named partial when building that skin.

Totals: 3 themes, 13 home variants, 22 screens, 28 distinct section keys (collapsed to 17 Flutter widgets), 13 header + 13 footer skins.

---------------------------------------------------------------------------------------------------

## 1. Screens (storefront routes -> mobile screens)

Legend: Y = view exists in theme; shared = same Livewire component, theme only re-skins. "Mobile" = proposed go_router path.
Auth = needs customer token.

| # | Screen | Web route / component | ecommet | elora | souqify | Mobile route | Auth |
|---|--------|----------------------|---------|-------|---------|--------------|------|
| 1 | Home | `/` HomePage | Y (`pages/home`) | Y (`home`, `home-v2..v6`) | Y (`home`, `home-v2..v6`) | `/` | no |
| 2 | Category / listing (+filters, sort, tabs) | `/categories/{slug?}` CategoryPage | Y 219 lines | Y 416 (filter drawer, category tabs, price range) | Y 713 | `/category/:slug?` | no |
| 3 | Search results | `/search` -> **BestSellingPage** with `keyword`; `/search/autocomplete`, `/search/image` (image search) | Y | Y | Y | `/search?q=` | no |
| 4 | Product detail (variants, qty, reviews modal, reviews limit) | `/products/{slug}` ProductPage | Y 621 | Y 1663 | Y 1843 | `/product/:slug` | no (review/fav need auth) |
| 5 | Cart | `/cart` CartPage | Y | Y | Y | `/cart` | no (guest cart) |
| 6 | Checkout (address, billing=shipping toggle, gateways, coupon, summary) | `/checkout` CheckoutPage | Y 755 | Y 917 | Y 843 | `/checkout` | yes |
| 7 | Payment redirect / success / cancel | `/checkout/payment/{gateway}/{orderUuid}`, `/success`, `/cancel` PaymentController | web | web | web | in-app browser / deep link `/payment/result` | yes |
| 8 | Auth: login + register (tab) | `/account/login` AuthPage (guest only) | Y 119 | Y 119 | Y 152 | `/auth` | guest |
| 9 | Social login (Google / Apple) | `/auth/google`, `/auth/apple` | web | web | web | native SDK -> token exchange | guest |
| 10 | Profile (tabs: orders+status filter, profile, returns) | `/profile` ProfilePage | Y 357 | Y 783 (+`partials/account-sidebar`) | Y 776 | `/account`, `/account/orders`, `/account/returns` | yes |
| 11 | Favorites | `/favorites` FavoritesPage | **N** (no `favorites.blade`; route breaks/falls back - gap) | Y 259 | Y 179 | `/favorites` | yes |
| 12 | Order status | `/orders/{uuid}/status` OrderStatusPage | Y 226 | Y 504 | Y 460 | `/order/:uuid` | owner or uuid link |
| 13 | Order tracking | `/orders/{uuid}/tracking` OrderTrackingPage | Y 295 | Y 371 | Y 810 | `/order/:uuid/tracking` | same |
| 14 | Return request form | `/orders/{uuid}/return` RequestReturnForm | Y (1-line include of `livewire.tenant.storefront.partials.return-form-content`, shared) | same | same | `/order/:uuid/return` | yes |
| 15 | Return detail | `/account/returns/{id}` ReturnDetailPage | Y (shared include `return-detail-content`) | same | same | `/returns/:id` | yes |
| 16 | Invoice | `/orders/{uuid}/invoice` StorefrontInvoiceController | web (PDF/HTML) | web | web | open/share PDF from API | yes |
| 17 | Best selling | `/best-selling` BestSellingPage | Y 91 | Y 81 | Y 124 | `/list/best-selling` | no |
| 18 | New in | `/new-in` NewInPage | Y 54 | Y 54 | Y 63 | `/list/new-in` | no |
| 19 | Full star (top rated) | `/full-star` FullStarPage | Y 57 | **N** | **N** | `/list/top-rated` | no |
| 20 | Offers / flash deals | `/offers` OffersPage | **N** | **N** | Y 1101 | `/offers` | no |
| 21 | CMS page | `/pages/{slug}` PageView | Y 20 | Y 21 | Y 24 | `/page/:slug` | no |
| 22 | 404 / not-found | `/not-found` + fallback NotFoundPage | Y 15 | Y 15 | Y 15 | `/not-found` + router errorBuilder | no |

Per-theme notes (differences that matter for mobile):
- Product/cart/checkout/profile share the SAME Livewire state and data; only markup differs. One Flutter screen + per-theme skin tokens (card style, button radius, header chrome) is enough. Do NOT build 3 copies of logic.
- `ecommet`: single header/footer, no favorites page, no offers; has `_categories-slider`, `_showcase-card`, `country-select` section, `address-modal`.
- `elora`: 6 header + 6 footer partials (`header`, `header-v2..v6`), `account-sidebar`, `_carousel-nav`, category filter drawer (min/max price, sort, availability, rating, flag, sale), `x-elora.category-tabs`. v3 and v6 add a fixed mobile bottom bar (`pb-[80px]` body class) - maps to app bottom nav styling.
- `souqify`: 6 header + 6 footer partials; only theme with `/offers` (1101 lines); `_explore-card`, per-variant deal/trending card partials (v2 purple, v3, v4, v5 `new_in_card`, v6 pink).
- Cross-cutting UI: locale/currency modal (`LocaleSwitcher`, tabs `currency|language`), country select, cart dropdown (`CartDropdown`, `CartManager`), autocomplete search, image search, address modal, flash-sale coupon strip, social links in footer, footer text/copyright per language.
- Ignored for mobile: Page Builder custom/uploaded blade themes (`UploadedBladeTemplateStrategy`, `custom/default` HomeVariant).

Route query params used by listing pages (from CategoryPage): `keyword, sort(latest|...), availability, productFlag, sale, ratings, section, min, max, perPage=15`.

---------------------------------------------------------------------------------------------------

## 2. Home variants (13)

Variant resolution (backend, to reuse in bootstrap): `HomeVariantResolver::resolveFor(theme, country)` = TenantHomeVariant (country-specific) -> TenantHomeVariant (country null) -> HomeVariant `is_default` (v1).
Sections: `resolvedHomeSections` = `TenantPageSection` rows for (theme, variant, page=home) ordered by `sort_order`, only `is_visible`, unsaved registry keys appended; if no rows -> `variant.sections` -> else `SectionRegistry::defaultsFor(theme,'home')`. Final render = `@includeIf` per key, so **a key with no partial file in that variant folder silently renders nothing** (the "Rendered" column below is registry-default order filtered by file existence). `homeDataNeeds` (HOME_VIEW_DATA) prunes unused datasets per section.

Layout (header/footer/styles/scripts) is chosen in `themes/{theme}/layout/app.blade.php` by `$storefrontThemeVariant->key`: `v2..v6 -> header-vN/footer-vN/styles-vN/scripts-vN`, else base `header/footer`. Ecommet always base.

Default colors = `HomeVariant.colors` JSON (seeded by `2026_09_03_000002_seed_default_home_variant_colors`), tenant overrides in `tenant_theme_colors`.
Palette A (orange, "Elora") = `--color-primary #ff4d00, --color-secondary #ff522c, --color-bg-main #fdfdfd, --color-text-primary #121212, --color-text-subtitle #adadad, --color-success #2aaf2f, --color-error #de1709`.

| Theme | Key | Name | View | Header / Footer partial | Default colors | DB `sections` | Rendered section order (key -> has partial) |
|-------|-----|------|------|------------------------|----------------|---------------|---------------------------------------------|
| ecommet | v1 | Default | `themes.ecommet.pages.home` | `partials/header`, `footer` | primary `#111827`, secondary `#4b5563` | null | hero_banner, trust_bar, flash_deals, new_year_sale, flash_sale_strip, best_selling, promo_banner |
| elora | v1 | Default | `elora.pages.home` (164 lines, inline mobile header) | `header`, `footer` | primary `#111827`, secondary `#4b5563` | null | hero_banner, trust_bar, tabbed_products, category_swiper, flash_sale_strip, trending_now, category_strip, best_seller, new_arrivals, shop_by_category, recommended_for_you, promo_banner (`category_carousel` has NO partial in v1; `category_circles` partial is orphaned, not in registry) |
| elora | v2 | Purple Edition | `home-v2` | `header-v2`, `footer-v2` | Palette A | null | hero_banner, trust_bar, category_carousel, tabbed_products, flash_sale_strip, trending_now, category_strip, best_seller, new_arrivals, shop_by_category, recommended_for_you, promo_banner (no category_swiper) |
| elora | v3 | Fresh Edition (elora-3) | `home-v3` | `header-v3`, `footer-v3` (+mobile bottom bar) | Palette A | null | same 12 as v2 |
| elora | v4 | Bold Edition (elora-4) | `home-v4` | `header-v4`, `footer-v4` | Palette A | null | hero_banner, trust_bar, tabbed_products, flash_sale_strip, trending_now, category_strip, best_seller, new_arrivals, shop_by_category, recommended_for_you, promo_banner (11; no category_carousel) |
| elora | v5 | Minimal Edition (elora-5) | `home-v5` | `header-v5`, `footer-v5` | primary `#132092`, secondary `#ff522c`, bg-main `#fdfdfd`, `--color-black #121212`, `--color-subtitle #adadad`, success `#2aaf2f`, error `#de1709` | null | same 11 as v4 |
| elora | v6 | New In Edition (elora-2) | `home-v6` | `header-v6`, `footer-v6` (+mobile bottom bar) | Palette A | null | same 12 as v2 |
| souqify | v1 | Default | `souqify.pages.home` (411 lines, inline mobile header + inline JS fetch) | `header`, `footer` | primary `#111827`, secondary `#4b5563` | null | hero, trust_bar, trending_this_week, browse_categories, flash_sale, top_products, hero_banners, new_arrivals, explore_products, featured_products, hot_deals, special_offers, top_rated_products, recommended_products, promo_banner (all 15 have partials) |
| souqify | v2 | Purple Edition | `home-v2` | `header-v2`, `footer-v2` | Palette A | null | hero, trust_bar, trending_this_week, browse_categories, flash_sale, top_products, new_arrivals, explore_products, recommended_products (9; partials `purple_deal_card`, `purple_mobile_card`, `product_card`, `mini_card`) |
| souqify | v3 | Modern Edition (souqify-2 teal) | `home-v3` | `header-v3`, `footer-v3` | Palette A (placeholder; real teal not seeded) | null | same 9 as v2 (partials `deal_card`, `trending_card`) |
| souqify | v4 | Green Edition (souqify-3) | `home-v4` | `header-v4`, `footer-v4` | Palette A (placeholder) | null | same 9 as v2 |
| souqify | v5 | Orange Edition (souqify-4) | `home-v5` | `header-v5`, `footer-v5` | Palette A (placeholder) | explicit | hero, trust_bar, browse_categories, flash_sale, new_arrivals, trending_this_week, explore_products, top_products, recommended_products (partial `new_in_card`) |
| souqify | v6 | Pink Edition (souqify-5) | `home-v6` | `header-v6`, `footer-v6` | Palette A (placeholder) | explicit | same 9 as v5 (partials `pink_deal_card`, `pink_mobile_card`) |

Per-variant visual type of each section: the DATA contract of a section key is identical across variants of a theme (confirmed by `ELORA_*`/`SOUQIFY_VARIANT_SECTIONS_DATA` maps in HomePage); only the partial skin changes. So the mobile widget is parameterised: `SectionWidget(sectionKey, skin = '<theme>:<variantKey>')`, skin controls card style, accent colors and layout flavour (carousel vs grid vs split). Concrete skin per (section, variant) = the blade at `themes/{theme}/pages/{home|home-vN}/sections/{key}.blade.php`; P5 reads that file when implementing. Defaults: Elora/Souqify v1 = base skins; v2..v6 = alternate skins listed above.

Header/footer skin ids needed by P5: ecommet `base`; elora `base,v2,v3,v4,v5,v6`; souqify `base,v2,v3,v4,v5,v6` (13 header + 13 footer skins; on mobile a header = app bar [logo/store name, search, cart, favorites, locale] + optional drawer menu with `rootCategories`; footer = about/links/social/pages list reachable from a "More" screen).

---------------------------------------------------------------------------------------------------

## 3. Union of section types -> Flutter widgets

28 distinct registry keys. Data names are the `HomePage::render()` datasets; repo methods are `StorefrontRepository`.

| Flutter widget (one per row) | Section keys mapped | Themes | Data source today | Mobile endpoint needed (bootstrap section payload or `/home/data`) | Config keys |
|------------------------------|---------------------|--------|-------------------|--------------------------------------------------------------------|-------------|
| `HeroBannerCarousel` | ecommet/elora `hero_banner`; souqify `hero` | all | `repo->activeBanners()` (banner: image_path, translations title/subtitle/button_text, link) | `sections[].data.banners` | autoplay, aspect, overlay, skin |
| `HeroBannersGrid` | souqify `hero_banners` | souqify v1 | banners/static + category links | banners + category link | layout |
| `TrustBar` | `trust_bar` | all | STATIC i18n strings + inline SVG (Secure Privacy, Safe payments, ...) | none (static list in app, localised) | items (icon, label) |
| `CategoryCircles / Carousel` | elora `category_carousel`, `category_swiper` (2 rows), `category_strip`; ecommet `_categories-slider`; souqify `browse_categories` | elora, souqify, ecommet | shared `categories`/`rootCategories` (StorefrontComposer) | `sections[].data.categories` (tree w/ image, slug) | rows(1/2), shape(circle/card), maxItems |
| `CategoryPillsProductGrid` | ecommet `new_year_sale` | ecommet | `categories` + `newInProducts` | categories + new_in | - |
| `FlashSaleStrip` | `flash_sale_strip` (ecommet, elora) | ecommet, elora | `repo->activeFlashSales()`, `flashBanner`, `flashCouponCode` (latest valid coupon for country) | `flash: {sales[], products[], banner, coupon_code, ends_at}` | showCoupon |
| `FlashDealsRail` | ecommet `flash_deals`; souqify `flash_sale` (hero deal + cards + countdown) | ecommet, souqify | `flashSales`, `flashProducts` (paged by `flashPage`) | same `flash` payload + `has_more` | layout(hero+rail), countdown |
| `ProductRail` (carousel / grid / mini cards; card skin param) | elora `trending_now`, `best_seller`, `new_arrivals`, `recommended_for_you`; ecommet `best_selling`; souqify `trending_this_week`, `top_products`, `new_arrivals`, `explore_products`, `featured_products`, `hot_deals`, `top_rated_products`, `recommended_products`, `special_offers` | all | `trendingNowProducts(10)`, `bestSellingProducts`, `newInProducts`, `recommendedProducts`, `featuredProducts(10)`, `homeTopRatedProducts`, `homeProducts` (paginated), `topRatedProducts` | `sections[].data.products[]` + `see_all` target (`storefront_see_all_url`) + `has_more` | source enum (trending,best_selling,new_in,recommended,featured,top_rated,products,hot_deals,offers), limit, cardSkin |
| `TabbedProducts` | elora `tabbed_products` (tabs `cat-flash, cat-bestselling, cat-newin, cat-toprated`) | elora | flash + bestSelling + newIn + topRated; JS endpoint `/api/home/tabbed-products` | one payload per tab, lazy `GET .../home/tabbed?tab=` | tabs[], defaultTab |
| `ShopByCategoryBuyTogether` | elora `shop_by_category` | elora | `categories` + `randomActiveProducts(2)` (buy-together pair) | categories + 2 products | - |
| `PromoBanner` | `promo_banner` | all | tenant settings `promo_banner_image_url, promo_banner_mobile_image_url, promo_banner_link, promo_banner_title, promo_banner_subtitle, promo_banner_cta_text` via `HomeViewData::settings` (rendered only if image or title set) | `sections[].data.promo` | link target mapping (web URL -> in-app route) |
| `SpecialOffersTeaser` | souqify `special_offers` | souqify v1 | `paginatedProducts` + link to `/category` | products | - |
| `HeaderBar` / `FooterInfo` | header/footer partials | all | StorefrontComposer: logo, store name, categories, social links, cart count, footer text/copyright | bootstrap `layout` | skin id |

Widgets counted: HeroBannerCarousel, HeroBannersGrid, TrustBar, CategoryCircles, CategoryPillsProductGrid, FlashSaleStrip, FlashDealsRail, ProductRail, TabbedProducts, ShopByCategoryBuyTogether, PromoBanner, SpecialOffersTeaser, HeaderBar, FooterInfo (14 section/chrome widgets) + shared `ProductCard` (skins: ecommet/elora/souqify `_product-card`, `_showcase-card`, `_explore-card`, deal/trending/new_in/pink/purple cards) + `Pagination/LoadMore` + `RatingStars` (`_stars`) = 17.

Section keys by theme (28 distinct total):
- ecommet (7): hero_banner, trust_bar, flash_deals, new_year_sale, flash_sale_strip, best_selling, promo_banner.
- elora (13): hero_banner, trust_bar, category_carousel, tabbed_products, category_swiper, flash_sale_strip, trending_now, category_strip, best_seller, new_arrivals, shop_by_category, recommended_for_you, promo_banner.
- souqify (15): hero, trust_bar, trending_this_week, browse_categories, flash_sale, top_products, hero_banners, new_arrivals, explore_products, featured_products, hot_deals, special_offers, top_rated_products, recommended_products, promo_banner.
- Orphan partial: elora `category_circles` (present in v1/v2/v3/v6 folders, absent from registry; ignore).

Dataset names used by HomePage `HOME_VIEW_DATA`: banners, flash, flashCoupon, newIn, bestSelling, bestSellingIfNoRecommended, topRated, productsIfNoTopRated, trending, featured, recommended, products, buyTogether. The bootstrap/home endpoint must deliver the same union per visible section only.

---------------------------------------------------------------------------------------------------

## 4. Tenant-changeable settings today -> mobile bootstrap fields

| Panel area (component) | Setting | Stored in | Bootstrap field (mobile) | Notes |
|------------------------|---------|-----------|--------------------------|-------|
| Appearance > General (`AppearancePage`) | logo_mode (image/text), logo_text_ar/en, logo_color, logo_bg_color, logo_shape, logo_font_ar/en, logo_path_ar/en | tenant `Setting` | `branding.logo{mode,text{ar,en},color,bg,shape,font,image{ar,en}}` | text logos are rendered by font/shape - app needs either server-rendered PNG (`LogoGeneratorService`) or font set; prefer API returns resolved `logo_url` per locale |
| Appearance > Colors | per theme/variant color overrides | `tenant_theme_colors` merged over `HomeVariant.colors` | `theme.colors{--var: hex}` mapped to Flutter tokens | var names differ by variant (`--color-primary/secondary/bg-main/text-primary/text-subtitle/success/error`, elora-v5 `--color-black/--color-subtitle`) -> normalise server-side |
| Appearance > Banners | hero banners (image_path, title, subtitle, button_text, link, order, active) | tenant banners table | `home.hero.banners[]` | translations via `translationValue` |
| Appearance > Social links | icon, url, serial_number | `SocialLink` | `social_links[]` | |
| Appearance > Promo banner | promo_banner_* (6 keys) | `Setting` | `home.promo` | |
| Appearance > Footer | footer_text, footer_copyright per language | Setting + translations | `footer{text,copyright}` per locale | |
| Themes (`ThemesPage`) | active theme (ecommet/elora/souqify/uploaded) | `themes` | web theme only; mobile theme is independent (Master plan #4) | |
| Home Variants (`HomeVariantsPage`) | variant per theme, optional per country | `TenantHomeVariant` | web only; mobile chooses via `mobile_app_theme` (P2) | can default to web choice if mobile unset |
| Page Builder (`PageBuilderPage`) | section order + visibility per (theme, variant, page=home) | `TenantPageSection` | `home.sections[]` ordered visible (mobile may reuse or own copy) | decision for P1/P2: reuse web rows as default, allow override |
| Settings > Languages (`LanguagesPage`, `LanguagesManagePage`) | active languages, default, RTL | `languages` | `locales[{code,name,rtl,default}]` | `repo->activeLanguages()` |
| Settings > Currencies (`CurrenciesPage`) | active currencies, rates, default | `currencies` | `currencies[{code,symbol,rate,default}]` + `currentCurrency` | price conversion is server side (`currentCurrency` in product-card) |
| Settings > General (`GeneralSettingsPage`) | store name, contact, etc. | `Setting` | `store{name,email,phone,...}` | exact keys: P1 to grep |
| Settings > Payment Gateways / Readiness | enabled gateways + payment_methods badges | gateways tables | checkout `gateways[]` | |
| Settings > Return Policy | return window/rules | Setting | `features.returns` + policy text | |
| Settings > Tracking (`TrackingSettingsPage`) | FB pixel / GA etc. | Setting | optional `analytics` ids (not required) | |
| Settings > Domains (`DomainsList`) | tenant domains | `domains` (central) | `/resolve` | |
| CMS Pages (`PagesList`, `AddEditPage`) | page slug/title/content | pages | `pages[]` for footer/More menu | |
| Store launch gate (`store.launch.gate` middleware) | store open/closed | tenant | `status`/maintenance | |
| Country detection (`CountryDetectorService`, `CustomerCountryResolver`) | customer country -> variant + shipping + coupons | request | `country` header/param | |
| Mobile-only (new, P2) | app name, splash, bottom-nav, feature flags, force-update, support links | `mobile_app_settings` | `settings` | per Master plan |

---------------------------------------------------------------------------------------------------

## 5. Gap list - backend endpoints needed per screen

Existing `/api/v1` (routes/api.php): categories (list/show), products (list/show), product reviews (list/store), cart add/remove/update (NO cart read), favorites toggle/list/ids, addresses CRUD+default, checkout store, coupon show/apply/remove, profile show/update/password/logout, orders index/show/cancel/reorder/cancellation-reasons. Auth is `Bearer tenant_id` + `auth:storefront` session guard -> unusable by mobile (Master plan). Everything below goes under `/api/mobile/v1`.

| Screen / need | Gap (missing today) | Reuse |
|---------------|--------------------|-------|
| Tenant bootstrap / resolve | `GET resolve?host=`; `GET bootstrap` (tenant, settings, theme slug, variant key, ordered visible sections + data, colors, locales, currencies, feature flags, config_version ETag, branding, social links, footer, pages) | HomeVariantResolver, SectionRegistry, TenantPageSection, StorefrontRepository |
| Home | `GET home` or section data embedded: banners, flash (+coupon code), new-in, best-selling, top-rated, trending, featured, recommended, products, buy-together, promo, tabbed (lazy) | StorefrontRepository methods listed in section 3 |
| Auth | login, register (name,email,phone,password,confirm), logout, refresh, forgot/reset password, social (Google/Apple token exchange), token table | AuthPage rules, StorefrontSocialAuthController |
| Category / listing | listing API with filters `keyword, sort, availability, productFlag, sale, ratings, section, min, max, page`; category tree w/ images; facet metadata (price min/max, rating options, flags) | CatalogController, CategoryPage queries, StorefrontSearchController |
| Search | autocomplete, image search (multipart), search results (best-selling-page logic) | exist as web routes `/search/autocomplete`, `/search/image` |
| Lists | best-selling, new-in, full-star, offers (flash sales + products, pagination/has_more) | repo methods |
| Product | detail incl. variants, stock, price per currency/country, shipping estimate, related, reviews list/store, view-content tracking | CatalogController::product, ReviewController, ProductPriceCalculationService, ShippingEstimateService |
| Cart | `GET cart` (read + totals + free-shipping progress + stock check + coupon), merge guest->user cart, line update | CartPage, CartManager, CalculatesFreeShipping, ChecksCartStock; totals ONLY via `OrderPricing` (CLAUDE.md) |
| Checkout | quote (items, shipping, tax, coupon, totals), gateways list with payment_methods, address select/create, place order, payment initiation + return URL (deep link), idempotency | CheckoutPage, App\Services\Tenant\Checkout\*, `OrderComposer`, `OrderPricing` - read `docs/ORDER_FORMULAS.md` first |
| Coupon | show/apply/remove exist but session-bound | CouponController |
| Profile | profile CRUD exists (session-bound); add avatar? (check ProfilePage), delete account (store policy) | ProfileController |
| Orders | list with status filter + pagination, show by uuid (guest-with-uuid allowed?), tracking timeline, invoice PDF URL, cancel (+reasons), reorder | OrderController, OrderTrackingPage, StorefrontInvoiceController |
| Returns | list returns, return eligibility + reasons, create return (items, qty, reason, photos upload), return detail, cancel return | RequestReturnForm, ReturnDetailPage, ReturnPolicyPage settings |
| Favorites | exists, needs token auth variant | FavoriteController |
| Addresses | exists, needs token auth; countries/states list (`country-select`), country-aware | AddressController, Country model |
| CMS page / footer | `GET pages`, `GET pages/{slug}` | PageView |
| Locale / currency / country | list + set (persist in token/session headers `Accept-Language`, `X-Currency`, `X-Country`) | LocaleSwitcher, CustomerCountryResolver, CountryDetectorService |
| Notifications / push (not in web) | FCM token register endpoint (optional, out of scope unless requested) | - |
| Deep links | `/.well-known/assetlinks.json`, `apple-app-site-association`; link parser table for web URLs -> in-app routes | Master plan #7 |
| Favorites page in ecommet | web has none; mobile will show it for all themes (design decision) | - |
| Offers / Full-star page in elora/ecommet/souqify | missing views per theme (see section 1); mobile shows them in all themes, backend provides data | OffersPage/FullStarPage logic |

Known inconsistencies to preserve/decide (flag for P1/P5):
1. `SectionRegistry` is one list per theme but variants have different partial sets -> Page Builder shows keys that render nothing (e.g. elora v4/v5 `category_carousel`; souqify v2..v6 lack `hero_banners, featured_products, hot_deals, special_offers, top_rated_products, promo_banner`). Bootstrap must filter by a per-variant allowed-key map.
2. Souqify v5/v6 `sections` are explicit with a different order from the registry-driven v2..v4.
3. Souqify v3..v6 default colors are copies of the orange palette (no real teal/green/orange/pink palette seeded) - P5 should define real tokens per variant from `resources/css/souqify-*.css` (only `souqify-v2.css` exists) or from the blade classes.
4. `/search` is served by `BestSellingPage`.
5. `full-star`/`offers` have no view in some themes (web routes can 500); mobile must not inherit this.
6. Section data semantics: `recommended_for_you` falls back to bestSelling (`bestSellingIfNoRecommended`), souqify `top_products` falls back via `productsIfNoTopRated`; replicate in API.

---------------------------------------------------------------------------------------------------

## 6. P7 verification checklist

Mark each row Done/Partial/N/A with file evidence.

| ID | Item | Where defined | Required in app | Required in API/panel | Status |
|----|------|---------------|-----------------|-----------------------|--------|
| S01 | Home (13 variants) | sec 2 | theme x variant layouts | bootstrap + home data | |
| S02 | Category listing + filters/sort | sec 1 | screen | listing API | |
| S03 | Search + autocomplete + image search | sec 1 | screen | 3 endpoints | |
| S04 | Product detail + variants + reviews | sec 1 | screen | product + reviews | |
| S05 | Cart | sec 1 | screen | cart read/merge/totals | |
| S06 | Checkout + gateways + coupon | sec 1 | screen | quote/place/payment | |
| S07 | Payment result / return deep link | sec 1 | screen | callback URL scheme | |
| S08 | Login / register / forgot password | sec 1 | screen | auth/* | |
| S09 | Social login Google/Apple | sec 1 | optional | token exchange | |
| S10 | Profile + Orders list + Returns list | sec 1 | 3 tabs | endpoints | |
| S11 | Favorites | sec 1 | screen | token variant | |
| S12 | Order status | sec 1 | screen | order show | |
| S13 | Order tracking | sec 1 | screen | tracking endpoint | |
| S14 | Return request | sec 1 | screen | return create | |
| S15 | Return detail | sec 1 | screen | return show | |
| S16 | Invoice | sec 1 | open/share | invoice URL | |
| S17 | Best selling list | sec 1 | screen | list | |
| S18 | New in list | sec 1 | screen | list | |
| S19 | Full star list | sec 1 | screen | list | |
| S20 | Offers | sec 1 | screen | list | |
| S21 | CMS page | sec 1 | screen | pages | |
| S22 | 404 / not found / store unavailable / maintenance / force update | sec 1, 4 | screens | status in bootstrap | |
| V01-V13 | ecommet v1; elora v1-v6; souqify v1-v6 each has home layout, header skin, footer skin, colors | sec 2 | ThemeRegistry entry | variant list in panel (P2) | |
| W01 | HeroBannerCarousel | sec 3 | widget | banners | |
| W02 | HeroBannersGrid | sec 3 | widget | banners | |
| W03 | TrustBar | sec 3 | widget | none | |
| W04 | CategoryCircles/Carousel/Swiper/Strip | sec 3 | widget | categories | |
| W05 | CategoryPillsProductGrid | sec 3 | widget | categories + new-in | |
| W06 | FlashSaleStrip | sec 3 | widget | flash + coupon | |
| W07 | FlashDealsRail | sec 3 | widget | flash | |
| W08 | ProductRail (all 12 product-list keys) | sec 3 | widget | products by source | |
| W09 | TabbedProducts | sec 3 | widget | tabbed endpoint | |
| W10 | ShopByCategoryBuyTogether | sec 3 | widget | categories + 2 products | |
| W11 | PromoBanner | sec 3 | widget | promo settings | |
| W12 | SpecialOffersTeaser | sec 3 | widget | products | |
| W13 | HeaderBar x13 skins / FooterInfo x13 skins | sec 2 | widgets | layout data | |
| W14 | ProductCard skins, RatingStars, LoadMore | sec 3 | widgets | - | |
| C01 | Branding: logo (image/text), name, social links, footer text | sec 4 | bootstrap | panel (exists) | |
| C02 | Colors per variant (normalised tokens) | sec 4 | theme tokens | bootstrap | |
| C03 | Locales + RTL | sec 4 | l10n | bootstrap | |
| C04 | Currencies + conversion | sec 4 | formatting | bootstrap + product price | |
| C05 | Country selection / detection | sec 4 | picker | header param | |
| C06 | Section order + visibility honoured; per-variant allowed keys | sec 2/5 | registry | bootstrap | |
| C07 | Mobile-only settings (name, splash, nav, flags, force update, maintenance) | sec 4 | consumed | P2 panel | |
| C08 | Mobile theme independent of web theme; custom themes excluded | Master #4 | - | panel | |
| A01 | Token auth (not `Bearer tenant_id`/session guard) | sec 5 | dio interceptor | mobile_access_tokens | |
| A02 | `X-Store` / `X-Tenant` isolation; per-tenant caches | Master #2, #8 | isolation | middleware | |
| A03 | Pricing/orders via OrderPricing/OrderComposer only | CLAUDE.md | no client math beyond display | checkout/cart endpoints | |
| A04 | Deep links (App Links/Universal Links + fallback) | Master #7 | app_links | well-known files | |
| G01-G06 | Inconsistencies in sec 5 resolved or documented | sec 5 | - | - | |
