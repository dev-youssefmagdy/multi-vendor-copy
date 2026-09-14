# Prompt 05 — Catalog: Products, Own Products, Edit Requests, Sorting, Categories, Badges, Image Search

Requires prompts 01–03. Follow the **Standard Conversion Recipe** (prompt 00). Controllers go in `app/Http/Controllers/Tenant/Panel/Catalog/`, views in `resources/views/tenant/pages/catalog/`, JS in `resources/js/tenant/pages/catalog/`.
Keep all group middleware exactly:
- `products` and `own-products`: `tenant.permission:catalog.products.manage`
- products index additionally: `tenant.setup:theme`
- `categories`: `tenant.permission:catalog.categories.manage`
- `badges`: `tenant.permission:catalog.badges.manage`
- `product-requests` is in prompt 08.

---

## 1. Products list — `tenant.products.index` (`Product\ProductsList`, 887 lines, generic `list-page` view + 4 modal partials)

**Read the whole class and these views first:**
- `livewire/tenant/pages/list-page.blade.php` (image-search branch)
- `product/social-posts-modal.blade.php`
- `product/price-finder-modal.blade.php`
- `product/share-modal.blade.php` (Alpine)
- `product/price-list-modal.blade.php`

### Page
- Page header, stats from `productStats()` (same 4 cards), filters card and datatable.
- **Filters:**
  - `search` — text, with the camera button that opens the image-search modal
  - `status`, `stock`
  - `category` — `x-tenant::select2` tree, built from the current category option source
  - hidden `image_ids[]` — set by image search; shows the "Visual match — N results" chip with a clear ✕, the same markup as today
- **Columns** (port the current row HTML into `_cols/*.blade.php`): Product, Central Price, Vendor Price, AI Price, Stock, Status, Categories, Updated At, Actions.
  - Status and Featured are rendered as `x-tenant::switch` row actions (they replace `toggleActive` / `toggleFeatured`).
  - Actions is an `x-tenant::dropdown` with: Edit (link), Social posts, Video ad, AI price, Share, Price list. Each opens its modal with `data-product-id`.
- `ProductsController@data`: `DataTables::eloquent($repo->queryProducts($filters))`.
  - Expose the builder behind `paginateProducts()` as `queryProducts(array $filters)`. It must honour `image_ids` exactly like `imageSearchIds`/`imageSearchActive` do now: restrict to ids and **preserve the visual-match order** via `orderByRaw('FIELD(id, …)')` when active.
  - Batch the `centralProductSnapshots()` lookup that `pageData()` does per page inside the data method, over the current page's collection, **before** rendering the cells. That means one call per draw, not per row: use `DataTables::eloquent(...)->setTransformer` or pre-fetch with `$dt->getFilteredQuery()->pluck` per page via `->with(['snapshots' => …])`.

### Modals → endpoints

| Livewire | New endpoint | Notes |
|---|---|---|
| `openSocialModal(id)` | `GET products/{product}/social` → `social` | Returns the initial modal state as JSON: `socialEnabledLanguages`, `socialStorefrontUrl`, the existing posts and the image flag. This is exactly what `openSocialModal` assigns today. |
| `generateSocialPosts()` | `POST products/{product}/social/generate` → `generateSocial` (FormRequest: `language`, `platform`, `include_image`) | `SocialPostService` as today; the success message is the current toast string. It returns `posts`, `image_b64` and `active_lang`, and the JS re-renders the posts panel. `socialError` becomes a 422 `message`. |
| `openVideoAdModal` / `closeVideoAdModal` | client-only | Port the current modal content. If it posts anything, add the matching endpoint. |
| `openPriceModal(id)` | `GET products/{product}/ai-price` → `aiPrice` | Returns `priceVariants` and the current `priceData`. |
| `fetchAiPrice()` | `POST products/{product}/ai-price` → `fetchAiPrice` (FormRequest: `use_image` bool, `variant_id` nullable exists) | `PriceFinderService`. Toast "Price data fetched and saved successfully." Reload the table row. |
| `openShareModal(id)` | `GET products/{product}/share` → `share` | Returns `title`, `caption`, `url`, `image_url` and `has_ai_content`. The JS replaces the modal's Alpine with vanilla code (copy buttons use `x-tenant::copy`, share links are plain anchors). |
| `toggleActive(id)` | `PATCH products/{product}/active` → `toggleActive` | Toast "Product status updated successfully." |
| `toggleFeatured(id)` | `PATCH products/{product}/featured` → `toggleFeatured` | Toast "Product featured state updated successfully." |
| `applyImageSearch(ids)` / `clearImageSearch()` | client-only | Sets or clears the hidden `image_ids` filter, then reloads the table. |
| `openPriceListModal(id)` | `GET products/{product}/price-list` → `priceList` | Returns the same arrays the method builds today: `prices`, `profits`, `variants`, `countryLabels`, `centralSalePrice`, `shippingByCountry`. |
| `recalculateProductPrices()` / `recalculateVariantPrices(i)` | client-side | Port the arithmetic to `pages/catalog/products-price-list.js`. **Read both methods and replicate the formula exactly**, including rounding and the use of `centralSalePrice` / `shippingByCountry`. If the formula calls a PHP service (`ProductPriceCalculationService`), expose `POST products/{product}/price-list/preview` instead of duplicating the logic in JS. |
| `savePriceList()` | `PUT products/{product}/price-list` → `savePriceList` (FormRequest mirroring the current validation of `priceListPrices`/variants) | Toast "Prices updated successfully." Close the modal and reload the table. |

