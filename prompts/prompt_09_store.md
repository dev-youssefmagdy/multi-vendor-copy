# Prompt 09 — Store: Themes, Pages, Coupons, Flash Sales, Banners, Appearance + Logo Builder, Blade Theme, Page Builder, Home Variants

Requires prompts 01–03. Follow the **Standard Conversion Recipe** (prompt 00).
Controllers: `app/Http/Controllers/Tenant/Panel/Store/`. Views: `resources/views/tenant/pages/store/`. JS: `resources/js/tenant/pages/store/`.
Keep each route's permission middleware exactly:

| Area | Permission |
|---|---|
| themes | `store.themes.manage` |
| pages | `store.pages.manage` |
| coupons | `store.coupons.manage` |
| flash sales | `store.flash-sales.manage` |
| appearance, banners | `store.appearance.manage` |
| blade theme | `store.blade-theme.manage` |
| page builder | `store.page-builder.manage` |
| home variants | `store.home-variants.manage` |

---

## 1. Themes — `tenant.store.themes` (`ThemesPage` → `store/themes-page.blade.php`, 474 lines, inline `<style>`)
- A card grid (not a table). `index()` builds the same data as `pageData()` (themes, variants, the active one, country assignments).
- Actions:

| Livewire | Endpoint | Toast |
|---|---|---|
| `activateTheme(id)` | `POST store/themes/{theme}/activate` | "Theme activated successfully." |
| `activateVariant(themeId, variantId)` | `POST store/themes/{theme}/variants/{variant}/activate` | "Variant activated successfully." |
| `deactivateTheme(id)` | `POST store/themes/{theme}/deactivate` | "Theme deactivated." (a service exception → 422 with its message) |
| `openCountries` / `toggleCountry` / `saveCountries` | `GET store/themes/{theme}/countries` (JSON: all countries + enabled ids) · `PUT store/themes/{theme}/countries` (`country_ids[]`) | "Theme countries updated successfully." |

- The countries modal: `x-tenant::modal` with an `x-tenant::checkbox-group select-all` plus a client-side search box that filters the rendered list (replaces `countriesSearch`). `getModalCountriesProperty` logic moves to the GET endpoint.
- After activate/deactivate, use `success="reload-page"` (the active badges and sidebar state depend on it). Also emit `tenant:setup-progress:refresh`, because theme selection is a setup step.
- The `<style>` moves to `resources/css/tenant/pages/themes.css`.

## 2. Pages — `tenant.store.pages`, `.pages.create`, `.pages.edit`
- **List** (`PagesList`):
  - **Keep the guard from `mount()`**: if no payment gateway is active, redirect to `tenant.settings.payment-gateways` with the warning flash "Please activate at least one payment gateway before managing store pages.". Put it in the controller `index()` (and `create`/`edit` if the Livewire class had it there too).
  - Stats `pageStats()`. Columns Page, Slug, Status, Updated At, Actions.
  - Data from the builder behind `paginatePages()`.
  - Delete → `DELETE store/pages/{page}` with a confirm; toast "Page deleted successfully.". Keep any "system/compliance page cannot be deleted" guards present in the service.
- **Form** (`AddEditPage` → `store/add-edit-page.blade.php`, uses Alpine):
  - `slug` (the `slug-from` default-locale title)
  - `active` switch
  - `x-tenant::locale-tabs` with `translations.{code}.title` (required for the default locale, otherwise nullable — the exact `rules()` loop) and `translations.{code}.content` via `x-tenant::editor`
  - `store`/`update` → `savePage`, redirect to `tenant.store.pages.edit` (JSON), plus 2 validate routes
  - `SavePageRequest::rules()` builds the per-language rules from `activeLanguages()` exactly like the Livewire `rules()`

