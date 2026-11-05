# Tenant panel – issues reported by "Root" in the Stores WhatsApp group

Source: Stores group, 2026-11-05 from 5:03 PM onward (text + 3 screenshots). Screenshots show the vendor panel
"Products → New products from the platform" page (2 very large cards per row, "Sync all new products (3)").

| # | Issue (Root's words, translated) | Area | Status |
|---|----------------------------------|------|--------|
| 1 | "Product request disappears after creation" (9:17 PM; Joe could not reproduce at 9:18 PM) | Tenant panel › Product requests list | Fixed – see below |
| 2 | "In the vendor dashboard 'New in products', products assigned to one specific vendor must not appear to all vendors" (9:27 PM, screenshot) | Dashboard + New products page | Fixed – see below |
| 3 | "On the products page put three products per row so it looks good and the image is clear" (9:34 PM, screenshot) | New products page grid | Fixed – see below |
| 4 | "A product page must be created so the vendor can open a product" / "a page that describes the product before adding it" (9:35–9:57 PM) | New products page → product preview | Fixed – see below |

| 6 | "In Brand requests add the ability to delete requests that are pending" (11:15 PM) | Tenant panel › Brand requests | Fixed – `DELETE /brand-requests/{id}` (`BrandRequestController::destroy`), pending only; "Delete" action in the list dropdown |
| 7 | "Clicking Partner Program opens a page inside Finance (Subscriptions & Balance tab); better an isolated page" (11:23 PM, screenshot) | Tenant panel › sidebar Partner Program | Fixed – own page `tenant.partner-program` (`PartnerProgramController`, view `partner-program/`), tab removed from the wallet page, `?tab=partner` redirects |
| 8 | "The countries select (Set Up Your Store › Target Countries) still responds slowly" (11:33 PM, screenshot) | Website store onboarding wizard, step 1 | Fixed – selection is client-side (Alpine `$wire.$entangle`), "All Countries" no longer does a Livewire round trip + full re-render per click; synced on Continue |

Earlier messages (before 5:03 PM, not in scope, still unconfirmed): belly not showing, no place to create a new
brand request, product/manufacturing request.

## Root causes & fixes

1. **Product requests vanish** – `ProductRequestController::index/data` call the repository inside
   `tenancy()->central()`, and the repository filtered with `tenant('id')`, which is empty while tenancy is ended,
   so the list/stat queries could match nothing. The tenant id is now captured *before* entering the central context
   and passed to `queryProductRequests()` / `productRequestStats()`.
2. **Assigned products leak to everyone** – `TenantCatalogSyncService::pendingNewProductsQuery()` listed every
   central product not yet carried. Products with `ProductTenantAssignment` rows are reserved: now a pending product
   is shown only if it has no assignments at all, or is assigned to the current tenant.
3. **3 per row** – the New products grid used the shared `.pm-grid` breakpoint (2 columns under 1100px container).
   Added `.pm-grid-3` modifier: 3 columns down to 760px.
4. **Product preview page** – new route `tenant.products.new.show` (`/products/new/{id}`), view
   `catalog/products/new-show.blade.php` with gallery, description, platform price and "Add to my store". Cards on the
   New products page and the dashboard "New in" link to it. Only products still pending for the tenant can be opened.

5. **Admin-assigned products auto-synced (admin product 151)** – `mayCreateTenantProduct()` and the full sync
   (`syncProducts()`) treated a `ProductTenantAssignment` as permission to create the tenant row, so a new/assigned
   product was pushed straight into the tenant store. An assignment now only makes the product *eligible* (it appears
   in dashboard "New in products" / New products page, even outside the tenant's categories); the row is created
   only when the tenant adds it (or on the first registration sync). Products the tenant already carries keep updating.
   **Edit product** went through the same path (live push job `SyncCentralProductToTenantJob` -> `syncProductToTenant()`).
   Its "tenant has no central products yet = first sync" exception let any push create the product for a tenant with an
   empty catalog; it was removed (registration still builds the first catalog via the bulk `syncProducts()`), and the
   Livewire add/edit form's assignment notification now points to "New in products" too.
   **"Assign to all tenants" on add product**: category/product section syncs queued by the add/edit form
   (`syncAllTenants([... 'products'])` -> `syncProducts()`) still treated "tenant carries no central-linked product"
   as the first sync and bulk-created everything. The first-catalog exception is now explicit:
   `syncSection(..., initialCatalog: true)` is passed only by the onboarding chain (`SyncTenantSectionJob(onboarding: true)`);
   every other sync only refreshes carried products and leaves new/assigned ones in "New in products".