- Move the heavy bodies of `openSocialModal`, `generateSocialPosts`, `openPriceListModal` and `savePriceList` into a new `App\Services\Tenant\ProductMarketingService` (social/share/video) and `App\Services\Tenant\ProductPriceListService` (price list). Controllers stay thin.
- **Image search:**
  - Create `x-tenant::image-search-modal`, a port of the global `components/image-search-modal.blade.php` whose inline `<style>` moves to `resources/css/tenant/components/image-search.css`.
  - Its JS is `resources/js/tenant/components/image-search.js`, ported from `resources/js/image-search.js`. It posts to `route('tenant.products.image-search')` (existing), then emits `tenant:image-search:results` `{ids}` instead of calling a window callback.
  - **Do not modify the global modal or `resources/js/image-search.js`** (storefront uses them).
- Modal partials become `resources/views/tenant/pages/catalog/products/_modals/{social,video-ad,price-finder,share,price-list}.blade.php`. Their inline `<style>` blocks (social-posts, price-finder) move to `resources/css/tenant/pages/products.css`, imported by the page entry.
- The price-list and price-finder variant tables are breakdowns → `x-tenant::table`.

## 2. Add/Edit central-catalog product — `tenant.products.create`, `tenant.products.edit` (`Product\AddEditProduct`)
- `ProductController@create|edit` render `tenant.pages.catalog.products.form`, porting `product/add-edit-product.blade.php`:
  - `x-tenant::locale-tabs` for `translations` (replaces `setActiveLocale`)
  - `x-tenant::select2` for the central product (AJAX; `updatedCentralProductId` → `GET products/central-snapshot/{id}` returning the snapshot the component loads, `centralProductSnapshot()`, so the JS fills the price/translations defaults)
  - `x-tenant::select2 multiple tree` for categories (`categoryTreeOptions()`)
  - `x-tenant::select2 multiple` for badges
  - the variants table (breakdown → `x-tenant::table` + inputs)
  - the pending-edit-request notice
- Endpoints:
  - `POST products` → `store`
  - `PUT products/{product}` → `update`
  - `POST products/validate` and `POST products/{product}/validate`
- `store`/`update` port `save()` exactly:
  - the plan limit check `canPerform(PlanLimitService::FEATURE_PRODUCTS)` → `PanelActionException(errorMessage(...))`
  - the rules from `rules()` → `SaveProductRequest`
  - `TenantPanelService::saveProduct(...)`
  - the `submitProductEditRequest(...)` branch when the product needs admin approval
  - redirect to `tenant.products.edit` with the current success message (JSON `redirect`)
- Uses `x-tenant::editor` where the Livewire view uses `x-editor`.

## 3. Own products — `tenant.own-products.*`
- **List** (`Product\OwnProductsList`):
  - filters `search`, `status`, `stock`
  - columns Product, Price, Stock, Status, Added, Actions
  - `deleteProduct(id)` → `DELETE own-products/{product}` → `destroy`, with the confirm text from the current `confirmAction` and the toast "Product deleted.". Port its exact guards (e.g. `is_own_product`).