## 3. Coupons — `tenant.store.coupons.index` + `tenant.store.coupons.list/{countryId?}`
- **Index** (`CouponsIndexPage`): country cards linking to the list, a port of `coupons-index-page.blade.php`.
- **List** (`CouponsPage`, `$countryId`):
  - Stats `couponStats()`. Columns Coupon, Type, Value, Window, Actions.
  - Data from the builder behind `paginateCoupons($countryId)` → `queryCoupons(?int $countryId)`. The data route carries `countryId`.
  - Create/Edit modal fields:
    - `code` (required, max:50, unique `coupons,code,{id}` — keep the exact rule, written as `Rule::unique('coupons','code')->ignore($id)`)
    - `name_text` (required max:255)
    - `type` (`fixed|percentage`, `x-tenant::radio-group variant="pills"`)
    - `value` (numeric min:0)
    - `minimum_spend`
    - `start_date` / `end_date` (`x-tenant::daterange` or two `x-tenant::date`)
    - copy the remaining rules from `save()`
  - Endpoints: `GET store/coupons/{coupon}` (edit JSON) · `POST store/coupons` · `PUT store/coupons/{coupon}` · `DELETE store/coupons/{coupon}` · validate routes. `country_id` is a hidden field from the route.
  - Toasts: "Coupon created successfully." / "Coupon updated successfully." / "Coupon deleted successfully.".

## 4. Flash sales — `tenant.store.flash-sales.index` + `tenant.store.flash-sales/{countryId?}`
- **Index** (`FlashSalesIndexPage`): country cards.
- **List** (`FlashSalesPage` + `store/partials/flash-sale-form.blade.php`):
  - Stats `flashSaleStats()`. Columns Product, Banner, Discount, Window, Source, Status, Actions.
  - Data `queryFlashSales(?int $countryId)`. Use `productNamesForIds` / `productImagesForIds` batched per draw (not per row).
  - Modal form:
    - `product_ids[]` — `x-tenant::select2 multiple ajax-url` → `GET store/flash-sales/products/search`, returning `Select2Response` from `searchProducts()` (replaces `productSearch`/`loadMoreProducts`/`toggleProduct`/`removeProduct`). Preselected ids on edit come back as `_options` in the edit JSON so select2 can render them.
    - `discount_percentage` 0–100
    - `start_date` / `end_date` (`after_or_equal:start_date`)
    - `active` switch
    - `banner_image` via `x-tenant::image-upload removable` (the `FlashSaleMediaService::syncBanner` logic)
    - the remaining rules from `save()`
  - Endpoints mirror coupons, plus the product search. Toasts: "Flash sale created successfully." / "Flash sale updated successfully." / "Flash sale deleted successfully.".

## 5. Banners — `tenant.store.banners.index` + `tenant.store.banners/{countryId?}`
- **Index** (`BannersIndexPage`): country cards.
- **List** (`BannersPage` → `store/banners-page.blade.php`, has a `<table>`, SortableJS, `x-dropzone`):
  - The banner list is **reorderable** (`updateBannerOrder`), so render it as `x-tenant::sortable-list` rows (thumbnail, url, serial, actions), with save → `POST store/banners/{countryId}/order`, toast "Banner order saved.".
  - This is a deliberate exception to the "record list = DataTable" rule, because drag ordering and server paging conflict. Banners per country are a short list.
  - Modal:
    - `url`, `serial` (required integer min:0)
    - `x-tenant::locale-tabs` with a per-locale image upload (`bannerTranslations`)
    - the rules loop exactly as `saveBanner()`, with `PlanLimitService` `canPerform` / `errorMessage` on create
  - Endpoints: `GET store/banners/item/{banner}` (edit JSON) · `POST store/banners` · `PUT store/banners/item/{banner}` · `DELETE store/banners/item/{banner}` · validate routes. Use the `item/` prefix so it doesn't clash with `banners/{countryId?}`.
  - Toasts: "Banner saved successfully." / "Banner deleted.".

## 6. Appearance — `tenant.store.appearance` (`AppearancePage` → `store/appearance-page.blade.php` + `store/partials/logo-builder.blade.php`, both with inline `<style>`)
- `x-tenant::tabs mode="hash"` with the same tab keys as `setTab()`/`$activeTab`. Each tab is its own `x-tenant::form`:

| Tab / Livewire | Endpoint | Rules / notes | Toast |
|---|---|---|---|
| General → `saveGeneral()` | `PUT store/appearance/general` | Copy verbatim: `logo_mode` in `text,image`; `logo_color` `#RRGGBB`; `logo_bg_color` `#RRGGBB` or `transparent`; `logo_shape` in `rectangle,rounded`; `logo_font_ar` / `logo_font_en` in `StorefrontRepository::LOGO_FONTS` keys; the logo texts and image uploads (`logoPathAr`/`logoPathEn` uploads) with their existing rules. `toggleLogoBgTransparent` and `updatedLogoBgColorHex` are client-side via `x-tenant::color allow-transparent`. | "General settings saved successfully." |
| Colors → `saveColors(themeId, variantId)` | `PUT store/appearance/colors/{theme}/{variant}` | `values.{property}` required regex `^#[0-9a-fA-F]{3,8}$` (the same loop) | "Storefront colors saved successfully." |
| Colors → `resetColors(themeId, variantId)` | `POST store/appearance/colors/{theme}/{variant}/reset` (confirm) | — | "Storefront colors reset to the default." Return the defaults so the JS refills the inputs. |
| Social links → `openSocialModal` / `saveSocialLink` / `deleteSocialLink` | `GET store/appearance/social/{link}` · `POST store/appearance/social` · `PUT store/appearance/social/{link}` · `DELETE store/appearance/social/{link}` | `icon` required max:50 + the rest from `saveSocialLink()` | "Social link saved successfully." / "Social link deleted." |
| Promo banner → `savePromoBanner()` | `PUT store/appearance/promo-banner` | from the method | "Promotional banner updated." |
| Footer → `saveFooter()` | `PUT store/appearance/footer` | locale-tabs for footer translations (`saveFooterTranslations`) | "Footer settings saved successfully." |

- The social links list is a small list inside the tab. Render it as a client-mode `x-tenant::datatable` (Icon, URL, Actions) and reload it after save/delete via a `GET store/appearance/social` fragment or a full JSON reload.
- **Logo builder** becomes a shared partial `resources/views/tenant/pages/store/_logo-builder.blade.php`, used by Appearance **and** Onboarding (prompt 11). Its live preview JS goes in `resources/js/tenant/modules/logo-builder.js`:
  - text/image mode switch
  - font preview
  - colour/background/shape preview
  - the image upload preview
- **Logo fonts are self-hosted**. Create `resources/css/tenant/pages/logo-fonts.css`, importing only the families in `StorefrontRepository::LOGO_FONTS`:
  - `@fontsource/{cairo,tajawal,almarai,noto-sans-arabic,ibm-plex-sans-arabic,readex-pro,lemonada,reem-kufi,amiri,scheherazade-new}/{400,700}.css` plus `@fontsource/lalezar/400.css`
  - `@fontsource/{poppins,montserrat,inter,nunito,dm-sans,oswald,raleway,space-grotesk,playfair-display,cormorant-garamond,lora}/{400,700}.css` plus `@fontsource/bebas-neue/400.css`
  - `npm i` all 23 `@fontsource/*` packages.
  - `logo-builder.js` imports that CSS, so the fonts ship **only** on Appearance and Onboarding. This replaces the 24-family Google `<link>` that was in the tenant layout.
  - Keep `font-display: swap` (the fontsource default).
- The `<style>` blocks of `appearance-page` and `logo-builder` move to `resources/css/tenant/pages/appearance.css`.

## 7. Blade theme — `tenant.store.blade-theme` (`BladeThemePage`)
- `index()` shows the same data. The uploads table becomes a client-mode `x-tenant::datatable` (version, status, uploaded at, actions).
- `submitUpload()` → `POST store/blade-theme` with `theme_zip` required file mimes:zip max:20480, via `BladeThemeService::upload(...)` exactly. Use `x-tenant::file accept=".zip"` and show upload progress through `http.upload` progress. Toast "Theme uploaded and queued for admin review.".
- `deactivate()` → `POST store/blade-theme/deactivate` (confirm), toast "Blade theme deactivated. Your storefront now uses the system theme again.".
- `delete(id)` → `DELETE store/blade-theme/{upload}` (confirm), toast "Theme deleted.". Port its guards.
- The starter-kit download route is unchanged.
- **Do not change** `BladeThemeService` / the symlink activation (`activateLatestApprovedForCurrentTenant`) behaviour.

## 8. Page builder — `tenant.store.page-builder` (`PageBuilderPage`, uses `SectionRegistry`)
- The theme/variant selectors are `x-tenant::select` in a GET form (`?theme=…&variant=…`), replacing `selectTheme`/`selectVariant`. Preserve the current default resolution when the params are missing.
- The section list uses `x-tenant::sortable-list` with save → `POST store/page-builder/order` (`ids[]`, `theme_id`, `home_variant_id`). Port `updateOrder()` exactly. Toast "Section order saved.".
- `toggleVisibility(sectionId)` → `x-tenant::switch action-url="…/sections/{section}/visibility"`, toast "Section visibility updated.".
- Inline `<script>` / `<style>` move to `pages/store/page-builder.js` / `resources/css/tenant/pages/page-builder.css`.