- **Form** (existing `OwnProductController` + `tenant/product/add-edit-own-product.blade.php` 1,057 lines + `_variant-row.blade.php`):
  - Keep the controller's logic, but move it to `Catalog\OwnProductController`.
  - `store`/`update` return `success('Product created successfully.' | 'Product updated successfully.', redirect: route('tenant.own-products.edit', $saved))` instead of `redirect()`.
  - The duplicate-variant check stays **in the FormRequest** (`after()` hook), so `validateForm` and `store` share it. `validateForm` becomes `validateStore` / `validateUpdate`, and the routes `tenant.own-products.validate` / `validate.update` keep their names.
  - Rewrite the view with components:
    - `locale-tabs`
    - `x-tenant::image-upload` (primary image + `remove_primary_image`)
    - `x-tenant::dropzone sortable` (gallery: `gallery_files[]`, `remove_gallery_ids[]`, `gallery_order`)
    - `x-tenant::repeater` for variants, replacing `_variant-row` + its inline JS; option pairs are a nested repeater
    - `x-tenant::select2` for categories/badges
    - `x-tenant::switch` for active/featured/manage_stock/is_taxable
    - `x-tenant::editor` for descriptions — **replaces the jsdelivr TinyMCE loader at line ~923**
    - the per-product return-policy override fields
  - The variation-groups JSON island (`<script id="variations-data" type="application/json">`) is allowed. Move the three executable `<script>` blocks to `pages/catalog/own-product-form.js`.
- Delete nothing yet. `AddEditOwnProduct` (dead Livewire) goes in prompt 12.

## 4. Edit requests — `tenant.products.edit-requests` (`Product\EditRequestsPage`)
- Filter `status` (the current `statusFilter` options).
- Datatable with the columns the view shows today (read `product/edit-requests-page.blade.php`).
- Read-only unless the view has actions. Port any that exist.

## 5. Sorting pages (SortableJS → `x-tenant::sortable-list`)

| Page | Livewire | Save endpoint | Toast |
|---|---|---|---|
| `tenant.products.sort` | `Product\SortProducts` (+ `search`) | `POST products/sort` → `ProductSortController@update` (`ids[]`) | "Product order saved." |
| `tenant.categories.sort` | `Category\SortCategories` | `POST categories/sort` → `CategorySortController@update` | "Category order saved." |
| `tenant.categories.products` | `Category\CategoryProducts` | `POST categories/{category}/products/sort` → `CategoryProductsController@update` | "Product order saved." |
| `tenant.badges.sort` | `Badge\SortBadgeProducts` (`$badge`, `$activeCountryId`) | `POST badges/{badge}/sort` → `BadgeSortController@update` (`ids[]`, `country_id`) | "Product order saved." |

- Port each `updateOrder()` body **exactly** (the column written and scoping by country/badge/category).
- The search on sort-products is a GET form reload (`?search=`) of the list (a sortable list is not a DataTable, since drag order over a paginated server table is meaningless).
- The current tables in these views become the sortable list rows (thumbnail, name, handle).
- Move the inline `<script>` blocks to `pages/catalog/sort-*.js`. Each is one line, `initComponents` handles it, so a single shared entry `pages/catalog/sortable.js` is fine.

## 6. Categories
- **List** (`Category\CategoriesList`):
  - filters `search`, `status`, `owner` (current options)
  - stats `categoryStats()`
  - columns Category, Parent, Status, Products, Updated At, Actions
  - builder: expose `queryCategories()`, and batch `centralCategorySnapshots()` per draw
  - `toggleActive` → `PATCH categories/{category}/active`, toast "Category status updated successfully."
  - `toggleFeatured` → `PATCH categories/{category}/featured`, toast "Category featured state updated successfully."
  - `deleteCategory` → `DELETE categories/{category}`. Keep `abort_if($category->central_category_id !== null, 403, 'Only tenant-created categories can be deleted.')` as a 403 JSON. Confirm text as today. Toast "Category deleted successfully."
  - Actions: Edit, Products (sort), Delete (only for tenant-created).