## 9. Home variants — `tenant.store.home-variants` (`HomeVariantsPage`)
- The theme selector is a GET param (replaces `selectTheme`).
- `selectVariant(variantId|null)` → `POST store/home-variants` (`theme_id`, `variant_id` nullable). Port the exact logic:
  - null/default → "Reverted to the theme's default variant."
  - else → "Home page variant saved."
- The variant list uses `x-tenant::radio-group variant="cards"` plus a submit (or instant submit on change). The existing table becomes that card list. If the view renders a table of variants, keep it as a client-mode datatable with an Activate action.
- The `<style>` moves to `resources/css/tenant/pages/home-variants.css`.

## 10. Routes to add (inside the `store` prefix; every route carries its area's permission)
```
themes:        POST themes/{theme}/activate · POST themes/{theme}/variants/{variant}/activate · POST themes/{theme}/deactivate
               GET themes/{theme}/countries · PUT themes/{theme}/countries
pages:         GET pages/data · POST pages · POST pages/validate · PUT pages/{page} · POST pages/{page}/validate · DELETE pages/{page}
coupons:       GET coupons/data/{countryId?} · GET coupons/item/{coupon} · POST coupons · POST coupons/validate
               PUT coupons/item/{coupon} · POST coupons/item/{coupon}/validate · DELETE coupons/item/{coupon}
flash-sales:   GET flash-sales/data/{countryId?} · GET flash-sales/products/search · GET flash-sales/item/{flashSale}
               POST flash-sales · POST flash-sales/validate · PUT flash-sales/item/{flashSale} · POST flash-sales/item/{flashSale}/validate · DELETE flash-sales/item/{flashSale}
banners:       GET banners/item/{banner} · POST banners · POST banners/validate · PUT banners/item/{banner} · POST banners/item/{banner}/validate
               DELETE banners/item/{banner} · POST banners/{countryId}/order
appearance:    PUT appearance/general (+validate) · PUT appearance/colors/{theme}/{variant} (+validate) · POST appearance/colors/{theme}/{variant}/reset
               GET appearance/social · GET appearance/social/{link} · POST appearance/social (+validate) · PUT appearance/social/{link} (+validate)
               DELETE appearance/social/{link} · PUT appearance/promo-banner (+validate) · PUT appearance/footer (+validate)
blade-theme:   POST blade-theme (+validate) · POST blade-theme/deactivate · DELETE blade-theme/{upload}
page-builder:  POST page-builder/order · PATCH page-builder/sections/{section}/visibility
home-variants: POST home-variants
```
- Every new `data`/`item`/`search` route is declared **before** the existing `…/list/{countryId?}` and `banners/list/{countryId?}` style routes. Keep the existing GET names (`tenant.store.coupons.list`, `tenant.store.flash-sales`, `tenant.store.banners`, …).

## 11. Vite entries
```
'resources/js/tenant/pages/store/themes.js',
'resources/js/tenant/pages/store/pages-index.js',
'resources/js/tenant/pages/store/page-form.js',
'resources/js/tenant/pages/store/country-index.js',     // coupons/flash-sales/banners index cards (if any JS; else omit)
'resources/js/tenant/pages/store/coupons.js',
'resources/js/tenant/pages/store/flash-sales.js',
'resources/js/tenant/pages/store/banners.js',
'resources/js/tenant/pages/store/appearance.js',
'resources/js/tenant/pages/store/blade-theme.js',
'resources/js/tenant/pages/store/page-builder.js',
'resources/js/tenant/pages/store/home-variants.js',
```

## 12. Verification
1. Themes: activate a theme and a variant, deactivate with the service error path, the countries modal (search, select-all, save). The setup widget updates.
2. Pages: the gateway guard redirect, CRUD with locale tabs + editor, the per-locale required rule.
3. Coupons, flash sales and banners per country: modal create/edit/delete, unique-code validation on blur, the flash-sale product select2 AJAX with preselected values, the banner reorder, the banner plan limit.
4. Appearance: every tab saves independently, colour reset refills the inputs, social CRUD, the logo preview updates live, and logo fonts come from `/build/assets` (no Google).
5. Blade theme: ZIP upload progress, a >20MB error, deactivate/delete confirms.
6. Page builder / home variants: order and visibility persist, the variant revert message.