- **Form** (`Category\AddEditCategory`):
  - `create`, `edit`, `store`, `update`, and the two validate routes
  - port `save()`: plan limit, `rules()`, `saveCategory`, `syncThumb`/`purgeThumb` via `TenantCategoryMediaService`, and the redirect to edit
  - fields: parent select2 (`categoryOptions()`), order number, active/featured switches, locale-tabs translations, `x-tenant::image-upload name="thumb" removable` (`remove_thumb`, the `thumbUpload` rule `nullable|image|max:4096`), and the central category snapshot read-only panel when mapped

## 7. Badges (`BadgeProductsController` + `tenant/badge/show.blade.php`)
- Move to `Catalog\BadgeController`.
- `show` keeps rendering, converted to components:
  - the product picker becomes `x-tenant::select2 multiple ajax-url="{{ route('tenant.badges.search', $badge) }}"`
  - make `searchProducts` return `Select2Response` (keep the current query)
  - the "assign all in category" category select + button hits `assignCategory`
- `assignCategory`: keep the JSON. Change the `{error: …}` 422 bodies to `{message: …}` so the interceptor toasts them ("Please choose a category first." / "No products found in that category."), and add a success `message`.
- `save`: return `success('Badge assignment saved — N products assigned for {label}.')` (the current text) instead of `back()`.
- Move the inline `<script>` / `<style>` to `pages/catalog/badge-show.js` / `resources/css/tenant/pages/badges.css`.
- `tenant.badges.index` keeps its redirect closure.

## 8. Routes to add (names are exact; place them inside the existing groups)
```
products:      GET data · GET central-snapshot/{centralProduct} · POST / (store) · POST validate · PUT {product} · POST {product}/validate
               PATCH {product}/active · PATCH {product}/featured
               GET {product}/social · POST {product}/social/generate · GET {product}/ai-price · POST {product}/ai-price
               GET {product}/share · GET {product}/price-list · PUT {product}/price-list [· POST {product}/price-list/preview]
               POST sort
own-products:  GET data · DELETE {product} (existing validate/store/update names kept)
edit-requests: GET products/edit-requests/data
categories:    GET data · POST / · POST validate · PUT {category} · POST {category}/validate · PATCH {category}/active
               PATCH {category}/featured · DELETE {category} · POST sort · POST {category}/products/sort
badges:        POST {badge}/sort (existing search/assign-category/save kept)
```
Name pattern: `tenant.products.data`, `tenant.products.store`, `tenant.products.update`, `tenant.products.validate`, `tenant.products.validate.update`, `tenant.products.toggle-active`, `tenant.products.toggle-featured`, `tenant.products.social`, `tenant.products.social.generate`, `tenant.products.ai-price`, `tenant.products.ai-price.fetch`, `tenant.products.share`, `tenant.products.price-list`, `tenant.products.price-list.save`, `tenant.products.sort.save`, and the same pattern for the other resources.

**Route ordering:** declare `products/data`, `products/sort` and `products/central-snapshot/*` **before** `products/{product}` wildcards.

## 9. Vite entries
```
'resources/js/tenant/pages/catalog/products-index.js',
'resources/js/tenant/pages/catalog/products-price-list.js',   // imported by products-index (may be a module import instead of an entry)
'resources/js/tenant/pages/catalog/product-form.js',
'resources/js/tenant/pages/catalog/own-products-index.js',
'resources/js/tenant/pages/catalog/own-product-form.js',
'resources/js/tenant/pages/catalog/edit-requests.js',
'resources/js/tenant/pages/catalog/sortable.js',
'resources/js/tenant/pages/catalog/categories-index.js',
'resources/js/tenant/pages/catalog/category-form.js',
'resources/js/tenant/pages/catalog/badge-show.js',
```

## 10. Verification
1. Products: every filter, image search (the chip, the order preserved), every modal flow (social generate, AI price, share copy, price list recalc/save) and both switches work. Each mutation toasts. The plan limit on create shows the limit message.
2. Own product: create with variants, gallery upload/reorder/remove, primary replace/remove. Blur-validation per field; a duplicate variant combination → the error on `variants`. TinyMCE loads from `/build/assets/…` (no jsdelivr).
3. Categories: CRUD, toggles, central-category delete → 403 toast, thumb upload validation.
4. Sort pages: drag → toast "… order saved." → reload keeps the order. A failure reverts.
5. Badges: select2 AJAX search, assign-by-category messages, save toast.
