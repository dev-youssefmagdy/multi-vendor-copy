# Module & Component Inventory (generated)

> Generated 2026-11-24 by joining `php artisan route:list --json` with PHP reflection of every class in `app/Livewire`, `app/Http/Controllers` and `app/Http/Requests`. For each route group (URI segment) it lists the routes with their required permission, then every handler class with its public state (Livewire form fields), public actions, validation rules (parsed from `rules()`, `#[Validate]`, inline `validate([...])` and type-hinted FormRequests) and views. Rules are truncated at 90 chars. This file is the traceability index behind the narrative chapters of `COMPLETE_SYSTEM_DOCUMENTATION.md`.


## Super Admin panel (172 routes)


### `(root)`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | dashboard.view | `Livewire\Admin\Dashboard` |

- **`Livewire\Admin\Dashboard`** — [`app/Livewire/Admin/Dashboard.php`](../../app/Livewire/Admin/Dashboard.php) (20 lines)
  - Views: `livewire.admin.dashboard`

### `admins`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/admins` | `admin.admins.index` | admins.users.view,admins.users.manage | `Livewire\Admin\Admin\AdminsList` |
| GET | `/admin/admins/roles-permissions` | `admin.admins.roles-permissions` | admins.roles.view,admins.roles.manage | `Livewire\Admin\Admin\RolesPermissionsList` |

- **`Livewire\Admin\Admin\AdminsList`** — [`app/Livewire/Admin/Admin/AdminsList.php`](../../app/Livewire/Admin/Admin/AdminsList.php) (220 lines)
  - Public state / form fields: `search`, `statusFilter`, `showFormModal`, `adminId`, `roleId`, `name`, `email`, `password`, `status`
  - Actions/methods: `openCreateModal()`, `editAdmin()`, `closeModal()`, `save()`, `activateAdmin()`, `deactivateAdmin()`, `confirmDelete()`, `deleteAdmin()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
  - Validation: `roleId`: nullable', 'integer', 'exists:admin_roles,id; `name`: required', 'string', 'max:255; `email`: required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId); `password`: $adminId ? 'nullable' : 'required', 'string', 'min:8; `status`: required', Rule::enum(ActivationStatus::class)
- **`Livewire\Admin\Admin\RolesPermissionsList`** — [`app/Livewire/Admin/Admin/RolesPermissionsList.php`](../../app/Livewire/Admin/Admin/RolesPermissionsList.php) (163 lines)
  - Public state / form fields: `search`, `showFormModal`, `roleId`, `name`, `permissions`
  - Actions/methods: `openCreateModal()`, `editRole()`, `closeModal()`, `save()`, `updatedSearch()`, `confirmDelete()`, `deleteRole()`, `clearFilters()`
  - Validation: `name`: required', 'string', 'max:255', Rule::unique('admin_roles', 'name')->ignore($roleId); `permissions`: nullable', 'array; `permissions.*`: string', Rule::in($availablePermissions)

### `affiliates`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/affiliates` | `admin.affiliates.index` | affiliates.manage | `Livewire\Admin\Affiliate\AffiliatesListPage` |
| GET | `/admin/affiliates/conversions` | `admin.affiliates.conversions` | affiliates.manage | `Livewire\Admin\Affiliate\ConversionsListPage` |
| GET | `/admin/affiliates/coupons` | `admin.affiliates.coupons` | affiliates.manage | `Livewire\Admin\Affiliate\AffiliateCouponsPage` |
| GET | `/admin/affiliates/payouts` | `admin.affiliates.payouts` | affiliates.manage | `Livewire\Admin\Affiliate\PayoutsListPage` |
| GET | `/admin/affiliates/reports` | `admin.affiliates.reports` | affiliates.manage | `Livewire\Admin\Affiliate\AffiliateReportsPage` |

- **`Livewire\Admin\Affiliate\AffiliatesListPage`** — [`app/Livewire/Admin/Affiliate/AffiliatesListPage.php`](../../app/Livewire/Admin/Affiliate/AffiliatesListPage.php) (245 lines)
  - Public state / form fields: `showFormModal`, `showPayoutModal`, `payoutRequestId`, `affiliateId`, `name`, `email`, `commissionType`, `commissionValue`, `status`, `typeFilter`, `editingTenant`, `payoutAffiliateId`, `payoutAffiliateName`, `payoutAffiliateBalance`, `payoutAmount`, `payoutReference`, `payoutNotes`, `payoutAttachment`, `showSettingsModal`, `settingCookieDays`, `settingCommissionType`, `settingCommissionValue`, `settingMinPayout`
  - Actions/methods: `updatedTypeFilter()`, `editAffiliate()`, `saveAffiliate()`, `openPayoutModal()`, `issuePayout()`, `rejectRequests()`, `closeModal()`, `clearFilters()`, `openSettingsModal()`, `saveSettings()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `name`: required|string|max:100; `commissionType`: required|in:percentage,fixed; `commissionValue`: required|numeric|min:0; `status`: required|in:pending,active,suspended; `payoutAmount`: required|numeric|min:0.01; `payoutReference`: nullable|string|max:255; `payoutNotes`: nullable|string|max:2000; `payoutAttachment`: nullable|file|max:10240
- **`Livewire\Admin\Affiliate\ConversionsListPage`** — [`app/Livewire/Admin/Affiliate/ConversionsListPage.php`](../../app/Livewire/Admin/Affiliate/ConversionsListPage.php) (109 lines)
  - Public state / form fields: `statusFilter`, `showSettingsModal`, `settingCookieDays`, `settingCommissionType`, `settingCommissionValue`, `settingMinPayout`
  - Actions/methods: `closeModal()`, `reverse()`, `clearFilters()`, `openSettingsModal()`, `saveSettings()`
- **`Livewire\Admin\Affiliate\AffiliateCouponsPage`** — [`app/Livewire/Admin/Affiliate/AffiliateCouponsPage.php`](../../app/Livewire/Admin/Affiliate/AffiliateCouponsPage.php) (168 lines)
  - Public state / form fields: `showFormModal`, `couponId`, `affiliateId`, `code`, `discountType`, `discountValue`, `commissionValue`, `startDate`, `endDate`, `minimumSpend`, `active`
  - Actions/methods: `openCreateModal()`, `editCoupon()`, `save()`, `deleteCoupon()`, `closeModal()`, `clearFilters()`
  - Validation: `affiliateId`: required|integer|exists:affiliates,id; `code`: required|string|max:64|alpha_dash|; `discountType`: required|in:percentage,fixed; `discountValue`: required|numeric|min:0; `commissionValue`: nullable|numeric|min:0|max:100; `minimumSpend`: nullable|numeric|min:0; `startDate`: nullable|date; `endDate`: nullable|date|after_or_equal:startDate
- **`Livewire\Admin\Affiliate\PayoutsListPage`** — [`app/Livewire/Admin/Affiliate/PayoutsListPage.php`](../../app/Livewire/Admin/Affiliate/PayoutsListPage.php) (373 lines)
  - Doc: Central admin: affiliate withdrawal requests (submitted from the affiliate portal or the vendor panel's Partner Program tab, stored in central `affiliate_payout_requests`) plus issued payouts. Admin pays a request (records an AffiliatePayout, receipt optional) or rejects it. Vendor identity and bank
  - Public state / form fields: `statusFilter`, `showPayModal`, `showRejectModal`, `requestId`, `requestAffiliateName`, `requestBalance`, `requestNote`, `payAmount`, `payReference`, `payNotes`, `payReceipt`, `rejectNote`, `payeeProfile`, `showDetailsModal`, `detailsStats`, `showSettingsModal`, `settingCookieDays`, `settingCommissionType`, `settingCommissionValue`, `settingMinPayout`
  - Actions/methods: `openDetailsModal()`, `updatedStatusFilter()`, `openPayModal()`, `markPaid()`, `openRejectModal()`, `rejectRequest()`, `closeModal()`, `clearFilters()`, `openSettingsModal()`, `saveSettings()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `payAmount`: amount; `payReference`: nullable|string|max:255; `payNotes`: nullable|string|max:2000; `payReceipt`: receipt; `rejectNote`: nullable|string|max:1000
- **`Livewire\Admin\Affiliate\AffiliateReportsPage`** — [`app/Livewire/Admin/Affiliate/AffiliateReportsPage.php`](../../app/Livewire/Admin/Affiliate/AffiliateReportsPage.php) (93 lines)

### `badges`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/badges/best-selling` | `admin.badges.best-selling` | catalog.badges.manage | `Admin\BadgeProductsController@show` |
| GET | `/admin/badges/featured` | `admin.badges.featured` | catalog.badges.manage | `Admin\BadgeProductsController@show` |
| GET | `/admin/badges/new-in` | `admin.badges.new-in` | catalog.badges.manage | `Admin\BadgeProductsController@show` |
| GET | `/admin/badges/recommended` | `admin.badges.recommended` | catalog.badges.manage | `Admin\BadgeProductsController@show` |
| GET | `/admin/badges/trending-now` | `admin.badges.trending-now` | catalog.badges.manage | `Admin\BadgeProductsController@show` |
| GET | `/admin/badges/{badge}` | `admin.badges.show` | catalog.badges.manage | `Admin\BadgeProductsController@show` |
| POST | `/admin/badges/{badge}/assign-category` | `admin.badges.assign-category` | catalog.badges.manage | `Admin\BadgeProductsController@assignCategory` |
| POST | `/admin/badges/{badge}/save` | `admin.badges.save` | catalog.badges.manage | `Admin\BadgeProductsController@save` |
| GET | `/admin/badges/{badge}/search` | `admin.badges.search` | catalog.badges.manage | `Admin\BadgeProductsController@searchProducts` |
| GET | `/admin/badges/{badge}/sort` | `admin.badges.sort` | catalog.badges.manage | `Admin\BadgeSortController@index` |
| POST | `/admin/badges/{badge}/sort` | `admin.badges.sort.update` | catalog.badges.manage | `Admin\BadgeSortController@update` |

- **`Http\Controllers\Admin\BadgeProductsController`** — [`app/Http/Controllers/Admin/BadgeProductsController.php`](../../app/Http/Controllers/Admin/BadgeProductsController.php) (153 lines)
  - Actions/methods: `show()`, `searchProducts()`, `assignCategory()`, `save()`
  - Views: `admin.badge.show`
- **`Http\Controllers\Admin\BadgeSortController`** — [`app/Http/Controllers/Admin/BadgeSortController.php`](../../app/Http/Controllers/Admin/BadgeSortController.php) (64 lines)
  - Doc: Central admin "Sort — <badge>" (drag order of the products of one central badge, per country; NULL country = the Default list). Blade + JSON + fetch() (dedup plan §7), replaces the Livewire `Admin\Product\SortBadgeProducts`.  After a save the global CacheVersion tag `CentralProductBadge` is bumped (
  - Actions/methods: `index()`, `update()`
  - Validation: `ids`: present', 'array', 'max:5000; `ids.*`: integer; `country_id`: nullable', 'integer
  - Views: `admin.badge.sort`

### `blog`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/blog/categories` | `admin.blog.categories` | content.blog-categories.view,content.blog-categories.manage | `Livewire\Admin\Blog\BlogCategoriesList` |
| GET | `/admin/blog/categories/create` | `admin.blog.categories.create` | content.blog-categories.manage | `Livewire\Admin\Blog\AddEditBlogCategory` |
| GET | `/admin/blog/categories/{blogCategory}/edit` | `admin.blog.categories.edit` | content.blog-categories.manage | `Livewire\Admin\Blog\AddEditBlogCategory` |
| GET | `/admin/blog/posts` | `admin.blog.posts` | content.blog-posts.view,content.blog-posts.manage | `Livewire\Admin\Blog\BlogPostsList` |
| GET | `/admin/blog/posts/create` | `admin.blog.posts.create` | content.blog-posts.manage | `Livewire\Admin\Blog\AddEditBlogPost` |
| GET | `/admin/blog/posts/{post}/edit` | `admin.blog.posts.edit` | content.blog-posts.manage | `Livewire\Admin\Blog\AddEditBlogPost` |

- **`Livewire\Admin\Blog\BlogCategoriesList`** — [`app/Livewire/Admin/Blog/BlogCategoriesList.php`](../../app/Livewire/Admin/Blog/BlogCategoriesList.php) (101 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `confirmDelete()`, `deleteCategory()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
- **`Livewire\Admin\Blog\AddEditBlogCategory`** — [`app/Livewire/Admin/Blog/AddEditBlogCategory.php`](../../app/Livewire/Admin/Blog/AddEditBlogCategory.php) (81 lines)
  - Public state / form fields: `categoryId`, `status`, `translations`, `activeLocale`
  - Actions/methods: `setActiveLocale()`, `save()`
  - Views: `livewire.admin.blog.add-edit-blog-category`
- **`Livewire\Admin\Blog\BlogPostsList`** — [`app/Livewire/Admin/Blog/BlogPostsList.php`](../../app/Livewire/Admin/Blog/BlogPostsList.php) (122 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `publishPost()`, `archivePost()`, `confirmDelete()`, `deletePost()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
- **`Livewire\Admin\Blog\AddEditBlogPost`** — [`app/Livewire/Admin/Blog/AddEditBlogPost.php`](../../app/Livewire/Admin/Blog/AddEditBlogPost.php) (111 lines)
  - Public state / form fields: `postId`, `blogCategoryId`, `status`, `publishedAt`, `translations`, `activeLocale`, `image`, `existingImageUrl`, `removeImage`
  - Actions/methods: `setActiveLocale()`, `removeImage()`, `save()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Views: `livewire.admin.blog.add-edit-blog-post`

### `branches`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/branches` | `admin.branches.index` | branches.view,branches.manage | `Livewire\Admin\Branch\BranchesList` |
| GET | `/admin/branches/create` | `admin.branches.create` | branches.manage | `Admin\AddEditBranchController@create` |
| POST | `/admin/branches/create` | `admin.branches.store` | branches.manage | `Admin\AddEditBranchController@store` |
| GET | `/admin/branches/{branch}/edit` | `admin.branches.edit` | branches.manage | `Admin\AddEditBranchController@edit` |
| PUT | `/admin/branches/{branch}/edit` | `admin.branches.update` | branches.manage | `Admin\AddEditBranchController@update` |

- **`Livewire\Admin\Branch\BranchesList`** — [`app/Livewire/Admin/Branch/BranchesList.php`](../../app/Livewire/Admin/Branch/BranchesList.php) (88 lines)
  - Public state / form fields: `search`, `activeFilter`, `defaultFreeShippingWeight`
  - Actions/methods: `saveDefaultFreeShippingWeight()`, `updatedSearch()`, `updatedActiveFilter()`, `clearFilters()`, `deleteBranch()`
  - Validation: `defaultFreeShippingWeight`: required', 'integer', 'min:0
  - Views: `livewire.admin.branch.branches-list`
- **`Http\Controllers\Admin\AddEditBranchController`** — [`app/Http/Controllers/Admin/AddEditBranchController.php`](../../app/Http/Controllers/Admin/AddEditBranchController.php) (168 lines)
  - Actions/methods: `create()`, `edit()`, `store()`, `update()`
  - Views: `admin.branch.add-edit-branch`

### `brand-requests`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/brand-requests` | `admin.brand-requests.index` | brand-requests.view,brand-requests.manage | `Livewire\Admin\BrandRequest\BrandRequestsList` |
| GET | `/admin/brand-requests/{id}` | `admin.brand-requests.show` | brand-requests.view | `Livewire\Admin\BrandRequest\BrandRequestDetail` |

- **`Livewire\Admin\BrandRequest\BrandRequestsList`** — [`app/Livewire/Admin/BrandRequest/BrandRequestsList.php`](../../app/Livewire/Admin/BrandRequest/BrandRequestsList.php) (182 lines)
  - Public state / form fields: `search`, `statusFilter`, `tenantFilter`, `showModal`, `selectedId`, `newStatus`, `adminNotes`, `selectedTitle`, `selectedTenantName`, `selectedCurrentStatus`, `selectedCurrentStatusClass`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedTenantFilter()`, `clearFilters()`, `openStatusModal()`, `closeModal()`, `updateStatus()`
  - Validation: `newStatus`: required|in:; `adminNotes`: nullable|string|max:2000
- **`Livewire\Admin\BrandRequest\BrandRequestDetail`** — [`app/Livewire/Admin/BrandRequest/BrandRequestDetail.php`](../../app/Livewire/Admin/BrandRequest/BrandRequestDetail.php) (228 lines)
  - Public state / form fields: `requestId`, `newStatus`, `adminNotes`, `chatMessage`, `showPaymentForm`, `paymentLabel`, `paymentAmount`, `paymentCurrency`, `paymentNotes`
  - Actions/methods: `updateStatus()`, `sendMessage()`, `togglePaymentForm()`, `createPaymentRequest()`, `cancelPaymentRequest()`
  - Validation: `newStatus`: required|in:; `adminNotes`: nullable|string|max:2000; `chatMessage`: required|string|max:2000; `paymentLabel`: required|string|max:255; `paymentAmount`: required|numeric|min:0.01|max:9999999; `paymentCurrency`: required|string|max:10; `paymentNotes`: nullable|string|max:1000
  - Views: `livewire.admin.brand-request.brand-request-detail`

### `cache`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/cache/main` | `admin.cache.main` | system.cache.manage | `Livewire\Admin\Cache\MainCachePage` |
| GET | `/admin/cache/tenants` | `admin.cache.tenants` | system.cache.manage | `Livewire\Admin\Cache\TenantsCachePage` |

- **`Livewire\Admin\Cache\MainCachePage`** — [`app/Livewire/Admin/Cache/MainCachePage.php`](../../app/Livewire/Admin/Cache/MainCachePage.php) (57 lines)
  - Actions/methods: `requestClearMain()`, `clearMain()`
- **`Livewire\Admin\Cache\TenantsCachePage`** — [`app/Livewire/Admin/Cache/TenantsCachePage.php`](../../app/Livewire/Admin/Cache/TenantsCachePage.php) (57 lines)
  - Actions/methods: `requestClearTenants()`, `clearTenants()`

### `categories`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/categories` | `admin.categories.index` | catalog.categories.view,catalog.categories.manage | `Livewire\Admin\Category\CategoriesList` |
| GET | `/admin/categories/create` | `admin.categories.create` | catalog.categories.manage | `Livewire\Admin\Category\AddEditCategory` |
| GET | `/admin/categories/sort` | `admin.categories.sort` | catalog.categories.manage | `Livewire\Admin\Category\SortCategories` |
| GET | `/admin/categories/{category}/edit` | `admin.categories.edit` | catalog.categories.manage | `Livewire\Admin\Category\AddEditCategory` |
| GET | `/admin/categories/{category}/products` | `admin.categories.products` | catalog.categories.manage | `Livewire\Admin\Category\CategoryProducts` |

- **`Livewire\Admin\Category\CategoriesList`** — [`app/Livewire/Admin/Category/CategoriesList.php`](../../app/Livewire/Admin/Category/CategoriesList.php) (55 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`, `deleteCategory()`
  - Views: `livewire.admin.category.categories-list`
- **`Livewire\Admin\Category\AddEditCategory`** — [`app/Livewire/Admin/Category/AddEditCategory.php`](../../app/Livewire/Admin/Category/AddEditCategory.php) (204 lines)
  - Public state / form fields: `categoryId`, `parentPath`, `status`, `isFeatured`, `translations`, `activeLocale`, `removeThumbImage`, `thumbImage`
  - Actions/methods: `setActiveLocale()`, `updatedParentPath()`, `removeThumb()`, `save()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `parentPath`: array; `parentPath.*`: integer', \App\Support\Catalog\LiveCatalogRule::exists(Category::class); `status`: required', Rule::enum(CategoryStatus::class); `isFeatured`: boolean; `removeThumbImage`: boolean; `thumbImage`: nullable', 'image', 'max:4096
  - Views: `livewire.admin.category.add-edit-category`
- **`Livewire\Admin\Category\SortCategories`** — [`app/Livewire/Admin/Category/SortCategories.php`](../../app/Livewire/Admin/Category/SortCategories.php) (33 lines)
  - Actions/methods: `updateOrder()`
  - Views: `livewire.admin.category.sort-categories`
- **`Livewire\Admin\Category\CategoryProducts`** — [`app/Livewire/Admin/Category/CategoryProducts.php`](../../app/Livewire/Admin/Category/CategoryProducts.php) (40 lines)
  - Public state / form fields: `category`
  - Actions/methods: `updateOrder()`
  - Views: `livewire.admin.category.category-products`

### `compliance`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/compliance` | `admin.compliance.index` | compliance.tenants.view | `Livewire\Admin\Compliance\ComplianceOverviewList` |
| GET | `/admin/compliance/{tenant}` | `admin.compliance.show` | compliance.tenants.view | `Livewire\Admin\Compliance\ComplianceDetailPage` |

- **`Livewire\Admin\Compliance\ComplianceOverviewList`** — [`app/Livewire/Admin/Compliance/ComplianceOverviewList.php`](../../app/Livewire/Admin/Compliance/ComplianceOverviewList.php) (107 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
- **`Livewire\Admin\Compliance\ComplianceDetailPage`** — [`app/Livewire/Admin/Compliance/ComplianceDetailPage.php`](../../app/Livewire/Admin/Compliance/ComplianceDetailPage.php) (108 lines)
  - Public state / form fields: `tenantId`, `note`
  - Actions/methods: `markVerified()`, `markNeedsAction()`

### `customers`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/customers` | `admin.customers.index` | sales.customers.view | `Livewire\Admin\Customer\CentralCustomersList` |

- **`Livewire\Admin\Customer\CentralCustomersList`** — [`app/Livewire/Admin/Customer/CentralCustomersList.php`](../../app/Livewire/Admin/Customer/CentralCustomersList.php) (118 lines)
  - Public state / form fields: `search`, `statusFilter`, `newOnlyFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedNewOnlyFilter()`, `clearFilters()`

### `dev-docs`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/dev-docs` | `admin.dev-docs.index` | system.dev-docs.view | `Livewire\Admin\Docs\DocsPage` |
| GET | `/admin/dev-docs/{topic}` | `admin.dev-docs.show` | system.dev-docs.view | `Livewire\Admin\Docs\DocsPage` |

- **`Livewire\Admin\Docs\DocsPage`** — [`app/Livewire/Admin/Docs/DocsPage.php`](../../app/Livewire/Admin/Docs/DocsPage.php) (73 lines)
  - Doc: /admin/dev-docs/{topic?} — In-app developer documentation.  Topics correspond to Blade partials under resources/views/admin/docs/_partials/<topic>.blade.php.
  - Public state / form fields: `topic`

### `domains`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/domains/dns-records` | `admin.domains.dns-records` | domains.dns.view,domains.dns.manage | `Livewire\Admin\Domain\DnsRecordsList` |
| GET | `/admin/domains/requests` | `admin.domains.requests` | domains.requests.view,domains.requests.manage | `Livewire\Admin\Domain\DomainRequestsList` |

- **`Livewire\Admin\Domain\DnsRecordsList`** — [`app/Livewire/Admin/Domain/DnsRecordsList.php`](../../app/Livewire/Admin/Domain/DnsRecordsList.php) (195 lines)
  - Public state / form fields: `search`, `typeFilter`, `showFormModal`, `dnsRecordId`, `type`, `name`, `value`, `ttl`, `priority`, `description`, `isRequired`
  - Actions/methods: `openCreateModal()`, `editRecord()`, `closeModal()`, `save()`, `confirmDelete()`, `deleteRecord()`, `updatedSearch()`, `updatedTypeFilter()`, `clearFilters()`
  - Validation: `type`: required', Rule::in(DnsRecord::types()); `name`: required', 'string', 'max:255; `value`: required', 'string', 'max:500; `ttl`: required', 'integer', 'min:60', 'max:86400; `priority`: nullable', 'integer', 'min:0', 'max:65535; `description`: nullable', 'string', 'max:1000; `isRequired`: boolean
- **`Livewire\Admin\Domain\DomainRequestsList`** — [`app/Livewire/Admin/Domain/DomainRequestsList.php`](../../app/Livewire/Admin/Domain/DomainRequestsList.php) (212 lines)
  - Public state / form fields: `search`, `statusFilter`, `showFormModal`, `domainRequestId`, `tenantId`, `domain`, `status`, `requestedAt`, `verifiedAt`
  - Actions/methods: `openCreateModal()`, `editRequest()`, `closeModal()`, `save()`, `markConnected()`, `markPending()`, `markRemoved()`, `confirmDelete()`, `deleteRequest()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
  - Validation: `tenantId`: nullable', 'string', 'exists:tenants,id; `domain`: required', 'string', 'max:255', Rule::unique('domain_requests', 'domain')->ignore($domainR; `status`: required', Rule::enum(DomainRequestStatus::class); `requestedAt`: nullable', 'date; `verifiedAt`: nullable', 'date

### `faqs`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/faqs` | `admin.faqs.index` | content.faqs.view,content.faqs.manage | `Livewire\Admin\Faq\FaqsList` |
| GET | `/admin/faqs/create` | `admin.faqs.create` | content.faqs.manage | `Livewire\Admin\Faq\AddEditFaq` |
| GET | `/admin/faqs/{faq}/edit` | `admin.faqs.edit` | content.faqs.manage | `Livewire\Admin\Faq\AddEditFaq` |

- **`Livewire\Admin\Faq\FaqsList`** — [`app/Livewire/Admin/Faq/FaqsList.php`](../../app/Livewire/Admin/Faq/FaqsList.php) (101 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `confirmDelete()`, `deleteFaq()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
- **`Livewire\Admin\Faq\AddEditFaq`** — [`app/Livewire/Admin/Faq/AddEditFaq.php`](../../app/Livewire/Admin/Faq/AddEditFaq.php) (82 lines)
  - Public state / form fields: `faqId`, `status`, `translations`, `activeLocale`
  - Actions/methods: `setActiveLocale()`, `save()`
  - Views: `livewire.admin.faq.add-edit-faq`

### `finance`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/finance/payouts` | `admin.finance.payouts.index` | billing.payouts.manage | `Livewire\Admin\Finance\PayoutsIndexPage` |
| GET | `/admin/finance/payouts/create/{tenant}` | `admin.finance.payouts.create` | billing.payouts.manage | `Livewire\Admin\Finance\TenantPayoutPage` |
| GET | `/admin/finance/payouts/{payout}/edit` | `admin.finance.payouts.edit` | billing.payouts.manage | `Livewire\Admin\Finance\TenantPayoutEditPage` |
| GET | `/admin/finance/reports` | `admin.finance.reports` | billing.reports.view | `Livewire\Admin\Finance\FinanceReportsPage` |

- **`Livewire\Admin\Finance\PayoutsIndexPage`** — [`app/Livewire/Admin/Finance/PayoutsIndexPage.php`](../../app/Livewire/Admin/Finance/PayoutsIndexPage.php) (137 lines)
  - Public state / form fields: `search`, `tenantFilter`, `statusFilter`, `dateFrom`, `dateTo`
  - Actions/methods: `updatedSearch()`, `updatedTenantFilter()`, `updatedStatusFilter()`, `updatedDateFrom()`, `updatedDateTo()`
- **`Livewire\Admin\Finance\TenantPayoutPage`** — [`app/Livewire/Admin/Finance/TenantPayoutPage.php`](../../app/Livewire/Admin/Finance/TenantPayoutPage.php) (155 lines)
  - Doc: /admin/finance/payouts/create/{tenant} — Record a payout from central to tenant.  Record-only: admin enters invoice number, bank/gateway name, transaction reference and amount; we persist a TenantPayout row and email the tenant admin a confirmation. The new row reduces central_owes_tenant on the led
  - Public state / form fields: `tenantId`, `ledgerEntry`, `invoiceNumber`, `method`, `bankName`, `bankAccount`, `gatewayName`, `transactionReference`, `amount`, `status`, `note`, `paidAt`, `sendEmail`
  - Actions/methods: `save()`
- **`Livewire\Admin\Finance\TenantPayoutEditPage`** — [`app/Livewire/Admin/Finance/TenantPayoutEditPage.php`](../../app/Livewire/Admin/Finance/TenantPayoutEditPage.php) (206 lines)
  - Doc: /admin/finance/payouts/{payout}/edit — edit or cancel a recorded tenant payout.  Editable only while status = pending. Transitioning pending → paid triggers the same payout_released side effect as the create page. Once paid, the record is read-only except for a cancel action, which flips status to c
  - Public state / form fields: `payoutId`, `tenantId`, `originalStatus`, `invoiceNumber`, `method`, `bankName`, `bankAccount`, `gatewayName`, `transactionReference`, `amount`, `status`, `note`, `paidAt`
  - Actions/methods: `save()`, `cancelPayout()`
- **`Livewire\Admin\Finance\FinanceReportsPage`** — [`app/Livewire/Admin/Finance/FinanceReportsPage.php`](../../app/Livewire/Admin/Finance/FinanceReportsPage.php) (61 lines)
  - Doc: /admin/finance/reports — Aggregate finance reports.  Tabs:  - overview          : platform-wide totals (gross, platform profit, settlements, payouts)  - tenants_owe       : ranked list of tenants owing central  - central_owes      : ranked list of tenants the central platform owes  - settlements    
  - Public state / form fields: `tab`
  - Actions/methods: `setTab()`

### `invoices`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/invoices` | `admin.invoices.index` | billing.invoices.view | `Livewire\Admin\Wallet\InvoicesList` |
| GET | `/admin/invoices/{invoiceId}` | `admin.invoices.show` | billing.invoices.view | `Livewire\Admin\Wallet\InvoiceDetailPage` |

- **`Livewire\Admin\Wallet\InvoicesList`** — [`app/Livewire/Admin/Wallet/InvoicesList.php`](../../app/Livewire/Admin/Wallet/InvoicesList.php) (118 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
- **`Livewire\Admin\Wallet\InvoiceDetailPage`** — [`app/Livewire/Admin/Wallet/InvoiceDetailPage.php`](../../app/Livewire/Admin/Wallet/InvoiceDetailPage.php) (60 lines)
  - Public state / form fields: `invoiceId`, `invoice`

### `labels`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/labels` | `admin.labels.index` | catalog.labels.view,catalog.labels.manage | `Livewire\Admin\Label\LabelsList` |
| GET | `/admin/labels/create` | `admin.labels.create` | catalog.labels.manage | `Livewire\Admin\Label\AddEditLabel` |
| GET | `/admin/labels/{label}/edit` | `admin.labels.edit` | catalog.labels.manage | `Livewire\Admin\Label\AddEditLabel` |

- **`Livewire\Admin\Label\LabelsList`** — [`app/Livewire/Admin/Label/LabelsList.php`](../../app/Livewire/Admin/Label/LabelsList.php) (54 lines)
  - Public state / form fields: `search`
  - Actions/methods: `updatedSearch()`, `toggleActive()`, `deleteLabel()`
  - Views: `livewire.admin.label.labels-list`
- **`Livewire\Admin\Label\AddEditLabel`** — [`app/Livewire/Admin/Label/AddEditLabel.php`](../../app/Livewire/Admin/Label/AddEditLabel.php) (87 lines)
  - Public state / form fields: `labelId`, `text`, `translations`, `activeLocale`, `icon`, `active`, `sortOrder`
  - Actions/methods: `setActiveLocale()`, `save()`
  - Validation: `text`: required', 'string', 'max:120; `translations`: array; `translations.*`: nullable', 'string', 'max:120; `icon`: nullable', Rule::in(array_keys(ProductLabelIcons::all())); `active`: boolean; `sortOrder`: integer', 'min:0
  - Views: `livewire.admin.label.add-edit-label`

### `login`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/login` | `admin.login` | — | `Livewire\Admin\Auth\LoginPage` |

- **`Livewire\Admin\Auth\LoginPage`** — [`app/Livewire/Admin/Auth/LoginPage.php`](../../app/Livewire/Admin/Auth/LoginPage.php) (77 lines)
  - Public state / form fields: `email`, `password`, `remember`
  - Actions/methods: `login()`
  - Validation: `email`: required', 'email; `password`: required', 'string; `remember`: boolean
  - Views: `livewire.auth.panel-login`

### `logout`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/admin/logout` | `admin.logout` | — | `Admin\AdminAuthController@logout` |

- **`Http\Controllers\Admin\AdminAuthController`** — [`app/Http/Controllers/Admin/AdminAuthController.php`](../../app/Http/Controllers/Admin/AdminAuthController.php) (35 lines)
  - Actions/methods: `logout()`, `broadcastAuth()`

### `manufacturing`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/manufacturing` | `admin.manufacturing.index` | manufacturing.view,manufacturing.manage | `Livewire\Admin\Manufacturing\ManufacturingRequestsList` |
| GET | `/admin/manufacturing/{id}` | `admin.manufacturing.show` | manufacturing.view | `Livewire\Admin\Manufacturing\ManufacturingRequestDetail` |
| GET | `/admin/manufacturing/{id}/file` | `admin.manufacturing.file` | manufacturing.view | `Admin\ManufacturingRequestFileController@show` |

- **`Livewire\Admin\Manufacturing\ManufacturingRequestsList`** — [`app/Livewire/Admin/Manufacturing/ManufacturingRequestsList.php`](../../app/Livewire/Admin/Manufacturing/ManufacturingRequestsList.php) (175 lines)
  - Public state / form fields: `search`, `statusFilter`, `tenantFilter`, `showModal`, `selectedId`, `newStatus`, `adminNotes`, `selectedProductName`, `selectedTenantName`, `selectedCurrentStatus`, `selectedCurrentStatusClass`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedTenantFilter()`, `clearFilters()`, `openStatusModal()`, `closeModal()`, `updateStatus()`
  - Validation: `newStatus`: required|in:; `adminNotes`: nullable|string|max:2000
- **`Livewire\Admin\Manufacturing\ManufacturingRequestDetail`** — [`app/Livewire/Admin/Manufacturing/ManufacturingRequestDetail.php`](../../app/Livewire/Admin/Manufacturing/ManufacturingRequestDetail.php) (228 lines)
  - Public state / form fields: `requestId`, `newStatus`, `adminNotes`, `chatMessage`, `showPaymentForm`, `paymentLabel`, `paymentAmount`, `paymentCurrency`, `paymentNotes`
  - Actions/methods: `updateStatus()`, `sendMessage()`, `togglePaymentForm()`, `createPaymentRequest()`, `cancelPaymentRequest()`
  - Validation: `newStatus`: required|in:; `adminNotes`: nullable|string|max:2000; `chatMessage`: required|string|max:2000; `paymentLabel`: required|string|max:255; `paymentAmount`: required|numeric|min:0.01|max:9999999; `paymentCurrency`: required|string|max:10; `paymentNotes`: nullable|string|max:1000
  - Views: `livewire.admin.manufacturing.manufacturing-request-detail`
- **`Http\Controllers\Admin\ManufacturingRequestFileController`** — [`app/Http/Controllers/Admin/ManufacturingRequestFileController.php`](../../app/Http/Controllers/Admin/ManufacturingRequestFileController.php) (31 lines)
  - Doc: PANELS-UPDATES-MANUFACTURING: lets admins open the reference images a tenant attached to a manufacturing request (they live in the tenant's own storage, outside the public symlink).
  - Actions/methods: `show()`

### `newsletter`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/newsletter` | `admin.newsletter.index` | content.newsletter.view,content.newsletter.manage | `Livewire\Admin\Newsletter\NewsletterSubscribersList` |

- **`Livewire\Admin\Newsletter\NewsletterSubscribersList`** — [`app/Livewire/Admin/Newsletter/NewsletterSubscribersList.php`](../../app/Livewire/Admin/Newsletter/NewsletterSubscribersList.php) (102 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `confirmDelete()`, `deleteSubscriber()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`

### `notifications`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/notifications` | `admin.notifications.index` | — | `Livewire\Admin\Notifications\NotificationsPage` |
| GET | `/admin/notifications/latest` | `admin.notifications.latest` | — | `Admin\AdminNotificationController@latest` |
| POST | `/admin/notifications/read-all` | `admin.notifications.read-all` | — | `Admin\AdminNotificationController@readAll` |
| GET | `/admin/notifications/{id}/open` | `admin.notifications.open` | — | `Admin\AdminNotificationController@open` |
| POST | `/admin/notifications/{id}/read` | `admin.notifications.read` | — | `Admin\AdminNotificationController@read` |

- **`Livewire\Admin\Notifications\NotificationsPage`** — [`app/Livewire/Admin/Notifications/NotificationsPage.php`](../../app/Livewire/Admin/Notifications/NotificationsPage.php) (73 lines)
  - Public state / form fields: `typeFilter`
  - Actions/methods: `markRead()`, `markAllRead()`, `deleteNotification()`, `updatedTypeFilter()`
- **`Http\Controllers\Admin\AdminNotificationController`** — [`app/Http/Controllers/Admin/AdminNotificationController.php`](../../app/Http/Controllers/Admin/AdminNotificationController.php) (48 lines)
  - Doc: JSON endpoints behind the admin bell dropdown (see resources/js/notifications/bell.js).
  - Actions/methods: `latest()`, `open()`, `read()`, `readAll()`

### `orders`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/orders` | `admin.orders.index` | sales.orders.view,sales.orders.manage | `Livewire\Admin\Order\OrdersList` |
| GET | `/admin/orders/refunds` | `admin.orders.refunds.index` | sales.orders.view,sales.orders.manage | `Livewire\Admin\Order\RefundsList` |
| GET | `/admin/orders/report` | `admin.orders.report` | sales.orders.report.view,sales.orders.manage | `Livewire\Admin\Order\OrdersReportPage` |
| GET | `/admin/orders/returns` | `admin.orders.returns.index` | sales.orders.view,sales.orders.manage | `Livewire\Admin\Order\OrderReturnsList` |
| GET | `/admin/orders/returns/analytics` | `admin.orders.returns.analytics` | sales.orders.view,sales.orders.manage | `Livewire\Admin\Order\ReturnAnalyticsPage` |
| GET | `/admin/orders/returns/{id}` | `admin.orders.returns.show` | sales.orders.manage | `Livewire\Admin\Order\OrderReturnDetailPage` |
| GET | `/admin/orders/{tenantId}/{orderNumber}` | `admin.orders.show` | sales.orders.view,sales.orders.manage | `Livewire\Admin\Order\OrderDetailPage` |
| GET | `/admin/orders/{tenantId}/{orderNumber}/receipt` | `admin.orders.receipt` | sales.orders.view,sales.orders.manage | `Admin\OrderReceiptController@show` |
| GET | `/admin/orders/{tenantId}/{orderNumber}/shipping-label` | `admin.orders.shipping-label` | sales.orders.view,sales.orders.manage | `Admin\OrderReceiptController@shippingLabel` |

- **`Livewire\Admin\Order\OrdersList`** — [`app/Livewire/Admin/Order/OrdersList.php`](../../app/Livewire/Admin/Order/OrdersList.php) (157 lines)
  - Public state / form fields: `search`, `statusFilter`, `paymentFilter`, `gatewayFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedPaymentFilter()`, `updatedGatewayFilter()`, `clearFilters()`
- **`Livewire\Admin\Order\RefundsList`** — [`app/Livewire/Admin/Order/RefundsList.php`](../../app/Livewire/Admin/Order/RefundsList.php) (258 lines)
  - Doc: Orders → Refunds: every refund across all tenants (cancellation, return and manual) read straight from the central `refunds` table (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Admin).
  - Public state / form fields: `search`, `tenantFilter`, `statusFilter`, `sourceFilter`, `dateFrom`, `dateTo`
  - Actions/methods: `updatedSearch()`, `updatedTenantFilter()`, `updatedStatusFilter()`, `updatedSourceFilter()`, `updatedDateFrom()`, `updatedDateTo()`, `clearFilters()`
- **`Livewire\Admin\Order\OrdersReportPage`** — [`app/Livewire/Admin/Order/OrdersReportPage.php`](../../app/Livewire/Admin/Order/OrdersReportPage.php) (37 lines)
- **`Livewire\Admin\Order\OrderReturnsList`** — [`app/Livewire/Admin/Order/OrderReturnsList.php`](../../app/Livewire/Admin/Order/OrderReturnsList.php) (133 lines)
  - Public state / form fields: `search`, `statusFilter`
- **`Livewire\Admin\Order\ReturnAnalyticsPage`** — [`app/Livewire/Admin/Order/ReturnAnalyticsPage.php`](../../app/Livewire/Admin/Order/ReturnAnalyticsPage.php) (209 lines)
  - Public state / form fields: `tenantFilter`
  - Actions/methods: `export()`
- **`Livewire\Admin\Order\OrderReturnDetailPage`** — [`app/Livewire/Admin/Order/OrderReturnDetailPage.php`](../../app/Livewire/Admin/Order/OrderReturnDetailPage.php) (490 lines)
  - Public state / form fields: `returnId`, `order`, `noteText`, `showApproveModal`, `approveNote`, `showRejectModal`, `rejectReason`, `showInfoModal`, `infoMessage`, `showInspectModal`, `inspectionResult`, `inspectionNotes`, `restock`, `showRefundModal`, `refundAmount`, `showShipModal`, `trackingNumber`, `completeRefundId`, `completeReference`, `rejectRefundId`, `rejectRefundReason`
  - Actions/methods: `addNote()`, `openApproveModal()`, `approve()`, `openRejectModal()`, `reject()`, `openInfoModal()`, `requestMoreInfo()`, `forwardToMerchant()`, `markItemReceived()`, `openInspectModal()`, `inspect()`, `openRefundModal()`, `issueRefund()`, `openShipModal()`, `markExchangeShipped()`, `markExchangeCompleted()`, `convertToRefund()`, `close()`, `retryRefund()`, `openCompleteRefund()`, `closeCompleteRefund()`, `completeRefund()`, `openRejectRefund()`, `closeRejectRefund()`, `rejectRefund()`
  - Validation: `noteText`: required', 'string', 'max:2000; `approveNote`: nullable', 'string', 'max:2000; `rejectReason`: required', 'string', 'max:2000; `infoMessage`: required', 'string', 'max:2000; `inspectionResult`: required', 'string', Rule::enum(InspectionResult::class); `inspectionNotes`: nullable', 'string', 'max:2000; `restock`: boolean; `inspectionResult.required`: Please choose an inspection result.; `inspectionResult.enum`: Please choose an inspection result.; `refundAmount`: required', 'numeric', 'min:0.01', 'max:'.max($max, 0.01); `trackingNumber`: required', 'string', 'max:190
- **`Livewire\Admin\Order\OrderDetailPage`** — [`app/Livewire/Admin/Order/OrderDetailPage.php`](../../app/Livewire/Admin/Order/OrderDetailPage.php) (356 lines)
  - Public state / form fields: `tenantId`, `orderNumber`, `order`, `trackingNumber`, `showReturnModal`, `returnReason`, `returnNotes`, `showCancelModal`, `cancelStep`, `cancelReason`, `cancelNote`, `showRefundModal`, `refundAmount`, `refundReason`, `completeRefundId`, `completeReference`, `rejectRefundId`, `rejectRefundReason`
  - Actions/methods: `updateTrackingNumber()`, `createReturn()`, `openCancelModal()`, `closeCancelModal()`, `continueCancel()`, `backToCancelReason()`, `cancelOrder()`, `openRefundModal()`, `closeRefundModal()`, `issueRefund()`, `retryRefund()`, `openCompleteRefund()`, `closeCompleteRefund()`, `completeRefund()`, `openRejectRefund()`, `closeRejectRefund()`, `rejectRefund()`
  - Validation: `trackingNumber`: nullable', 'string', 'max:255; `returnReason`: required', 'string', 'min:10', 'max:1000; `returnNotes`: nullable', 'string', 'max:1000; `cancelReason`: required', 'string', Rule::in(array_keys(CancellationReason::options(CancellationActor::Ad; `cancelNote`: Rule::requiredIf($this->cancelReason === CancellationReason::Other->value), 'nullable', 's; `refundAmount`: required', 'numeric', 'min:0.01', 'max:'.max($refundable, 0.01); `refundReason`: required', 'string', 'max:255
- **`Http\Controllers\Admin\OrderReceiptController`** — [`app/Http/Controllers/Admin/OrderReceiptController.php`](../../app/Http/Controllers/Admin/OrderReceiptController.php) (60 lines)
  - Actions/methods: `show()`, `shippingLabel()`
  - Views: `admin.order.receipt`, `admin.order.shipping-label`

### `pages`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/pages` | `admin.pages.index` | content.pages.view,content.pages.manage | `Livewire\Admin\Page\StaticPagesList` |
| GET | `/admin/pages/create` | `admin.pages.create` | content.pages.manage | `Livewire\Admin\Page\AddEditStaticPage` |
| GET | `/admin/pages/{staticPage}/edit` | `admin.pages.edit` | content.pages.manage | `Livewire\Admin\Page\AddEditStaticPage` |

- **`Livewire\Admin\Page\StaticPagesList`** — [`app/Livewire/Admin/Page/StaticPagesList.php`](../../app/Livewire/Admin/Page/StaticPagesList.php) (120 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `activatePage()`, `archivePage()`, `confirmDelete()`, `deletePage()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
- **`Livewire\Admin\Page\AddEditStaticPage`** — [`app/Livewire/Admin/Page/AddEditStaticPage.php`](../../app/Livewire/Admin/Page/AddEditStaticPage.php) (86 lines)
  - Public state / form fields: `pageId`, `status`, `isCompliance`, `translations`, `activeLocale`
  - Actions/methods: `setActiveLocale()`, `save()`
  - Views: `livewire.admin.pages.add-edit-static-page`

### `payment-logs`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/payment-logs` | `admin.payment-logs.index` | billing.payment-logs.view | `Livewire\Admin\PaymentLog\PaymentLogsList` |
| GET | `/admin/payment-logs/{tenantId}/{orderNumber}` | `admin.payment-logs.show` | billing.payment-logs.view | `Livewire\Admin\PaymentLog\PaymentLogDetailPage` |

- **`Livewire\Admin\PaymentLog\PaymentLogsList`** — [`app/Livewire/Admin/PaymentLog/PaymentLogsList.php`](../../app/Livewire/Admin/PaymentLog/PaymentLogsList.php) (119 lines)
  - Public state / form fields: `search`, `statusFilter`, `gatewayFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedGatewayFilter()`, `clearFilters()`
- **`Livewire\Admin\PaymentLog\PaymentLogDetailPage`** — [`app/Livewire/Admin/PaymentLog/PaymentLogDetailPage.php`](../../app/Livewire/Admin/PaymentLog/PaymentLogDetailPage.php) (60 lines)
  - Public state / form fields: `tenantId`, `orderNumber`, `log`

### `plans`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/plans` | `admin.plans.index` | plans.packages.view,plans.packages.manage | `Livewire\Admin\Plan\PlansList` |
| GET | `/admin/plans/create` | `admin.plans.create` | plans.packages.manage | `Livewire\Admin\Plan\AddEditPackage` |
| GET | `/admin/plans/pending-registrations` | `admin.plans.pending-registrations` | plans.pending-registrations.view,plans.pending-registrations.manage | `Livewire\Admin\Plan\PendingRegistrationsList` |
| GET | `/admin/plans/registered-users` | `admin.plans.users` | plans.tenants.view,plans.tenants.manage | `Livewire\Admin\Plan\RegisteredUsersList` |
| POST | `/admin/plans/registered-users` | `admin.plans.users.store` | plans.tenants.manage | `Admin\TenantEditorController@store` |
| GET | `/admin/plans/registered-users/create` | `admin.plans.users.create` | plans.tenants.manage | `Admin\TenantEditorController@create` |
| GET | `/admin/plans/registered-users/{tenantId}/impersonate` | `admin.plans.users.impersonate` | plans.tenants.manage | `Admin\TenantImpersonateController@generate` |
| PUT | `/admin/plans/registered-users/{tenant}` | `admin.plans.users.update` | plans.tenants.manage | `Admin\TenantEditorController@update` |
| GET | `/admin/plans/registered-users/{tenant}/edit` | `admin.plans.users.edit` | plans.tenants.manage | `Admin\TenantEditorController@edit` |
| PUT | `/admin/plans/registered-users/{tenant}/mobile-app` | `admin.plans.users.mobile-app.update` | plans.tenants.manage | `Admin\TenantMobileAppController@update` |
| GET | `/admin/plans/tenant-change-requests` | `admin.plans.tenant-change-requests` | plans.tenant-change-requests.manage | `Livewire\Admin\Plan\TenantChangeRequestsList` |
| GET | `/admin/plans/{package}/edit` | `admin.plans.edit` | plans.packages.manage | `Livewire\Admin\Plan\AddEditPackage` |

- **`Livewire\Admin\Plan\PlansList`** — [`app/Livewire/Admin/Plan/PlansList.php`](../../app/Livewire/Admin/Plan/PlansList.php) (54 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`, `deletePackage()`
  - Views: `livewire.admin.plan.plans-list`
- **`Livewire\Admin\Plan\AddEditPackage`** — [`app/Livewire/Admin/Plan/AddEditPackage.php`](../../app/Livewire/Admin/Plan/AddEditPackage.php) (259 lines)
  - Public state / form fields: `packageId`, `status`, `term`, `price`, `trialDays`, `featureRows`, `translations`, `activeLocale`, `icon`, `categoriesCount`, `allCategories`, `productsLimit`, `bannersLimit`, `languagesLimit`, `ordersPerMonthLimit`, `aiCallsLimit`, `imageSearchesLimit`, `aiTranslationEnabled`
  - Actions/methods: `setActiveLocale()`, `updatedAllCategories()`, `addFeatureRow()`, `removeFeatureRow()`, `save()`, `iconOptions()`
  - Validation: `status`: required', Rule::enum(PackageStatus::class); `price`: required', 'numeric', 'min:0; `trialDays`: nullable', 'integer', 'min:0; `featureRows`: array; `featureRows.*.label`: nullable', 'string', 'max:255; `featureRows.*.value`: nullable', 'string', 'max:255; `icon`: nullable', 'string', 'max:255; `productsLimit`: required', 'integer', 'min:-1; `bannersLimit`: required', 'integer', 'min:-1; `languagesLimit`: required', 'integer', 'min:-1; `ordersPerMonthLimit`: required', 'integer', 'min:-1; `aiCallsLimit`: required', 'integer', 'min:-1; `imageSearchesLimit`: required', 'integer', 'min:-1; `aiTranslationEnabled`: boolean
  - Views: `livewire.admin.plan.add-edit-package`
- **`Livewire\Admin\Plan\PendingRegistrationsList`** — [`app/Livewire/Admin/Plan/PendingRegistrationsList.php`](../../app/Livewire/Admin/Plan/PendingRegistrationsList.php) (85 lines)
  - Public state / form fields: `search`, `statusFilter`, `packageFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedPackageFilter()`, `clearFilters()`, `deleteRecord()`
  - Views: `livewire.admin.plan.pending-registrations-list`
- **`Livewire\Admin\Plan\RegisteredUsersList`** — [`app/Livewire/Admin/Plan/RegisteredUsersList.php`](../../app/Livewire/Admin/Plan/RegisteredUsersList.php) (128 lines)
  - Public state / form fields: `search`, `statusFilter`, `packageFilter`, `imageFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedPackageFilter()`, `updatedImageFilter()`, `clearFilters()`, `confirmDeleteTenant()`, `deleteTenant()`, `downloadLogo()`, `export()`
  - Views: `livewire.admin.plan.registered-users-list`
- **`Http\Controllers\Admin\TenantEditorController`** — [`app/Http/Controllers/Admin/TenantEditorController.php`](../../app/Http/Controllers/Admin/TenantEditorController.php) (181 lines)
  - Actions/methods: `create()`, `edit()`, `store()`, `update()`
  - Validation: `name`: required', 'string', 'max:255
  - Views: `admin.plan.add-edit-tenant`
- **`Http\Controllers\Admin\TenantImpersonateController`** — [`app/Http/Controllers/Admin/TenantImpersonateController.php`](../../app/Http/Controllers/Admin/TenantImpersonateController.php) (81 lines)
  - Doc: Generates a short-lived impersonation token that allows a central admin to log into a tenant's admin panel without knowing the tenant's credentials.  Flow:   1. Central admin visits  GET /admin/tenants/{tenantId}/impersonate   2. This controller stores a one-time token in the cache (TTL 90 s)   3. T
  - Actions/methods: `generate()`
- **`Http\Controllers\Admin\TenantMobileAppController`** — [`app/Http/Controllers/Admin/TenantMobileAppController.php`](../../app/Http/Controllers/Admin/TenantMobileAppController.php) (115 lines)
  - Doc: Central admin > Tenants > Edit tenant > "Mobile App" tab: the tenant's own white-label app ids (assetlinks / apple-app-site-association on its domains, /app landing, payment return scheme). Values come from application/tenants/<id>/tenant.json + the store consoles (PUBLISH_TO_STORES.md §5-§7).
  - Actions/methods: `update()`
  - Validation: `_tab`: nullable', 'string
- **`Livewire\Admin\Plan\TenantChangeRequestsList`** — [`app/Livewire/Admin/Plan/TenantChangeRequestsList.php`](../../app/Livewire/Admin/Plan/TenantChangeRequestsList.php) (209 lines)
  - Public state / form fields: `search`, `statusFilter`, `typeFilter`, `rejectingId`, `adminNote`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedTypeFilter()`, `approve()`, `openRejectModal()`, `cancelReject()`, `confirmReject()`
  - Validation: `adminNote`: nullable', 'string', 'max:500
  - Views: `livewire.admin.plan.tenant-change-requests-list`

### `product-requests`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/product-requests` | `admin.product-requests.index` | catalog.product-requests.view,catalog.product-requests.manage | `Livewire\Admin\ProductRequest\RequestsList` |
| GET | `/admin/product-requests/{requestId}` | `admin.product-requests.show` | catalog.product-requests.view,catalog.product-requests.manage | `Livewire\Admin\ProductRequest\RequestDetail` |
| GET | `/admin/product-requests/{requestId}/file` | `admin.product-requests.file` | catalog.product-requests.view,catalog.product-requests.manage | `Admin\ProductRequestFileController@show` |

- **`Livewire\Admin\ProductRequest\RequestsList`** — [`app/Livewire/Admin/ProductRequest/RequestsList.php`](../../app/Livewire/Admin/ProductRequest/RequestsList.php) (131 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `archive()`, `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`
- **`Livewire\Admin\ProductRequest\RequestDetail`** — [`app/Livewire/Admin/ProductRequest/RequestDetail.php`](../../app/Livewire/Admin/ProductRequest/RequestDetail.php) (216 lines)
  - Public state / form fields: `requestId`, `request`, `reply`, `statusSelection`, `priority`, `replyFiles`
  - Actions/methods: `sendReply()`, `updateStatus()`, `refreshRequest()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `reply`: required', 'string', 'min:2', 'max:5000; `replyFiles`: array', 'max:5; `replyFiles.*`: file', 'max:10240', 'mimes:jpg,jpeg,png,webp,avif,gif,pdf,doc,docx; `statusSelection`: required', 'string', 'in:' . implode(',', array_column(ProductRequestStatus::cases(), 'val
- **`Http\Controllers\Admin\ProductRequestFileController`** — [`app/Http/Controllers/Admin/ProductRequestFileController.php`](../../app/Http/Controllers/Admin/ProductRequestFileController.php) (36 lines)
  - Doc: PANELS-UPDATES-PRODUCT-REQUESTS: lets admins open attachments a tenant uploaded with a product request (they live in the tenant's own storage, outside the public symlink).
  - Actions/methods: `show()`

### `products`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/products` | `admin.products.index` | catalog.products.view,catalog.products.manage | `Livewire\Admin\Product\ProductsList` |
| GET | `/admin/products/create` | `admin.products.create` | catalog.products.manage | `Admin\ProductController@create` |
| POST | `/admin/products/create` | `admin.products.store` | catalog.products.manage | `Admin\ProductController@store` |
| GET | `/admin/products/edit-requests` | `admin.products.edit-requests` | catalog.product-edit-requests.manage | `Admin\ProductEditRequestController@index` |
| GET | `/admin/products/edit-requests/{editRequest}` | `admin.products.edit-requests.show` | catalog.product-edit-requests.manage | `Admin\ProductEditRequestController@show` |
| POST | `/admin/products/edit-requests/{editRequest}/approve` | `admin.products.edit-requests.approve` | catalog.product-edit-requests.manage | `Admin\ProductEditRequestController@approve` |
| POST | `/admin/products/edit-requests/{editRequest}/reject` | `admin.products.edit-requests.reject` | catalog.product-edit-requests.manage | `Admin\ProductEditRequestController@reject` |
| POST | `/admin/products/edit-requests/{editRequest}/reset` | `admin.products.edit-requests.reset` | catalog.product-edit-requests.manage | `Admin\ProductEditRequestController@reset` |
| POST | `/admin/products/image-search` | `admin.products.image-search` | catalog.products.view,catalog.products.manage | `ImageSearchController@adminPanel` |
| GET | `/admin/products/sort` | `admin.products.sort` | catalog.products.manage | `Livewire\Admin\Product\SortProducts` |
| POST | `/admin/products/validate` | `admin.products.validate` | catalog.products.manage | `Admin\ProductController@validateForm` |
| GET | `/admin/products/{product}/edit` | `admin.products.edit` | catalog.products.manage | `Admin\ProductController@edit` |
| PUT | `/admin/products/{product}/edit` | `admin.products.update` | catalog.products.manage | `Admin\ProductController@update` |
| POST | `/admin/products/{product}/validate` | `admin.products.validate.update` | catalog.products.manage | `Admin\ProductController@validateForm` |

- **`Livewire\Admin\Product\ProductsList`** — [`app/Livewire/Admin/Product/ProductsList.php`](../../app/Livewire/Admin/Product/ProductsList.php) (202 lines)
  - Public state / form fields: `search`, `statusFilter`, `categoryFilter`, `deliveryScopeFilter`, `stockFilter`, `showDeleted`, `imageSearchIds`, `imageSearchActive`, `priceListOpen`, `priceListProductName`, `priceListRows`, `sortKey`, `sortDir`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedCategoryFilter()`, `updatedDeliveryScopeFilter()`, `updatedStockFilter()`, `updatedShowDeleted()`, `clearFilters()`, `applyImageSearch()`, `clearImageSearch()`, `deleteProduct()`, `restoreProduct()`, `openPriceListModal()`, `closePriceListModal()`, `sortByColumn()`
  - Views: `livewire.admin.product.products-list`
- **`Http\Controllers\Admin\ProductController`** — [`app/Http/Controllers/Admin/ProductController.php`](../../app/Http/Controllers/Admin/ProductController.php) (426 lines)
  - Actions/methods: `create()`, `edit()`, `store()`, `update()`, `validateForm()`
  - Views: `admin.product.add-edit-product`
- **`Http\Controllers\Admin\ProductEditRequestController`** — [`app/Http/Controllers/Admin/ProductEditRequestController.php`](../../app/Http/Controllers/Admin/ProductEditRequestController.php) (142 lines)
  - Doc: Central admin "Product Edit Requests" (dedup plan §4.2a, §7 no-Livewire rule): Blade list with GET filters + JSON actions called with fetch() (details, approve, reject, reset to central). Replaces the Livewire `Admin\Product\ProductEditRequestsList`. The whole route group requires `catalog.product-e
  - Actions/methods: `index()`, `show()`, `approve()`, `reject()`, `reset()`
  - Validation: `admin_note`: nullable', 'string', 'max:500
  - Views: `admin.product.edit-requests.index`
- **`Http\Controllers\ImageSearchController`** — [`app/Http/Controllers/ImageSearchController.php`](../../app/Http/Controllers/ImageSearchController.php) (155 lines)
  - Actions/methods: `storefront()`, `adminPanel()`, `tenantPanel()`
  - Validation: `image`: required', 'image', 'max:8192
- **`Livewire\Admin\Product\SortProducts`** — [`app/Livewire/Admin/Product/SortProducts.php`](../../app/Livewire/Admin/Product/SortProducts.php) (43 lines)
  - Public state / form fields: `search`
  - Actions/methods: `updateOrder()`
  - Views: `livewire.admin.product.sort-products`

### `products-run`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/products-run` | `admin.` | — | `Admin\DevToolsController@syncProductFixedShippingCosts` |

- **`Http\Controllers\Admin\DevToolsController`** — [`app/Http/Controllers/Admin/DevToolsController.php`](../../app/Http/Controllers/Admin/DevToolsController.php) (24 lines)
  - Actions/methods: `syncProductFixedShippingCosts()`, `importNeozenaProducts()`

### `settings`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/settings/ai` | `admin.settings.ai` | settings.general.manage | `Livewire\Admin\Setting\AiSettingsPage` |
| GET | `/admin/settings/ai-logo-limit` | `admin.settings.ai-logo-limit` | settings.ai-logo-limits.manage | `Livewire\Admin\Setting\AiLogoLimitPage` |
| GET | `/admin/settings/blade-themes` | `admin.settings.blade-themes` | settings.templates.manage | `Livewire\Admin\Setting\BladeThemeQueuePage` |
| GET | `/admin/settings/countries` | `admin.settings.countries` | settings.countries.view,settings.countries.manage | `Livewire\Admin\Setting\CountriesPage` |
| GET | `/admin/settings/currencies` | `admin.settings.currencies` | settings.currencies.view,settings.currencies.manage | `Livewire\Admin\Setting\CurrenciesPage` |
| GET | `/admin/settings/currencies/create` | `admin.settings.currencies.create` | settings.currencies.manage | `Livewire\Admin\Setting\AddEditCurrency` |
| GET | `/admin/settings/currencies/{currency}/edit` | `admin.settings.currencies.edit` | settings.currencies.manage | `Livewire\Admin\Setting\AddEditCurrency` |
| GET | `/admin/settings/default-banners` | `admin.settings.default-banners` | settings.default-banners.manage | `Livewire\Admin\Setting\DefaultBannersListPage` |
| GET | `/admin/settings/default-banners/list/{countryId?}` | `admin.settings.default-banners.list` | — | `Closure` |
| GET | `/admin/settings/email-configuration` | `admin.settings.email-configuration` | settings.email-configuration.manage | `Livewire\Admin\Setting\EmailConfigurationPage` |
| GET | `/admin/settings/email-templates` | `admin.settings.email-templates` | settings.email-templates.manage | `Livewire\Admin\Setting\EmailTemplatesPage` |
| GET | `/admin/settings/email-templates/create` | `admin.settings.email-templates.create` | settings.email-templates.manage | `Livewire\Admin\Setting\AddEditEmailTemplate` |
| GET | `/admin/settings/email-templates/{emailTemplate}/edit` | `admin.settings.email-templates.edit` | settings.email-templates.manage | `Livewire\Admin\Setting\AddEditEmailTemplate` |
| GET | `/admin/settings/general` | `admin.settings.general` | settings.general.manage | `Livewire\Admin\Setting\GeneralSettingsPage` |
| GET | `/admin/settings/home-variants` | `admin.settings.home-variants` | settings.home-variants.manage | `Livewire\Admin\Setting\HomeVariantsPage` |
| GET | `/admin/settings/language-purchases` | `admin.settings.language-purchases` | billing.payment-logs.view,settings.languages.purchases.view | `Livewire\Admin\Setting\LanguagePurchasesPage` |
| GET | `/admin/settings/languages` | `admin.settings.languages` | settings.languages.view,settings.languages.manage | `Livewire\Admin\Setting\LanguagesPage` |
| POST | `/admin/settings/languages` | `admin.settings.languages.store` | settings.languages.manage | `Admin\LanguageController@store` |
| GET | `/admin/settings/languages/create` | `admin.settings.languages.create` | settings.languages.manage | `Admin\LanguageController@create` |
| PUT | `/admin/settings/languages/{language}` | `admin.settings.languages.update` | settings.languages.manage | `Admin\LanguageController@update` |
| GET | `/admin/settings/languages/{language}/edit` | `admin.settings.languages.edit` | settings.languages.manage | `Admin\LanguageController@edit` |
| GET | `/admin/settings/maintenance` | `admin.settings.maintenance` | settings.maintenance.manage | `Livewire\Admin\Setting\MaintenanceModePage` |
| GET | `/admin/settings/payment-gateway-limits` | `admin.settings.payment-gateway-limits` | settings.payment-gateway-limits.manage | `Livewire\Admin\Setting\PaymentGatewayLimitsPage` |
| GET | `/admin/settings/payment-gateways` | `admin.settings.payment-gateways` | settings.payment-gateways.manage | `Livewire\Admin\Setting\PaymentGatewaysPage` |
| GET | `/admin/settings/payment-gateways/{gateway}/edit` | `admin.settings.payment-gateways.edit` | settings.payment-gateways.manage | `Livewire\Admin\Setting\AddEditPaymentGateway` |
| GET | `/admin/settings/return-policy` | `admin.settings.return-policy` | settings.general.manage | `Livewire\Admin\Setting\ReturnPolicySettingsPage` |
| GET | `/admin/settings/social-login` | `admin.settings.social-login` | settings.social-login.manage | `Livewire\Admin\Setting\SocialLoginPage` |
| GET | `/admin/settings/template-parts` | `admin.settings.template-parts` | settings.templates.manage | `Livewire\Admin\Setting\TemplatePartsPage` |
| GET | `/admin/settings/template-parts/create/{templateId?}` | `admin.settings.template-parts.create` | settings.templates.manage | `Livewire\Admin\Setting\AddEditTemplatePart` |
| GET | `/admin/settings/template-parts/{partId}/edit` | `admin.settings.template-parts.edit` | settings.templates.manage | `Livewire\Admin\Setting\AddEditTemplatePart` |
| GET | `/admin/settings/template-parts/{templateId}` | `admin.settings.template-parts.show` | settings.templates.manage | `Livewire\Admin\Setting\TemplatePartsPage` |
| GET | `/admin/settings/templates` | `admin.settings.templates` | settings.templates.manage | `Livewire\Admin\Setting\TemplateControlPage` |
| GET | `/admin/settings/translation-key-access` | `admin.settings.translation-key-access` | settings.languages.manage | `Livewire\Admin\Setting\TranslationKeyAccessPage` |
| GET | `/admin/settings/translations` | `admin.settings.translations` | settings.languages.view,settings.languages.manage | `Livewire\Admin\Setting\TranslationsPage` |

- **`Livewire\Admin\Setting\AiSettingsPage`** — [`app/Livewire/Admin/Setting/AiSettingsPage.php`](../../app/Livewire/Admin/Setting/AiSettingsPage.php) (165 lines)
  - Doc: Central admin: OpenAI provider settings used by every AI feature (price finder, social posts, logo generator, visual search, AI translation). Stored in central `app_settings`; the API key is encrypted. See App\Support\AiSettings.
  - Public state / form fields: `apiKey`, `baseUrl`, `textModel`, `imageModel`, `visionModel`, `searchModel`, `embeddingModel`, `translationModel`, `testResult`
  - Actions/methods: `save()`, `removeKey()`, `testConnection()`
  - Validation: `apiKey`: nullable', 'string', 'max:500', 'regex:/^\S+$/; `baseUrl`: required', 'url', 'max:255; `textModel`: required', 'string', 'max:100; `imageModel`: required', 'string', 'max:100; `visionModel`: required', 'string', 'max:100; `searchModel`: required', 'string', 'max:100; `embeddingModel`: required', 'string', 'max:100; `translationModel`: required', 'string', 'max:100; `apiKey.regex`: The API key must not contain spaces.
- **`Livewire\Admin\Setting\AiLogoLimitPage`** — [`app/Livewire/Admin/Setting/AiLogoLimitPage.php`](../../app/Livewire/Admin/Setting/AiLogoLimitPage.php) (83 lines)
  - Public state / form fields: `aiLogoGenerationLimit`
  - Actions/methods: `save()`
  - Validation: `aiLogoGenerationLimit`: nullable', 'integer', 'min:0
- **`Livewire\Admin\Setting\BladeThemeQueuePage`** — [`app/Livewire/Admin/Setting/BladeThemeQueuePage.php`](../../app/Livewire/Admin/Setting/BladeThemeQueuePage.php) (124 lines)
  - Public state / form fields: `status`, `rejectModalOpen`, `rejectingId`, `rejectionReason`
  - Actions/methods: `setStatus()`, `requestApprove()`, `approve()`, `openReject()`, `closeReject()`, `reject()`
  - Validation: `rejectionReason`: required', 'string', 'max:1000
  - Views: `livewire.admin.setting.blade-theme-queue-page`
- **`Livewire\Admin\Setting\CountriesPage`** — [`app/Livewire/Admin/Setting/CountriesPage.php`](../../app/Livewire/Admin/Setting/CountriesPage.php) (64 lines)
  - Public state / form fields: `search`
  - Actions/methods: `updatedSearch()`, `toggleActiveForTenants()`, `toggleFree()`
  - Views: `livewire.admin.setting.countries-page`
- **`Livewire\Admin\Setting\CurrenciesPage`** — [`app/Livewire/Admin/Setting/CurrenciesPage.php`](../../app/Livewire/Admin/Setting/CurrenciesPage.php) (57 lines)
  - Public state / form fields: `search`
  - Actions/methods: `updatedSearch()`, `clearFilters()`, `deleteCurrency()`
  - Views: `livewire.admin.setting.currencies-page`
- **`Livewire\Admin\Setting\AddEditCurrency`** — [`app/Livewire/Admin/Setting/AddEditCurrency.php`](../../app/Livewire/Admin/Setting/AddEditCurrency.php) (65 lines)
  - Public state / form fields: `currencyId`, `code`, `name`, `sign`, `conversionRate`, `isDefault`
  - Actions/methods: `save()`
  - Validation: `code`: required', 'string', 'size:3', Rule::unique('currencies', 'code')->ignore($this->currencyI; `name`: required', 'string', 'max:255; `sign`: nullable', 'string', 'max:12; `conversionRate`: required', 'numeric', 'gt:0; `isDefault`: boolean
  - Views: `livewire.admin.setting.add-edit-currency`
- **`Livewire\Admin\Setting\DefaultBannersListPage`** — [`app/Livewire/Admin/Setting/DefaultBannersListPage.php`](../../app/Livewire/Admin/Setting/DefaultBannersListPage.php) (193 lines)
  - Public state / form fields: `modalOpen`, `bannerId`, `bannerUrl`, `bannerSerial`, `bannerImage`, `bannerTranslations`, `countryIds`, `countryFilter`, `countryScopeVersion`
  - Actions/methods: `openModal()`, `closeModal()`, `save()`, `delete()`, `updateOrder()`, `clearFilters()`, `updatedCountryFilter()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
- **`Livewire\Admin\Setting\EmailConfigurationPage`** — [`app/Livewire/Admin/Setting/EmailConfigurationPage.php`](../../app/Livewire/Admin/Setting/EmailConfigurationPage.php) (180 lines)
  - Public state / form fields: `mailMailer`, `mailHost`, `mailPort`, `mailUsername`, `mailPassword`, `mailEncryption`, `mailFromAddress`, `mailFromName`, `mailQueue`, `showTestEmailModal`, `testEmailAddress`
  - Actions/methods: `save()`, `openTestEmailModal()`, `closeTestEmailModal()`, `sendTestEmail()`
  - Validation: `mailMailer`: required', 'string', 'max:50; `mailHost`: nullable', 'string', 'max:255; `mailPort`: required', 'string', 'max:10; `mailUsername`: nullable', 'string', 'max:255; `mailPassword`: nullable', 'string', 'max:255; `mailEncryption`: nullable', 'string', 'max:50; `mailFromAddress`: nullable', 'email', 'max:255; `mailFromName`: nullable', 'string', 'max:255; `mailQueue`: boolean; `testEmailAddress`: required', 'email', 'max:255
- **`Livewire\Admin\Setting\EmailTemplatesPage`** — [`app/Livewire/Admin/Setting/EmailTemplatesPage.php`](../../app/Livewire/Admin/Setting/EmailTemplatesPage.php) (115 lines)
  - Public state / form fields: `search`, `statusFilter`, `typeFilter`
  - Actions/methods: `confirmDelete()`, `deleteTemplate()`, `updatedSearch()`, `updatedStatusFilter()`, `updatedTypeFilter()`, `clearFilters()`
- **`Livewire\Admin\Setting\AddEditEmailTemplate`** — [`app/Livewire/Admin/Setting/AddEditEmailTemplate.php`](../../app/Livewire/Admin/Setting/AddEditEmailTemplate.php) (161 lines)
  - Public state / form fields: `templateId`, `name`, `templateAction`, `subject`, `body`, `templateType`, `status`, `translations`, `activeLocaleTab`
  - Actions/methods: `updatedTemplateType()`, `setActiveLocaleTab()`, `save()`, `syncToTenants()`
  - Validation: `name`: required', 'string', 'max:255; `templateAction`: required', Rule::enum(EmailTemplateAction::class), Rule::unique('email_templates', 'action; `subject`: required', 'string', 'max:255; `body`: nullable', 'string; `templateType`: required', Rule::enum(EmailTemplateType::class); `status`: required', Rule::enum(ActivationStatus::class); `translations.*.subject`: nullable', 'string', 'max:255; `translations.*.body`: nullable', 'string
  - Views: `livewire.admin.setting.add-edit-email-template`
- **`Livewire\Admin\Setting\GeneralSettingsPage`** — [`app/Livewire/Admin/Setting/GeneralSettingsPage.php`](../../app/Livewire/Admin/Setting/GeneralSettingsPage.php) (230 lines)
  - Public state / form fields: `appName`, `supportEmail`, `defaultCurrency`, `defaultLanguage`, `marketplaceName`, `headerLogoPath`, `headerLogoUpload`, `footerLogoPath`, `footerLogoUpload`, `faviconPath`, `faviconUpload`, `outOfStockArchiveDays`, `facebookUrl`, `twitterUrl`, `instagramUrl`, `youtubeUrl`, `linkedinUrl`
  - Actions/methods: `save()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `appName`: required', 'string', 'max:255; `supportEmail`: nullable', 'email', 'max:255; `defaultCurrency`: required', 'string', 'max:12; `defaultLanguage`: required', 'string', 'max:12; `marketplaceName`: required', 'string', 'max:255; `headerLogoUpload`: nullable', 'image', 'max:2048; `footerLogoUpload`: nullable', 'image', 'max:2048; `faviconUpload`: nullable', 'image', 'max:1024; `facebookUrl`: nullable', 'url', 'max:255; `twitterUrl`: nullable', 'url', 'max:255; `instagramUrl`: nullable', 'url', 'max:255; `youtubeUrl`: nullable', 'url', 'max:255; `linkedinUrl`: nullable', 'url', 'max:255; `outOfStockArchiveDays`: nullable', 'integer', 'min:1', 'max:3650
- **`Livewire\Admin\Setting\HomeVariantsPage`** — [`app/Livewire/Admin/Setting/HomeVariantsPage.php`](../../app/Livewire/Admin/Setting/HomeVariantsPage.php) (234 lines)
  - Public state / form fields: `modalOpen`, `editingId`, `themeSlug`, `name`, `key`, `description`, `sections`, `view`, `isDefault`, `isActive`, `previewImageFile`, `previewImageUrl`
  - Actions/methods: `updatedThemeSlug()`, `updatedName()`, `openCreate()`, `openEdit()`, `toggleSection()`, `updateSectionOrder()`, `closeModal()`, `save()`, `confirmDelete()`, `delete()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
- **`Livewire\Admin\Setting\LanguagePurchasesPage`** — [`app/Livewire/Admin/Setting/LanguagePurchasesPage.php`](../../app/Livewire/Admin/Setting/LanguagePurchasesPage.php) (114 lines)
  - Public state / form fields: `search`, `gatewayFilter`, `showPurchaseModal`, `selectedPurchase`
  - Actions/methods: `updatedSearch()`, `updatedGatewayFilter()`, `clearFilters()`, `viewPurchase()`, `closePurchaseModal()`
- **`Livewire\Admin\Setting\LanguagesPage`** — [`app/Livewire/Admin/Setting/LanguagesPage.php`](../../app/Livewire/Admin/Setting/LanguagesPage.php) (90 lines)
  - Public state / form fields: `search`, `directionFilter`, `activeFilter`
  - Actions/methods: `updatedSearch()`, `updatedDirectionFilter()`, `updatedActiveFilter()`, `clearFilters()`, `deleteLanguage()`, `retryTranslation()`, `updateOrder()`
  - Views: `livewire.admin.setting.languages-page`
- **`Http\Controllers\Admin\LanguageController`** — [`app/Http/Controllers/Admin/LanguageController.php`](../../app/Http/Controllers/Admin/LanguageController.php) (128 lines)
  - Actions/methods: `create()`, `edit()`, `store()`, `update()`
  - Validation: `name`: required', 'string', 'max:255; `code`: required', 'string', 'max:12', Rule::unique('languages', 'code')->ignore($language?->id); `native_name`: required', 'string', 'max:255; `direction`: required', Rule::enum(LanguageDirection::class); `is_default`: boolean; `is_active`: boolean; `is_free`: boolean; `price`: nullable', 'numeric', 'min:0.01', 'required_if:is_free,0; `offers_ai_translation`: boolean; `ai_translation_price`: nullable', 'numeric', 'min:0', 'required_if:offers_ai_translation,1; `auto_translate_catalog`: boolean; `countries`: nullable', 'array; `countries.*`: string', 'size:2; `image`: nullable', 'image', 'max:2048; `remove_image`: boolean
  - Views: `admin.setting.add-edit-language`
- **`Livewire\Admin\Setting\MaintenanceModePage`** — [`app/Livewire/Admin/Setting/MaintenanceModePage.php`](../../app/Livewire/Admin/Setting/MaintenanceModePage.php) (78 lines)
  - Public state / form fields: `message`, `isActive`, `startsAt`, `endsAt`
  - Actions/methods: `save()`
  - Validation: `message`: nullable', 'string; `isActive`: boolean; `startsAt`: nullable', 'date; `endsAt`: nullable', 'date
- **`Livewire\Admin\Setting\PaymentGatewayLimitsPage`** — [`app/Livewire/Admin/Setting/PaymentGatewayLimitsPage.php`](../../app/Livewire/Admin/Setting/PaymentGatewayLimitsPage.php) (99 lines)
  - Public state / form fields: `notifiedSalesAmount`, `notifiedSalesAmountBlock`
  - Actions/methods: `save()`
  - Validation: `notifiedSalesAmount`: nullable', 'numeric', 'min:0; `notifiedSalesAmountBlock`: nullable', 'numeric', 'min:0
- **`Livewire\Admin\Setting\PaymentGatewaysPage`** — [`app/Livewire/Admin/Setting/PaymentGatewaysPage.php`](../../app/Livewire/Admin/Setting/PaymentGatewaysPage.php) (124 lines)
  - Public state / form fields: `typeFilter`, `statusFilter`
  - Actions/methods: `updatedTypeFilter()`, `updatedStatusFilter()`, `clearFilters()`, `requestToggle()`, `toggle()`
- **`Livewire\Admin\Setting\AddEditPaymentGateway`** — [`app/Livewire/Admin/Setting/AddEditPaymentGateway.php`](../../app/Livewire/Admin/Setting/AddEditPaymentGateway.php) (141 lines)
  - Public state / form fields: `gatewayId`, `name`, `code`, `status`, `mode`, `sandboxMode`, `credentials`, `removeLogo`, `existingLogoUrl`, `feePercentage`, `feeFixed`, `logoImage`
  - Actions/methods: `removeLogoImage()`, `save()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `name`: required', 'string', 'max:255; `status`: required', Rule::enum(ActivationStatus::class); `credentials`: array; `credentials.*.key`: required', 'string', 'max:100; `credentials.*.value`: nullable', 'string', 'max:1000; `logoImage`: nullable', 'image', 'max:2048; `feePercentage`: nullable', 'numeric', 'min:0', 'max:100; `feeFixed`: nullable', 'numeric', 'min:0
  - Views: `livewire.admin.setting.add-edit-payment-gateway`
- **`Livewire\Admin\Setting\ReturnPolicySettingsPage`** — [`app/Livewire/Admin/Setting/ReturnPolicySettingsPage.php`](../../app/Livewire/Admin/Setting/ReturnPolicySettingsPage.php) (150 lines)
  - Public state / form fields: `windowDays`, `nonReturnableIds`, `fee`, `conditions`, `videoRequiredReasons`, `videoRequiredReasonsArray`
  - Actions/methods: `updatedVideoRequiredReasonsArray()`, `save()`
  - Validation: `windowDays`: required', 'integer', 'min:1', 'max:365; `nonReturnableIds`: nullable', 'string; `fee`: required', 'numeric', 'min:0; `conditions`: nullable', 'string', 'max:5000; `videoRequiredReasons`: nullable', 'string
- **`Livewire\Admin\Setting\SocialLoginPage`** — [`app/Livewire/Admin/Setting/SocialLoginPage.php`](../../app/Livewire/Admin/Setting/SocialLoginPage.php) (148 lines)
  - Public state / form fields: `googleEnabled`, `googleClientId`, `googleClientSecret`, `googleHasSecret`, `appleEnabled`, `appleClientId`, `appleClientSecret`, `appleHasSecret`
  - Actions/methods: `save()`
  - Validation: `googleEnabled`: boolean; `googleClientId`: nullable', 'string', 'max:255', 'required_if:googleEnabled,true; `googleClientSecret`: nullable', 'string', 'max:4000; `appleEnabled`: boolean; `appleClientId`: nullable', 'string', 'max:255', 'required_if:appleEnabled,true; `appleClientSecret`: nullable', 'string', 'max:4000
- **`Livewire\Admin\Setting\TemplatePartsPage`** — [`app/Livewire/Admin/Setting/TemplatePartsPage.php`](../../app/Livewire/Admin/Setting/TemplatePartsPage.php) (117 lines)
  - Public state / form fields: `templateId`, `typeFilter`, `pendingDeleteId`
  - Actions/methods: `selectTemplate()`, `setTypeFilter()`, `setDefault()`, `toggleActive()`, `requestDelete()`, `deletePart()`
  - Views: `livewire.admin.setting.template-parts-page`
- **`Livewire\Admin\Setting\AddEditTemplatePart`** — [`app/Livewire/Admin/Setting/AddEditTemplatePart.php`](../../app/Livewire/Admin/Setting/AddEditTemplatePart.php) (133 lines)
  - Public state / form fields: `partId`, `templateId`, `type`, `name`, `content`, `imagePath`, `imageUpload`, `isActive`, `isDefault`
  - Actions/methods: `updatedImageUpload()`, `removeImage()`, `save()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `imageUpload`: nullable', 'image', 'max:2048; `templateId`: required', 'exists:templates,id; `name`: required', 'string', 'max:255; `type`: required', 'in:' . implode(',', TemplatePart::TYPES); `content`: nullable', 'string; `isActive`: boolean; `isDefault`: boolean
  - Views: `livewire.admin.setting.add-edit-template-part`
- **`Livewire\Admin\Setting\TemplateControlPage`** — [`app/Livewire/Admin/Setting/TemplateControlPage.php`](../../app/Livewire/Admin/Setting/TemplateControlPage.php) (269 lines)
  - Public state / form fields: `showEditModal`, `editingId`, `editName`, `editSlug`, `editVersion`, `editAuthor`, `removePreview`, `existingPreviewUrl`, `previewImage`, `showCountriesModal`, `countriesTemplateId`, `countriesTemplateName`, `countriesSearch`, `countriesAll`, `selectedCountryIds`
  - Actions/methods: `requestActivate()`, `activate()`, `requestDeactivate()`, `deactivate()`, `requestSetDefault()`, `setDefault()`, `openEdit()`, `closeEdit()`, `removePreviewImage()`, `saveEdit()`, `openCountries()`, `closeCountries()`, `toggleCountry()`, `toggleAllCountries()`, `saveCountries()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `editName`: required', 'string', 'max:255; `editVersion`: nullable', 'string', 'max:50; `editAuthor`: nullable', 'string', 'max:255; `previewImage`: nullable', 'image', 'max:4096
  - Views: `livewire.admin.setting.template-control-page`
- **`Livewire\Admin\Setting\TranslationKeyAccessPage`** — [`app/Livewire/Admin/Setting/TranslationKeyAccessPage.php`](../../app/Livewire/Admin/Setting/TranslationKeyAccessPage.php) (107 lines)
  - Public state / form fields: `search`, `page`, `perPage`, `lockedKeys`
  - Actions/methods: `updatedSearch()`, `toggleLock()`
  - Views: `livewire.admin.setting.translation-key-access-page`
- **`Livewire\Admin\Setting\TranslationsPage`** — [`app/Livewire/Admin/Setting/TranslationsPage.php`](../../app/Livewire/Admin/Setting/TranslationsPage.php) (287 lines)
  - Public state / form fields: `page`, `perPage`, `selectedResource`, `search`, `showOnlyMissing`, `newPhpFile`, `resources`, `locales`, `rows`
  - Actions/methods: `updatedSelectedResource()`, `updatedSearch()`, `updatedShowOnlyMissing()`, `updatedPerPage()`, `addRow()`, `removeRow()`, `saveRow()`, `createPhpFile()`, `save()`
  - Views: `livewire.admin.setting.translations-page`

### `shipping`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/shipping/delivery-charges` | `admin.shipping.delivery-charges` | shipping.delivery.view,shipping.delivery.manage | `Livewire\Admin\Shipping\DeliveryChargesList` |
| GET | `/admin/shipping/delivery-charges/create` | `admin.shipping.delivery-charges.create` | shipping.delivery.manage | `Livewire\Admin\Shipping\AddEditShippingZone` |
| GET | `/admin/shipping/delivery-charges/{shippingZone}/edit` | `admin.shipping.delivery-charges.edit` | shipping.delivery.manage | `Livewire\Admin\Shipping\AddEditShippingZone` |
| GET | `/admin/shipping/delivery-popup` | `admin.shipping.delivery-popup.index` | shipping.settings.view,shipping.settings.manage | `Livewire\Admin\Shipping\DeliveryPopupPage` |
| GET | `/admin/shipping/fixed-costs` | `admin.shipping.fixed-costs` | shipping.delivery.view,shipping.delivery.manage | `Livewire\Admin\Shipping\FixedShippingCostsList` |
| GET | `/admin/shipping/fixed-costs/create` | `admin.shipping.fixed-costs.create` | shipping.delivery.manage | `Livewire\Admin\Shipping\AddEditFixedShippingCost` |
| GET | `/admin/shipping/fixed-costs/{fixedShippingCost}/edit` | `admin.shipping.fixed-costs.edit` | shipping.delivery.manage | `Livewire\Admin\Shipping\AddEditFixedShippingCost` |
| GET | `/admin/shipping/in-delivery` | `admin.shipping.in-delivery` | shipping.delivery.view,shipping.delivery.manage | `Livewire\Admin\Order\InDeliveryOrdersList` |
| GET | `/admin/shipping/in-delivery/{tenantId}/{orderNumber}` | `admin.shipping.in-delivery.show` | shipping.delivery.view,shipping.delivery.manage | `Livewire\Admin\Order\InDeliveryOrderDetailPage` |
| GET | `/admin/shipping/settings` | `admin.shipping.settings` | shipping.settings.view,shipping.settings.manage | `Livewire\Admin\Shipping\ShippingSettingsPage` |
| GET | `/admin/shipping/shipping-days` | `admin.shipping.shipping-days.index` | shipping.settings.view,shipping.settings.manage | `Livewire\Admin\Shipping\ShippingDaysPage` |

- **`Livewire\Admin\Shipping\DeliveryChargesList`** — [`app/Livewire/Admin/Shipping/DeliveryChargesList.php`](../../app/Livewire/Admin/Shipping/DeliveryChargesList.php) (54 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`, `deleteShippingZone()`
  - Views: `livewire.admin.shipping.delivery-charges-list`
- **`Livewire\Admin\Shipping\AddEditShippingZone`** — [`app/Livewire/Admin/Shipping/AddEditShippingZone.php`](../../app/Livewire/Admin/Shipping/AddEditShippingZone.php) (135 lines)
  - Public state / form fields: `shippingZoneId`, `code`, `currencyCode`, `status`, `translations`, `rates`, `activeLocale`
  - Actions/methods: `setActiveLocale()`, `addRate()`, `removeRate()`, `save()`
  - Validation: `code`: required', 'string', 'max:120; `currencyCode`: required', 'string', 'size:3; `status`: required', Rule::enum(ShippingZoneStatus::class); `rates`: array; `rates.*.name`: required', 'string', 'max:255; `rates.*.min_weight`: nullable', 'numeric', 'min:0; `rates.*.max_weight`: nullable', 'numeric', 'min:0; `rates.*.price`: required', 'numeric', 'min:0; `rates.*.is_active`: boolean
  - Views: `livewire.admin.shipping.add-edit-shipping-zone`
- **`Livewire\Admin\Shipping\DeliveryPopupPage`** — [`app/Livewire/Admin/Shipping/DeliveryPopupPage.php`](../../app/Livewire/Admin/Shipping/DeliveryPopupPage.php) (250 lines)
  - Doc: Manage the delivery-time distribution shown in the storefront "Delivery" popup modal on every product page.  Records are grouped by country_id:  - country_id = NULL  -> Global Default (fallback when customer country has no specific records)  - country_id = X     -> Shown only when the customer's det
  - Public state / form fields: `showFormModal`, `recordId`, `countryId`, `dayNumber`, `percentage`, `countrySearch`, `countryPage`
  - Actions/methods: `getCountryResultsProperty()`, `updatedCountrySearch()`, `countryPageNext()`, `countryPagePrev()`, `openCreateModal()`, `openEditModal()`, `closeModal()`, `save()`, `confirmDelete()`, `deleteRecord()`
  - Validation: `countryId`: nullable', 'integer', 'exists:countries,id; `dayNumber`: required', 'integer', 'min:1', 'max:365; `percentage`: required', 'numeric', 'min:0', 'max:100
- **`Livewire\Admin\Shipping\FixedShippingCostsList`** — [`app/Livewire/Admin/Shipping/FixedShippingCostsList.php`](../../app/Livewire/Admin/Shipping/FixedShippingCostsList.php) (46 lines)
  - Public state / form fields: `countryFilter`
  - Actions/methods: `updatedCountryFilter()`, `clearFilters()`, `deleteFixedShippingCost()`
  - Views: `livewire.admin.shipping.fixed-shipping-costs-list`
- **`Livewire\Admin\Shipping\AddEditFixedShippingCost`** — [`app/Livewire/Admin/Shipping/AddEditFixedShippingCost.php`](../../app/Livewire/Admin/Shipping/AddEditFixedShippingCost.php) (77 lines)
  - Public state / form fields: `fixedShippingCostId`, `countryId`, `pricePerGram`, `currencyCode`, `isActive`
  - Actions/methods: `save()`
  - Validation: `countryId`: required', 'integer', 'exists:countries,id', $uniqueCountryRule; `pricePerGram`: required', 'numeric', 'min:0; `currencyCode`: required', 'string', 'size:3; `isActive`: boolean
  - Views: `livewire.admin.shipping.add-edit-fixed-shipping-cost`
- **`Livewire\Admin\Order\InDeliveryOrdersList`** — [`app/Livewire/Admin/Order/InDeliveryOrdersList.php`](../../app/Livewire/Admin/Order/InDeliveryOrdersList.php) (162 lines)
  - Public state / form fields: `search`, `shippingStatusFilter`, `selectedShippingStatus`, `showOrderStatusModal`, `selectedOrderStatus`, `selectedOrderForStatus`
  - Actions/methods: `updatedSearch()`, `updatedShippingStatusFilter()`, `clearFilters()`, `openOrderStatusModal()`, `updateSelectedOrderStatus()`, `closeOrderStatusModal()`
- **`Livewire\Admin\Order\InDeliveryOrderDetailPage`** — [`app/Livewire/Admin/Order/InDeliveryOrderDetailPage.php`](../../app/Livewire/Admin/Order/InDeliveryOrderDetailPage.php) (277 lines)
  - Public state / form fields: `tenantId`, `orderNumber`, `order`, `orderAttachments`, `selectedOrderStatus`, `trackingNumber`, `attachmentFile`, `showReturnModal`, `returnReason`, `returnNotes`
  - Actions/methods: `updateOrderStatus()`, `updateTrackingNumber()`, `loadOrderAttachments()`, `uploadAttachment()`, `deleteAttachment()`, `createReturn()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `trackingNumber`: nullable', 'string', 'max:255; `attachmentFile`: required|file|max:5120|mimes:jpg,jpeg,png,gif,webp,avif,pdf,xlsx,xls,csv,doc,docx,mp4,mov,; `returnReason`: required', 'string', 'min:10', 'max:1000; `returnNotes`: nullable', 'string', 'max:1000
- **`Livewire\Admin\Shipping\ShippingSettingsPage`** — [`app/Livewire/Admin/Shipping/ShippingSettingsPage.php`](../../app/Livewire/Admin/Shipping/ShippingSettingsPage.php) (218 lines)
  - Public state / form fields: `defaultShippingDays`, `showFormModal`, `stopId`, `fromDate`, `toDate`, `reason`, `stopActive`
  - Actions/methods: `saveDefaultDays()`, `openCreateModal()`, `editStop()`, `saveStop()`, `confirmDeleteStop()`, `deleteStop()`, `closeModal()`
  - Validation: `defaultShippingDays`: required', 'integer', 'min:1', 'max:365; `fromDate`: required', 'date; `toDate`: required', 'date', 'after_or_equal:fromDate; `reason`: nullable', 'string', 'max:255; `stopActive`: boolean
- **`Livewire\Admin\Shipping\ShippingDaysPage`** — [`app/Livewire/Admin/Shipping/ShippingDaysPage.php`](../../app/Livewire/Admin/Shipping/ShippingDaysPage.php) (215 lines)
  - Doc: Central admin CRUD for per-country default shipping days.  A rule with country_code = null acts as the global fallback shown on the storefront when no country-specific rule matches the customer's country.
  - Public state / form fields: `showFormModal`, `recordId`, `countryCode`, `minDays`, `maxDays`, `countrySearch`, `countryPage`
  - Actions/methods: `getCountryResultsProperty()`, `updatedCountrySearch()`, `countryPageNext()`, `countryPagePrev()`, `openCreateModal()`, `openEditModal()`, `closeModal()`, `save()`, `confirmDelete()`, `deleteRecord()`
  - Validation: `countryCode`: nullable', 'string', 'max:2; `minDays`: required', 'integer', 'min:1', 'max:365; `maxDays`: required', 'integer', 'min:1', 'max:365', 'gte:minDays

### `store`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/store/coupons` | `admin.store.coupons.index` | store.coupons.manage | `Livewire\Admin\Store\CouponsPage` |
| GET | `/admin/store/coupons/list/{countryId?}` | `admin.store.coupons.list` | — | `Closure` |
| GET | `/admin/store/flash-sales` | `admin.store.flash-sales.index` | store.flash-sales.manage | `Livewire\Admin\Store\FlashSalesPage` |
| GET | `/admin/store/flash-sales/list/{countryId?}` | `admin.store.flash-sales.list` | — | `Closure` |
| GET | `/admin/store/tenant-sync` | `admin.store.tenant-sync.index` | store.sync.manage | `Livewire\Admin\Store\TenantSyncPage` |

- **`Livewire\Admin\Store\CouponsPage`** — [`app/Livewire/Admin/Store/CouponsPage.php`](../../app/Livewire/Admin/Store/CouponsPage.php) (235 lines)
  - Public state / form fields: `showFormModal`, `couponId`, `code`, `name`, `type`, `value`, `minimumSpend`, `startDate`, `endDate`, `active`, `countryIds`, `countryFilter`, `countryScopeVersion`
  - Actions/methods: `openCreateModal()`, `editCoupon()`, `save()`, `confirmDelete()`, `deleteCoupon()`, `closeModal()`, `clearFilters()`, `updatedCountryFilter()`
  - Validation: `code`: required|string|max:60; `type`: required|in:fixed,percentage; `value`: required|numeric|min:0; `minimumSpend`: nullable|numeric|min:0; `startDate`: nullable|date; `endDate`: nullable|date|after_or_equal:startDate; `title`: Delete Coupon?; `text`: This will also remove it from all tenant storefronts.
- **`Livewire\Admin\Store\FlashSalesPage`** — [`app/Livewire/Admin/Store/FlashSalesPage.php`](../../app/Livewire/Admin/Store/FlashSalesPage.php) (283 lines)
  - Public state / form fields: `showFormModal`, `flashSaleId`, `productIds`, `discountPercentage`, `startDate`, `endDate`, `active`, `productSearch`, `productSearchPage`, `productSearchResults`, `hasMoreProducts`, `selectedProductLabels`, `countryIds`, `countryFilter`, `countryScopeVersion`
  - Actions/methods: `openCreateModal()`, `editFlashSale()`, `save()`, `confirmDelete()`, `deleteFlashSale()`, `closeModal()`, `clearFilters()`, `updatedProductSearch()`, `loadMoreProducts()`, `toggleProduct()`, `removeProduct()`, `updatedCountryFilter()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `productIds`: required', 'array', 'min:1; `productIds.*`: integer', 'exists:products,id; `discountPercentage`: required', 'numeric', 'min:0', 'max:100; `startDate`: required', 'date; `endDate`: required', 'date', 'after_or_equal:startDate; `active`: boolean; `title`: Delete flash sale?; `confirmButtonText`: Delete flash sale
- **`Livewire\Admin\Store\TenantSyncPage`** — [`app/Livewire/Admin/Store/TenantSyncPage.php`](../../app/Livewire/Admin/Store/TenantSyncPage.php) (112 lines)
  - Public state / form fields: `selectedSections`, `selectedTenants`, `allTenants`, `syncing`, `successMessage`, `tenantSearch`
  - Actions/methods: `sync()`
  - Validation: `selectedSections`: required', 'array', 'min:1; `selectedSections.*`: string', 'in:' . implode(',', array_keys($this->availableSections)); `allTenants`: boolean; `selectedTenants`: array

### `support`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/support` | `admin.support.index` | support.tickets.view,support.tickets.manage | `Livewire\Admin\Support\TicketsList` |
| GET | `/admin/support/{ticketId}` | `admin.support.show` | support.tickets.view,support.tickets.manage | `Livewire\Admin\Support\TicketDetail` |

- **`Livewire\Admin\Support\TicketsList`** — [`app/Livewire/Admin/Support/TicketsList.php`](../../app/Livewire/Admin/Support/TicketsList.php) (118 lines)
  - Public state / form fields: `search`, `statusFilter`, `priorityFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `updatedPriorityFilter()`, `clearFilters()`
- **`Livewire\Admin\Support\TicketDetail`** — [`app/Livewire/Admin/Support/TicketDetail.php`](../../app/Livewire/Admin/Support/TicketDetail.php) (175 lines)
  - Public state / form fields: `ticketId`, `ticket`, `reply`, `statusSelection`, `prioritySelection`
  - Actions/methods: `sendReply()`, `updateStatus()`, `refreshTicket()`
  - Validation: `reply`: required', 'string', 'min:2', 'max:5000; `statusSelection`: required', 'string', 'in:' . implode(',', array_keys(SupportTicket::statusOptions())); `prioritySelection`: required', 'string', 'in:' . implode(',', array_keys(SupportTicket::priorityOptions()))

### `variations`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/variations` | `admin.variations.index` | catalog.variations.view,catalog.variations.manage | `Livewire\Admin\Variation\VariationsList` |
| GET | `/admin/variations/create` | `admin.variations.create` | catalog.variations.manage | `Livewire\Admin\Variation\AddEditVariation` |
| GET | `/admin/variations/{variation}/edit` | `admin.variations.edit` | catalog.variations.manage | `Livewire\Admin\Variation\AddEditVariation` |

- **`Livewire\Admin\Variation\VariationsList`** — [`app/Livewire/Admin/Variation/VariationsList.php`](../../app/Livewire/Admin/Variation/VariationsList.php) (54 lines)
  - Public state / form fields: `search`, `statusFilter`
  - Actions/methods: `updatedSearch()`, `updatedStatusFilter()`, `clearFilters()`, `deleteVariation()`
  - Views: `livewire.admin.variation.variations-list`
- **`Livewire\Admin\Variation\AddEditVariation`** — [`app/Livewire/Admin/Variation/AddEditVariation.php`](../../app/Livewire/Admin/Variation/AddEditVariation.php) (128 lines)
  - Public state / form fields: `variationId`, `status`, `translations`, `options`, `activeLocale`
  - Actions/methods: `setActiveLocale()`, `addOption()`, `removeOption()`, `save()`
  - Validation: `status`: required', Rule::enum(VariationStatus::class); `options`: array', 'min:1; `options.*.swatch`: nullable', 'string', 'max:20
  - Views: `livewire.admin.variation.add-edit-variation`

### `vendor-settlements`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/vendor-settlements` | `admin.vendor-settlements.index` | billing.vendor-settlements.view | `Livewire\Admin\Finance\VendorSettlementsPage` |

- **`Livewire\Admin\Finance\VendorSettlementsPage`** — [`app/Livewire/Admin/Finance/VendorSettlementsPage.php`](../../app/Livewire/Admin/Finance/VendorSettlementsPage.php) (152 lines)
  - Public state / form fields: `search`, `gatewayFilter`, `tenantFilter`, `statusFilter`
  - Actions/methods: `updatedSearch()`, `updatedGatewayFilter()`, `updatedTenantFilter()`, `updatedStatusFilter()`

### `wallet-transactions`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/wallet-transactions` | `admin.wallet-transactions.index` | billing.transactions.view | `Livewire\Admin\Wallet\TransactionsList` |

- **`Livewire\Admin\Wallet\TransactionsList`** — [`app/Livewire/Admin/Wallet/TransactionsList.php`](../../app/Livewire/Admin/Wallet/TransactionsList.php) (326 lines)
  - Public state / form fields: `search`, `directionFilter`, `sourceFilter`, `showTransactionModal`, `selectedTransaction`
  - Actions/methods: `updatedSearch()`, `updatedDirectionFilter()`, `updatedSourceFilter()`, `clearFilters()`, `viewTransaction()`, `closeTransactionModal()`

### `wallets`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/wallets` | `admin.wallets.index` | billing.wallets.view | `Livewire\Admin\Wallet\WalletsList` |
| GET | `/admin/wallets/{tenantId}` | `admin.wallets.show` | billing.wallets.view | `Livewire\Admin\Wallet\WalletDetailPage` |

- **`Livewire\Admin\Wallet\WalletsList`** — [`app/Livewire/Admin/Wallet/WalletsList.php`](../../app/Livewire/Admin/Wallet/WalletsList.php) (189 lines)
  - Doc: /admin/wallets — Tenants Ledger.  Reads its values from each tenant's orders (subtotal, discount, shipping, tax, platform profit, vendor profit, vendor gateway fee) and combines them with central vendor_settlements (tenant -> central) and tenant_payouts (central -> tenant) to compute the final outst
  - Public state / form fields: `search`, `directionFilter`
  - Actions/methods: `clearFilters()`, `updatedSearch()`, `updatedDirectionFilter()`
- **`Livewire\Admin\Wallet\WalletDetailPage`** — [`app/Livewire/Admin/Wallet/WalletDetailPage.php`](../../app/Livewire/Admin/Wallet/WalletDetailPage.php) (182 lines)
  - Public state / form fields: `tenantId`, `row`, `showMarkPaidModal`, `markOrderId`, `markOrderUuid`, `markAmount`, `markMethod`, `markReference`, `markNote`, `markError`, `markSuccess`
  - Actions/methods: `openMarkPaid()`, `updatedMarkMethod()`, `confirmMarkPaid()`, `closeMarkPaid()`

## Tenant (vendor) admin panel (424 routes)


### `(root)`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin` | `` | — | `Tenant\Panel\Auth\LoginController@redirectToStart` |

- **`Http\Controllers\Tenant\Panel\Auth\LoginController`** — [`app/Http/Controllers/Tenant/Panel/Auth/LoginController.php`](../../app/Http/Controllers/Tenant/Panel/Auth/LoginController.php) (87 lines)
  - Actions/methods: `redirectToStart()`, `show()`, `validateLogin()`, `login()`, `logout()`
  - Views: `tenant.pages.auth.login`
  - FormRequest `LoginRequest`: `email`: required', 'email; `password`: required', 'string; `remember`: boolean

### `_ui`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/_ui` | `tenant.ui-kit` | — | `Tenant\Panel\UiKitController@__invoke` |
| POST | `/admin/_ui` | `tenant.ui-kit.store` | — | `Tenant\Panel\UiKitController@store` |
| GET | `/admin/_ui/data` | `tenant.ui-kit.data` | — | `Tenant\Panel\UiKitController@data` |
| POST | `/admin/_ui/validate` | `tenant.ui-kit.validate` | — | `Tenant\Panel\UiKitController@validateForm` |

- **`Http\Controllers\Tenant\Panel\UiKitController`** — [`app/Http/Controllers/Tenant/Panel/UiKitController.php`](../../app/Http/Controllers/Tenant/Panel/UiKitController.php) (65 lines)
  - Actions/methods: `data()`, `validateForm()`, `store()`
  - Views: `tenant.pages.ui-kit`
  - FormRequest `UiKitRequest`: `text`: required', 'string', 'max:120; `email`: required', 'email; `textarea`: nullable', 'string; `select`: required', 'in:one,two,three

### `ai-translation-purchase`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/ai-translation-purchase/{gateway}/cancel` | `tenant.ai-translation-purchase.cancel` | — | `Tenant\AiTranslationPaymentController@cancel` |
| GET|POST | `/admin/ai-translation-purchase/{gateway}/success` | `tenant.ai-translation-purchase.success` | — | `Tenant\AiTranslationPaymentController@success` |
| GET | `/admin/ai-translation-purchase/{gateway}/{languageId}` | `tenant.ai-translation-purchase.charge` | — | `Tenant\AiTranslationPaymentController@charge` |

- **`Http\Controllers\Tenant\AiTranslationPaymentController`** — [`app/Http/Controllers/Tenant/AiTranslationPaymentController.php`](../../app/Http/Controllers/Tenant/AiTranslationPaymentController.php) (155 lines)
  - Doc: Handles the payment lifecycle for paid per-language AI translation runs, mirroring App\Http\Controllers\Tenant\LanguagePaymentController.
  - Actions/methods: `charge()`, `success()`, `cancel()`

### `analytics`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/analytics/customer-lifetime-value/data` | `tenant.analytics.clv.data` | analytics.view | `Tenant\Panel\Insights\CustomerLifetimeValueController@data` |
| GET | `/admin/analytics/orders/data/monthly` | `tenant.analytics.orders.data.monthly` | analytics.view | `Tenant\Panel\Insights\OrderAnalyticsController@dataMonthly` |
| GET | `/admin/analytics/orders/data/status` | `tenant.analytics.orders.data.status` | analytics.view | `Tenant\Panel\Insights\OrderAnalyticsController@dataStatus` |
| GET | `/admin/analytics/profitability/data` | `tenant.analytics.profitability.data` | analytics.view | `Tenant\Panel\Insights\ProductProfitabilityController@data` |
| GET | `/admin/analytics/shipping/data/monthly` | `tenant.analytics.shipping.data.monthly` | analytics.view | `Tenant\Panel\Insights\ShippingAnalyticsController@dataMonthly` |
| GET | `/admin/analytics/{tab?}` | `tenant.analytics` | analytics.view | `Tenant\Panel\Insights\AnalyticsController` |

- **`Http\Controllers\Tenant\Panel\Insights\CustomerLifetimeValueController`** — [`app/Http/Controllers/Tenant/Panel/Insights/CustomerLifetimeValueController.php`](../../app/Http/Controllers/Tenant/Panel/Insights/CustomerLifetimeValueController.php) (105 lines)
  - Actions/methods: `viewData()`, `data()`
  - Views: `tenant.pages.insights._cols.customer`
- **`Http\Controllers\Tenant\Panel\Insights\OrderAnalyticsController`** — [`app/Http/Controllers/Tenant/Panel/Insights/OrderAnalyticsController.php`](../../app/Http/Controllers/Tenant/Panel/Insights/OrderAnalyticsController.php) (115 lines)
  - Actions/methods: `viewData()`, `dataMonthly()`, `dataStatus()`
- **`Http\Controllers\Tenant\Panel\Insights\ProductProfitabilityController`** — [`app/Http/Controllers/Tenant/Panel/Insights/ProductProfitabilityController.php`](../../app/Http/Controllers/Tenant/Panel/Insights/ProductProfitabilityController.php) (112 lines)
  - Actions/methods: `viewData()`, `data()`
  - Views: `tenant::components.status-badge`
- **`Http\Controllers\Tenant\Panel\Insights\ShippingAnalyticsController`** — [`app/Http/Controllers/Tenant/Panel/Insights/ShippingAnalyticsController.php`](../../app/Http/Controllers/Tenant/Panel/Insights/ShippingAnalyticsController.php) (88 lines)
  - Actions/methods: `viewData()`, `dataMonthly()`
- **`Http\Controllers\Tenant\Panel\Insights\AnalyticsController`** — [`app/Http/Controllers/Tenant/Panel/Insights/AnalyticsController.php`](../../app/Http/Controllers/Tenant/Panel/Insights/AnalyticsController.php) (39 lines)
  - Doc: Single Analytics page. Each former analytics page is now a tab rendered inside this page; only the active tab's module is queried.
  - Views: `tenant.pages.insights.index`

### `badges`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/badges` | `tenant.badges.index` | catalog.badges.manage | `Tenant\Panel\Catalog\BadgeController@index` |
| GET | `/admin/badges/{badge}` | `tenant.badges.show` | catalog.badges.manage | `Tenant\Panel\Catalog\BadgeController@show` |
| POST | `/admin/badges/{badge}/assign-category` | `tenant.badges.assign-category` | catalog.badges.manage | `Tenant\Panel\Catalog\BadgeController@assignCategory` |
| POST | `/admin/badges/{badge}/save` | `tenant.badges.save` | catalog.badges.manage | `Tenant\Panel\Catalog\BadgeController@save` |
| GET | `/admin/badges/{badge}/search` | `tenant.badges.search` | catalog.badges.manage | `Tenant\Panel\Catalog\BadgeController@searchProducts` |
| GET | `/admin/badges/{badge}/sort` | `tenant.badges.sort` | catalog.badges.manage | `Tenant\Panel\Catalog\BadgeSortController@index` |
| POST | `/admin/badges/{badge}/sort` | `tenant.badges.sort` | catalog.badges.manage | `Tenant\Panel\Catalog\BadgeSortController@update` |

- **`Http\Controllers\Tenant\Panel\Catalog\BadgeController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/BadgeController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/BadgeController.php) (194 lines)
  - Doc: Tenant panel badge assignment (route key = badge text). Dedup plan phase 2: while the tenant's `catalog_source.badges` is `central`, `{badge}` resolves through the merged BadgeCatalog (central badges are read-only, assignments are tenant data written by BadgeAssignments); otherwise the synced `produ
  - Actions/methods: `index()`, `show()`, `searchProducts()`, `assignCategory()`, `save()`
  - Views: `tenant.pages.catalog.badges.show`
- **`Http\Controllers\Tenant\Panel\Catalog\BadgeSortController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/BadgeSortController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/BadgeSortController.php) (95 lines)
  - Doc: Per-country product order of a badge. Dedup plan phase 2: in `badges = central` mode the merged list is read from BadgeCatalog and the order is written by BadgeAssignments (central pairs → tenant sort override rows, tenant attaches → their pivot row); sync mode unchanged.
  - Actions/methods: `index()`, `update()`
  - Views: `tenant.pages.catalog.sort.badge-products`

### `brand-request-payment`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/brand-request-payment/{gateway}/cancel` | `tenant.brand-request-payment.cancel` | — | `Tenant\BrandRequestPaymentController@cancel` |
| GET|POST | `/admin/brand-request-payment/{gateway}/success` | `tenant.brand-request-payment.success` | — | `Tenant\BrandRequestPaymentController@success` |
| GET | `/admin/brand-request-payment/{gateway}/{paymentRequestId}` | `tenant.brand-request-payment.charge` | — | `Tenant\BrandRequestPaymentController@charge` |

- **`Http\Controllers\Tenant\BrandRequestPaymentController`** — [`app/Http/Controllers/Tenant/BrandRequestPaymentController.php`](../../app/Http/Controllers/Tenant/BrandRequestPaymentController.php) (186 lines)
  - Doc: BrandRequestPaymentController  Handles the payment lifecycle for brand request payment requests:   1. charge()  — initiate a gateway payment for a pending payment request   2. success() — verify callback, mark as paid, redirect to detail page   3. cancel()  — forget pending context, redirect back
  - Actions/methods: `charge()`, `success()`, `cancel()`

### `brand-requests`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/brand-requests` | `tenant.brand-requests.index` | — | `Tenant\Panel\Requests\BrandRequestController@index` |
| POST | `/admin/brand-requests` | `tenant.brand-requests.store` | — | `Tenant\Panel\Requests\BrandRequestController@store` |
| GET | `/admin/brand-requests/create` | `tenant.brand-requests.create` | — | `Tenant\Panel\Requests\BrandRequestController@create` |
| GET | `/admin/brand-requests/data` | `tenant.brand-requests.data` | — | `Tenant\Panel\Requests\BrandRequestController@data` |
| POST | `/admin/brand-requests/validate` | `tenant.brand-requests.validate` | — | `Tenant\Panel\Requests\BrandRequestController@validateStore` |
| GET | `/admin/brand-requests/{id}` | `tenant.brand-requests.show` | — | `Tenant\Panel\Requests\BrandRequestController@show` |
| DELETE | `/admin/brand-requests/{id}` | `tenant.brand-requests.destroy` | — | `Tenant\Panel\Requests\BrandRequestController@destroy` |
| POST | `/admin/brand-requests/{id}/messages` | `tenant.brand-requests.messages` | — | `Tenant\Panel\Requests\BrandRequestController@sendMessage` |
| POST | `/admin/brand-requests/{id}/messages/validate` | `tenant.brand-requests.messages.validate` | — | `Tenant\Panel\Requests\BrandRequestController@validateMessage` |
| POST | `/admin/brand-requests/{id}/pay` | `tenant.brand-requests.pay` | — | `Tenant\Panel\Requests\BrandRequestController@pay` |
| POST | `/admin/brand-requests/{id}/pay/validate` | `tenant.brand-requests.pay.validate` | — | `Tenant\Panel\Requests\BrandRequestController@validatePay` |

- **`Http\Controllers\Tenant\Panel\Requests\BrandRequestController`** — [`app/Http/Controllers/Tenant/Panel/Requests/BrandRequestController.php`](../../app/Http/Controllers/Tenant/Panel/Requests/BrandRequestController.php) (222 lines)
  - Actions/methods: `index()`, `data()`, `create()`, `store()`, `validateStore()`, `show()`, `destroy()`, `sendMessage()`, `validateMessage()`, `pay()`, `validatePay()`
  - Views: `tenant.pages.requests.brand.index`, `tenant.pages.requests.brand._cols.request`, `tenant::components.status-badge`, `tenant.pages.requests.brand._cols.actions`, `tenant.pages.requests.brand.create`, `tenant.pages.requests.brand.show`
  - FormRequest `PayBrandRequestRequest`: `payment_request_id`: required', 'integer
  - FormRequest `SendBrandRequestMessageRequest`: `message`: required', 'string', 'max:2000
  - FormRequest `StoreBrandRequestRequest`: `title`: required', 'string', 'max:255; `description`: required', 'string', 'min:20', 'max:8000; `files`: array', 'max:5; `files.*`: file', 'max:10240', 'mimes:jpg,jpeg,png,webp,avif,gif,pdf,doc,docx,xls,xlsx,zip

### `categories`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/categories` | `tenant.categories.index` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@index` |
| POST | `/admin/categories` | `tenant.categories.store` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@store` |
| GET | `/admin/categories/create` | `tenant.categories.create` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@create` |
| GET | `/admin/categories/data` | `tenant.categories.data` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@data` |
| GET | `/admin/categories/sort` | `tenant.categories.sort` | catalog.categories.manage | `Tenant\Panel\Catalog\CategorySortController@index` |
| POST | `/admin/categories/sort` | `tenant.categories.sort.save` | catalog.categories.manage | `Tenant\Panel\Catalog\CategorySortController@update` |
| POST | `/admin/categories/validate` | `tenant.categories.validate` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@validateStore` |
| PUT | `/admin/categories/{category}` | `tenant.categories.update` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@update` |
| DELETE | `/admin/categories/{category}` | `tenant.categories.destroy` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@destroy` |
| PATCH | `/admin/categories/{category}/active` | `tenant.categories.toggle-active` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@toggleActive` |
| GET | `/admin/categories/{category}/edit` | `tenant.categories.edit` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@edit` |
| PATCH | `/admin/categories/{category}/featured` | `tenant.categories.toggle-featured` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@toggleFeatured` |
| GET | `/admin/categories/{category}/products` | `tenant.categories.products` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryProductsController@index` |
| POST | `/admin/categories/{category}/products/sort` | `tenant.categories.products.sort` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryProductsController@update` |
| POST | `/admin/categories/{category}/validate` | `tenant.categories.validate.update` | catalog.categories.manage | `Tenant\Panel\Catalog\CategoryController@validateUpdate` |

- **`Http\Controllers\Tenant\Panel\Catalog\CategoryController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/CategoryController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/CategoryController.php) (230 lines)
  - Actions/methods: `index()`, `data()`, `create()`, `edit()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`, `toggleActive()`, `toggleFeatured()`, `destroy()`
  - Views: `tenant.pages.catalog.categories.index`, `tenant.pages.catalog.categories._cols.category`, `tenant.pages.catalog.categories._cols.status`, `tenant.pages.catalog.categories._cols.actions`, `tenant.pages.catalog.categories.form`
  - FormRequest `SaveCategoryRequest`: `central_category_id`: nullable', 'integer; `order_number`: nullable', 'integer', 'min:0; `active`: boolean; `featured`: boolean; `thumb`: nullable', 'image', 'max:4096; `remove_thumb`: boolean; `active_locale`: required', 'string
- **`Http\Controllers\Tenant\Panel\Catalog\CategorySortController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/CategorySortController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/CategorySortController.php) (46 lines)
  - Actions/methods: `index()`, `update()`
  - Views: `tenant.pages.catalog.sort.categories`
- **`Http\Controllers\Tenant\Panel\Catalog\CategoryProductsController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/CategoryProductsController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/CategoryProductsController.php) (43 lines)
  - Actions/methods: `index()`, `update()`
  - Views: `tenant.pages.catalog.sort.category-products`

### `cities-by-country`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/cities-by-country/{countryId?}` | `tenant.cities.by-country` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@citiesByCountry` |

- **`Http\Controllers\Tenant\Panel\Sales\CustomerDetailController`** — [`app/Http/Controllers/Tenant/Panel/Sales/CustomerDetailController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/CustomerDetailController.php) (231 lines)
  - Actions/methods: `show()`, `updateProfile()`, `validateProfile()`, `toggleActive()`, `paymentsData()`, `showAddress()`, `storeAddress()`, `updateAddress()`, `validateAddress()`, `destroyAddress()`, `citiesByCountry()`
  - Views: `tenant.pages.sales.customers.show`, `tenant::components.status-badge`
  - FormRequest `SaveCustomerAddressRequest`: `label`: nullable', 'string', 'max:191; `full_name`: required', 'string', 'max:191; `email`: nullable', 'email', 'max:191; `phone`: nullable', 'string', 'max:50; `city`: nullable', 'string', 'max:191; `state`: nullable', 'string', 'max:191; `country`: nullable', 'string', 'max:191; `is_default`: boolean
  - FormRequest `UpdateCustomerProfileRequest`: `full_name`: required', 'string', 'max:191; `email`: required', 'email', 'max:191', 'unique:customers,email,' . $customerId; `phone`: nullable', 'string', 'max:50; `address`: nullable', 'string', 'max:500; `country_id`: nullable', 'integer; `city_id`: nullable', 'integer; `password`: nullable', 'string', 'min:8', 'same:password_confirmation; `password_confirmation`: nullable', 'string; `active`: boolean

### `compliance`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/admin/compliance/accept` | `tenant.compliance.accept` | — | `Tenant\Panel\Shell\ComplianceController@accept` |
| POST | `/admin/compliance/accept/validate` | `tenant.compliance.accept.validate` | — | `Tenant\Panel\Shell\ComplianceController@validateAccept` |

- **`Http\Controllers\Tenant\Panel\Shell\ComplianceController`** — [`app/Http/Controllers/Tenant/Panel/Shell/ComplianceController.php`](../../app/Http/Controllers/Tenant/Panel/Shell/ComplianceController.php) (38 lines)
  - Actions/methods: `accept()`, `validateAccept()`
  - FormRequest `AcceptComplianceRequest`: `accept`: accepted

### `customers`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/customers` | `tenant.customers.index` | sales.customers.manage | `Tenant\Panel\Sales\CustomersController@index` |
| POST | `/admin/customers` | `tenant.customers.store` | sales.customers.manage | `Tenant\Panel\Sales\CustomerCreateController@store` |
| GET | `/admin/customers/create` | `tenant.customers.create` | sales.customers.manage | `Tenant\Panel\Sales\CustomerCreateController@create` |
| GET | `/admin/customers/data` | `tenant.customers.data` | sales.customers.manage | `Tenant\Panel\Sales\CustomersController@data` |
| GET | `/admin/customers/export` | `tenant.customers.export` | sales.customers.manage | `Tenant\Panel\Sales\CustomersController@export` |
| POST | `/admin/customers/validate` | `tenant.customers.validate` | sales.customers.manage | `Tenant\Panel\Sales\CustomerCreateController@validateStore` |
| GET | `/admin/customers/{customerId}` | `tenant.customers.show` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@show` |
| PUT | `/admin/customers/{customerId}` | `tenant.customers.update` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@updateProfile` |
| DELETE | `/admin/customers/{customerId}` | `tenant.customers.destroy` | sales.customers.manage | `Tenant\Panel\Sales\CustomersController@destroy` |
| PATCH | `/admin/customers/{customerId}/active` | `tenant.customers.toggle-active` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@toggleActive` |
| POST | `/admin/customers/{customerId}/addresses` | `tenant.customers.addresses.store` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@storeAddress` |
| POST | `/admin/customers/{customerId}/addresses/validate` | `tenant.customers.addresses.validate` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@validateAddress` |
| GET | `/admin/customers/{customerId}/addresses/{addressId}` | `tenant.customers.addresses.show` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@showAddress` |
| PUT | `/admin/customers/{customerId}/addresses/{addressId}` | `tenant.customers.addresses.update` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@updateAddress` |
| DELETE | `/admin/customers/{customerId}/addresses/{addressId}` | `tenant.customers.addresses.destroy` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@destroyAddress` |
| GET | `/admin/customers/{customerId}/payments/data` | `tenant.customers.payments.data` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@paymentsData` |
| POST | `/admin/customers/{customerId}/validate` | `tenant.customers.validate.update` | sales.customers.manage | `Tenant\Panel\Sales\CustomerDetailController@validateProfile` |

- **`Http\Controllers\Tenant\Panel\Sales\CustomersController`** — [`app/Http/Controllers/Tenant/Panel/Sales/CustomersController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/CustomersController.php) (97 lines)
  - Actions/methods: `index()`, `data()`, `export()`, `destroy()`
  - Views: `tenant.pages.sales.customers.index`, `tenant.pages.sales.customers._cols.customer`, `tenant.pages.sales.customers._cols.contact`, `tenant.pages.sales.customers._cols.orders`, `tenant.pages.sales.customers._cols.lifetime-value`, `tenant::components.status-badge`, `tenant.pages.sales.customers._cols.actions`
- **`Http\Controllers\Tenant\Panel\Sales\CustomerCreateController`** — [`app/Http/Controllers/Tenant/Panel/Sales/CustomerCreateController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/CustomerCreateController.php) (61 lines)
  - Actions/methods: `create()`, `store()`, `validateStore()`
  - Views: `tenant.pages.sales.customers.create`
  - FormRequest `StoreCustomerRequest`: `full_name`: required', 'string', 'max:191; `email`: required', 'email', 'max:191', 'unique:customers,email; `phone`: nullable', 'string', 'max:50; `address`: nullable', 'string', 'max:500; `country_id`: nullable', 'integer; `city_id`: nullable', 'integer; `password`: required', 'string', 'min:6', 'same:password_confirmation; `password_confirmation`: nullable', 'string; `active`: boolean
- **`Http\Controllers\Tenant\Panel\Sales\CustomerDetailController`** — [`app/Http/Controllers/Tenant/Panel/Sales/CustomerDetailController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/CustomerDetailController.php) (231 lines)
  - Actions/methods: `show()`, `updateProfile()`, `validateProfile()`, `toggleActive()`, `paymentsData()`, `showAddress()`, `storeAddress()`, `updateAddress()`, `validateAddress()`, `destroyAddress()`, `citiesByCountry()`
  - Views: `tenant.pages.sales.customers.show`, `tenant::components.status-badge`
  - FormRequest `SaveCustomerAddressRequest`: `label`: nullable', 'string', 'max:191; `full_name`: required', 'string', 'max:191; `email`: nullable', 'email', 'max:191; `phone`: nullable', 'string', 'max:50; `city`: nullable', 'string', 'max:191; `state`: nullable', 'string', 'max:191; `country`: nullable', 'string', 'max:191; `is_default`: boolean
  - FormRequest `UpdateCustomerProfileRequest`: `full_name`: required', 'string', 'max:191; `email`: required', 'email', 'max:191', 'unique:customers,email,' . $customerId; `phone`: nullable', 'string', 'max:50; `address`: nullable', 'string', 'max:500; `country_id`: nullable', 'integer; `city_id`: nullable', 'integer; `password`: nullable', 'string', 'min:8', 'same:password_confirmation; `password_confirmation`: nullable', 'string; `active`: boolean

### `dashboard`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/dashboard` | `tenant.dashboard` | dashboard.view | `Tenant\Panel\Insights\DashboardController@index` |

- **`Http\Controllers\Tenant\Panel\Insights\DashboardController`** — [`app/Http/Controllers/Tenant/Panel/Insights/DashboardController.php`](../../app/Http/Controllers/Tenant/Panel/Insights/DashboardController.php) (148 lines)
  - Actions/methods: `index()`
  - Views: `tenant.pages.dashboard.index`

### `email`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/admin/email/verification-notification` | `tenant.verification.send` | — | `Tenant\EmailVerificationController@send` |
| GET | `/admin/email/verify/{id}/{hash}` | `tenant.verification.verify` | — | `Tenant\EmailVerificationController@verify` |

- **`Http\Controllers\Tenant\EmailVerificationController`** — [`app/Http/Controllers/Tenant/EmailVerificationController.php`](../../app/Http/Controllers/Tenant/EmailVerificationController.php) (81 lines)
  - Actions/methods: `send()`, `verify()`

### `finance`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/finance/billing` | `tenant.finance.billing` | finance.billing.view | `Tenant\Panel\Finance\BillingController@index` |
| GET | `/admin/finance/billing/data` | `tenant.finance.billing.data` | finance.billing.view | `Tenant\Panel\Finance\BillingController@data` |
| GET | `/admin/finance/billing/{orderId}` | `tenant.finance.billing.detail` | finance.billing.view | `Tenant\Panel\Finance\BillingController@show` |
| GET | `/admin/finance/buy-languages` | `tenant.finance.buy-languages` | settings.languages.purchase | `Tenant\Panel\Finance\BuyLanguageController@index` |
| POST | `/admin/finance/buy-languages` | `tenant.finance.buy-languages.purchase` | settings.languages.purchase | `Tenant\Panel\Finance\BuyLanguageController@purchase` |
| GET | `/admin/finance/buy-languages/data` | `tenant.finance.buy-languages.data` | settings.languages.purchase | `Tenant\Panel\Finance\BuyLanguageController@data` |
| POST | `/admin/finance/buy-languages/validate` | `tenant.finance.buy-languages.purchase.validate` | settings.languages.purchase | `Tenant\Panel\Finance\BuyLanguageController@validatePurchase` |
| GET | `/admin/finance/payouts-received` | `tenant.finance.payouts` | finance.wallet.view | `Tenant\Panel\Finance\PayoutsController@index` |
| GET | `/admin/finance/payouts-received/data` | `tenant.finance.payouts.data` | finance.wallet.view | `Tenant\Panel\Finance\PayoutsController@data` |
| GET | `/admin/finance/settlement-payments` | `tenant.finance.settlement-payments` | finance.vendor-purchases.view | `Tenant\Panel\Finance\SettlementPaymentsController@index` |
| GET | `/admin/finance/settlement-payments/data` | `tenant.finance.settlement-payments.data` | finance.vendor-purchases.view | `Tenant\Panel\Finance\SettlementPaymentsController@data` |
| GET | `/admin/finance/vendor-purchases` | `tenant.finance.vendor-purchases` | finance.vendor-purchases.view | `Tenant\Panel\Finance\VendorPurchaseController@index` |
| GET | `/admin/finance/vendor-purchases/data` | `tenant.finance.vendor-purchases.data` | finance.vendor-purchases.view | `Tenant\Panel\Finance\VendorPurchaseController@data` |
| GET | `/admin/finance/vendor-purchases/export` | `tenant.finance.vendor-purchases.export` | finance.vendor-purchases.view | `Tenant\Panel\Finance\VendorPurchaseController@export` |
| GET | `/admin/finance/vendor-purchases/{orderId}/settle` | `tenant.finance.vendor-purchase-settle` | finance.vendor-purchases.view | `Tenant\Panel\Finance\VendorSettleOrderController@show` |
| POST | `/admin/finance/vendor-purchases/{orderId}/settle` | `tenant.finance.vendor-purchase-settle.pay` | finance.vendor-purchases.view | `Tenant\Panel\Finance\VendorSettleOrderController@settle` |
| GET | `/admin/finance/vendor-purchases/{orderId}/settle/breakdown` | `tenant.finance.vendor-purchase-settle.breakdown` | finance.vendor-purchases.view | `Tenant\Panel\Finance\VendorSettleOrderController@breakdown` |
| POST | `/admin/finance/vendor-purchases/{orderId}/settle/validate` | `tenant.finance.vendor-purchase-settle.pay.validate` | finance.vendor-purchases.view | `Tenant\Panel\Finance\VendorSettleOrderController@validateSettle` |
| GET | `/admin/finance/wallet` | `tenant.finance.wallet` | finance.wallet.view | `Tenant\Panel\Finance\WalletController@index` |
| GET | `/admin/finance/wallet/partner/conversions/data` | `tenant.finance.wallet.partner.conversions.data` | finance.wallet.view | `Tenant\Panel\Finance\PartnerWalletController@conversionsData` |
| GET | `/admin/finance/wallet/partner/payouts/data` | `tenant.finance.wallet.partner.payouts.data` | finance.wallet.view | `Tenant\Panel\Finance\PartnerWalletController@payoutsData` |
| POST | `/admin/finance/wallet/partner/sso` | `tenant.finance.wallet.partner.sso` | finance.wallet.view | `Tenant\Panel\Finance\PartnerWalletController@sso` |
| POST | `/admin/finance/wallet/partner/withdraw` | `tenant.finance.wallet.partner.withdraw` | finance.wallet.view | `Tenant\Panel\Finance\PartnerWalletController@withdraw` |
| POST | `/admin/finance/wallet/subscription` | `tenant.finance.wallet.subscribe` | finance.wallet.view | `Tenant\Panel\Finance\WalletController@subscribe` |
| POST | `/admin/finance/wallet/subscription/validate` | `tenant.finance.wallet.subscribe.validate` | finance.wallet.view | `Tenant\Panel\Finance\WalletController@validateSubscribe` |
| GET | `/admin/finance/wallet/subscriptions/data` | `tenant.finance.wallet.subscriptions.data` | finance.wallet.view | `Tenant\Panel\Finance\WalletController@subscriptionsData` |
| GET | `/admin/finance/wallet/transactions/data` | `tenant.finance.wallet.transactions.data` | finance.wallet.view | `Tenant\Panel\Finance\WalletController@transactionsData` |

- **`Http\Controllers\Tenant\Panel\Finance\BillingController`** — [`app/Http/Controllers/Tenant/Panel/Finance/BillingController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/BillingController.php) (117 lines)
  - Actions/methods: `index()`, `data()`, `show()`
  - Views: `tenant.pages.finance.billing.index`, `tenant.pages.finance.billing._cols.order`, `tenant.pages.finance.billing._cols.customer`, `tenant.pages.finance.billing._cols.totals`, `tenant.pages.finance.billing._cols.payment`, `tenant.pages.finance.billing._cols.gateway`, `tenant.pages.finance.billing._cols.payment-details`, `tenant.pages.finance.billing._cols.actions`
- **`Http\Controllers\Tenant\Panel\Finance\BuyLanguageController`** — [`app/Http/Controllers/Tenant/Panel/Finance/BuyLanguageController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/BuyLanguageController.php) (95 lines)
  - Actions/methods: `index()`, `data()`, `purchase()`, `validatePurchase()`
  - Views: `tenant.pages.finance.buy-languages.index`, `tenant.pages.finance.buy-languages._cols.language`, `tenant.pages.finance.buy-languages._cols.actions`
  - FormRequest `BuyLanguageRequest`: `language_id`: required', 'integer
- **`Http\Controllers\Tenant\Panel\Finance\PayoutsController`** — [`app/Http/Controllers/Tenant/Panel/Finance/PayoutsController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/PayoutsController.php) (63 lines)
  - Actions/methods: `index()`, `data()`
  - Views: `tenant.pages.finance.payouts.index`, `tenant.pages.finance.payouts._cols.invoice`, `tenant.pages.finance.payouts._cols.status`, `tenant.pages.finance.payouts._cols.paid-at`
- **`Http\Controllers\Tenant\Panel\Finance\SettlementPaymentsController`** — [`app/Http/Controllers/Tenant/Panel/Finance/SettlementPaymentsController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/SettlementPaymentsController.php) (67 lines)
  - Actions/methods: `index()`, `data()`
  - Views: `tenant.pages.finance.settlement-payments.index`, `tenant.pages.finance.settlement-payments._cols.invoice`, `tenant.pages.finance.settlement-payments._cols.order`, `tenant.pages.finance.settlement-payments._cols.transaction`, `tenant.pages.finance.settlement-payments._cols.amount`, `tenant.pages.finance.settlement-payments._cols.settled-at`
- **`Http\Controllers\Tenant\Panel\Finance\VendorPurchaseController`** — [`app/Http/Controllers/Tenant/Panel/Finance/VendorPurchaseController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/VendorPurchaseController.php) (91 lines)
  - Actions/methods: `index()`, `data()`, `export()`
  - Views: `tenant.pages.finance.vendor-purchases.index`, `tenant.pages.finance.vendor-purchases._cols.order`, `tenant.pages.finance.vendor-purchases._cols.customer`, `tenant.pages.finance.vendor-purchases._cols.paid-via`, `tenant.pages.finance.vendor-purchases._cols.gateway-fee`, `tenant.pages.finance.vendor-purchases._cols.settlement`, `tenant.pages.finance.vendor-purchases._cols.actions`
- **`Http\Controllers\Tenant\Panel\Finance\VendorSettleOrderController`** — [`app/Http/Controllers/Tenant/Panel/Finance/VendorSettleOrderController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/VendorSettleOrderController.php) (91 lines)
  - Actions/methods: `show()`, `breakdown()`, `settle()`, `validateSettle()`
  - Views: `tenant.pages.finance.vendor-settle.show`, `tenant.pages.finance.vendor-settle._breakdown`
- **`Http\Controllers\Tenant\Panel\Finance\WalletController`** — [`app/Http/Controllers/Tenant/Panel/Finance/WalletController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/WalletController.php) (141 lines)
  - Actions/methods: `index()`, `subscriptionsData()`, `transactionsData()`, `subscribe()`, `validateSubscribe()`
  - Views: `tenant.pages.finance.wallet.index`, `tenant.pages.finance.wallet._cols.subscription`, `tenant.pages.finance.wallet._cols.status`, `tenant.pages.finance.wallet._cols.gateway`, `tenant.pages.finance.wallet._cols.transaction`, `tenant.pages.finance.wallet._cols.direction`, `tenant.pages.finance.wallet._cols.linked-model`, `tenant.pages.finance.wallet._cols.details`
  - FormRequest `SubscribeRequest`: `package_id`: required', 'integer
- **`Http\Controllers\Tenant\Panel\Finance\PartnerWalletController`** — [`app/Http/Controllers/Tenant/Panel/Finance/PartnerWalletController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/PartnerWalletController.php) (126 lines)
  - Doc: Partner (affiliate) tab of the vendor wallet. Always scoped to the current tenant's own affiliate row - no affiliate id is ever read from the request.
  - Actions/methods: `sso()`, `conversionsData()`, `payoutsData()`, `withdraw()`
  - FormRequest `PartnerWithdrawRequest`: `amount`: required', 'numeric', 'gt:0', 'min:' . AffiliateSettings::minPayout(), 'max:9999999; `note`: nullable', 'string', 'max:500

### `forgot-password`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/forgot-password` | `tenant.password.request` | — | `Tenant\Panel\Auth\PasswordResetController@showForgot` |
| POST | `/admin/forgot-password` | `tenant.password.email` | — | `Tenant\Panel\Auth\PasswordResetController@sendLink` |

- **`Http\Controllers\Tenant\Panel\Auth\PasswordResetController`** — [`app/Http/Controllers/Tenant/Panel/Auth/PasswordResetController.php`](../../app/Http/Controllers/Tenant/Panel/Auth/PasswordResetController.php) (66 lines)
  - Actions/methods: `showForgot()`, `sendLink()`, `showReset()`, `reset()`
  - Views: `tenant.pages.auth.forgot-password`, `tenant.pages.auth.reset-password`
  - FormRequest `ForgotPasswordRequest`: `email`: required', 'email
  - FormRequest `ResetPasswordRequest`: `token`: required', 'string; `email`: required', 'email; `password`: required', 'string', 'confirmed', Password::min(8)

### `help`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/help` | `tenant.help.index` | — | `Tenant\Panel\Support\HelpController@index` |
| GET | `/admin/help/articles/{slug}` | `tenant.help.article` | — | `Tenant\Panel\Support\HelpController@article` |

- **`Http\Controllers\Tenant\Panel\Support\HelpController`** — [`app/Http/Controllers/Tenant/Panel/Support/HelpController.php`](../../app/Http/Controllers/Tenant/Panel/Support/HelpController.php) (98 lines)
  - Actions/methods: `index()`, `article()`
  - Views: `tenant.pages.support.help.index`

### `impersonate`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/impersonate/{token}` | `tenant.impersonate.accept` | — | `Tenant\TenantImpersonateController@accept` |

- **`Http\Controllers\Tenant\TenantImpersonateController`** — [`app/Http/Controllers/Tenant/TenantImpersonateController.php`](../../app/Http/Controllers/Tenant/TenantImpersonateController.php) (78 lines)
  - Doc: Accepts a central-admin impersonation token, logs the caller into the tenant admin panel, then redirects to the tenant dashboard.  Called from:  GET {tenant-domain}/admin/impersonate/{token}  The matching token was generated by App\Http\Controllers\Admin\TenantImpersonateController::generate().  Sec
  - Actions/methods: `accept()`

### `language-purchase`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/language-purchase/{gateway}/cancel` | `tenant.language-purchase.cancel` | — | `Tenant\LanguagePaymentController@cancel` |
| GET|POST | `/admin/language-purchase/{gateway}/success` | `tenant.language-purchase.success` | — | `Tenant\LanguagePaymentController@success` |
| GET | `/admin/language-purchase/{gateway}/{languageId}` | `tenant.language-purchase.charge` | — | `Tenant\LanguagePaymentController@charge` |

- **`Http\Controllers\Tenant\LanguagePaymentController`** — [`app/Http/Controllers/Tenant/LanguagePaymentController.php`](../../app/Http/Controllers/Tenant/LanguagePaymentController.php) (159 lines)
  - Doc: LanguagePaymentController  Handles the payment lifecycle for tenant language add-on purchases:   1. charge()   — initiate a gateway payment for the selected language   2. success()  — verify callback, record purchase, sync language to tenant   3. cancel()   — redirect back to the buy page
  - Actions/methods: `charge()`, `success()`, `cancel()`

### `locale`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/admin/locale/{locale}` | `tenant.locale.switch` | — | `Tenant\Panel\Shell\LocaleController` |

- **`Http\Controllers\Tenant\Panel\Shell\LocaleController`** — [`app/Http/Controllers/Tenant/Panel/Shell/LocaleController.php`](../../app/Http/Controllers/Tenant/Panel/Shell/LocaleController.php) (24 lines)
  - Doc: Header language switcher of the vendor panel.

### `login`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/login` | `tenant.login` | — | `Tenant\Panel\Auth\LoginController@show` |
| POST | `/admin/login` | `tenant.login.attempt` | — | `Tenant\Panel\Auth\LoginController@login` |
| POST | `/admin/login/validate` | `tenant.login.validate` | — | `Tenant\Panel\Auth\LoginController@validateLogin` |

- **`Http\Controllers\Tenant\Panel\Auth\LoginController`** — [`app/Http/Controllers/Tenant/Panel/Auth/LoginController.php`](../../app/Http/Controllers/Tenant/Panel/Auth/LoginController.php) (87 lines)
  - Actions/methods: `redirectToStart()`, `show()`, `validateLogin()`, `login()`, `logout()`
  - Views: `tenant.pages.auth.login`
  - FormRequest `LoginRequest`: `email`: required', 'email; `password`: required', 'string; `remember`: boolean

### `logout`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/admin/logout` | `tenant.logout` | — | `Tenant\Panel\Auth\LoginController@logout` |

- **`Http\Controllers\Tenant\Panel\Auth\LoginController`** — [`app/Http/Controllers/Tenant/Panel/Auth/LoginController.php`](../../app/Http/Controllers/Tenant/Panel/Auth/LoginController.php) (87 lines)
  - Actions/methods: `redirectToStart()`, `show()`, `validateLogin()`, `login()`, `logout()`
  - Views: `tenant.pages.auth.login`
  - FormRequest `LoginRequest`: `email`: required', 'email; `password`: required', 'string; `remember`: boolean

### `manufacturing`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/manufacturing` | `tenant.manufacturing.index` | — | `Tenant\Panel\Requests\ManufacturingController@index` |
| POST | `/admin/manufacturing` | `tenant.manufacturing.store` | — | `Tenant\Panel\Requests\ManufacturingController@store` |
| GET | `/admin/manufacturing/create` | `tenant.manufacturing.create` | — | `Tenant\Panel\Requests\ManufacturingController@create` |
| GET | `/admin/manufacturing/data` | `tenant.manufacturing.data` | — | `Tenant\Panel\Requests\ManufacturingController@data` |
| GET | `/admin/manufacturing/export` | `tenant.manufacturing.export` | — | `Tenant\Panel\Requests\ManufacturingController@export` |
| GET | `/admin/manufacturing/products/search` | `tenant.manufacturing.products.search` | — | `Tenant\Panel\Requests\ManufacturingController@searchProducts` |
| POST | `/admin/manufacturing/validate` | `tenant.manufacturing.validate` | — | `Tenant\Panel\Requests\ManufacturingController@validateStore` |
| GET | `/admin/manufacturing/{id}` | `tenant.manufacturing.show` | — | `Tenant\Panel\Requests\ManufacturingController@show` |
| POST | `/admin/manufacturing/{id}/cancel` | `tenant.manufacturing.cancel` | — | `Tenant\Panel\Requests\ManufacturingController@cancel` |
| POST | `/admin/manufacturing/{id}/messages` | `tenant.manufacturing.messages` | — | `Tenant\Panel\Requests\ManufacturingController@sendMessage` |
| POST | `/admin/manufacturing/{id}/messages/validate` | `tenant.manufacturing.messages.validate` | — | `Tenant\Panel\Requests\ManufacturingController@validateMessage` |
| POST | `/admin/manufacturing/{id}/pay` | `tenant.manufacturing.pay` | — | `Tenant\Panel\Requests\ManufacturingController@pay` |
| POST | `/admin/manufacturing/{id}/pay/validate` | `tenant.manufacturing.pay.validate` | — | `Tenant\Panel\Requests\ManufacturingController@validatePay` |

- **`Http\Controllers\Tenant\Panel\Requests\ManufacturingController`** — [`app/Http/Controllers/Tenant/Panel/Requests/ManufacturingController.php`](../../app/Http/Controllers/Tenant/Panel/Requests/ManufacturingController.php) (242 lines)
  - Actions/methods: `index()`, `data()`, `export()`, `create()`, `store()`, `validateStore()`, `searchProducts()`, `show()`, `cancel()`, `sendMessage()`, `validateMessage()`, `pay()`, `validatePay()`
  - Views: `tenant.pages.requests.manufacturing.index`, `tenant.pages.requests.manufacturing._cols.product`, `tenant.pages.requests.manufacturing._cols.actions`, `tenant.pages.requests.manufacturing.create`, `tenant.pages.requests.manufacturing.show`
  - FormRequest `PayManufacturingRequestRequest`: `payment_request_id`: required', 'integer
  - FormRequest `SendManufacturingMessageRequest`: `message`: required', 'string', 'max:2000
  - FormRequest `StoreManufacturingRequestRequest`: `product_name`: required', 'string', 'max:255; `description`: nullable', 'string', 'max:2000; `quantity`: required', 'integer', 'min:1', 'max:99999; `linked_product_id`: nullable', 'integer', 'exists:products,id; `reference_url`: nullable', 'url', 'max:2000; `images`: array', 'max:5; `images.*`: image', 'max:10240', 'mimes:jpg,jpeg,png,webp,avif,gif

### `manufacturing-payment`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/manufacturing-payment/{gateway}/cancel` | `tenant.manufacturing-payment.cancel` | — | `Tenant\ManufacturingPaymentController@cancel` |
| GET|POST | `/admin/manufacturing-payment/{gateway}/success` | `tenant.manufacturing-payment.success` | — | `Tenant\ManufacturingPaymentController@success` |
| GET | `/admin/manufacturing-payment/{gateway}/{paymentRequestId}` | `tenant.manufacturing-payment.charge` | — | `Tenant\ManufacturingPaymentController@charge` |

- **`Http\Controllers\Tenant\ManufacturingPaymentController`** — [`app/Http/Controllers/Tenant/ManufacturingPaymentController.php`](../../app/Http/Controllers/Tenant/ManufacturingPaymentController.php) (186 lines)
  - Doc: ManufacturingPaymentController  Handles the payment lifecycle for manufacturing request payment requests:   1. charge()  — initiate a gateway payment for a pending payment request   2. success() — verify callback, mark as paid, redirect to detail page   3. cancel()  — forget pending context, redirec
  - Actions/methods: `charge()`, `success()`, `cancel()`

### `notifications`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/notifications` | `tenant.notifications.index` | — | `Tenant\Panel\Support\NotificationsController@index` |
| GET | `/admin/notifications/feed` | `tenant.notifications.feed` | — | `Tenant\Panel\Support\NotificationsController@feed` |
| GET | `/admin/notifications/latest` | `tenant.notifications.latest` | — | `Tenant\Panel\Support\NotificationsController@latest` |
| POST | `/admin/notifications/read-all` | `tenant.notifications.read-all` | — | `Tenant\Panel\Support\NotificationsController@markAllRead` |
| GET | `/admin/notifications/{id}/open` | `tenant.notifications.open` | — | `Tenant\Panel\Support\NotificationsController@open` |
| PATCH | `/admin/notifications/{id}/read` | `tenant.notifications.mark-read` | — | `Tenant\Panel\Support\NotificationsController@markRead` |

- **`Http\Controllers\Tenant\Panel\Support\NotificationsController`** — [`app/Http/Controllers/Tenant/Panel/Support/NotificationsController.php`](../../app/Http/Controllers/Tenant/Panel/Support/NotificationsController.php) (72 lines)
  - Actions/methods: `index()`, `feed()`, `latest()`, `open()`, `markAllRead()`, `markRead()`
  - Views: `tenant.pages.support.notifications.index`

### `onboarding`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/admin/onboarding/dismiss` | `tenant.onboarding.dismiss` | — | `Tenant\Panel\Onboarding\OnboardingController@dismiss` |
| POST | `/admin/onboarding/logo` | `tenant.onboarding.logo` | — | `Tenant\Panel\Onboarding\OnboardingController@saveLogo` |
| POST | `/admin/onboarding/logo/validate` | `tenant.onboarding.logo.validate` | — | `Tenant\Panel\Onboarding\OnboardingController@validateLogo` |
| POST | `/admin/onboarding/payment-readiness/skip` | `tenant.onboarding.payment-readiness.skip` | — | `Tenant\Panel\Onboarding\OnboardingController@skipPaymentReadiness` |
| POST | `/admin/onboarding/tour/complete` | `tenant.onboarding.tour.complete` | — | `Tenant\Panel\Onboarding\OnboardingController@completeTour` |
| GET | `/admin/onboarding/{tab?}` | `tenant.onboarding` | — | `Tenant\Panel\Onboarding\OnboardingController@show` |

- **`Http\Controllers\Tenant\Panel\Onboarding\OnboardingController`** — [`app/Http/Controllers/Tenant/Panel/Onboarding/OnboardingController.php`](../../app/Http/Controllers/Tenant/Panel/Onboarding/OnboardingController.php) (137 lines)
  - Actions/methods: `show()`, `completeTour()`, `saveLogo()`, `validateLogo()`, `skipPaymentReadiness()`, `dismiss()`
  - Views: `tenant.pages.onboarding.index`

### `orders`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/orders` | `tenant.orders.index` | sales.orders.view (setup: payment_gateway) | `Tenant\Panel\Sales\OrdersController@index` |
| GET | `/admin/orders/data` | `tenant.orders.data` | sales.orders.view (setup: payment_gateway) | `Tenant\Panel\Sales\OrdersController@data` |
| GET | `/admin/orders/export` | `tenant.orders.export` | sales.orders.view (setup: payment_gateway) | `Tenant\Panel\Sales\OrdersController@export` |
| GET | `/admin/orders/{orderId}` | `tenant.orders.show` | sales.orders.view | `Tenant\Panel\Sales\OrdersController@show` |
| POST | `/admin/orders/{orderId}/cancel` | `tenant.orders.cancel` | sales.orders.manage | `Tenant\Panel\Sales\OrdersController@cancel` |
| POST | `/admin/orders/{orderId}/cancel/validate` | `tenant.orders.cancel.validate` | sales.orders.manage | `Tenant\Panel\Sales\OrdersController@validateCancel` |
| POST | `/admin/orders/{orderId}/refunds` | `tenant.orders.refunds.store` | sales.returns.manage | `Tenant\Panel\Sales\OrdersController@storeRefund` |
| POST | `/admin/orders/{orderId}/refunds/validate` | `tenant.orders.refunds.validate` | sales.returns.manage | `Tenant\Panel\Sales\OrdersController@validateStoreRefund` |
| PATCH | `/admin/orders/{orderId}/shipping-status` | `tenant.orders.shipping-status` | sales.orders.view | `Tenant\Panel\Sales\OrdersController@updateShippingStatus` |
| POST | `/admin/orders/{orderId}/shipping-status/validate` | `tenant.orders.shipping-status.validate` | sales.orders.view | `Tenant\Panel\Sales\OrdersController@validateUpdateShippingStatus` |

- **`Http\Controllers\Tenant\Panel\Sales\OrdersController`** — [`app/Http/Controllers/Tenant/Panel/Sales/OrdersController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/OrdersController.php) (328 lines)
  - Actions/methods: `index()`, `data()`, `export()`, `show()`, `updateShippingStatus()`, `validateUpdateShippingStatus()`, `validateCancel()`, `cancel()`, `validateStoreRefund()`, `storeRefund()`
  - Views: `tenant.pages.sales.orders.index`, `tenant.pages.sales.orders._cols.order`, `tenant.pages.sales.orders._cols.customer`, `tenant.pages.sales.orders._cols.value`, `tenant.pages.sales.orders._cols.vendor-own-platform`, `tenant::components.status-badge`, `tenant.pages.sales.orders._cols.gateway`, `tenant.pages.sales.orders._cols.actions`
  - FormRequest `StoreManualRefundRequest`: `amount`: required', 'numeric', 'min:0.01', 'max:99999999; `reason`: required', 'string', 'max:255
  - FormRequest `UpdateShippingStatusRequest`: `shipping_status`: required', Rule::enum(OrderShippingStatus::class)

### `own-products`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/own-products` | `tenant.own-products.index` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductsListController@index` |
| POST | `/admin/own-products` | `tenant.own-products.store` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductController@store` |
| GET | `/admin/own-products/create` | `tenant.own-products.create` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductController@create` |
| GET | `/admin/own-products/data` | `tenant.own-products.data` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductsListController@data` |
| POST | `/admin/own-products/validate` | `tenant.own-products.validate` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductController@validateStore` |
| PUT | `/admin/own-products/{product}` | `tenant.own-products.update` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductController@update` |
| DELETE | `/admin/own-products/{product}` | `tenant.own-products.destroy` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductsListController@destroy` |
| GET | `/admin/own-products/{product}/edit` | `tenant.own-products.edit` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductController@edit` |
| POST | `/admin/own-products/{product}/validate` | `tenant.own-products.validate.update` | catalog.products.manage | `Tenant\Panel\Catalog\OwnProductController@validateUpdate` |

- **`Http\Controllers\Tenant\Panel\Catalog\OwnProductsListController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/OwnProductsListController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/OwnProductsListController.php) (67 lines)
  - Actions/methods: `index()`, `data()`, `destroy()`
  - Views: `tenant.pages.catalog.own-products.index`, `tenant.pages.catalog.own-products._cols.product`, `tenant.pages.catalog.own-products._cols.status`, `tenant.pages.catalog.own-products._cols.actions`
- **`Http\Controllers\Tenant\Panel\Catalog\OwnProductController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/OwnProductController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/OwnProductController.php) (440 lines)
  - Actions/methods: `create()`, `edit()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`
  - Views: `tenant.pages.catalog.own-products.form`
  - FormRequest `SaveOwnProductRequest`: `sku`: required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id); `slug`: nullable', 'string', 'max:255; `base_price`: required', 'numeric', 'min:0; `sale_price`: nullable', 'numeric', 'min:0; `cost_price`: nullable', 'numeric', 'min:0; `stock`: required', 'integer', 'min:0; `min_stock`: required', 'integer', 'min:0; `manage_stock`: boolean; `is_taxable`: boolean; `weight_grams`: nullable', 'integer', 'min:0; `active`: boolean; `featured`: boolean; `category_ids`: array; `badge_ids`: array; `countries`: array; `countries.*`: string', Rule::in(array_keys(OwnProductController::countryOptions())); `primary_image`: nullable', 'image', 'max:4096; `remove_primary_image`: boolean; `gallery_files`: array; `gallery_files.*`: nullable', 'mimes:jpg,jpeg,png,gif,webp,avif,mp4,webm,mov', 'max:51200; `remove_gallery_ids`: array; `remove_gallery_ids.*`: nullable', 'integer; `gallery_order`: nullable', 'string; `variants`: array; `variants.*.id`: nullable', 'integer; `variants.*.pairs`: array', 'min:1; `variants.*.pairs.*.variation_id`: nullable', 'integer; `variants.*.pairs.*.option_id`: nullable', 'integer; `variants.*.price`: nullable', 'numeric', 'min:0; `variants.*.stock`: nullable', 'integer', 'min:0; `variants.*.sku`: nullable', 'string', 'max:100; `variants.*.weight_grams`: nullable', 'integer', 'min:0; `variants.*.image`: nullable', 'image', 'max:5120; `variants.*.remove_image`: boolean; `variants.*.active`: boolean; `return_policy_override`: boolean; `is_returnable`: boolean; `return_window_days`: nullable', 'integer', 'min:1', 'max:365; `return_fee`: nullable', 'numeric', 'min:0; `return_video_required`: boolean

### `partner-program`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/partner-program` | `tenant.partner-program` | finance.wallet.view | `Tenant\Panel\Finance\PartnerProgramController@index` |

- **`Http\Controllers\Tenant\Panel\Finance\PartnerProgramController`** — [`app/Http/Controllers/Tenant/Panel/Finance/PartnerProgramController.php`](../../app/Http/Controllers/Tenant/Panel/Finance/PartnerProgramController.php) (47 lines)
  - Doc: Stand-alone "Partner Program" page (referral link, stats, conversions, payouts, withdraw). Data/withdraw/SSO endpoints stay in {@see PartnerWalletController}.
  - Actions/methods: `index()`
  - Views: `tenant.pages.partner-program.index`

### `product-requests`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/product-requests` | `tenant.product-requests.index` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@index` |
| POST | `/admin/product-requests` | `tenant.product-requests.store` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@store` |
| GET | `/admin/product-requests/data` | `tenant.product-requests.data` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@data` |
| GET | `/admin/product-requests/new` | `tenant.product-requests.create` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@create` |
| POST | `/admin/product-requests/validate` | `tenant.product-requests.validate` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@validateStore` |
| GET | `/admin/product-requests/{requestId}` | `tenant.product-requests.show` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@show` |
| POST | `/admin/product-requests/{requestId}/replies` | `tenant.product-requests.replies` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@reply` |
| POST | `/admin/product-requests/{requestId}/replies/validate` | `tenant.product-requests.replies.validate` | catalog.products.manage | `Tenant\Panel\Requests\ProductRequestController@validateReply` |

- **`Http\Controllers\Tenant\Panel\Requests\ProductRequestController`** — [`app/Http/Controllers/Tenant/Panel/Requests/ProductRequestController.php`](../../app/Http/Controllers/Tenant/Panel/Requests/ProductRequestController.php) (188 lines)
  - Actions/methods: `index()`, `data()`, `create()`, `store()`, `validateStore()`, `show()`, `reply()`, `validateReply()`
  - Views: `tenant.pages.requests.product-requests.index`, `tenant.pages.requests.product-requests._cols.request`, `tenant.pages.requests.product-requests._cols.actions`, `tenant.pages.requests.product-requests.create`, `tenant.pages.requests.product-requests.show`
  - FormRequest `ReplyProductRequestRequest`: `reply`: required', 'string', 'min:2', 'max:5000; `attachments`: array', 'max:5; `attachments.*`: file', 'max:10240', 'mimes:jpg,jpeg,png,webp,avif,gif,pdf,doc,docx
  - FormRequest `StoreProductRequestRequest`: `title`: required', 'string', 'max:255; `description`: required', 'string', 'min:20', 'max:8000; `product_url`: nullable', 'url', 'max:2000; `files`: array', 'max:5; `files.*`: file', 'max:10240', 'mimes:jpg,jpeg,png,webp,avif,gif,pdf,doc,docx,xls,xlsx,zip

### `products`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/products` | `tenant.products.index` | catalog.products.manage (setup: theme) | `Tenant\Panel\Catalog\ProductsListController@index` |
| POST | `/admin/products` | `tenant.products.store` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@store` |
| POST | `/admin/products/ai-translate` | `tenant.products.ai-translate` | catalog.products.manage | `Tenant\Panel\Catalog\ProductAiTranslationController@translate` |
| GET | `/admin/products/central-search` | `tenant.products.central-search` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@centralSearch` |
| GET | `/admin/products/central-snapshot/{centralProduct}` | `tenant.products.central-snapshot` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@centralSnapshot` |
| GET | `/admin/products/create` | `tenant.products.create` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@create` |
| GET | `/admin/products/data` | `tenant.products.data` | catalog.products.manage | `Tenant\Panel\Catalog\ProductsListController@data` |
| GET | `/admin/products/edit-requests` | `tenant.products.edit-requests` | catalog.products.manage | `Tenant\Panel\Catalog\EditRequestsController@index` |
| GET | `/admin/products/edit-requests/data` | `tenant.products.edit-requests.data` | catalog.products.manage | `Tenant\Panel\Catalog\EditRequestsController@data` |
| POST | `/admin/products/image-search` | `tenant.products.image-search` | catalog.products.manage | `ImageSearchController@tenantPanel` |
| GET | `/admin/products/new` | `tenant.products.new` | catalog.products.manage | `Tenant\Panel\Catalog\ProductsListController@newProducts` |
| GET | `/admin/products/new/{centralProductId}` | `tenant.products.new.show` | catalog.products.manage | `Tenant\Panel\Catalog\ProductsListController@previewNew` |
| GET | `/admin/products/sort` | `tenant.products.sort` | catalog.products.manage | `Tenant\Panel\Catalog\ProductSortController@index` |
| POST | `/admin/products/sort` | `tenant.products.sort.save` | catalog.products.manage | `Tenant\Panel\Catalog\ProductSortController@update` |
| POST | `/admin/products/sync-new` | `tenant.products.sync-new` | catalog.products.manage | `Tenant\Panel\Catalog\ProductsListController@syncNew` |
| POST | `/admin/products/validate` | `tenant.products.validate` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@validateStore` |
| PUT | `/admin/products/{product}` | `tenant.products.update` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@update` |
| PATCH | `/admin/products/{product}/active` | `tenant.products.toggle-active` | catalog.products.manage | `Tenant\Panel\Catalog\ProductsListController@toggleActive` |
| GET | `/admin/products/{product}/ai-price` | `tenant.products.ai-price` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@aiPrice` |
| POST | `/admin/products/{product}/ai-price` | `tenant.products.ai-price.fetch` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@fetchAiPrice` |
| GET | `/admin/products/{product}/edit` | `tenant.products.edit` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@edit` |
| PATCH | `/admin/products/{product}/featured` | `tenant.products.toggle-featured` | catalog.products.manage | `Tenant\Panel\Catalog\ProductsListController@toggleFeatured` |
| GET | `/admin/products/{product}/price-list` | `tenant.products.price-list` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@priceListShow` |
| PUT | `/admin/products/{product}/price-list` | `tenant.products.price-list.save` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@priceListSave` |
| POST | `/admin/products/{product}/price-list/preview` | `tenant.products.price-list.preview` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@priceListPreview` |
| GET | `/admin/products/{product}/share` | `tenant.products.share` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@share` |
| GET | `/admin/products/{product}/social` | `tenant.products.social` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@social` |
| POST | `/admin/products/{product}/social/generate` | `tenant.products.social.generate` | catalog.products.manage | `Tenant\Panel\Catalog\ProductModalsController@generateSocial` |
| POST | `/admin/products/{product}/validate` | `tenant.products.validate.update` | catalog.products.manage | `Tenant\Panel\Catalog\ProductController@validateUpdate` |

- **`Http\Controllers\Tenant\Panel\Catalog\ProductsListController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/ProductsListController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/ProductsListController.php) (279 lines)
  - Actions/methods: `index()`, `newProducts()`, `previewNew()`, `syncNew()`, `data()`, `toggleActive()`, `toggleFeatured()`
  - Views: `tenant.pages.catalog.products.index`, `tenant.pages.catalog.products.new`, `tenant.pages.catalog.products.new-show`, `tenant.pages.catalog.products._cols.product`, `tenant.pages.catalog.products._cols.central-price`, `tenant.pages.catalog.products._cols.vendor-price`, `tenant.pages.catalog.products._cols.ai-price`, `tenant.pages.catalog.products._cols.stock`
- **`Http\Controllers\Tenant\Panel\Catalog\ProductController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/ProductController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/ProductController.php) (286 lines)
  - Actions/methods: `create()`, `edit()`, `centralSearch()`, `centralSnapshot()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`
  - Views: `tenant.pages.catalog.products.form`, `tenant.pages.catalog.products._central-snapshot`, `tenant.pages.catalog.products._variants-table`
  - FormRequest `SaveProductRequest`: `central_product_id`: nullable', 'integer; `price`: required', 'numeric', 'min:0; `active`: boolean; `featured`: boolean; `category_ids`: array; `badge_ids`: array; `active_locale`: required', 'string; `variants`: array; `stock_override`: nullable', 'integer', 'min:0; `weight_grams_override`: nullable', 'integer', 'min:0; `variants.*.central_product_variant_id`: nullable', 'integer; `variants.*.real_price`: nullable', 'numeric', 'min:0; `variants.*.sell_price`: required', 'numeric', 'min:0; `variants.*.profit_margin`: nullable', 'numeric', 'min:0', 'max:100000; `variants.*.active`: boolean
- **`Http\Controllers\Tenant\Panel\Catalog\ProductAiTranslationController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/ProductAiTranslationController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/ProductAiTranslationController.php) (113 lines)
  - Doc: "Translate with AI" on the vendor product forms (own products + platform-catalog products). Returns a translation preview for one target language; nothing is saved until the vendor saves the product. Paid per generation: see ProductAiTranslationService::authorizationError() / chargeFor(). The bulk A
  - Actions/methods: `translate()`
  - FormRequest `AiTranslateProductRequest`: `target_locale`: required', 'string', 'max:10', $activeLanguage(); `source_locale`: nullable', 'string', 'max:10', 'different:target_locale', $activeLanguage(); `product_id`: nullable', 'integer', Rule::exists(Product::class, 'id'); `fields`: required', 'array', 'min:1; `fields.*`: required', 'string', 'distinct', Rule::in(ProductAiTranslationService::FIELDS); `source`: nullable', 'array; `source.name`: nullable', 'string', 'max:255; `source.label`: nullable', 'string', 'max:255; `source.summary`: nullable', 'string', 'max:255; `source.description`: nullable', 'string', 'max:20000; `source.meta_keywords`: nullable', 'string', 'max:500; `source.meta_description`: nullable', 'string', 'max:500; `regenerate`: boolean
- **`Http\Controllers\Tenant\Panel\Catalog\EditRequestsController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/EditRequestsController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/EditRequestsController.php) (67 lines)
  - Actions/methods: `index()`, `data()`
  - Views: `tenant.pages.catalog.edit-requests.index`, `tenant.pages.catalog.edit-requests._cols.changes`, `tenant.pages.catalog.edit-requests._cols.status`
- **`Http\Controllers\ImageSearchController`** — [`app/Http/Controllers/ImageSearchController.php`](../../app/Http/Controllers/ImageSearchController.php) (155 lines)
  - Actions/methods: `storefront()`, `adminPanel()`, `tenantPanel()`
  - Validation: `image`: required', 'image', 'max:8192
- **`Http\Controllers\Tenant\Panel\Catalog\ProductSortController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/ProductSortController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/ProductSortController.php) (50 lines)
  - Actions/methods: `index()`, `update()`
  - Views: `tenant.pages.catalog.sort.products`
- **`Http\Controllers\Tenant\Panel\Catalog\ProductModalsController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/ProductModalsController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/ProductModalsController.php) (140 lines)
  - Actions/methods: `social()`, `generateSocial()`, `aiPrice()`, `fetchAiPrice()`, `share()`, `priceListShow()`, `priceListSave()`, `priceListPreview()`
  - FormRequest `FetchAiPriceRequest`: `use_image`: boolean; `variant_id`: nullable', 'integer', 'exists:product_variants,id
  - FormRequest `GenerateSocialPostsRequest`: `language`: required', 'string', 'max:10; `platform`: required', 'string', Rule::in(['all', 'instagram', 'facebook', 'twitter', 'linkedin', 'tik; `include_image`: boolean
  - FormRequest `SavePriceListRequest`: `profits`: required', 'array; `profits.*.type`: required', 'string', Rule::in(['percentage', 'fixed; `profits.*.value`: required', 'numeric', 'min:0; `variants`: nullable', 'array; `variants.*.id`: required', 'integer', 'exists:product_variants,id; `variants.*.real_price`: nullable', 'numeric', 'min:0; `variants.*.profits`: required', 'array; `variants.*.profits.*.type`: required', 'string', Rule::in(['percentage', 'fixed; `variants.*.profits.*.value`: required', 'numeric', 'min:0

### `refunds`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/refunds` | `tenant.refunds.index` | sales.returns.manage | `Tenant\Panel\Sales\RefundsController@index` |
| GET | `/admin/refunds/data` | `tenant.refunds.data` | sales.returns.manage | `Tenant\Panel\Sales\RefundsController@data` |
| POST | `/admin/refunds/{id}/complete` | `tenant.refunds.complete` | sales.returns.manage | `Tenant\Panel\Sales\RefundController@complete` |
| POST | `/admin/refunds/{id}/complete/validate` | `tenant.refunds.complete.validate` | sales.returns.manage | `Tenant\Panel\Sales\RefundController@validateComplete` |
| POST | `/admin/refunds/{id}/reject` | `tenant.refunds.reject` | sales.returns.manage | `Tenant\Panel\Sales\RefundController@reject` |
| POST | `/admin/refunds/{id}/reject/validate` | `tenant.refunds.reject.validate` | sales.returns.manage | `Tenant\Panel\Sales\RefundController@validateReject` |
| POST | `/admin/refunds/{id}/retry` | `tenant.refunds.retry` | sales.returns.manage | `Tenant\Panel\Sales\RefundController@retry` |

- **`Http\Controllers\Tenant\Panel\Sales\RefundsController`** — [`app/Http/Controllers/Tenant/Panel/Sales/RefundsController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/RefundsController.php) (91 lines)
  - Doc: Sales → Refunds (RETURN_EXCHANGE_REFUND_PLAN.md B.8 Vendor): every refund of the current tenant (cancellation, return and manual), with status / source filters and stat cards. Single-refund actions live in RefundController and on the order / return pages.
  - Actions/methods: `index()`, `data()`
  - Views: `tenant.pages.sales.refunds.index`, `tenant.pages.sales.refunds._cols.reference`, `tenant.pages.sales.refunds._cols.source`, `tenant.pages.sales.refunds._cols.amount`, `tenant.pages.sales.refunds._cols.method`, `tenant::components.status-badge`, `tenant.pages.sales.refunds._cols.order`
- **`Http\Controllers\Tenant\Panel\Sales\RefundController`** — [`app/Http/Controllers/Tenant/Panel/Sales/RefundController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/RefundController.php) (92 lines)
  - Doc: Vendor actions on a single refund (RETURN_EXCHANGE_REFUND_PLAN.md B.4 / B.8). Refunds live in the central DB; every action is guarded to the current tenant. RefundException (wrong state, manual-only, …) renders itself as a 422 with a user-safe message.
  - Actions/methods: `retry()`, `validateComplete()`, `complete()`, `validateReject()`, `reject()`
  - FormRequest `CompleteRefundRequest`: `reference`: nullable', 'string', 'max:190
  - FormRequest `RejectRefundRequest`: `reason`: required', 'string', 'max:1000

### `reset-password`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/admin/reset-password` | `tenant.password.update` | — | `Tenant\Panel\Auth\PasswordResetController@reset` |
| GET | `/admin/reset-password/{token}` | `tenant.password.reset` | — | `Tenant\Panel\Auth\PasswordResetController@showReset` |

- **`Http\Controllers\Tenant\Panel\Auth\PasswordResetController`** — [`app/Http/Controllers/Tenant/Panel/Auth/PasswordResetController.php`](../../app/Http/Controllers/Tenant/Panel/Auth/PasswordResetController.php) (66 lines)
  - Actions/methods: `showForgot()`, `sendLink()`, `showReset()`, `reset()`
  - Views: `tenant.pages.auth.forgot-password`, `tenant.pages.auth.reset-password`
  - FormRequest `ForgotPasswordRequest`: `email`: required', 'email
  - FormRequest `ResetPasswordRequest`: `token`: required', 'string; `email`: required', 'email; `password`: required', 'string', 'confirmed', Password::min(8)

### `returns`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/returns` | `tenant.returns.index` | sales.returns.manage | `Tenant\Panel\Sales\ReturnsController@index` |
| GET | `/admin/returns/analytics` | `tenant.returns.analytics` | sales.returns.manage | `Tenant\Panel\Sales\ReturnAnalyticsController@index` |
| GET | `/admin/returns/analytics/data` | `tenant.returns.analytics.data` | sales.returns.manage | `Tenant\Panel\Sales\ReturnAnalyticsController@data` |
| GET | `/admin/returns/data` | `tenant.returns.data` | sales.returns.manage | `Tenant\Panel\Sales\ReturnsController@data` |
| GET | `/admin/returns/{id}` | `tenant.returns.show` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@show` |
| POST | `/admin/returns/{id}/approve` | `tenant.returns.approve` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@approve` |
| POST | `/admin/returns/{id}/approve/validate` | `tenant.returns.approve.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateApprove` |
| POST | `/admin/returns/{id}/close` | `tenant.returns.close` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@close` |
| POST | `/admin/returns/{id}/convert-to-refund` | `tenant.returns.convert-to-refund` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@convertToRefund` |
| POST | `/admin/returns/{id}/exchange-completed` | `tenant.returns.exchange-completed` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@markExchangeCompleted` |
| POST | `/admin/returns/{id}/exchange-shipped` | `tenant.returns.exchange-shipped` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@markExchangeShipped` |
| POST | `/admin/returns/{id}/exchange-shipped/validate` | `tenant.returns.exchange-shipped.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateExchangeShipped` |
| POST | `/admin/returns/{id}/inspect` | `tenant.returns.inspect` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@inspect` |
| POST | `/admin/returns/{id}/inspect/validate` | `tenant.returns.inspect.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateInspect` |
| POST | `/admin/returns/{id}/issue-refund` | `tenant.returns.issue-refund` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@issueRefund` |
| POST | `/admin/returns/{id}/issue-refund/validate` | `tenant.returns.issue-refund.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateIssueRefund` |
| POST | `/admin/returns/{id}/notes` | `tenant.returns.notes` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@addNote` |
| POST | `/admin/returns/{id}/notes/validate` | `tenant.returns.notes.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateNote` |
| POST | `/admin/returns/{id}/received` | `tenant.returns.received` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@markItemReceived` |
| POST | `/admin/returns/{id}/refunded` | `tenant.returns.refunded` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@markRefunded` |
| POST | `/admin/returns/{id}/refunded/validate` | `tenant.returns.refunded.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateRefunded` |
| POST | `/admin/returns/{id}/reject` | `tenant.returns.reject` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@reject` |
| POST | `/admin/returns/{id}/reject/validate` | `tenant.returns.reject.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateReject` |
| POST | `/admin/returns/{id}/request-info` | `tenant.returns.request-info` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@requestMoreInfo` |
| POST | `/admin/returns/{id}/request-info/validate` | `tenant.returns.request-info.validate` | sales.returns.manage | `Tenant\Panel\Sales\ReturnController@validateRequestInfo` |

- **`Http\Controllers\Tenant\Panel\Sales\ReturnsController`** — [`app/Http/Controllers/Tenant/Panel/Sales/ReturnsController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/ReturnsController.php) (72 lines)
  - Actions/methods: `index()`, `data()`
  - Views: `tenant.pages.sales.returns.index`, `tenant.pages.sales.returns._cols.order`, `tenant.pages.sales.returns._cols.status`, `tenant.pages.sales.returns._cols.actions`
- **`Http\Controllers\Tenant\Panel\Sales\ReturnAnalyticsController`** — [`app/Http/Controllers/Tenant/Panel/Sales/ReturnAnalyticsController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/ReturnAnalyticsController.php) (80 lines)
  - Actions/methods: `index()`, `data()`
  - Views: `tenant.pages.insights._layout`
- **`Http\Controllers\Tenant\Panel\Sales\ReturnController`** — [`app/Http/Controllers/Tenant/Panel/Sales/ReturnController.php`](../../app/Http/Controllers/Tenant/Panel/Sales/ReturnController.php) (376 lines)
  - Actions/methods: `show()`, `validateApprove()`, `approve()`, `validateReject()`, `reject()`, `validateRequestInfo()`, `requestMoreInfo()`, `markItemReceived()`, `validateInspect()`, `inspect()`, `validateIssueRefund()`, `issueRefund()`, `validateExchangeShipped()`, `markExchangeShipped()`, `markExchangeCompleted()`, `convertToRefund()`, `close()`, `validateRefunded()`, `markRefunded()`, `validateNote()`, `addNote()`
  - Views: `tenant.pages.sales.returns.show`
  - FormRequest `AddReturnNoteRequest`: `note_text`: required', 'string', 'max:2000
  - FormRequest `ApproveReturnRequest`: `approve_note`: nullable', 'string', 'max:2000
  - FormRequest `InspectReturnRequest`: `inspection_result`: required', 'string', Rule::enum(InspectionResult::class); `inspection_notes`: nullable', 'string', 'max:2000; `restock`: nullable', 'boolean
  - FormRequest `IssueReturnRefundRequest`: `amount`: required', 'numeric', 'min:0.01', 'max:99999999
  - FormRequest `MarkExchangeShippedRequest`: `tracking_number`: required', 'string', 'max:190
  - FormRequest `MarkReturnRefundedRequest`: `refund_amount`: required', 'numeric', 'min:0
  - FormRequest `RejectReturnRequest`: `reject_reason`: required', 'string', 'max:2000
  - FormRequest `RequestReturnInfoRequest`: `info_message`: required', 'string', 'max:2000

### `settings`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/settings/account` | `tenant.settings.account` | settings.account.manage | `Tenant\Panel\Settings\AccountSettingsController@show` |
| PUT | `/admin/settings/account` | `tenant.settings.account.update` | settings.account.manage | `Tenant\Panel\Settings\AccountSettingsController@update` |
| POST | `/admin/settings/account/validate` | `tenant.settings.account.validate` | settings.account.manage | `Tenant\Panel\Settings\AccountSettingsController@validateUpdate` |
| GET | `/admin/settings/admins` | `tenant.settings.admins` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@index` |
| POST | `/admin/settings/admins` | `tenant.settings.admins.store` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@store` |
| GET | `/admin/settings/admins/data` | `tenant.settings.admins.data` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@data` |
| POST | `/admin/settings/admins/validate` | `tenant.settings.admins.validate` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@validateStore` |
| GET | `/admin/settings/admins/{admin}` | `tenant.settings.admins.show` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@show` |
| PUT | `/admin/settings/admins/{admin}` | `tenant.settings.admins.update` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@update` |
| DELETE | `/admin/settings/admins/{admin}` | `tenant.settings.admins.destroy` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@destroy` |
| POST | `/admin/settings/admins/{admin}/activate` | `tenant.settings.admins.activate` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@activate` |
| POST | `/admin/settings/admins/{admin}/deactivate` | `tenant.settings.admins.deactivate` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@deactivate` |
| POST | `/admin/settings/admins/{admin}/validate` | `tenant.settings.admins.validate.update` | settings.admins.manage | `Tenant\Panel\Settings\AdminsController@validateUpdate` |
| GET | `/admin/settings/ai-translation` | `tenant.settings.ai-translation` | settings.translations.manage | `Tenant\Panel\Settings\AiTranslationController@index` |
| GET | `/admin/settings/ai-translation/history/data` | `tenant.settings.ai-translation.history.data` | settings.translations.manage | `Tenant\Panel\Settings\AiTranslationController@historyData` |
| POST | `/admin/settings/ai-translation/purchase/validate` | `tenant.settings.ai-translation.purchase.validate` | settings.translations.manage | `Tenant\Panel\Settings\AiTranslationController@validatePurchase` |
| GET | `/admin/settings/ai-translation/status` | `tenant.settings.ai-translation.status` | settings.translations.manage | `Tenant\Panel\Settings\AiTranslationController@status` |
| POST | `/admin/settings/ai-translation/{language}/purchase` | `tenant.settings.ai-translation.purchase` | settings.translations.manage | `Tenant\Panel\Settings\AiTranslationController@purchase` |
| POST | `/admin/settings/ai-translation/{language}/run` | `tenant.settings.ai-translation.run` | settings.translations.manage | `Tenant\Panel\Settings\AiTranslationController@run` |
| GET | `/admin/settings/compliance` | `tenant.settings.compliance` | settings.account.manage | `Tenant\Panel\Settings\ComplianceCenterController@show` |
| POST | `/admin/settings/compliance` | `tenant.settings.compliance.update` | settings.account.manage | `Tenant\Panel\Settings\ComplianceCenterController@update` |
| GET | `/admin/settings/compliance/cities-by-country/{countryId}` | `tenant.settings.compliance.cities-by-country` | settings.account.manage | `Tenant\Panel\Settings\ComplianceCenterController@citiesByCountry` |
| POST | `/admin/settings/compliance/validate` | `tenant.settings.compliance.validate` | settings.account.manage | `Tenant\Panel\Settings\ComplianceCenterController@validateUpdate` |
| GET | `/admin/settings/currencies` | `tenant.settings.currencies` | settings.regional.manage | `Tenant\Panel\Settings\CurrenciesController@index` |
| GET | `/admin/settings/currencies/data` | `tenant.settings.currencies.data` | settings.regional.manage | `Tenant\Panel\Settings\CurrenciesController@data` |
| PATCH | `/admin/settings/currencies/{currency}/active` | `tenant.settings.currencies.toggle-active` | settings.regional.manage | `Tenant\Panel\Settings\CurrenciesController@toggleActive` |
| POST | `/admin/settings/currencies/{currency}/default` | `tenant.settings.currencies.default` | settings.regional.manage | `Tenant\Panel\Settings\CurrenciesController@makeDefault` |
| GET | `/admin/settings/domains` | `tenant.settings.domains` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@index` |
| POST | `/admin/settings/domains` | `tenant.settings.domains.store` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@store` |
| POST | `/admin/settings/domains/validate` | `tenant.settings.domains.validate` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@validateStore` |
| GET | `/admin/settings/domains/{domainRequest}` | `tenant.settings.domains.show` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@show` |
| PUT | `/admin/settings/domains/{domainRequest}` | `tenant.settings.domains.update` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@update` |
| DELETE | `/admin/settings/domains/{domainRequest}` | `tenant.settings.domains.destroy` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@destroy` |
| POST | `/admin/settings/domains/{domainRequest}/check-dns` | `tenant.settings.domains.check-dns` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@checkDns` |
| POST | `/admin/settings/domains/{domainRequest}/validate` | `tenant.settings.domains.validate.update` | settings.domains.manage | `Tenant\Panel\Settings\DomainsController@validateUpdate` |
| GET | `/admin/settings/email-templates` | `tenant.settings.email-templates` | settings.mail.manage | `Tenant\Panel\Settings\EmailTemplateController@index` |
| GET | `/admin/settings/email-templates/data` | `tenant.settings.email-templates.data` | settings.mail.manage | `Tenant\Panel\Settings\EmailTemplateController@data` |
| PUT | `/admin/settings/email-templates/{emailTemplate}` | `tenant.settings.email-templates.update` | settings.mail.manage | `Tenant\Panel\Settings\EmailTemplateController@update` |
| GET | `/admin/settings/email-templates/{emailTemplate}/edit` | `tenant.settings.email-templates.edit` | settings.mail.manage | `Tenant\Panel\Settings\EmailTemplateController@edit` |
| POST | `/admin/settings/email-templates/{emailTemplate}/resync` | `tenant.settings.email-templates.resync` | settings.mail.manage | `Tenant\Panel\Settings\EmailTemplateController@resync` |
| POST | `/admin/settings/email-templates/{emailTemplate}/validate` | `tenant.settings.email-templates.validate` | settings.mail.manage | `Tenant\Panel\Settings\EmailTemplateController@validateUpdate` |
| GET | `/admin/settings/general` | `tenant.settings.general` | settings.account.manage | `Tenant\Panel\Settings\GeneralSettingsController@index` |
| PUT | `/admin/settings/general` | `tenant.settings.general.update` | settings.account.manage | `Tenant\Panel\Settings\GeneralSettingsController@update` |
| POST | `/admin/settings/general/category-request` | `tenant.settings.general.category-request` | settings.account.manage | `Tenant\Panel\Settings\GeneralSettingsController@submitCategoryRequest` |
| POST | `/admin/settings/general/category-request/validate` | `tenant.settings.general.category-request.validate` | settings.account.manage | `Tenant\Panel\Settings\GeneralSettingsController@validateCategoryRequest` |
| POST | `/admin/settings/general/country-request` | `tenant.settings.general.country-request` | settings.account.manage | `Tenant\Panel\Settings\GeneralSettingsController@submitCountryRequest` |
| POST | `/admin/settings/general/country-request/validate` | `tenant.settings.general.country-request.validate` | settings.account.manage | `Tenant\Panel\Settings\GeneralSettingsController@validateCountryRequest` |
| POST | `/admin/settings/general/validate` | `tenant.settings.general.validate` | settings.account.manage | `Tenant\Panel\Settings\GeneralSettingsController@validateUpdate` |
| GET | `/admin/settings/languages` | `tenant.settings.languages` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesController@index` |
| GET | `/admin/settings/languages-manage` | `tenant.settings.languages-manage` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesManageController@index` |
| GET | `/admin/settings/languages-manage/available` | `tenant.settings.languages-manage.available.data` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesManageController@available` |
| GET | `/admin/settings/languages-manage/data` | `tenant.settings.languages-manage.data` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesManageController@data` |
| POST | `/admin/settings/languages-manage/purchase` | `tenant.settings.languages-manage.purchase` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesManageController@purchase` |
| POST | `/admin/settings/languages-manage/purchase/validate` | `tenant.settings.languages-manage.purchase.validate` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesManageController@validatePurchase` |
| PATCH | `/admin/settings/languages-manage/{language}/active` | `tenant.settings.languages-manage.toggle-active` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesManageController@toggleActive` |
| POST | `/admin/settings/languages-manage/{language}/default` | `tenant.settings.languages-manage.default` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesManageController@makeDefault` |
| GET | `/admin/settings/languages/data` | `tenant.settings.languages.data` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesController@data` |
| PATCH | `/admin/settings/languages/{language}/active` | `tenant.settings.languages.toggle-active` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesController@toggleActive` |
| POST | `/admin/settings/languages/{language}/default` | `tenant.settings.languages.default` | settings.regional.manage | `Tenant\Panel\Settings\LanguagesController@makeDefault` |
| GET | `/admin/settings/mail` | `tenant.settings.mail` | settings.mail.manage | `Tenant\Panel\Settings\MailConfigurationsController@index` |
| PUT | `/admin/settings/mail` | `tenant.settings.mail.update` | settings.mail.manage | `Tenant\Panel\Settings\MailConfigurationsController@update` |
| POST | `/admin/settings/mail/test` | `tenant.settings.mail.test` | settings.mail.manage | `Tenant\Panel\Settings\MailConfigurationsController@sendTest` |
| POST | `/admin/settings/mail/test/validate` | `tenant.settings.mail.test.validate` | settings.mail.manage | `Tenant\Panel\Settings\MailConfigurationsController@validateSendTest` |
| POST | `/admin/settings/mail/validate` | `tenant.settings.mail.validate` | settings.mail.manage | `Tenant\Panel\Settings\MailConfigurationsController@validateUpdate` |
| GET | `/admin/settings/payment-gateways` | `tenant.settings.payment-gateways` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentGatewaysController@index` |
| GET | `/admin/settings/payment-gateways/data` | `tenant.settings.payment-gateways.data` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentGatewaysController@data` |
| GET | `/admin/settings/payment-gateways/{gateway}` | `tenant.settings.payment-gateways.show` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentGatewaysController@show` |
| PUT | `/admin/settings/payment-gateways/{gateway}` | `tenant.settings.payment-gateways.update` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentGatewaysController@update` |
| POST | `/admin/settings/payment-gateways/{gateway}/check-connection` | `tenant.settings.payment-gateways.check` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentGatewaysController@checkConnection` |
| POST | `/admin/settings/payment-gateways/{gateway}/primary` | `tenant.settings.payment-gateways.primary` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentGatewaysController@setPrimary` |
| POST | `/admin/settings/payment-gateways/{gateway}/validate` | `tenant.settings.payment-gateways.validate.update` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentGatewaysController@validateUpdate` |
| GET | `/admin/settings/payment-readiness` | `tenant.settings.payment-readiness` | settings.payment-gateways.manage | `Tenant\Panel\Settings\PaymentReadinessController@index` |
| GET | `/admin/settings/return-policy` | `tenant.settings.return-policy` | sales.returns.manage | `Tenant\Panel\Settings\ReturnPolicyController@index` |
| PUT | `/admin/settings/return-policy` | `tenant.settings.return-policy.update` | sales.returns.manage | `Tenant\Panel\Settings\ReturnPolicyController@update` |
| POST | `/admin/settings/return-policy/validate` | `tenant.settings.return-policy.validate` | sales.returns.manage | `Tenant\Panel\Settings\ReturnPolicyController@validateUpdate` |
| GET | `/admin/settings/roles-permissions` | `tenant.settings.roles-permissions` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@index` |
| POST | `/admin/settings/roles-permissions` | `tenant.settings.roles-permissions.store` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@store` |
| GET | `/admin/settings/roles-permissions/data` | `tenant.settings.roles-permissions.data` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@data` |
| POST | `/admin/settings/roles-permissions/validate` | `tenant.settings.roles-permissions.validate` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@validateStore` |
| GET | `/admin/settings/roles-permissions/{role}` | `tenant.settings.roles-permissions.show` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@show` |
| PUT | `/admin/settings/roles-permissions/{role}` | `tenant.settings.roles-permissions.update` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@update` |
| DELETE | `/admin/settings/roles-permissions/{role}` | `tenant.settings.roles-permissions.destroy` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@destroy` |
| POST | `/admin/settings/roles-permissions/{role}/validate` | `tenant.settings.roles-permissions.validate.update` | settings.roles.manage | `Tenant\Panel\Settings\RolesPermissionsController@validateUpdate` |
| GET | `/admin/settings/subscribers` | `tenant.settings.subscribers` | store.subscribers.manage | `Tenant\Panel\Settings\SubscribersController@index` |
| GET | `/admin/settings/subscribers/data` | `tenant.settings.subscribers.data` | store.subscribers.manage | `Tenant\Panel\Settings\SubscribersController@data` |
| GET | `/admin/settings/subscribers/export` | `tenant.settings.subscribers.export` | store.subscribers.manage | `Tenant\Panel\Settings\SubscribersController@export` |
| DELETE | `/admin/settings/subscribers/{subscriber}` | `tenant.settings.subscribers.destroy` | store.subscribers.manage | `Tenant\Panel\Settings\SubscribersController@destroy` |
| GET | `/admin/settings/tracking` | `tenant.settings.tracking` | settings.tracking.manage | `Tenant\Panel\Settings\TrackingSettingsController@index` |
| PUT | `/admin/settings/tracking` | `tenant.settings.tracking.update` | settings.tracking.manage | `Tenant\Panel\Settings\TrackingSettingsController@update` |
| POST | `/admin/settings/tracking/validate` | `tenant.settings.tracking.validate` | settings.tracking.manage | `Tenant\Panel\Settings\TrackingSettingsController@validateUpdate` |
| GET | `/admin/settings/translations` | `tenant.settings.translations` | settings.translations.manage | `Closure` |

- **`Http\Controllers\Tenant\Panel\Settings\AccountSettingsController`** — [`app/Http/Controllers/Tenant/Panel/Settings/AccountSettingsController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/AccountSettingsController.php) (72 lines)
  - Actions/methods: `show()`, `update()`, `validateUpdate()`
  - Views: `tenant.pages.settings.account.index`
  - FormRequest `UpdateAccountRequest`: `adminName`: required', 'string', 'max:255; `adminEmail`: required', 'email', 'max:255; `phone`: nullable', 'string', 'max:50; `shopName`: required', 'string', 'max:255; `description`: nullable', 'string', 'max:1000; `address`: nullable', 'string', 'max:255; `password`: nullable', 'string', 'min:6
- **`Http\Controllers\Tenant\Panel\Settings\AdminsController`** — [`app/Http/Controllers/Tenant/Panel/Settings/AdminsController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/AdminsController.php) (150 lines)
  - Actions/methods: `index()`, `data()`, `show()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`, `activate()`, `deactivate()`, `destroy()`
  - Views: `tenant.pages.settings.admins.index`, `tenant.pages.settings.admins._cols.admin`, `tenant::components.status-badge`, `tenant.pages.settings.admins._cols.actions`
  - FormRequest `SaveAdminRequest`: `role_id`: nullable', 'integer', 'exists:admin_roles,id; `name`: required', 'string', 'max:255; `email`: required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId); `password`: $adminId ? 'nullable' : 'required', 'string', 'min:8; `status`: required', Rule::enum(ActivationStatus::class)
- **`Http\Controllers\Tenant\Panel\Settings\AiTranslationController`** — [`app/Http/Controllers/Tenant/Panel/Settings/AiTranslationController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/AiTranslationController.php) (191 lines)
  - Actions/methods: `index()`, `historyData()`, `run()`, `purchase()`, `validatePurchase()`, `status()`
  - Views: `tenant.pages.settings.ai-translation.index`, `tenant.pages.settings.ai-translation._cols.status`
- **`Http\Controllers\Tenant\Panel\Settings\ComplianceCenterController`** — [`app/Http/Controllers/Tenant/Panel/Settings/ComplianceCenterController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/ComplianceCenterController.php) (244 lines)
  - Actions/methods: `citiesByCountry()`, `show()`, `update()`, `validateUpdate()`
  - Views: `tenant.pages.settings.compliance.index`
  - FormRequest `UpdateComplianceRequest`: `owner`: ownerName' => ['required', 'string', 'max:255; `ownerIdNumber`: required', 'string', 'max:100; `ownerDob`: nullable', 'date; `ownerContact`: nullable', 'string', 'max:100; `company`: companyName' => ['nullable', 'string', 'max:255; `registrationNumber`: required', 'string', 'max:100; `registrationExpiry`: nullable', 'date; `vatNumber`: nullable', 'string', 'max:100; `registrationDocumentUpload`: nullable', 'file', 'max:5120; `existing_registration_document_path`: nullable', 'string; `remove_registration_document`: nullable', 'boolean; `bank`: bankName' => ['required', 'string', 'max:255; `bankHolderName`: required', 'string', 'max:255; `bankAccountNumber`: required', 'string', 'max:100; `bankIban`: nullable', 'string', 'max:100; `currencyId`: nullable', 'integer', 'exists:'.config('tenancy.database.central_connection').'.currencies; `documents`: docNationalIdUpload' => ['nullable', 'file', 'max:5120; `existing_doc_national_id_path`: nullable', 'string; `remove_doc_national_id`: nullable', 'boolean; `docCommercialRegistrationUpload`: nullable', 'file', 'max:5120; `existing_doc_commercial_registration_path`: nullable', 'string; `remove_doc_commercial_registration`: nullable', 'boolean; `docTaxCertificateUpload`: nullable', 'file', 'max:5120; `existing_doc_tax_certificate_path`: nullable', 'string; `remove_doc_tax_certificate`: nullable', 'boolean; `docAdditionalUploads.*`: nullable', 'file', 'max:5120; `existing_doc_additional_paths`: nullable', 'array; `remove_doc_additional_paths`: nullable', 'array; `accountType`: required', 'in:individual,company; `businessName`: required_if:accountType,company', 'nullable', 'string', 'max:255; `storeName`: required', 'string', 'max:255; `countryId`: required', 'integer', 'exists:'.config('tenancy.database.central_connection').'.countries,; `cityId`: nullable', 'integer', 'exists:'.config('tenancy.database.central_connection').'.cities,id; `phone`: required', 'string', 'max:50; `email`: required', 'email', 'max:255
- **`Http\Controllers\Tenant\Panel\Settings\CurrenciesController`** — [`app/Http/Controllers/Tenant/Panel/Settings/CurrenciesController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/CurrenciesController.php) (80 lines)
  - Actions/methods: `index()`, `data()`, `toggleActive()`, `makeDefault()`
  - Views: `tenant.pages.settings.currencies.index`, `tenant.pages.settings.currencies._cols.status`, `tenant.pages.settings.currencies._cols.actions`
- **`Http\Controllers\Tenant\Panel\Settings\DomainsController`** — [`app/Http/Controllers/Tenant/Panel/Settings/DomainsController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/DomainsController.php) (155 lines)
  - Actions/methods: `index()`, `store()`, `validateStore()`, `show()`, `update()`, `validateUpdate()`, `destroy()`, `checkDns()`
  - Views: `tenant.pages.settings.domains.index`
  - FormRequest `AddDomainRequest`: `domain`: required', 'string', 'max:255', 'regex:/^(?:[a-z0-9
  - FormRequest `UpdateDomainRequest`: `domain`: required', 'string', 'max:255', 'regex:/^(?:[a-z0-9
- **`Http\Controllers\Tenant\Panel\Settings\EmailTemplateController`** — [`app/Http/Controllers/Tenant/Panel/Settings/EmailTemplateController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/EmailTemplateController.php) (147 lines)
  - Actions/methods: `index()`, `data()`, `edit()`, `update()`, `validateUpdate()`, `resync()`
  - Views: `tenant.pages.settings.email-templates.index`, `tenant.pages.settings.email-templates._cols.template`, `tenant::components.status-badge`, `tenant.pages.settings.email-templates._cols.actions`, `tenant.pages.settings.email-templates.edit`
  - FormRequest `SaveEmailTemplateRequest`: `subject`: required', 'string', 'max:255; `body`: nullable', 'string; `is_active`: boolean; `translations.*.subject`: nullable', 'string', 'max:255; `translations.*.body`: nullable', 'string
- **`Http\Controllers\Tenant\Panel\Settings\GeneralSettingsController`** — [`app/Http/Controllers/Tenant/Panel/Settings/GeneralSettingsController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/GeneralSettingsController.php) (204 lines)
  - Actions/methods: `index()`, `update()`, `validateUpdate()`, `submitCountryRequest()`, `validateCountryRequest()`, `submitCategoryRequest()`, `validateCategoryRequest()`
  - Views: `tenant.pages.settings.general.index`
  - FormRequest `SaveGeneralSettingsRequest`: `profit_percentage`: required', 'numeric', 'min:0', 'max:1000
  - FormRequest `SubmitCategoryChangeRequest`: `requested_category_ids`: required', 'array', 'min:1; `requested_category_ids.*`: integer
  - FormRequest `SubmitCountryChangeRequest`: `requested_country_ids`: required', 'array', 'min:1; `requested_country_ids.*`: integer
- **`Http\Controllers\Tenant\Panel\Settings\LanguagesController`** — [`app/Http/Controllers/Tenant/Panel/Settings/LanguagesController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/LanguagesController.php) (83 lines)
  - Actions/methods: `index()`, `data()`, `toggleActive()`, `makeDefault()`
  - Views: `tenant.pages.settings.languages.index`, `tenant.pages.settings.languages._cols.language`, `tenant.pages.settings.languages._cols.actions`
- **`Http\Controllers\Tenant\Panel\Settings\LanguagesManageController`** — [`app/Http/Controllers/Tenant/Panel/Settings/LanguagesManageController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/LanguagesManageController.php) (173 lines)
  - Actions/methods: `index()`, `data()`, `available()`, `toggleActive()`, `makeDefault()`, `purchase()`, `validatePurchase()`
  - Views: `tenant.pages.settings.languages-manage.index`, `tenant.pages.settings.languages-manage._cols.language`, `tenant.pages.settings.languages-manage._cols.countries`, `tenant.pages.settings.languages-manage._cols.actions`, `tenant.pages.settings.languages-manage._cols.available-language`, `tenant.pages.settings.languages-manage._cols.buy-action`
  - FormRequest `PurchaseLanguageRequest`: `language_id`: required', 'integer
- **`Http\Controllers\Tenant\Panel\Settings\MailConfigurationsController`** — [`app/Http/Controllers/Tenant/Panel/Settings/MailConfigurationsController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/MailConfigurationsController.php) (206 lines)
  - Actions/methods: `index()`, `update()`, `validateUpdate()`, `sendTest()`, `validateSendTest()`
  - Views: `tenant.pages.settings.mail.index`, `tenant.pages.settings.mail.partials.status`
  - FormRequest `SaveMailSettingsRequest`: `mail_mailer`: nullable', 'string', Rule::in(array_keys(MailConfigurationResolver::availableMailers())); `mail_host`: $presence('host'), 'string', 'max:255; `mail_port`: $presence('port'), 'integer', 'between:1,65535; `mail_username`: $presence('username'), 'string', 'max:255; `mail_password`: $presence('password'), 'string', 'max:255; `mail_encryption`: nullable', 'string', Rule::in(self::ENCRYPTIONS); `mail_from_address`: $presence('from_address'), 'email', 'max:255; `mail_from_name`: nullable', 'string', 'max:255
  - FormRequest `SendTestEmailRequest`: `email`: required', 'email', 'max:255
- **`Http\Controllers\Tenant\Panel\Settings\PaymentGatewaysController`** — [`app/Http/Controllers/Tenant/Panel/Settings/PaymentGatewaysController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/PaymentGatewaysController.php) (192 lines)
  - Actions/methods: `index()`, `data()`, `show()`, `setPrimary()`, `checkConnection()`, `update()`, `validateUpdate()`
  - Views: `tenant.pages.settings.payment-gateways.index`, `tenant.pages.settings.payment-gateways._cols.gateway`, `tenant.pages.settings.payment-gateways._cols.connection`, `tenant.pages.settings.payment-gateways._cols.webhook`, `tenant.pages.settings.payment-gateways._cols.monitoring`, `tenant.pages.settings.payment-gateways._cols.actions`
  - FormRequest `SavePaymentGatewayRequest`: `sandbox_mode`: boolean; `use_own`: boolean; `required_fields`: array; `required_fields.*.key`: required', 'string
- **`Http\Controllers\Tenant\Panel\Settings\PaymentReadinessController`** — [`app/Http/Controllers/Tenant/Panel/Settings/PaymentReadinessController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/PaymentReadinessController.php) (25 lines)
  - Actions/methods: `index()`
  - Views: `tenant.pages.settings.payment-readiness.index`
- **`Http\Controllers\Tenant\Panel\Settings\ReturnPolicyController`** — [`app/Http/Controllers/Tenant/Panel/Settings/ReturnPolicyController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/ReturnPolicyController.php) (107 lines)
  - Actions/methods: `index()`, `update()`, `validateUpdate()`
  - Views: `tenant.pages.settings.return-policy.index`
  - FormRequest `SaveReturnPolicyRequest`: `window_days`: required', 'integer', 'min:1', 'max:365; `non_returnable_ids`: nullable', 'string; `fee`: required', 'numeric', 'min:0; `conditions`: nullable', 'string', 'max:5000; `video_required_reasons`: nullable', 'string
- **`Http\Controllers\Tenant\Panel\Settings\RolesPermissionsController`** — [`app/Http/Controllers/Tenant/Panel/Settings/RolesPermissionsController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/RolesPermissionsController.php) (142 lines)
  - Actions/methods: `index()`, `data()`, `show()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`, `destroy()`
  - Views: `tenant.pages.settings.roles-permissions.index`, `tenant.pages.settings.roles-permissions._cols.actions`
  - FormRequest `SaveAdminRoleRequest`: `name`: required', 'string', 'max:255', Rule::unique('admin_roles', 'name')->ignore($roleId); `permissions`: nullable', 'array; `permissions.*`: string', Rule::in($availablePermissions)
- **`Http\Controllers\Tenant\Panel\Settings\SubscribersController`** — [`app/Http/Controllers/Tenant/Panel/Settings/SubscribersController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/SubscribersController.php) (87 lines)
  - Actions/methods: `index()`, `data()`, `export()`, `destroy()`
  - Views: `tenant.pages.settings.subscribers.index`, `tenant.pages.settings.subscribers._cols.actions`
- **`Http\Controllers\Tenant\Panel\Settings\TrackingSettingsController`** — [`app/Http/Controllers/Tenant/Panel/Settings/TrackingSettingsController.php`](../../app/Http/Controllers/Tenant/Panel/Settings/TrackingSettingsController.php) (61 lines)
  - Actions/methods: `index()`, `update()`, `validateUpdate()`
  - Views: `tenant.pages.settings.tracking.index`
  - FormRequest `SaveTrackingSettingsRequest`: `fb_pixel_id`: nullable', 'string', 'max:64; `tiktok_pixel_id`: nullable', 'string', 'max:64; `snapchat_pixel_id`: nullable', 'string', 'max:64; `ga_measurement_id`: nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9

### `store`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/store/appearance` | `tenant.store.appearance` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@index` |
| PUT | `/admin/store/appearance/colors/{theme}/{variant}` | `tenant.store.appearance.colors` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@saveColors` |
| POST | `/admin/store/appearance/colors/{theme}/{variant}/reset` | `tenant.store.appearance.colors.reset` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@resetColors` |
| POST | `/admin/store/appearance/colors/{theme}/{variant}/validate` | `tenant.store.appearance.colors.validate` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@validateColors` |
| PUT | `/admin/store/appearance/footer` | `tenant.store.appearance.footer` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@saveFooter` |
| POST | `/admin/store/appearance/footer/validate` | `tenant.store.appearance.footer.validate` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@validateFooter` |
| PUT | `/admin/store/appearance/general` | `tenant.store.appearance.general` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@saveGeneral` |
| POST | `/admin/store/appearance/general/validate` | `tenant.store.appearance.general.validate` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@validateGeneral` |
| PUT | `/admin/store/appearance/promo-banner` | `tenant.store.appearance.promo-banner` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@savePromoBanner` |
| POST | `/admin/store/appearance/promo-banner/validate` | `tenant.store.appearance.promo-banner.validate` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@validatePromoBanner` |
| GET | `/admin/store/appearance/social` | `tenant.store.appearance.social.index` | store.appearance.manage | `Tenant\Panel\Store\SocialLinkController@index` |
| POST | `/admin/store/appearance/social` | `tenant.store.appearance.social.store` | store.appearance.manage | `Tenant\Panel\Store\SocialLinkController@store` |
| POST | `/admin/store/appearance/social/validate` | `tenant.store.appearance.social.validate` | store.appearance.manage | `Tenant\Panel\Store\SocialLinkController@validateStore` |
| GET | `/admin/store/appearance/social/{link}` | `tenant.store.appearance.social.show` | store.appearance.manage | `Tenant\Panel\Store\SocialLinkController@show` |
| PUT | `/admin/store/appearance/social/{link}` | `tenant.store.appearance.social.update` | store.appearance.manage | `Tenant\Panel\Store\SocialLinkController@update` |
| DELETE | `/admin/store/appearance/social/{link}` | `tenant.store.appearance.social.destroy` | store.appearance.manage | `Tenant\Panel\Store\SocialLinkController@destroy` |
| POST | `/admin/store/appearance/social/{link}/validate` | `tenant.store.appearance.social.validate.update` | store.appearance.manage | `Tenant\Panel\Store\SocialLinkController@validateUpdate` |
| PUT | `/admin/store/appearance/top-header-links` | `tenant.store.appearance.top-header-links` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@saveTopHeaderLinks` |
| POST | `/admin/store/appearance/top-header-links/validate` | `tenant.store.appearance.top-header-links.validate` | store.appearance.manage | `Tenant\Panel\Store\AppearanceController@validateTopHeaderLinks` |
| GET | `/admin/store/banners` | `tenant.store.banners.index` | store.appearance.manage | `Tenant\Panel\Store\BannerController@index` |
| POST | `/admin/store/banners` | `tenant.store.banners.store` | store.appearance.manage | `Tenant\Panel\Store\BannerController@store` |
| GET | `/admin/store/banners/item/{banner}` | `tenant.store.banners.show` | store.appearance.manage | `Tenant\Panel\Store\BannerController@show` |
| PUT | `/admin/store/banners/item/{banner}` | `tenant.store.banners.update` | store.appearance.manage | `Tenant\Panel\Store\BannerController@update` |
| DELETE | `/admin/store/banners/item/{banner}` | `tenant.store.banners.destroy` | store.appearance.manage | `Tenant\Panel\Store\BannerController@destroy` |
| POST | `/admin/store/banners/item/{banner}/validate` | `tenant.store.banners.validate.update` | store.appearance.manage | `Tenant\Panel\Store\BannerController@validateUpdate` |
| GET | `/admin/store/banners/list/{countryId?}` | `tenant.store.banners` | store.appearance.manage | `Tenant\Panel\Store\BannerController@list` |
| POST | `/admin/store/banners/order` | `tenant.store.banners.order` | store.appearance.manage | `Tenant\Panel\Store\BannerController@updateOrder` |
| POST | `/admin/store/banners/validate` | `tenant.store.banners.validate` | store.appearance.manage | `Tenant\Panel\Store\BannerController@validateStore` |
| GET | `/admin/store/blade-theme` | `tenant.store.blade-theme` | store.blade-theme.manage | `Tenant\Panel\Store\BladeThemeController@index` |
| POST | `/admin/store/blade-theme` | `tenant.store.blade-theme.upload` | store.blade-theme.manage | `Tenant\Panel\Store\BladeThemeController@upload` |
| POST | `/admin/store/blade-theme/deactivate` | `tenant.store.blade-theme.deactivate` | store.blade-theme.manage | `Tenant\Panel\Store\BladeThemeController@deactivate` |
| GET | `/admin/store/blade-theme/starter-kit` | `tenant.store.blade-theme.starter-kit` | store.blade-theme.manage | `Tenant\BladeThemeStarterKitController@download` |
| POST | `/admin/store/blade-theme/validate` | `tenant.store.blade-theme.upload.validate` | store.blade-theme.manage | `Tenant\Panel\Store\BladeThemeController@validateUpload` |
| DELETE | `/admin/store/blade-theme/{upload}` | `tenant.store.blade-theme.destroy` | store.blade-theme.manage | `Tenant\Panel\Store\BladeThemeController@destroy` |
| GET | `/admin/store/coupons` | `tenant.store.coupons.index` | store.coupons.manage | `Tenant\Panel\Store\CouponController@index` |
| POST | `/admin/store/coupons` | `tenant.store.coupons.store` | store.coupons.manage | `Tenant\Panel\Store\CouponController@store` |
| GET | `/admin/store/coupons/data` | `tenant.store.coupons.data` | store.coupons.manage | `Tenant\Panel\Store\CouponController@data` |
| GET | `/admin/store/coupons/item/{coupon}` | `tenant.store.coupons.show` | store.coupons.manage | `Tenant\Panel\Store\CouponController@show` |
| PUT | `/admin/store/coupons/item/{coupon}` | `tenant.store.coupons.update` | store.coupons.manage | `Tenant\Panel\Store\CouponController@update` |
| DELETE | `/admin/store/coupons/item/{coupon}` | `tenant.store.coupons.destroy` | store.coupons.manage | `Tenant\Panel\Store\CouponController@destroy` |
| POST | `/admin/store/coupons/item/{coupon}/validate` | `tenant.store.coupons.validate.update` | store.coupons.manage | `Tenant\Panel\Store\CouponController@validateUpdate` |
| GET | `/admin/store/coupons/list/{countryId?}` | `tenant.store.coupons.list` | store.coupons.manage | `Tenant\Panel\Store\CouponController@list` |
| POST | `/admin/store/coupons/validate` | `tenant.store.coupons.validate` | store.coupons.manage | `Tenant\Panel\Store\CouponController@validateStore` |
| GET | `/admin/store/flash-sales` | `tenant.store.flash-sales.index` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@index` |
| POST | `/admin/store/flash-sales` | `tenant.store.flash-sales.store` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@store` |
| GET | `/admin/store/flash-sales/available-for-product` | `tenant.store.flash-sales.available-for-product` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@availableForProduct` |
| GET | `/admin/store/flash-sales/data` | `tenant.store.flash-sales.data` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@data` |
| GET | `/admin/store/flash-sales/item/{flashSale}` | `tenant.store.flash-sales.show` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@show` |
| PUT | `/admin/store/flash-sales/item/{flashSale}` | `tenant.store.flash-sales.update` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@update` |
| DELETE | `/admin/store/flash-sales/item/{flashSale}` | `tenant.store.flash-sales.destroy` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@destroy` |
| POST | `/admin/store/flash-sales/item/{flashSale}/attach-product` | `tenant.store.flash-sales.attach-product` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@attachProduct` |
| POST | `/admin/store/flash-sales/item/{flashSale}/validate` | `tenant.store.flash-sales.validate.update` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@validateUpdate` |
| GET | `/admin/store/flash-sales/list/{countryId?}` | `tenant.store.flash-sales` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@list` |
| GET | `/admin/store/flash-sales/products/search` | `tenant.store.flash-sales.products.search` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@searchProducts` |
| POST | `/admin/store/flash-sales/validate` | `tenant.store.flash-sales.validate` | store.flash-sales.manage | `Tenant\Panel\Store\FlashSaleController@validateStore` |
| GET | `/admin/store/home-variants` | `tenant.store.home-variants` | store.home-variants.manage | `Tenant\Panel\Store\HomeVariantsController@index` |
| POST | `/admin/store/home-variants` | `tenant.store.home-variants.select` | store.home-variants.manage | `Tenant\Panel\Store\HomeVariantsController@selectVariant` |
| GET | `/admin/store/mobile-app` | `tenant.store.mobile-app` | store.mobile-app.manage | `Tenant\Panel\Store\MobileAppController@index` |
| PUT | `/admin/store/mobile-app` | `tenant.store.mobile-app.save` | store.mobile-app.manage | `Tenant\Panel\Store\MobileAppController@save` |
| POST | `/admin/store/mobile-app/validate` | `tenant.store.mobile-app.validate` | store.mobile-app.manage | `Tenant\Panel\Store\MobileAppController@validateSave` |
| GET | `/admin/store/mobile-themes` | `tenant.store.mobile-themes` | store.mobile-app.manage | `Tenant\Panel\Store\MobileAppController@themes` |
| PUT | `/admin/store/mobile-themes/colors` | `tenant.store.mobile-themes.colors` | store.mobile-app.manage | `Tenant\Panel\Store\MobileAppController@saveColors` |
| POST | `/admin/store/mobile-themes/{themeSlug}/{variantKey}/activate` | `tenant.store.mobile-themes.activate` | store.mobile-app.manage | `Tenant\Panel\Store\MobileAppController@activate` |
| GET | `/admin/store/page-builder` | `tenant.store.page-builder` | store.page-builder.manage | `Tenant\Panel\Store\PageBuilderController@index` |
| POST | `/admin/store/page-builder/order` | `tenant.store.page-builder.order` | store.page-builder.manage | `Tenant\Panel\Store\PageBuilderController@updateOrder` |
| PATCH | `/admin/store/page-builder/sections/{section}/visibility` | `tenant.store.page-builder.sections.visibility` | store.page-builder.manage | `Tenant\Panel\Store\PageBuilderController@toggleVisibility` |
| GET | `/admin/store/pages` | `tenant.store.pages` | store.pages.manage | `Tenant\Panel\Store\PagesController@index` |
| POST | `/admin/store/pages` | `tenant.store.pages.store` | store.pages.manage | `Tenant\Panel\Store\PageFormController@store` |
| GET | `/admin/store/pages/create` | `tenant.store.pages.create` | store.pages.manage | `Tenant\Panel\Store\PageFormController@create` |
| GET | `/admin/store/pages/data` | `tenant.store.pages.data` | store.pages.manage | `Tenant\Panel\Store\PagesController@data` |
| POST | `/admin/store/pages/validate` | `tenant.store.pages.validate` | store.pages.manage | `Tenant\Panel\Store\PageFormController@validateStore` |
| PUT | `/admin/store/pages/{page}` | `tenant.store.pages.update` | store.pages.manage | `Tenant\Panel\Store\PageFormController@update` |
| DELETE | `/admin/store/pages/{page}` | `tenant.store.pages.destroy` | store.pages.manage | `Tenant\Panel\Store\PagesController@destroy` |
| GET | `/admin/store/pages/{page}/edit` | `tenant.store.pages.edit` | store.pages.manage | `Tenant\Panel\Store\PageFormController@edit` |
| POST | `/admin/store/pages/{page}/validate` | `tenant.store.pages.validate.update` | store.pages.manage | `Tenant\Panel\Store\PageFormController@validateUpdate` |
| GET | `/admin/store/themes` | `tenant.store.themes` | store.themes.manage | `Tenant\Panel\Store\ThemesController@index` |
| POST | `/admin/store/themes/{theme}/activate` | `tenant.store.themes.activate` | store.themes.manage | `Tenant\Panel\Store\ThemesController@activate` |
| GET | `/admin/store/themes/{theme}/countries` | `tenant.store.themes.countries` | store.themes.manage | `Tenant\Panel\Store\ThemesController@countries` |
| PUT | `/admin/store/themes/{theme}/countries` | `tenant.store.themes.countries.update` | store.themes.manage | `Tenant\Panel\Store\ThemesController@updateCountries` |
| GET | `/admin/store/themes/{theme}/country-variants` | `tenant.store.themes.country-variants` | store.themes.manage | `Tenant\Panel\Store\ThemesController@countryVariants` |
| PUT | `/admin/store/themes/{theme}/country-variants` | `tenant.store.themes.country-variants.update` | store.themes.manage | `Tenant\Panel\Store\ThemesController@updateCountryVariants` |
| POST | `/admin/store/themes/{theme}/deactivate` | `tenant.store.themes.deactivate` | store.themes.manage | `Tenant\Panel\Store\ThemesController@deactivate` |
| POST | `/admin/store/themes/{theme}/variants/{variant}/activate` | `tenant.store.themes.variants.activate` | store.themes.manage | `Tenant\Panel\Store\ThemesController@activateVariant` |

- **`Http\Controllers\Tenant\Panel\Store\AppearanceController`** — [`app/Http/Controllers/Tenant/Panel/Store/AppearanceController.php`](../../app/Http/Controllers/Tenant/Panel/Store/AppearanceController.php) (322 lines)
  - Actions/methods: `index()`, `saveGeneral()`, `validateGeneral()`, `saveColors()`, `validateColors()`, `resetColors()`, `savePromoBanner()`, `validatePromoBanner()`, `saveTopHeaderLinks()`, `validateTopHeaderLinks()`, `saveFooter()`, `validateFooter()`
  - Views: `tenant.pages.store.appearance.index`
  - FormRequest `SaveColorsRequest`: `fonts`: nullable', 'array; `fonts.ltr`: nullable', 'string', Rule::in(array_keys(ThemeFonts::LTR)); `fonts.rtl`: nullable', 'string', Rule::in(array_keys(ThemeFonts::RTL))
  - FormRequest `SaveFooterRequest`: `footer_logo_mode`: required', 'in:text,image; `footer_logo_text_ar`: nullable', 'string', 'max:60; `footer_logo_text_en`: nullable', 'string', 'max:60; `footer_logo_color`: required', 'string', 'regex:/^#[0-9a-fA-F; `footer_logo_bg_color`: required', 'string', 'regex:/^(#[0-9a-fA-F; `footer_logo_shape`: required', 'in:rectangle,rounded; `footer_logo_font_ar`: required', 'in:' . $fontKeys; `footer_logo_font_en`: required', 'in:' . $fontKeys; `footer_logo_upload_ar`: nullable', 'image', 'max:2048; `footer_logo_upload_en`: nullable', 'image', 'max:2048; `footer_logo_width`: nullable', 'integer', 'min:20', 'max:800
  - FormRequest `SavePromoBannerRequest`: `promo_banner_title`: nullable', 'string', 'max:120; `promo_banner_subtitle`: nullable', 'string', 'max:255; `promo_banner_link`: nullable', 'url', 'max:500; `promo_banner_cta_text`: nullable', 'string', 'max:40; `promo_banner_image`: nullable', 'image', 'max:4096; `promo_banner_mobile_image`: nullable', 'image', 'max:4096; `remove_promo_banner_image`: nullable', 'boolean; `remove_promo_banner_mobile_image`: nullable', 'boolean
- **`Http\Controllers\Tenant\Panel\Store\SocialLinkController`** — [`app/Http/Controllers/Tenant/Panel/Store/SocialLinkController.php`](../../app/Http/Controllers/Tenant/Panel/Store/SocialLinkController.php) (74 lines)
  - Actions/methods: `index()`, `show()`, `store()`, `validateStore()`, `update()`, `validateUpdate()`, `destroy()`
  - Views: `tenant.pages.store.appearance._cols.social-icon`, `tenant.pages.store.appearance._cols.social-url`, `tenant.pages.store.appearance._cols.social-actions`
  - FormRequest `SaveSocialLinkRequest`: `icon`: required', 'string', 'max:50', 'in:' . implode(',', array_column(SocialMediaIconEnum::case; `url`: required', 'url', 'max:500; `serial_number`: required', 'integer', 'min:0
- **`Http\Controllers\Tenant\Panel\Store\BannerController`** — [`app/Http/Controllers/Tenant/Panel/Store/BannerController.php`](../../app/Http/Controllers/Tenant/Panel/Store/BannerController.php) (164 lines)
  - Actions/methods: `index()`, `list()`, `show()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`, `destroy()`, `updateOrder()`
  - Views: `tenant.pages.store.banners.index`
  - FormRequest `SaveBannerRequest`: `url`: nullable', 'url', 'max:500; `serial_number`: required', 'integer', 'min:0; `banner_image`: nullable', 'image', 'max:2048
- **`Http\Controllers\Tenant\Panel\Store\BladeThemeController`** — [`app/Http/Controllers/Tenant/Panel/Store/BladeThemeController.php`](../../app/Http/Controllers/Tenant/Panel/Store/BladeThemeController.php) (129 lines)
  - Actions/methods: `index()`, `upload()`, `validateUpload()`, `deactivate()`, `destroy()`
  - Views: `tenant.pages.store.blade-theme._cols.status`, `tenant.pages.store.blade-theme._cols.actions`, `tenant.pages.store.blade-theme.index`
  - FormRequest `UploadBladeThemeRequest`: `theme_zip`: required', 'file', 'mimes:zip', 'max:20480
- **`Http\Controllers\Tenant\BladeThemeStarterKitController`** — [`app/Http/Controllers/Tenant/BladeThemeStarterKitController.php`](../../app/Http/Controllers/Tenant/BladeThemeStarterKitController.php) (55 lines)
  - Actions/methods: `download()`
- **`Http\Controllers\Tenant\Panel\Store\CouponController`** — [`app/Http/Controllers/Tenant/Panel/Store/CouponController.php`](../../app/Http/Controllers/Tenant/Panel/Store/CouponController.php) (193 lines)
  - Actions/methods: `index()`, `list()`, `data()`, `show()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`, `destroy()`
  - Views: `tenant.pages.store.coupons.index`, `tenant.pages.store.coupons._cols.actions`
  - FormRequest `SaveCouponRequest`: `code`: required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($couponId), // Dedu; `name_text`: required', 'string', 'max:255; `type`: required', 'in:fixed,percentage; `value`: required', 'numeric', 'min:0; `minimum_spend`: nullable', 'numeric', 'min:0; `start_date`: nullable', 'date; `end_date`: nullable', 'date', 'after_or_equal:start_date
- **`Http\Controllers\Tenant\Panel\Store\FlashSaleController`** — [`app/Http/Controllers/Tenant/Panel/Store/FlashSaleController.php`](../../app/Http/Controllers/Tenant/Panel/Store/FlashSaleController.php) (350 lines)
  - Actions/methods: `index()`, `list()`, `data()`, `searchProducts()`, `show()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`, `destroy()`, `availableForProduct()`, `attachProduct()`
  - Views: `tenant.pages.store.flash-sales.index`, `tenant.pages.store.flash-sales._cols.product`, `tenant.pages.store.flash-sales._cols.banner`, `tenant.pages.store.flash-sales._cols.actions`
  - FormRequest `SaveFlashSaleRequest`: `product_ids`: required', 'array', 'min:1; `product_ids.*`: integer', 'exists:products,id; `discount_percentage`: required', 'numeric', 'min:0', 'max:100; `start_date`: required', 'date; `end_date`: required', 'date', 'after_or_equal:start_date; `active`: boolean; `banner_image`: nullable', 'image
- **`Http\Controllers\Tenant\Panel\Store\HomeVariantsController`** — [`app/Http/Controllers/Tenant/Panel/Store/HomeVariantsController.php`](../../app/Http/Controllers/Tenant/Panel/Store/HomeVariantsController.php) (133 lines)
  - Actions/methods: `index()`, `selectVariant()`
  - Views: `tenant.pages.store.home-variants._cols.variant-select`, `tenant.pages.store.home-variants._cols.colors`, `tenant.pages.store.home-variants.index`
- **`Http\Controllers\Tenant\Panel\Store\MobileAppController`** — [`app/Http/Controllers/Tenant/Panel/Store/MobileAppController.php`](../../app/Http/Controllers/Tenant/Panel/Store/MobileAppController.php) (199 lines)
  - Doc: Tenant panel: "Mobile App" settings + "App Themes" (independent of the web theme).
  - Actions/methods: `index()`, `save()`, `validateSave()`, `themes()`, `activate()`, `saveColors()`
  - Validation: `app_name`: nullable', 'string', 'max:60; `logo`: nullable', 'image', 'max:2048; `splash`: nullable', 'image', 'max:4096; `features`: nullable', 'array; `features.*`: boolean; `nav_items`: nullable', 'array', 'max:5; `nav_items.*`: string', Rule::in(self::NAV_ITEMS); `default_locale`: nullable', 'string', 'max:10; `default_currency`: nullable', 'string', 'max:10; `support_email`: nullable', 'email', 'max:190; `support_phone`: nullable', 'string', 'max:30; `support_whatsapp`: nullable', 'string', 'max:30; `play_store_url`: nullable', 'url', 'max:255; `app_store_url`: nullable', 'url', 'max:255; `min_app_version`: nullable', 'regex:/^\d+(\.\d+){0,2}$/; `maintenance`: nullable', 'boolean; `maintenance_message`: nullable', 'string', 'max:500
  - Views: `tenant.pages.store.mobile.index`, `tenant.pages.store.mobile.themes`
- **`Http\Controllers\Tenant\Panel\Store\PageBuilderController`** — [`app/Http/Controllers/Tenant/Panel/Store/PageBuilderController.php`](../../app/Http/Controllers/Tenant/Panel/Store/PageBuilderController.php) (241 lines)
  - Actions/methods: `index()`, `updateOrder()`, `toggleVisibility()`
  - Views: `tenant.pages.store.page-builder.index`
- **`Http\Controllers\Tenant\Panel\Store\PagesController`** — [`app/Http/Controllers/Tenant/Panel/Store/PagesController.php`](../../app/Http/Controllers/Tenant/Panel/Store/PagesController.php) (96 lines)
  - Actions/methods: `index()`, `data()`, `destroy()`
  - Views: `tenant.pages.store.pages.index`, `tenant.pages.store.pages._cols.status`, `tenant.pages.store.pages._cols.actions`
- **`Http\Controllers\Tenant\Panel\Store\PageFormController`** — [`app/Http/Controllers/Tenant/Panel/Store/PageFormController.php`](../../app/Http/Controllers/Tenant/Panel/Store/PageFormController.php) (108 lines)
  - Actions/methods: `create()`, `edit()`, `store()`, `update()`, `validateStore()`, `validateUpdate()`
  - Views: `tenant.pages.store.pages.form`
  - FormRequest `SavePageRequest`: `slug`: nullable', 'string', 'max:150', Rule::unique('pages', 'slug')->ignore($pageId); `active`: boolean
- **`Http\Controllers\Tenant\Panel\Store\ThemesController`** — [`app/Http/Controllers/Tenant/Panel/Store/ThemesController.php`](../../app/Http/Controllers/Tenant/Panel/Store/ThemesController.php) (361 lines)
  - Actions/methods: `index()`, `activate()`, `activateVariant()`, `deactivate()`, `countries()`, `updateCountries()`, `countryVariants()`, `updateCountryVariants()`
  - Views: `tenant.pages.store.themes.index`
  - FormRequest `UpdateThemeCountriesRequest`: `country_ids`: nullable', 'array; `country_ids.*`: integer

### `subscription-payment`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/subscription-payment/{gateway}/cancel` | `tenant.subscription-payment.cancel` | — | `Tenant\SubscriptionPaymentController@cancel` |
| GET|POST | `/admin/subscription-payment/{gateway}/success` | `tenant.subscription-payment.success` | — | `Tenant\SubscriptionPaymentController@success` |
| GET | `/admin/subscription-payment/{gateway}/{packageId}/{type}` | `tenant.subscription-payment.charge` | — | `Tenant\SubscriptionPaymentController@charge` |

- **`Http\Controllers\Tenant\SubscriptionPaymentController`** — [`app/Http/Controllers/Tenant/SubscriptionPaymentController.php`](../../app/Http/Controllers/Tenant/SubscriptionPaymentController.php) (162 lines)
  - Actions/methods: `charge()`, `success()`, `cancel()`

### `support`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/support` | `tenant.support.index` | — | `Tenant\Panel\Support\TicketController@index` |
| POST | `/admin/support` | `tenant.support.store` | — | `Tenant\Panel\Support\TicketController@store` |
| GET | `/admin/support/data` | `tenant.support.data` | — | `Tenant\Panel\Support\TicketController@data` |
| GET | `/admin/support/new` | `tenant.support.create` | — | `Tenant\Panel\Support\TicketController@create` |
| POST | `/admin/support/validate` | `tenant.support.validate` | — | `Tenant\Panel\Support\TicketController@validateStore` |
| GET | `/admin/support/{ticketId}` | `tenant.support.show` | — | `Tenant\Panel\Support\TicketController@show` |
| POST | `/admin/support/{ticketId}/replies` | `tenant.support.replies` | — | `Tenant\Panel\Support\TicketController@reply` |
| POST | `/admin/support/{ticketId}/replies/validate` | `tenant.support.replies.validate` | — | `Tenant\Panel\Support\TicketController@validateReply` |
| GET | `/admin/support/{ticketId}/thread` | `tenant.support.thread` | — | `Tenant\Panel\Support\TicketController@thread` |

- **`Http\Controllers\Tenant\Panel\Support\TicketController`** — [`app/Http/Controllers/Tenant/Panel/Support/TicketController.php`](../../app/Http/Controllers/Tenant/Panel/Support/TicketController.php) (217 lines)
  - Actions/methods: `index()`, `data()`, `create()`, `store()`, `validateStore()`, `show()`, `reply()`, `validateReply()`, `thread()`
  - Views: `tenant.pages.support.tickets.index`, `tenant.pages.support.tickets.create`, `tenant.pages.support.tickets.show`
  - FormRequest `ReplyTicketRequest`: `reply`: required', 'string', 'min:2', 'max:5000
  - FormRequest `StoreTicketRequest`: `subject`: required', 'string', 'max:255; `category`: required', 'string', 'in:'.implode(',', array_keys(SupportTicket::categoryOptions())); `body`: required', 'string', 'min:10', 'max:5000

### `todays-chances`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/todays-chances` | `tenant.todays-chances` | dashboard.view | `Tenant\Panel\Catalog\TodayChancesController@index` |

- **`Http\Controllers\Tenant\Panel\Catalog\TodayChancesController`** — [`app/Http/Controllers/Tenant/Panel/Catalog/TodayChancesController.php`](../../app/Http/Controllers/Tenant/Panel/Catalog/TodayChancesController.php) (67 lines)
  - Actions/methods: `index()`
  - Views: `tenant.pages.todays-chances.index`

### `vendor-settlement`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/vendor-settlement/{gateway}/cancel` | `tenant.vendor-settlement.cancel` | — | `Tenant\VendorSettlementPaymentController@cancel` |
| GET|POST | `/admin/vendor-settlement/{gateway}/success` | `tenant.vendor-settlement.success` | — | `Tenant\VendorSettlementPaymentController@success` |
| GET | `/admin/vendor-settlement/{gateway}/{orderId}` | `tenant.vendor-settlement.charge` | — | `Tenant\VendorSettlementPaymentController@charge` |

- **`Http\Controllers\Tenant\VendorSettlementPaymentController`** — [`app/Http/Controllers/Tenant/VendorSettlementPaymentController.php`](../../app/Http/Controllers/Tenant/VendorSettlementPaymentController.php) (311 lines)
  - Doc: VendorSettlementPaymentController  Handles the real-money payment flow when a tenant (vendor) settles what they owe the central platform for product cost + shipping on a fulfilled order.  Flow (platform_profit owed by the vendor, see OrderProfitCalculator::vendorOwnPlatformForOrder):   1. VendorPurc
  - Actions/methods: `charge()`, `success()`, `cancel()`

### `widgets`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/admin/widgets/setup-progress` | `tenant.widgets.setup-progress` | — | `Tenant\Panel\Shell\SetupProgressController@show` |
| POST | `/admin/widgets/setup-progress/pages-reviewed` | `tenant.widgets.setup-progress.pages-reviewed` | — | `Tenant\Panel\Shell\SetupProgressController@pagesReviewed` |

- **`Http\Controllers\Tenant\Panel\Shell\SetupProgressController`** — [`app/Http/Controllers/Tenant/Panel/Shell/SetupProgressController.php`](../../app/Http/Controllers/Tenant/Panel/Shell/SetupProgressController.php) (25 lines)
  - Actions/methods: `show()`, `pagesReviewed()`

## Affiliate panel (10 routes)


### `affiliate`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET|POST|PUT|PATCH|DELETE|OPTIONS | `/affiliate` | `affiliate.` | — | `Illuminate\Routing\RedirectController` |
| GET | `/affiliate/conversions` | `affiliate.conversions` | — | `Livewire\Affiliate\ConversionsPage` |
| GET | `/affiliate/dashboard` | `affiliate.dashboard` | — | `Livewire\Affiliate\DashboardPage` |
| GET | `/affiliate/links` | `affiliate.links` | — | `Livewire\Affiliate\LinksPage` |
| GET | `/affiliate/login` | `affiliate.login` | — | `Livewire\Affiliate\Auth\LoginPage` |
| POST | `/affiliate/logout` | `affiliate.logout` | — | `Affiliate\AuthController@logout` |
| GET | `/affiliate/payouts` | `affiliate.payouts` | — | `Livewire\Affiliate\PayoutsPage` |
| GET | `/affiliate/profile` | `affiliate.profile` | — | `Livewire\Affiliate\ProfilePage` |
| GET | `/affiliate/register` | `affiliate.register` | — | `Livewire\Affiliate\Auth\RegisterPage` |
| GET | `/affiliate/sso/{token}` | `affiliate.sso` | — | `Affiliate\SsoController` |

- **`Livewire\Affiliate\ConversionsPage`** — [`app/Livewire/Affiliate/ConversionsPage.php`](../../app/Livewire/Affiliate/ConversionsPage.php) (33 lines)
  - Public state / form fields: `statusFilter`
  - Actions/methods: `updatingStatusFilter()`
  - Views: `livewire.affiliate.conversions`, `layouts.affiliate`
- **`Livewire\Affiliate\DashboardPage`** — [`app/Livewire/Affiliate/DashboardPage.php`](../../app/Livewire/Affiliate/DashboardPage.php) (32 lines)
  - Views: `livewire.affiliate.dashboard`, `layouts.affiliate`
- **`Livewire\Affiliate\LinksPage`** — [`app/Livewire/Affiliate/LinksPage.php`](../../app/Livewire/Affiliate/LinksPage.php) (28 lines)
  - Views: `livewire.affiliate.links`, `layouts.affiliate`
- **`Livewire\Affiliate\Auth\LoginPage`** — [`app/Livewire/Affiliate/Auth/LoginPage.php`](../../app/Livewire/Affiliate/Auth/LoginPage.php) (65 lines)
  - Public state / form fields: `email`, `password`, `remember`
  - Actions/methods: `login()`
  - Validation: `email`: required', 'email; `password`: required', 'string; `remember`: boolean
  - Views: `livewire.auth.panel-login`
- **`Http\Controllers\Affiliate\AuthController`** — [`app/Http/Controllers/Affiliate/AuthController.php`](../../app/Http/Controllers/Affiliate/AuthController.php) (19 lines)
  - Actions/methods: `logout()`
- **`Livewire\Affiliate\PayoutsPage`** — [`app/Livewire/Affiliate/PayoutsPage.php`](../../app/Livewire/Affiliate/PayoutsPage.php) (56 lines)
  - Public state / form fields: `requestAmount`, `requestNote`
  - Actions/methods: `requestPayout()`
  - Validation: `requestAmount`: required', 'numeric', 'min:' . max(AffiliateSettings::minPayout(), 0.01); `requestNote`: nullable', 'string', 'max:500
  - Views: `livewire.affiliate.payouts`, `layouts.affiliate`
- **`Livewire\Affiliate\ProfilePage`** — [`app/Livewire/Affiliate/ProfilePage.php`](../../app/Livewire/Affiliate/ProfilePage.php) (72 lines)
  - Public state / form fields: `name`, `paypal_email`, `bank_name`, `bank_account`, `bank_iban`, `current_password`, `new_password`, `new_password_confirmation`
  - Actions/methods: `updateProfile()`, `updatePassword()`
  - Validation: `name`: required', 'string', 'max:100; `paypal_email`: nullable', 'email; `bank_name`: nullable', 'string', 'max:150; `bank_account`: nullable', 'string', 'max:100; `bank_iban`: nullable', 'string', 'max:100; `current_password`: required', 'string; `new_password`: required', 'string', 'min:8', 'confirmed
  - Views: `livewire.affiliate.profile`, `layouts.affiliate`
- **`Livewire\Affiliate\Auth\RegisterPage`** — [`app/Livewire/Affiliate/Auth/RegisterPage.php`](../../app/Livewire/Affiliate/Auth/RegisterPage.php) (51 lines)
  - Public state / form fields: `name`, `email`, `password`, `password_confirmation`
  - Actions/methods: `register()`
  - Validation: `name`: required', 'string', 'max:100; `email`: required', 'email', 'unique:affiliates,email; `password`: required', 'string', 'min:8', 'confirmed
  - Views: `livewire.affiliate.auth.register`
- **`Http\Controllers\Affiliate\SsoController`** — [`app/Http/Controllers/Affiliate/SsoController.php`](../../app/Http/Controllers/Affiliate/SsoController.php) (37 lines)

## Central website, registration & tenant-owner portal (35 routes)


### `(root)`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `//` | `website.home` | — | `Livewire\Website\LandingPage` |

- **`Livewire\Website\LandingPage`** — [`app/Livewire/Website/LandingPage.php`](../../app/Livewire/Website/LandingPage.php) (141 lines)
  - Public state / form fields: `newsletterEmail`, `newsletterSent`, `newsletterError`
  - Actions/methods: `subscribeNewsletter()`
  - Validation: `newsletterEmail`: required|email|max:255; `newsletterEmail.required`: Please enter your email address.; `newsletterEmail.email`: Please enter a valid email address.
  - Views: `livewire.website.landing`, `layouts.website`

### `about`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/about` | `website.about` | — | `Livewire\Website\AboutPage` |

- **`Livewire\Website\AboutPage`** — [`app/Livewire/Website/AboutPage.php`](../../app/Livewire/Website/AboutPage.php) (51 lines)
  - Views: `livewire.website.about`, `layouts.website`

### `auth`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/auth/apple/callback` | `website.social.apple.callback` | — | `Website\CentralSocialAuthController@handleAppleCallback` |
| GET | `/auth/apple/{intent}` | `website.social.apple` | — | `Website\CentralSocialAuthController@redirectToApple` |
| GET | `/auth/google/callback` | `website.social.google.callback` | — | `Website\CentralSocialAuthController@handleGoogleCallback` |
| GET | `/auth/google/{intent}` | `website.social.google` | — | `Website\CentralSocialAuthController@redirectToGoogle` |

- **`Http\Controllers\Website\CentralSocialAuthController`** — [`app/Http/Controllers/Website/CentralSocialAuthController.php`](../../app/Http/Controllers/Website/CentralSocialAuthController.php) (216 lines)
  - Actions/methods: `logout()`, `redirectToGoogle()`, `handleGoogleCallback()`, `redirectToApple()`, `handleAppleCallback()`
  - Views: `auth.social-popup-result`

### `blog`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/blog` | `website.blog` | — | `Livewire\Website\BlogListPage` |
| GET | `/blog/{slug}` | `website.blog.show` | — | `Livewire\Website\BlogDetailPage` |

- **`Livewire\Website\BlogListPage`** — [`app/Livewire/Website/BlogListPage.php`](../../app/Livewire/Website/BlogListPage.php) (80 lines)
  - Public state / form fields: `search`, `categoryId`
  - Actions/methods: `updatedSearch()`, `updatedCategoryId()`
  - Views: `livewire.website.blog-list`, `layouts.website`
- **`Livewire\Website\BlogDetailPage`** — [`app/Livewire/Website/BlogDetailPage.php`](../../app/Livewire/Website/BlogDetailPage.php) (55 lines)
  - Public state / form fields: `post`
  - Views: `livewire.website.blog-detail`, `layouts.website`

### `broadcasting`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/broadcasting/auth` | `broadcasting.auth` | — | `Admin\AdminAuthController@broadcastAuth` |

- **`Http\Controllers\Admin\AdminAuthController`** — [`app/Http/Controllers/Admin/AdminAuthController.php`](../../app/Http/Controllers/Admin/AdminAuthController.php) (35 lines)
  - Actions/methods: `logout()`, `broadcastAuth()`

### `contact`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/contact` | `website.contact` | — | `Livewire\Website\ContactPage` |

- **`Livewire\Website\ContactPage`** — [`app/Livewire/Website/ContactPage.php`](../../app/Livewire/Website/ContactPage.php) (90 lines)
  - Public state / form fields: `contactName`, `contactEmail`, `contactSubject`, `contactMessage`, `supportEmail`, `marketplaceName`, `officePhone`, `officeAddress`, `officeHours`, `sent`
  - Actions/methods: `send()`
  - Validation: `contactName`: required', 'string', 'max:255; `contactEmail`: required', 'email', 'max:255; `contactSubject`: required', 'string', 'max:255; `contactMessage`: required', 'string', 'min:10', 'max:5000
  - Views: `livewire.website.contact`, `layouts.website`

### `faqs`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/faqs` | `website.faqs` | — | `Livewire\Website\FaqsPage` |

- **`Livewire\Website\FaqsPage`** — [`app/Livewire/Website/FaqsPage.php`](../../app/Livewire/Website/FaqsPage.php) (44 lines)
  - Views: `livewire.website.faqs`, `layouts.website`

### `how-it-works`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/how-it-works` | `website.how-it-works` | — | `Livewire\Website\HowItWorksPage` |

- **`Livewire\Website\HowItWorksPage`** — [`app/Livewire/Website/HowItWorksPage.php`](../../app/Livewire/Website/HowItWorksPage.php) (14 lines)
  - Views: `livewire.website.how-it-works`, `layouts.website`

### `locale`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/locale/{locale}` | `locale.switch` | — | `Website\LocaleController@switch` |

- **`Http\Controllers\Website\LocaleController`** — [`app/Http/Controllers/Website/LocaleController.php`](../../app/Http/Controllers/Website/LocaleController.php) (20 lines)
  - Actions/methods: `switch()`

### `login`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/login` | `owner.login` | — | `Livewire\TenantOwner\Auth\LoginPage` |

- **`Livewire\TenantOwner\Auth\LoginPage`** — [`app/Livewire/TenantOwner/Auth/LoginPage.php`](../../app/Livewire/TenantOwner/Auth/LoginPage.php) (75 lines)
  - Public state / form fields: `email`, `password`, `remember`
  - Actions/methods: `login()`
  - Validation: `email`: required', 'email; `password`: required', 'string; `remember`: boolean
  - Views: `livewire.auth.panel-login`

### `maintenance`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/maintenance` | `website.maintenance` | — | `Livewire\Website\MaintenancePage` |

- **`Livewire\Website\MaintenancePage`** — [`app/Livewire/Website/MaintenancePage.php`](../../app/Livewire/Website/MaintenancePage.php) (18 lines)
  - Views: `livewire.website.maintenance`, `layouts.website`

### `my-account`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET|POST|PUT|PATCH|DELETE|OPTIONS | `/my-account` | `owner.dashboard` | — | `Illuminate\Routing\RedirectController` |
| GET | `/my-account/domains` | `owner.domains` | — | `Livewire\TenantOwner\DomainsList` |
| POST | `/my-account/logout` | `owner.logout` | — | `Website\CentralSocialAuthController@logout` |
| GET | `/my-account/select-tenant` | `owner.select-tenant` | — | `Livewire\TenantOwner\Auth\SelectTenantPage` |
| GET | `/my-account/subscriptions` | `owner.subscriptions` | — | `Livewire\TenantOwner\SubscriptionsList` |

- **`Livewire\TenantOwner\DomainsList`** — [`app/Livewire/TenantOwner/DomainsList.php`](../../app/Livewire/TenantOwner/DomainsList.php) (262 lines)
  - Public state / form fields: `showAddModal`, `newDomain`, `dnsCheckingDomain`, `dnsCheckResult`, `showEditModal`, `editingRequestId`, `editDomain`
  - Actions/methods: `getOwner()`, `openAddModal()`, `addDomain()`, `openEditModal()`, `updateDomain()`, `removeDomain()`, `checkDns()`, `secretAdminLogin()`
  - Validation: `newDomain`: required', 'string', 'max:255', 'regex:/^(?:[a-z0-9; `editDomain`: required', 'string', 'max:255', 'regex:/^(?:[a-z0-9
  - Views: `livewire.owner.domains-list`
- **`Http\Controllers\Website\CentralSocialAuthController`** — [`app/Http/Controllers/Website/CentralSocialAuthController.php`](../../app/Http/Controllers/Website/CentralSocialAuthController.php) (216 lines)
  - Actions/methods: `logout()`, `redirectToGoogle()`, `handleGoogleCallback()`, `redirectToApple()`, `handleAppleCallback()`
  - Views: `auth.social-popup-result`
- **`Livewire\TenantOwner\Auth\SelectTenantPage`** — [`app/Livewire/TenantOwner/Auth/SelectTenantPage.php`](../../app/Livewire/TenantOwner/Auth/SelectTenantPage.php) (54 lines)
  - Actions/methods: `getOwner()`, `selectTenant()`
  - Views: `livewire.owner.select-tenant`
- **`Livewire\TenantOwner\SubscriptionsList`** — [`app/Livewire/TenantOwner/SubscriptionsList.php`](../../app/Livewire/TenantOwner/SubscriptionsList.php) (35 lines)
  - Actions/methods: `getOwner()`
  - Views: `livewire.owner.subscriptions-list`

### `pages`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/pages/{slug}` | `website.page` | — | `Livewire\Website\StaticPageView` |

- **`Livewire\Website\StaticPageView`** — [`app/Livewire/Website/StaticPageView.php`](../../app/Livewire/Website/StaticPageView.php) (36 lines)
  - Public state / form fields: `slug`
  - Views: `livewire.website.static-page`, `layouts.website`

### `pricing`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/pricing` | `website.pricing` | — | `Livewire\Website\PricingPage` |

- **`Livewire\Website\PricingPage`** — [`app/Livewire/Website/PricingPage.php`](../../app/Livewire/Website/PricingPage.php) (37 lines)
  - Public state / form fields: `billingTerm`
  - Actions/methods: `setBillingTerm()`
  - Views: `livewire.website.pricing`, `layouts.website`

### `privacy`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/privacy` | `website.privacy` | — | `Livewire\Website\PrivacyPage` |

- **`Livewire\Website\PrivacyPage`** — [`app/Livewire/Website/PrivacyPage.php`](../../app/Livewire/Website/PrivacyPage.php) (14 lines)
  - Views: `livewire.website.privacy`, `layouts.website`

### `prod`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/prod` | `` | — | `Admin\DevToolsController@importNeozenaProducts` |

- **`Http\Controllers\Admin\DevToolsController`** — [`app/Http/Controllers/Admin/DevToolsController.php`](../../app/Http/Controllers/Admin/DevToolsController.php) (24 lines)
  - Actions/methods: `syncProductFixedShippingCosts()`, `importNeozenaProducts()`

### `register`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/register` | `website.register` | — | `Livewire\Website\RegisterPage` |
| GET | `/register/complete` | `website.register.complete` | — | `Livewire\Website\CompleteRegistrationPage` |
| GET | `/register/payment/{gateway}/{registration}/cancel` | `website.register.payment.cancel` | — | `Website\RegistrationPaymentController@cancel` |
| GET | `/register/payment/{gateway}/{registration}/charge` | `website.register.payment.charge` | — | `Website\RegistrationPaymentController@charge` |
| GET|POST | `/register/payment/{gateway}/{registration}/success` | `website.register.payment.success` | — | `Website\RegistrationPaymentController@success` |
| GET | `/register/setup/{tenantId}` | `website.store.onboarding` | — | `Livewire\Website\StoreOnboardingWizard` |

- **`Livewire\Website\RegisterPage`** — [`app/Livewire/Website/RegisterPage.php`](../../app/Livewire/Website/RegisterPage.php) (507 lines)
  - Public state / form fields: `step`, `email`, `phone`, `packageId`, `gatewayCode`, `stripeToken`, `authnetDescriptor`, `authnetValue`, `twocoToken`, `couponCode`, `couponError`, `couponDiscount`, `appliedCouponId`, `acceptCompliance`, `emailSent`, `resendStatus`, `oauthProvider`, `oauthProviderId`, `oauthName`, `oauthAvatar`, `centralDomain`
  - Actions/methods: `nextStep()`, `prevStep()`, `updatedGatewayCode()`, `updatedPackageId()`, `requiresPayment()`, `proceed()`, `socialAuthComplete()`, `applyCoupon()`, `createPendingAndSendEmail()`, `resend()`, `requireCompliance()`
  - Validation: `email`: required', 'email', 'max:255; `phone`: nullable', 'string', 'max:50', 'regex:/^\+?[0-9; `packageId`: nullable', 'integer', Rule::exists('packages', 'id'); `gatewayCode`: required', 'string', Rule::exists('payment_gateways', 'code')->where('status', ActivationS
  - Views: `livewire.website.register`, `layouts.website`
- **`Livewire\Website\CompleteRegistrationPage`** — [`app/Livewire/Website/CompleteRegistrationPage.php`](../../app/Livewire/Website/CompleteRegistrationPage.php) (490 lines)
  - Public state / form fields: `token`, `email`, `phone`, `planName`, `step`, `categoriesCount`, `name`, `password`, `passwordConfirmation`, `accountType`, `shopName`, `domainType`, `customDomain`, `profitPercentage`, `selectedCategoryIds`, `categoryPreviewTree`, `shopNameTaken`, `invalid`, `registered`, `hasDomainRequest`, `registeredTenantId`, `tenantDomain`, `centralDomain`, `dnsConnected`, `dnsChecking`, `dnsCheckResults`, `dnsRecordsForDisplay`
  - Actions/methods: `updatedDomainType()`, `updatedShopName()`, `updatedSelectedCategoryIds()`, `buildCategoryPreview()`, `submitStep1()`, `submitStep2()`, `prevStep()`, `checkDns()`, `continueToOnboarding()`
  - Validation: `name`: required', 'string', 'max:255; `password`: required', 'string', 'min:8; `passwordConfirmation`: required', 'same:password; `selectedCategoryIds`: required', 'array', 'min:1', "max:{$this->categoriesCount}",; `selectedCategoryIds.*`: integer', \App\Support\Catalog\LiveCatalogRule::exists(Category::class)
  - Views: `livewire.website.complete-registration`, `layouts.website`
- **`Http\Controllers\Website\RegistrationPaymentController`** — [`app/Http/Controllers/Website/RegistrationPaymentController.php`](../../app/Http/Controllers/Website/RegistrationPaymentController.php) (218 lines)
  - Actions/methods: `charge()`, `success()`, `cancel()`
- **`Livewire\Website\StoreOnboardingWizard`** — [`app/Livewire/Website/StoreOnboardingWizard.php`](../../app/Livewire/Website/StoreOnboardingWizard.php) (257 lines)
  - Public state / form fields: `step`, `tenantId`, `invalid`, `countryIds`, `languageIds`, `tenantDataLoaded`
  - Actions/methods: `isProvisioned()`, `isCatalogSynced()`, `nextStep()`, `skipStep()`, `toggleAllCountries()`, `submitCountries()`, `submitLanguages()`, `launch()`
  - Views: `livewire.website.store-onboarding-wizard`, `layouts.website`

### `sitemap.xml`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/sitemap.xml` | `website.sitemap` | — | `Website\SitemapController` |

- **`Http\Controllers\Website\SitemapController`** — [`app/Http/Controllers/Website/SitemapController.php`](../../app/Http/Controllers/Website/SitemapController.php) (84 lines)
  - Views: `sitemap.central`

### `templates`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/templates` | `website.templates` | — | `Livewire\Website\TemplatesPage` |

- **`Livewire\Website\TemplatesPage`** — [`app/Livewire/Website/TemplatesPage.php`](../../app/Livewire/Website/TemplatesPage.php) (25 lines)
  - Views: `livewire.website.templates`, `layouts.website`

### `terms`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/terms` | `website.terms` | — | `Livewire\Website\TermsPage` |

- **`Livewire\Website\TermsPage`** — [`app/Livewire/Website/TermsPage.php`](../../app/Livewire/Website/TermsPage.php) (14 lines)
  - Views: `livewire.website.terms`, `layouts.website`

### `upload-files`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/upload-files` | `upload-files` | — | `Website\FileUploadController@store` |

- **`Http\Controllers\Website\FileUploadController`** — [`app/Http/Controllers/Website/FileUploadController.php`](../../app/Http/Controllers/Website/FileUploadController.php) (27 lines)
  - Actions/methods: `store()`
  - Validation: `file`: required

### `{fallbackPlaceholder}`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/{fallbackPlaceholder}` | `website.not-found` | — | `Livewire\Website\NotFoundPage` |

- **`Livewire\Website\NotFoundPage`** — [`app/Livewire/Website/NotFoundPage.php`](../../app/Livewire/Website/NotFoundPage.php) (15 lines)
  - Views: `livewire.website.not-found`, `layouts.website`

## Tenant storefront (domain) (50 routes)


### `(root)`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `//` | `tenant.home` | — | `Livewire\Tenant\Storefront\HomePage` |

- **`Livewire\Tenant\Storefront\HomePage`** — [`app/Livewire/Tenant/Storefront/HomePage.php`](../../app/Livewire/Tenant/Storefront/HomePage.php) (416 lines)
  - Public state / form fields: `newInPage`, `bestSellingPage`, `flashPage`, `topRatedPage`, `recommendedPage`, `hasMoreNewIn`, `hasMoreBestSelling`, `hasMoreFlash`, `hasMoreTopRated`, `hasMoreRecommended`, `activeProductTab`, `mobileMenuOpen`
  - Actions/methods: `loadMoreNewIn()`, `loadMoreBestSelling()`, `loadMoreFlash()`, `loadMoreTopRated()`, `loadMoreRecommended()`, `addToCart()`, `showProductTab()`, `toggleMobileMenu()`

### `account`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/account/login` | `tenant.storefront.login` | — | `Livewire\Tenant\Storefront\AuthPage` |
| POST | `/account/logout` | `tenant.storefront.logout` | — | `Tenant\StorefrontSocialAuthController@logout` |
| GET | `/account/returns/{id}` | `tenant.storefront.return-detail` | — | `Livewire\Tenant\Storefront\ReturnDetailPage` |

- **`Livewire\Tenant\Storefront\AuthPage`** — [`app/Livewire/Tenant/Storefront/AuthPage.php`](../../app/Livewire/Tenant/Storefront/AuthPage.php) (138 lines)
  - Public state / form fields: `tab`, `loginEmail`, `loginPassword`, `regName`, `regEmail`, `regPhone`, `regPassword`, `regConfirm`, `mobileMenuOpen`
  - Actions/methods: `updatedTab()`, `login()`, `register()`, `toggleMobileMenu()`
  - Validation: `loginEmail`: required|email; `loginPassword`: required|min:6; `regName`: required|string|max:120; `regEmail`: required|email|unique:customers,email; `regPhone`: nullable|max:30; `regPassword`: required|min:8|same:regConfirm
- **`Http\Controllers\Tenant\StorefrontSocialAuthController`** — [`app/Http/Controllers/Tenant/StorefrontSocialAuthController.php`](../../app/Http/Controllers/Tenant/StorefrontSocialAuthController.php) (117 lines)
  - Actions/methods: `logout()`, `logoutPath()`, `redirectToGoogle()`, `handleGoogleCallback()`, `redirectToApple()`, `handleAppleCallback()`
- **`Livewire\Tenant\Storefront\ReturnDetailPage`** — [`app/Livewire/Tenant/Storefront/ReturnDetailPage.php`](../../app/Livewire/Tenant/Storefront/ReturnDetailPage.php) (212 lines)
  - Public state / form fields: `returnId`, `replyText`, `replyPhotos`, `replyVideo`, `confirmingWithdraw`, `mobileMenuOpen`
  - Actions/methods: `submitReply()`, `askWithdraw()`, `cancelWithdraw()`, `withdraw()`, `toggleMobileMenu()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `replyText`: required', 'string', 'min:10', 'max:2000; `replyPhotos`: nullable', 'array; `replyPhotos.*`: image', 'max:5120; `replyVideo`: nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200

### `app`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/app` | `tenant.app-landing` | — | `Mobile\AppLandingController` |

- **`Http\Controllers\Mobile\AppLandingController`** — [`app/Http/Controllers/Mobile/AppLandingController.php`](../../app/Http/Controllers/Mobile/AppLandingController.php) (45 lines)
  - Doc: "Open in app / Install" fallback on tenant domains. Preserves ?path= and the store identity (host).
  - Views: `storefront.app-landing`

### `auth`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/auth/apple` | `tenant.storefront.social.apple` | — | `Tenant\StorefrontSocialAuthController@redirectToApple` |
| POST | `/auth/apple/callback` | `tenant.storefront.social.apple.callback` | — | `Tenant\StorefrontSocialAuthController@handleAppleCallback` |
| GET | `/auth/google` | `tenant.storefront.social.google` | — | `Tenant\StorefrontSocialAuthController@redirectToGoogle` |
| GET | `/auth/google/callback` | `tenant.storefront.social.google.callback` | — | `Tenant\StorefrontSocialAuthController@handleGoogleCallback` |

- **`Http\Controllers\Tenant\StorefrontSocialAuthController`** — [`app/Http/Controllers/Tenant/StorefrontSocialAuthController.php`](../../app/Http/Controllers/Tenant/StorefrontSocialAuthController.php) (117 lines)
  - Actions/methods: `logout()`, `logoutPath()`, `redirectToGoogle()`, `handleGoogleCallback()`, `redirectToApple()`, `handleAppleCallback()`

### `best-selling`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/best-selling` | `tenant.storefront.best-selling` | — | `Livewire\Tenant\Storefront\BestSellingPage` |

- **`Livewire\Tenant\Storefront\BestSellingPage`** — [`app/Livewire/Tenant/Storefront/BestSellingPage.php`](../../app/Livewire/Tenant/Storefront/BestSellingPage.php) (160 lines)
  - Public state / form fields: `search`, `days`, `categoryId`, `imageSearchId`, `isSearchRoute`, `isImageSearch`, `perPage`, `hasMore`, `mobileMenuOpen`
  - Actions/methods: `updating()`, `loadMore()`, `setDays()`, `setCategory()`, `addToCart()`, `toggleMobileMenu()`

### `broadcasting`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET|POST | `/broadcasting/auth` | `` | — | `Illuminate\Broadcasting\BroadcastController@authenticate` |


### `cart`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/cart` | `tenant.storefront.cart` | — | `Livewire\Tenant\Storefront\CartPage` |
| POST | `/cart/add` | `tenant.storefront.cart.add` | — | `Tenant\CartController@add` |
| POST | `/cart/remove` | `tenant.storefront.cart.remove` | — | `Tenant\CartController@remove` |
| POST | `/cart/update` | `tenant.storefront.cart.update` | — | `Tenant\CartController@update` |

- **`Livewire\Tenant\Storefront\CartPage`** — [`app/Livewire/Tenant/Storefront/CartPage.php`](../../app/Livewire/Tenant/Storefront/CartPage.php) (309 lines)
  - Public state / form fields: `couponCode`, `mobileMenuOpen`
  - Actions/methods: `addToCart()`, `removeFromCart()`, `updateQty()`, `applyCoupon()`, `removeCoupon()`, `toggleMobileMenu()`
- **`Http\Controllers\Tenant\CartController`** — [`app/Http/Controllers/Tenant/CartController.php`](../../app/Http/Controllers/Tenant/CartController.php) (137 lines)
  - Actions/methods: `add()`, `remove()`, `update()`
  - Validation: `slug`: nullable|string; `product_id`: nullable|integer; `variant_id`: nullable|integer; `qty`: required|integer|min:1|max:99; `key`: required|string

### `categories-products`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/categories-products/{slug?}` | `tenant.storefront.category.products.json` | — | `Tenant\StorefrontSearchController@categoryProducts` |

- **`Http\Controllers\Tenant\StorefrontSearchController`** — [`app/Http/Controllers/Tenant/StorefrontSearchController.php`](../../app/Http/Controllers/Tenant/StorefrontSearchController.php) (174 lines)
  - Actions/methods: `autocomplete()`, `products()`, `categoryProducts()`, `autocompletePath()`, `productsPath()`, `categoryProductsPath()`

### `categories`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/categories/{slug?}` | `tenant.storefront.category` | — | `Livewire\Tenant\Storefront\CategoryPage` |

- **`Livewire\Tenant\Storefront\CategoryPage`** — [`app/Livewire/Tenant/Storefront/CategoryPage.php`](../../app/Livewire/Tenant/Storefront/CategoryPage.php) (321 lines)
  - Public state / form fields: `slug`, `keyword`, `sort`, `availability`, `productFlag`, `sale`, `ratings`, `section`, `min`, `max`, `options`, `perPage`, `hasMore`, `mobileMenuOpen`
  - Actions/methods: `updating()`, `loadMore()`, `toggleSale()`, `toggleProductFlag()`, `toggleRatings()`, `toggleOption()`, `applyFilters()`, `clearFilters()`, `addToCart()`, `toggleMobileMenu()`

### `checkout`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/checkout` | `tenant.storefront.checkout` | — | `Livewire\Tenant\Storefront\CheckoutPage` |
| GET | `/checkout/payment/{gateway}/cancel` | `tenant.payment.cancel` | — | `Tenant\PaymentController@cancel` |
| GET|POST | `/checkout/payment/{gateway}/success` | `tenant.payment.success` | — | `Tenant\PaymentController@success` |
| POST | `/checkout/payment/{gateway}/webhook` | `tenant.payment.webhook` | — | `Tenant\PaymentWebhookController@handle` |
| GET | `/checkout/payment/{gateway}/{orderUuid}` | `tenant.payment.charge` | — | `Tenant\PaymentController@charge` |

- **`Livewire\Tenant\Storefront\CheckoutPage`** — [`app/Livewire/Tenant/Storefront/CheckoutPage.php`](../../app/Livewire/Tenant/Storefront/CheckoutPage.php) (976 lines)
  - Public state / form fields: `data`, `mobileMenuOpen`
  - Actions/methods: `selectShippingAddress()`, `selectBillingAddress()`, `openAddressModal()`, `closeAddressModal()`, `saveNewAddress()`, `removeFromCart()`, `updateQty()`, `selectPayment()`, `applyCoupon()`, `removeCoupon()`, `placeOrder()`, `toggleMobileMenu()`
  - Validation: `data.modal.full_name`: required|string|max:120; `data.modal.email`: nullable|email|max:120; `data.modal.phone`: nullable|string|max:30; `data.modal.city`: nullable|string|max:100; `data.modal.state`: nullable|string|max:100; `data.modal.country`: nullable|string|max:100; `data.modal.country_id`: nullable|integer; `data.payment.method`: required|string; `data.shipping.name`: required|string|max:120; `data.shipping.email`: required|email|max:120; `data.shipping.phone`: required|string|max:30; `data.shipping.address`: required|string|max:300
- **`Http\Controllers\Tenant\PaymentController`** — [`app/Http/Controllers/Tenant/PaymentController.php`](../../app/Http/Controllers/Tenant/PaymentController.php) (334 lines)
  - Doc: PaymentController  Handles the payment lifecycle for storefront customers:   1. charge()  — create a gateway charge for a pending order   2. success() — verify the gateway callback and mark the order as paid   3. cancel()  — handle a user-cancelled payment  The controller expects the customer to alr
  - Actions/methods: `charge()`, `success()`, `confirmPayment()`, `cancel()`
- **`Http\Controllers\Tenant\PaymentWebhookController`** — [`app/Http/Controllers/Tenant/PaymentWebhookController.php`](../../app/Http/Controllers/Tenant/PaymentWebhookController.php) (73 lines)
  - Doc: PaymentWebhookController  POST /checkout/payment/{gateway}/webhook  Inbound endpoint tenants paste into their own gateway dashboard so the gateway can notify the platform of async events (capture confirmation, refunds, disputes). This is separate from success()/cancel() in PaymentController, which h
  - Actions/methods: `handle()`

### `favorites`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/favorites` | `tenant.storefront.favorites` | — | `Livewire\Tenant\Storefront\FavoritesPage` |
| GET | `/favorites/ids` | `tenant.storefront.favorites.ids` | — | `Tenant\FavoriteController@ids` |
| GET | `/favorites/list` | `tenant.storefront.favorites.list` | — | `Tenant\FavoriteController@list` |
| POST | `/favorites/toggle` | `tenant.storefront.favorites.toggle` | — | `Tenant\FavoriteController@toggle` |

- **`Livewire\Tenant\Storefront\FavoritesPage`** — [`app/Livewire/Tenant/Storefront/FavoritesPage.php`](../../app/Livewire/Tenant/Storefront/FavoritesPage.php) (75 lines)
  - Public state / form fields: `mobileMenuOpen`
  - Actions/methods: `cartData()`, `toggleMobileMenu()`, `favoriteDisplayData()`
- **`Http\Controllers\Tenant\FavoriteController`** — [`app/Http/Controllers/Tenant/FavoriteController.php`](../../app/Http/Controllers/Tenant/FavoriteController.php) (97 lines)
  - Actions/methods: `toggle()`, `list()`, `ids()`

### `full-star`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/full-star` | `tenant.storefront.full-star` | — | `Livewire\Tenant\Storefront\FullStarPage` |

- **`Livewire\Tenant\Storefront\FullStarPage`** — [`app/Livewire/Tenant/Storefront/FullStarPage.php`](../../app/Livewire/Tenant/Storefront/FullStarPage.php) (81 lines)
  - Public state / form fields: `search`, `categoryId`, `mobileMenuOpen`
  - Actions/methods: `addToCart()`, `toggleMobileMenu()`

### `new-in`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/new-in` | `tenant.storefront.new-in` | — | `Livewire\Tenant\Storefront\NewInPage` |

- **`Livewire\Tenant\Storefront\NewInPage`** — [`app/Livewire/Tenant/Storefront/NewInPage.php`](../../app/Livewire/Tenant/Storefront/NewInPage.php) (75 lines)
  - Public state / form fields: `search`, `categoryId`, `mobileMenuOpen`
  - Actions/methods: `addToCart()`, `toggleMobileMenu()`

### `not-found`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/not-found` | `tenant.storefront.not-found` | — | `Livewire\Tenant\Storefront\NotFoundPage` |

- **`Livewire\Tenant\Storefront\NotFoundPage`** — [`app/Livewire/Tenant/Storefront/NotFoundPage.php`](../../app/Livewire/Tenant/Storefront/NotFoundPage.php) (25 lines)
  - Public state / form fields: `mobileMenuOpen`
  - Actions/methods: `toggleMobileMenu()`

### `offers`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/offers` | `tenant.storefront.offers` | — | `Livewire\Tenant\Storefront\OffersPage` |

- **`Livewire\Tenant\Storefront\OffersPage`** — [`app/Livewire/Tenant/Storefront/OffersPage.php`](../../app/Livewire/Tenant/Storefront/OffersPage.php) (140 lines)
  - Public state / form fields: `sort`, `categoryId`, `flashOnly`, `minDiscount`, `perPage`, `mobileMenuOpen`
  - Actions/methods: `addToCart()`, `updating()`, `loadMore()`, `setCategory()`, `clearFilters()`, `toggleMobileMenu()`

### `orders`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/orders/{uuid}/invoice` | `tenant.storefront.order-invoice` | — | `Tenant\StorefrontInvoiceController@show` |
| GET | `/orders/{uuid}/return` | `tenant.storefront.order-return` | — | `Livewire\Tenant\Storefront\RequestReturnForm` |
| GET | `/orders/{uuid}/status` | `tenant.storefront.order-status` | — | `Livewire\Tenant\Storefront\OrderStatusPage` |
| GET | `/orders/{uuid}/tracking` | `tenant.storefront.order-tracking` | — | `Livewire\Tenant\Storefront\OrderTrackingPage` |

- **`Http\Controllers\Tenant\StorefrontInvoiceController`** — [`app/Http/Controllers/Tenant/StorefrontInvoiceController.php`](../../app/Http/Controllers/Tenant/StorefrontInvoiceController.php) (119 lines)
  - Actions/methods: `download()`, `show()`
  - Views: `tenant.storefront.invoice`
- **`Livewire\Tenant\Storefront\RequestReturnForm`** — [`app/Livewire/Tenant/Storefront/RequestReturnForm.php`](../../app/Livewire/Tenant/Storefront/RequestReturnForm.php) (307 lines)
  - Doc: Storefront return / exchange request form (RETURN_EXCHANGE_REFUND_PLAN.md B.5 / B.6 / B.8): quantity (≤ units left), resolution (refund / exchange + replacement option), return method, reason, description, photos / video and notes, with a live refund estimate. All rules are the shared ReturnRequestS
  - Public state / form fields: `uuid`, `orderItemId`, `reason`, `description`, `quantity`, `type`, `returnMethod`, `customerNote`, `replacementVariantId`, `photos`, `video`, `validationErrors`, `mobileMenuOpen`
  - Actions/methods: `updated()`, `incrementQuantity()`, `decrementQuantity()`, `removePhoto()`, `submit()`, `toggleMobileMenu()`, `_startUpload()`, `_finishUpload()`, `_uploadErrored()`, `_removeUpload()`
  - Validation: `reason`: required', 'string', 'in:'.implode(',', array_column(ReturnReason::cases(), 'value')); `description`: nullable', 'string', 'max:2000; `quantity`: required', 'integer', 'min:1; `type`: required', 'in:'.implode(',', array_column(ReturnType::cases(), 'value')); `returnMethod`: required', 'in:'.implode(',', array_column(ReturnMethod::cases(), 'value')); `customerNote`: nullable', 'string', 'max:1000; `replacementVariantId`: nullable', 'integer', 'required_if:type,'.ReturnType::Exchange->value; `photos`: nullable', 'array; `photos.*`: image', 'max:'.config('returns.photo_max_kb'); `video`: nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:'.config('return
- **`Livewire\Tenant\Storefront\OrderStatusPage`** — [`app/Livewire/Tenant/Storefront/OrderStatusPage.php`](../../app/Livewire/Tenant/Storefront/OrderStatusPage.php) (374 lines)
  - Public state / form fields: `uuid`, `mode`, `showReviewModal`, `reviewProductId`, `reviewStars`, `reviewComment`, `mobileMenuOpen`, `cancelOrderUuid`, `cancelStep`, `cancelReason`, `cancelNote`
  - Actions/methods: `showTracking()`, `showDetails()`, `openReviewModal()`, `updatedReviewProductId()`, `setReviewStars()`, `submitReview()`, `closeReviewModal()`, `reorder()`, `toggleMobileMenu()`, `openCancelModal()`, `continueCancel()`, `backToCancelReason()`, `closeCancelModal()`, `confirmCancelOrder()`, `customerCancellationReasons()`
  - Validation: `reviewProductId`: required|integer; `reviewStars`: required|integer|min:1|max:5; `reviewComment`: nullable|string|max:1000
- **`Livewire\Tenant\Storefront\OrderTrackingPage`** — [`app/Livewire/Tenant/Storefront/OrderTrackingPage.php`](../../app/Livewire/Tenant/Storefront/OrderTrackingPage.php) (48 lines)
  - Public state / form fields: `uuid`, `mobileMenuOpen`
  - Actions/methods: `showDetails()`, `toggleMobileMenu()`

### `pages`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/pages/{slug}` | `tenant.storefront.page` | — | `Livewire\Tenant\Storefront\PageView` |

- **`Livewire\Tenant\Storefront\PageView`** — [`app/Livewire/Tenant/Storefront/PageView.php`](../../app/Livewire/Tenant/Storefront/PageView.php) (45 lines)
  - Public state / form fields: `slug`, `mobileMenuOpen`
  - Actions/methods: `toggleMobileMenu()`

### `preview`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/preview` | `preview` | — | `Livewire\Tenant\Storefront\HomePage` |
| GET | `/preview/api/home/tabbed-products` | `preview.home.tabbed-products` | — | `Tenant\HomePageController` |
| GET | `/preview/api/products` | `preview.products.json` | — | `Tenant\StorefrontSearchController@products` |
| GET | `/preview/categories-products/{slug?}` | `preview.category.products.json` | — | `Tenant\StorefrontSearchController@categoryProducts` |
| GET | `/preview/search/autocomplete` | `preview.search.autocomplete` | — | `Tenant\StorefrontSearchController@autocomplete` |

- **`Livewire\Tenant\Storefront\HomePage`** — [`app/Livewire/Tenant/Storefront/HomePage.php`](../../app/Livewire/Tenant/Storefront/HomePage.php) (416 lines)
  - Public state / form fields: `newInPage`, `bestSellingPage`, `flashPage`, `topRatedPage`, `recommendedPage`, `hasMoreNewIn`, `hasMoreBestSelling`, `hasMoreFlash`, `hasMoreTopRated`, `hasMoreRecommended`, `activeProductTab`, `mobileMenuOpen`
  - Actions/methods: `loadMoreNewIn()`, `loadMoreBestSelling()`, `loadMoreFlash()`, `loadMoreTopRated()`, `loadMoreRecommended()`, `addToCart()`, `showProductTab()`, `toggleMobileMenu()`
- **`Http\Controllers\Tenant\HomePageController`** — [`app/Http/Controllers/Tenant/HomePageController.php`](../../app/Http/Controllers/Tenant/HomePageController.php) (62 lines)
  - Views: `themes.elora.pages._product-card`
- **`Http\Controllers\Tenant\StorefrontSearchController`** — [`app/Http/Controllers/Tenant/StorefrontSearchController.php`](../../app/Http/Controllers/Tenant/StorefrontSearchController.php) (174 lines)
  - Actions/methods: `autocomplete()`, `products()`, `categoryProducts()`, `autocompletePath()`, `productsPath()`, `categoryProductsPath()`

### `products`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/products/{slug}` | `tenant.storefront.product` | — | `Livewire\Tenant\Storefront\ProductPage` |

- **`Livewire\Tenant\Storefront\ProductPage`** — [`app/Livewire/Tenant/Storefront/ProductPage.php`](../../app/Livewire/Tenant/Storefront/ProductPage.php) (504 lines)
  - Public state / form fields: `slug`, `selectedVariantId`, `qty`, `showReviewsModal`, `reviewsLimit`, `viewContentTracked`, `mobileMenuOpen`
  - Actions/methods: `openReviewsModal()`, `closeReviewsModal()`, `loadMoreReviews()`, `addToCart()`, `toggleMobileMenu()`

### `profile`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/profile` | `tenant.storefront.profile` | — | `Livewire\Tenant\Storefront\ProfilePage` |

- **`Livewire\Tenant\Storefront\ProfilePage`** — [`app/Livewire/Tenant/Storefront/ProfilePage.php`](../../app/Livewire/Tenant/Storefront/ProfilePage.php) (480 lines)
  - Public state / form fields: `activeTab`, `statusFilter`, `showAddressModal`, `editingAddressId`, `addrLabel`, `addrFullName`, `addrPhone`, `addrLine1`, `addrCity`, `addrState`, `addrCountry`, `addrCountryId`, `addrIsDefault`, `mobileMenuOpen`, `cancelOrderUuid`, `cancelStep`, `cancelReason`, `cancelNote`
  - Actions/methods: `setTab()`, `openAddressModal()`, `closeAddressModal()`, `saveAddress()`, `deleteAddress()`, `setDefaultAddress()`, `filterStatus()`, `logout()`, `reorder()`, `cancelOrder()`, `submitReview()`, `toggleMobileMenu()`, `openCancelModal()`, `continueCancel()`, `backToCancelReason()`, `closeCancelModal()`, `confirmCancelOrder()`, `customerCancellationReasons()`, `favoriteDisplayData()`
  - Validation: `addrFullName`: required|string|max:100; `addrCity`: required|string|max:100; `addrCountryId`: required', 'integer', Rule::exists('mysql.countries', 'id'); `addrPhone`: nullable|string|max:30; `addrState`: nullable|string|max:100; `addrLabel`: nullable|string|max:50

### `robots.txt`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/robots.txt` | `tenant.robots` | — | `Tenant\RobotsController` |

- **`Http\Controllers\Tenant\RobotsController`** — [`app/Http/Controllers/Tenant/RobotsController.php`](../../app/Http/Controllers/Tenant/RobotsController.php) (29 lines)
  - Views: `robots`

### `search`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/search` | `tenant.storefront.search` | — | `Livewire\Tenant\Storefront\BestSellingPage` |
| GET | `/search/autocomplete` | `tenant.storefront.search.autocomplete` | — | `Tenant\StorefrontSearchController@autocomplete` |
| POST | `/search/image` | `tenant.storefront.search.image` | — | `ImageSearchController@storefront` |

- **`Livewire\Tenant\Storefront\BestSellingPage`** — [`app/Livewire/Tenant/Storefront/BestSellingPage.php`](../../app/Livewire/Tenant/Storefront/BestSellingPage.php) (160 lines)
  - Public state / form fields: `search`, `days`, `categoryId`, `imageSearchId`, `isSearchRoute`, `isImageSearch`, `perPage`, `hasMore`, `mobileMenuOpen`
  - Actions/methods: `updating()`, `loadMore()`, `setDays()`, `setCategory()`, `addToCart()`, `toggleMobileMenu()`
- **`Http\Controllers\Tenant\StorefrontSearchController`** — [`app/Http/Controllers/Tenant/StorefrontSearchController.php`](../../app/Http/Controllers/Tenant/StorefrontSearchController.php) (174 lines)
  - Actions/methods: `autocomplete()`, `products()`, `categoryProducts()`, `autocompletePath()`, `productsPath()`, `categoryProductsPath()`
- **`Http\Controllers\ImageSearchController`** — [`app/Http/Controllers/ImageSearchController.php`](../../app/Http/Controllers/ImageSearchController.php) (155 lines)
  - Actions/methods: `storefront()`, `adminPanel()`, `tenantPanel()`
  - Validation: `image`: required', 'image', 'max:8192

### `sitemap.xml`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/sitemap.xml` | `tenant.sitemap` | — | `Tenant\SitemapController` |

- **`Http\Controllers\Tenant\SitemapController`** — [`app/Http/Controllers/Tenant/SitemapController.php`](../../app/Http/Controllers/Tenant/SitemapController.php) (98 lines)
  - Views: `sitemap.index`

### `tenancy`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/tenancy/assets/{path?}` | `stancl.tenancy.asset` | — | `Stancl\Tenancy\Controllers\TenantAssetsController@asset` |

- `Stancl\Tenancy\Controllers\TenantAssetsController` (closure or not reflected)

### `tenant`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/tenant/broadcasting/auth` | `tenant.broadcasting.auth` | — | `Tenant\BroadcastAuthController` |

- **`Http\Controllers\Tenant\BroadcastAuthController`** — [`app/Http/Controllers/Tenant/BroadcastAuthController.php`](../../app/Http/Controllers/Tenant/BroadcastAuthController.php) (26 lines)
  - Doc: The default /broadcasting/auth route (registered on the central router) never initializes tenancy, so private channels scoped to a tenant subdomain authorize here instead.

### `{fallbackPlaceholder}`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/{fallbackPlaceholder}` | `tenant.storefront.404` | — | `Livewire\Tenant\Storefront\NotFoundPage` |

- **`Livewire\Tenant\Storefront\NotFoundPage`** — [`app/Livewire/Tenant/Storefront/NotFoundPage.php`](../../app/Livewire/Tenant/Storefront/NotFoundPage.php) (25 lines)
  - Public state / form fields: `mobileMenuOpen`
  - Actions/methods: `toggleMobileMenu()`

## Storefront JSON API (/api) (32 routes)


### `home`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/home/tabbed-products` | `tenant.storefront.home.tabbed-products` | — | `Tenant\HomePageController` |

- **`Http\Controllers\Tenant\HomePageController`** — [`app/Http/Controllers/Tenant/HomePageController.php`](../../app/Http/Controllers/Tenant/HomePageController.php) (62 lines)
  - Views: `themes.elora.pages._product-card`

### `products`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/products` | `tenant.storefront.products.json` | — | `Tenant\StorefrontSearchController@products` |

- **`Http\Controllers\Tenant\StorefrontSearchController`** — [`app/Http/Controllers/Tenant/StorefrontSearchController.php`](../../app/Http/Controllers/Tenant/StorefrontSearchController.php) (174 lines)
  - Actions/methods: `autocomplete()`, `products()`, `categoryProducts()`, `autocompletePath()`, `productsPath()`, `categoryProductsPath()`

### `v1`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/v1/addresses` | `api.v1.addresses.index` | — | `Tenant\AddressController@index` |
| POST | `/api/v1/addresses` | `api.v1.addresses.store` | — | `Tenant\AddressController@store` |
| PUT | `/api/v1/addresses/{address}` | `api.v1.addresses.update` | — | `Tenant\AddressController@update` |
| DELETE | `/api/v1/addresses/{address}` | `api.v1.addresses.destroy` | — | `Tenant\AddressController@destroy` |
| POST | `/api/v1/addresses/{address}/default` | `api.v1.addresses.default` | — | `Tenant\AddressController@setDefault` |
| POST | `/api/v1/cart/add` | `api.v1.cart.add` | — | `Tenant\CartController@add` |
| POST | `/api/v1/cart/remove` | `api.v1.cart.remove` | — | `Tenant\CartController@remove` |
| POST | `/api/v1/cart/update` | `api.v1.cart.update` | — | `Tenant\CartController@update` |
| GET | `/api/v1/categories` | `api.v1.categories.index` | — | `Tenant\CatalogController@categories` |
| GET | `/api/v1/categories/{slug}` | `api.v1.categories.show` | — | `Tenant\CatalogController@category` |
| POST | `/api/v1/checkout` | `api.v1.checkout.store` | — | `Tenant\CheckoutController@store` |
| GET | `/api/v1/coupon` | `api.v1.coupon.show` | — | `Tenant\CouponController@show` |
| POST | `/api/v1/coupon/apply` | `api.v1.coupon.apply` | — | `Tenant\CouponController@apply` |
| POST | `/api/v1/coupon/remove` | `api.v1.coupon.remove` | — | `Tenant\CouponController@remove` |
| GET | `/api/v1/favorites/ids` | `api.v1.favorites.ids` | — | `Tenant\FavoriteController@ids` |
| GET | `/api/v1/favorites/list` | `api.v1.favorites.list` | — | `Tenant\FavoriteController@list` |
| POST | `/api/v1/favorites/toggle` | `api.v1.favorites.toggle` | — | `Tenant\FavoriteController@toggle` |
| POST | `/api/v1/logout` | `api.v1.logout` | — | `Tenant\ProfileController@logout` |
| GET | `/api/v1/orders` | `api.v1.orders.index` | — | `Tenant\OrderController@index` |
| GET | `/api/v1/orders/cancellation-reasons` | `api.v1.orders.cancellation-reasons` | — | `Tenant\OrderController@cancellationReasons` |
| GET | `/api/v1/orders/{uuid}` | `api.v1.orders.show` | — | `Tenant\OrderController@show` |
| POST | `/api/v1/orders/{uuid}/cancel` | `api.v1.orders.cancel` | — | `Tenant\OrderController@cancel` |
| POST | `/api/v1/orders/{uuid}/reorder` | `api.v1.orders.reorder` | — | `Tenant\OrderController@reorder` |
| GET | `/api/v1/products` | `api.v1.products.index` | — | `Tenant\CatalogController@products` |
| GET | `/api/v1/products/{product}/reviews` | `api.v1.reviews.index` | — | `Tenant\ReviewController@index` |
| GET | `/api/v1/products/{slug}` | `api.v1.products.show` | — | `Tenant\CatalogController@product` |
| GET | `/api/v1/profile` | `api.v1.profile.show` | — | `Tenant\ProfileController@show` |
| PUT | `/api/v1/profile` | `api.v1.profile.update` | — | `Tenant\ProfileController@update` |
| POST | `/api/v1/profile/password` | `api.v1.profile.password` | — | `Tenant\ProfileController@updatePassword` |
| POST | `/api/v1/reviews` | `api.v1.reviews.store` | — | `Tenant\ReviewController@store` |

- **`Http\Controllers\Tenant\AddressController`** — [`app/Http/Controllers/Tenant/AddressController.php`](../../app/Http/Controllers/Tenant/AddressController.php) (152 lines)
  - Actions/methods: `index()`, `store()`, `update()`, `destroy()`, `setDefault()`
  - Validation: `label`: nullable|string|max:50; `full_name`: required|string|max:100; `phone`: nullable|string|max:30; `city`: required|string|max:100; `state`: nullable|string|max:100; `country_id`: required', 'integer', Rule::exists('mysql.countries', 'id'); `is_default`: nullable|boolean
- **`Http\Controllers\Tenant\CartController`** — [`app/Http/Controllers/Tenant/CartController.php`](../../app/Http/Controllers/Tenant/CartController.php) (137 lines)
  - Actions/methods: `add()`, `remove()`, `update()`
  - Validation: `slug`: nullable|string; `product_id`: nullable|integer; `variant_id`: nullable|integer; `qty`: required|integer|min:1|max:99; `key`: required|string
- **`Http\Controllers\Tenant\CatalogController`** — [`app/Http/Controllers/Tenant/CatalogController.php`](../../app/Http/Controllers/Tenant/CatalogController.php) (143 lines)
  - Actions/methods: `categories()`, `category()`, `products()`, `product()`
- **`Http\Controllers\Tenant\CheckoutController`** — [`app/Http/Controllers/Tenant/CheckoutController.php`](../../app/Http/Controllers/Tenant/CheckoutController.php) (72 lines)
  - Actions/methods: `store()`
  - Validation: `payment_method`: required|string; `payment_gateway_id`: nullable|integer; `shipping.name`: required|string|max:120; `shipping.email`: required|email|max:120; `shipping.phone`: required|string|max:30; `shipping.address`: required|string|max:300; `shipping.address_id`: nullable|integer|exists:customer_addresses,id; `shipping.country_id`: nullable|integer; `coupon_code`: nullable|string|max:50
- **`Http\Controllers\Tenant\CouponController`** — [`app/Http/Controllers/Tenant/CouponController.php`](../../app/Http/Controllers/Tenant/CouponController.php) (107 lines)
  - Actions/methods: `show()`, `apply()`, `remove()`
  - Validation: `code`: required|string|max:50
- **`Http\Controllers\Tenant\FavoriteController`** — [`app/Http/Controllers/Tenant/FavoriteController.php`](../../app/Http/Controllers/Tenant/FavoriteController.php) (97 lines)
  - Actions/methods: `toggle()`, `list()`, `ids()`
- **`Http\Controllers\Tenant\ProfileController`** — [`app/Http/Controllers/Tenant/ProfileController.php`](../../app/Http/Controllers/Tenant/ProfileController.php) (94 lines)
  - Actions/methods: `show()`, `update()`, `updatePassword()`, `logout()`
  - Validation: `full_name`: required|string|max:120; `phone`: nullable|string|max:30; `email`: required', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customer->id); `language`: nullable|string|max:10; `current_password`: required|string; `password`: required', 'string', 'confirmed', Password::defaults()
- **`Http\Controllers\Tenant\OrderController`** — [`app/Http/Controllers/Tenant/OrderController.php`](../../app/Http/Controllers/Tenant/OrderController.php) (226 lines)
  - Actions/methods: `index()`, `show()`, `reorder()`, `cancel()`, `cancellationReasons()`
- **`Http\Controllers\Tenant\ReviewController`** — [`app/Http/Controllers/Tenant/ReviewController.php`](../../app/Http/Controllers/Tenant/ReviewController.php) (87 lines)
  - Actions/methods: `store()`, `index()`
  - Validation: `stars`: required|integer|min:1|max:5; `comment`: nullable|string|max:2000

## Mobile API (/api/mobile/v1) (63 routes)


### `addresses`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/addresses` | `mobile.addresses.index` | — | `Tenant\AddressController@index` |
| POST | `/api/mobile/v1/addresses` | `mobile.addresses.store` | — | `Tenant\AddressController@store` |
| PUT | `/api/mobile/v1/addresses/{address}` | `mobile.addresses.update` | — | `Tenant\AddressController@update` |
| DELETE | `/api/mobile/v1/addresses/{address}` | `mobile.addresses.destroy` | — | `Tenant\AddressController@destroy` |
| POST | `/api/mobile/v1/addresses/{address}/default` | `mobile.addresses.default` | — | `Tenant\AddressController@setDefault` |

- **`Http\Controllers\Tenant\AddressController`** — [`app/Http/Controllers/Tenant/AddressController.php`](../../app/Http/Controllers/Tenant/AddressController.php) (152 lines)
  - Actions/methods: `index()`, `store()`, `update()`, `destroy()`, `setDefault()`
  - Validation: `label`: nullable|string|max:50; `full_name`: required|string|max:100; `phone`: nullable|string|max:30; `city`: required|string|max:100; `state`: nullable|string|max:100; `country_id`: required', 'integer', Rule::exists('mysql.countries', 'id'); `is_default`: nullable|boolean

### `auth`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| DELETE | `/api/mobile/v1/auth/account` | `mobile.auth.account.delete` | — | `Mobile\AuthController@deleteAccount` |
| POST | `/api/mobile/v1/auth/forgot-password` | `mobile.auth.forgot` | — | `Mobile\AuthController@forgotPassword` |
| POST | `/api/mobile/v1/auth/login` | `mobile.auth.login` | — | `Mobile\AuthController@login` |
| POST | `/api/mobile/v1/auth/logout` | `mobile.auth.logout` | — | `Mobile\AuthController@logout` |
| POST | `/api/mobile/v1/auth/refresh` | `mobile.auth.refresh` | — | `Mobile\AuthController@refresh` |
| POST | `/api/mobile/v1/auth/register` | `mobile.auth.register` | — | `Mobile\AuthController@register` |
| POST | `/api/mobile/v1/auth/reset-password` | `mobile.auth.reset` | — | `Mobile\AuthController@resetPassword` |

- **`Http\Controllers\Mobile\AuthController`** — [`app/Http/Controllers/Mobile/AuthController.php`](../../app/Http/Controllers/Mobile/AuthController.php) (134 lines)
  - Actions/methods: `register()`, `login()`, `refresh()`, `logout()`, `forgotPassword()`, `resetPassword()`, `deleteAccount()`
  - Validation: `email`: required|email; `token`: required|string; `password`: required|min:8|confirmed

### `bootstrap`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/bootstrap` | `mobile.bootstrap` | — | `Mobile\BootstrapController` |

- **`Http\Controllers\Mobile\BootstrapController`** — [`app/Http/Controllers/Mobile/BootstrapController.php`](../../app/Http/Controllers/Mobile/BootstrapController.php) (26 lines)
  - Doc: GET /bootstrap with ETag / 304 on config_version.

### `cart`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/cart` | `mobile.cart.show` | — | `Mobile\CartController@show` |
| DELETE | `/api/mobile/v1/cart` | `mobile.cart.clear` | — | `Mobile\CartController@clear` |
| POST | `/api/mobile/v1/cart/add` | `mobile.cart.add` | — | `Mobile\CartController@add` |
| POST | `/api/mobile/v1/cart/merge` | `mobile.cart.merge` | — | `Mobile\CartController@merge` |
| POST | `/api/mobile/v1/cart/remove` | `mobile.cart.remove` | — | `Mobile\CartController@remove` |
| POST | `/api/mobile/v1/cart/update` | `mobile.cart.update` | — | `Mobile\CartController@update` |

- **`Http\Controllers\Mobile\CartController`** — [`app/Http/Controllers/Mobile/CartController.php`](../../app/Http/Controllers/Mobile/CartController.php) (90 lines)
  - Doc: Server-side cart for the authenticated customer. Mutations delegate to the /api/v1 CartController (same stock checks); AuthenticateMobileToken persists the session-shaped cart to mobile_carts.
  - Actions/methods: `show()`, `add()`, `update()`, `remove()`, `clear()`, `merge()`
  - Validation: `items`: required|array|max:100; `items.*.product_id`: nullable|integer; `items.*.slug`: nullable|string; `items.*.variant_id`: nullable|integer; `items.*.qty`: nullable|integer|min:1|max:99

### `categories`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/categories` | `mobile.categories.index` | — | `Mobile\CatalogController@categories` |
| GET | `/api/mobile/v1/categories/{slug}` | `mobile.categories.show` | — | `Mobile\CatalogController@category` |

- **`Http\Controllers\Mobile\CatalogController`** — [`app/Http/Controllers/Mobile/CatalogController.php`](../../app/Http/Controllers/Mobile/CatalogController.php) (136 lines)
  - Doc: Catalog endpoints (reuse /api/v1 transformers) plus search and list screens.
  - Actions/methods: `search()`, `autocomplete()`, `lists()`, `product()`, `related()`

### `checkout`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/api/mobile/v1/checkout` | `mobile.checkout.store` | — | `Mobile\CheckoutController@store` |
| GET | `/api/mobile/v1/checkout/gateways` | `mobile.checkout.gateways` | — | `Mobile\CheckoutController@gateways` |
| POST | `/api/mobile/v1/checkout/quote` | `mobile.checkout.quote` | — | `Mobile\CheckoutController@quote` |

- **`Http\Controllers\Mobile\CheckoutController`** — [`app/Http/Controllers/Mobile/CheckoutController.php`](../../app/Http/Controllers/Mobile/CheckoutController.php) (126 lines)
  - Actions/methods: `gateways()`, `quote()`, `store()`
  - Validation: `shipping.address_id`: nullable|integer|exists:customer_addresses,id; `shipping.country_id`: nullable|integer; `payment_method`: required|string|max:50; `payment_gateway_id`: nullable|integer; `expected_total`: nullable|numeric|min:0; `shipping.name`: required|string|max:120; `shipping.email`: required|email|max:120; `shipping.phone`: required|string|max:30; `shipping.address`: required|string|max:300

### `coupon`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/coupon` | `mobile.coupon.show` | — | `Tenant\CouponController@show` |
| POST | `/api/mobile/v1/coupon/apply` | `mobile.coupon.apply` | — | `Tenant\CouponController@apply` |
| POST | `/api/mobile/v1/coupon/remove` | `mobile.coupon.remove` | — | `Tenant\CouponController@remove` |

- **`Http\Controllers\Tenant\CouponController`** — [`app/Http/Controllers/Tenant/CouponController.php`](../../app/Http/Controllers/Tenant/CouponController.php) (107 lines)
  - Actions/methods: `show()`, `apply()`, `remove()`
  - Validation: `code`: required|string|max:50

### `device-tokens`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/api/mobile/v1/device-tokens` | `mobile.device-tokens.store` | — | `Mobile\PreferencesController@storeDeviceToken` |
| DELETE | `/api/mobile/v1/device-tokens` | `mobile.device-tokens.destroy` | — | `Mobile\PreferencesController@deleteDeviceToken` |

- **`Http\Controllers\Mobile\PreferencesController`** — [`app/Http/Controllers/Mobile/PreferencesController.php`](../../app/Http/Controllers/Mobile/PreferencesController.php) (58 lines)
  - Doc: Locale / currency / country choices. The server is stateless about them: the app stores its choice and sends Accept-Language, X-Currency and X-Country on every request (see ResolveMobileTenant).
  - Actions/methods: `options()`, `storeDeviceToken()`, `deleteDeviceToken()`
  - Validation: `token`: required|string|max:512; `platform`: nullable|string|max:20

### `favorites`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/favorites/ids` | `mobile.favorites.ids` | — | `Tenant\FavoriteController@ids` |
| GET | `/api/mobile/v1/favorites/list` | `mobile.favorites.list` | — | `Tenant\FavoriteController@list` |
| POST | `/api/mobile/v1/favorites/toggle` | `mobile.favorites.toggle` | — | `Tenant\FavoriteController@toggle` |

- **`Http\Controllers\Tenant\FavoriteController`** — [`app/Http/Controllers/Tenant/FavoriteController.php`](../../app/Http/Controllers/Tenant/FavoriteController.php) (97 lines)
  - Actions/methods: `toggle()`, `list()`, `ids()`

### `home`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/home/sections/{key}` | `mobile.home.section` | — | `Mobile\HomeController@section` |

- **`Http\Controllers\Mobile\HomeController`** — [`app/Http/Controllers/Mobile/HomeController.php`](../../app/Http/Controllers/Mobile/HomeController.php) (162 lines)
  - Doc: GET /home/sections/{key}: data for one home section (same datasets as web HomePage).
  - Actions/methods: `section()`

### `lists`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/lists/{list}` | `mobile.lists` | — | `Mobile\CatalogController@lists` |

- **`Http\Controllers\Mobile\CatalogController`** — [`app/Http/Controllers/Mobile/CatalogController.php`](../../app/Http/Controllers/Mobile/CatalogController.php) (136 lines)
  - Doc: Catalog endpoints (reuse /api/v1 transformers) plus search and list screens.
  - Actions/methods: `search()`, `autocomplete()`, `lists()`, `product()`, `related()`

### `me`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/me` | `mobile.profile.show` | — | `Tenant\ProfileController@show` |

- **`Http\Controllers\Tenant\ProfileController`** — [`app/Http/Controllers/Tenant/ProfileController.php`](../../app/Http/Controllers/Tenant/ProfileController.php) (94 lines)
  - Actions/methods: `show()`, `update()`, `updatePassword()`, `logout()`
  - Validation: `full_name`: required|string|max:120; `phone`: nullable|string|max:30; `email`: required', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customer->id); `language`: nullable|string|max:10; `current_password`: required|string; `password`: required', 'string', 'confirmed', Password::defaults()

### `menus`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/menus` | `mobile.menus` | — | `Mobile\PageController@menus` |

- **`Http\Controllers\Mobile\PageController`** — [`app/Http/Controllers/Mobile/PageController.php`](../../app/Http/Controllers/Mobile/PageController.php) (56 lines)
  - Actions/methods: `show()`, `menus()`

### `orders`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/orders` | `mobile.orders.index` | — | `Tenant\OrderController@index` |
| GET | `/api/mobile/v1/orders/cancellation-reasons` | `mobile.orders.cancellation-reasons` | — | `Tenant\OrderController@cancellationReasons` |
| GET | `/api/mobile/v1/orders/{uuid}` | `mobile.orders.show` | — | `Tenant\OrderController@show` |
| POST | `/api/mobile/v1/orders/{uuid}/cancel` | `mobile.orders.cancel` | — | `Tenant\OrderController@cancel` |
| GET | `/api/mobile/v1/orders/{uuid}/invoice` | `mobile.orders.invoice` | — | `Tenant\StorefrontInvoiceController@show` |
| POST | `/api/mobile/v1/orders/{uuid}/pay` | `mobile.orders.pay` | — | `Mobile\PaymentController@pay` |
| GET | `/api/mobile/v1/orders/{uuid}/payment-status` | `mobile.orders.payment-status` | — | `Mobile\PaymentController@status` |
| POST | `/api/mobile/v1/orders/{uuid}/reorder` | `mobile.orders.reorder` | — | `Tenant\OrderController@reorder` |
| POST | `/api/mobile/v1/orders/{uuid}/return` | `mobile.returns.store` | — | `Mobile\ReturnController@store` |
| GET | `/api/mobile/v1/orders/{uuid}/tracking` | `mobile.orders.tracking` | — | `Mobile\OrderExtrasController@tracking` |

- **`Http\Controllers\Tenant\OrderController`** — [`app/Http/Controllers/Tenant/OrderController.php`](../../app/Http/Controllers/Tenant/OrderController.php) (226 lines)
  - Actions/methods: `index()`, `show()`, `reorder()`, `cancel()`, `cancellationReasons()`
- **`Http\Controllers\Tenant\StorefrontInvoiceController`** — [`app/Http/Controllers/Tenant/StorefrontInvoiceController.php`](../../app/Http/Controllers/Tenant/StorefrontInvoiceController.php) (119 lines)
  - Actions/methods: `download()`, `show()`
  - Views: `tenant.storefront.invoice`
- **`Http\Controllers\Mobile\PaymentController`** — [`app/Http/Controllers/Mobile/PaymentController.php`](../../app/Http/Controllers/Mobile/PaymentController.php) (182 lines)
  - Doc: Hosted-gateway payments for the app's in-app WebView. The app never marks anything paid: the gateway callback is verified by the gateway driver (verify()), and the app polls GET /orders/{uuid}/payment-status for the real state.
  - Actions/methods: `pay()`, `success()`, `cancel()`, `status()`
- **`Http\Controllers\Mobile\ReturnController`** — [`app/Http/Controllers/Mobile/ReturnController.php`](../../app/Http/Controllers/Mobile/ReturnController.php) (142 lines)
  - Doc: Returns / exchanges: same validation + service path as the storefront RequestReturnForm.
  - Actions/methods: `options()`, `index()`, `show()`, `store()`
  - Validation: `order_item_id`: required|integer; `reason`: required', Rule::in(array_column(ReturnReason::cases(), 'value')); `quantity`: required|integer|min:1; `type`: required', Rule::in(array_column(ReturnType::cases(), 'value')); `return_method`: required', Rule::in(array_column(ReturnMethod::cases(), 'value')); `description`: nullable|string|max:2000; `customer_note`: nullable|string|max:1000; `replacement_variant_id`: nullable|integer|required_if:type,; `photos`: nullable|array; `photos.*`: image|max:; `video`: nullable|file|mimetypes:video/mp4,video/quicktime,video/webm|max:
- **`Http\Controllers\Mobile\OrderExtrasController`** — [`app/Http/Controllers/Mobile/OrderExtrasController.php`](../../app/Http/Controllers/Mobile/OrderExtrasController.php) (35 lines)
  - Actions/methods: `tracking()`

### `pages`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/pages/{slug}` | `mobile.pages.show` | — | `Mobile\PageController@show` |

- **`Http\Controllers\Mobile\PageController`** — [`app/Http/Controllers/Mobile/PageController.php`](../../app/Http/Controllers/Mobile/PageController.php) (56 lines)
  - Actions/methods: `show()`, `menus()`

### `payments`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/payments/{tenant}/{gateway}/cancel` | `mobile.payments.cancel` | — | `Mobile\PaymentController@cancel` |
| GET|POST | `/api/mobile/v1/payments/{tenant}/{gateway}/success` | `mobile.payments.success` | — | `Mobile\PaymentController@success` |

- **`Http\Controllers\Mobile\PaymentController`** — [`app/Http/Controllers/Mobile/PaymentController.php`](../../app/Http/Controllers/Mobile/PaymentController.php) (182 lines)
  - Doc: Hosted-gateway payments for the app's in-app WebView. The app never marks anything paid: the gateway callback is verified by the gateway driver (verify()), and the app polls GET /orders/{uuid}/payment-status for the real state.
  - Actions/methods: `pay()`, `success()`, `cancel()`, `status()`

### `preferences`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/preferences/options` | `mobile.preferences.options` | — | `Mobile\PreferencesController@options` |

- **`Http\Controllers\Mobile\PreferencesController`** — [`app/Http/Controllers/Mobile/PreferencesController.php`](../../app/Http/Controllers/Mobile/PreferencesController.php) (58 lines)
  - Doc: Locale / currency / country choices. The server is stateless about them: the app stores its choice and sends Accept-Language, X-Currency and X-Country on every request (see ResolveMobileTenant).
  - Actions/methods: `options()`, `storeDeviceToken()`, `deleteDeviceToken()`
  - Validation: `token`: required|string|max:512; `platform`: nullable|string|max:20

### `products`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/products` | `mobile.products.index` | — | `Mobile\CatalogController@products` |
| GET | `/api/mobile/v1/products/{product}/reviews` | `mobile.reviews.index` | — | `Tenant\ReviewController@index` |
| GET | `/api/mobile/v1/products/{slug}` | `mobile.products.show` | — | `Mobile\CatalogController@product` |
| GET | `/api/mobile/v1/products/{slug}/related` | `mobile.products.related` | — | `Mobile\CatalogController@related` |

- **`Http\Controllers\Mobile\CatalogController`** — [`app/Http/Controllers/Mobile/CatalogController.php`](../../app/Http/Controllers/Mobile/CatalogController.php) (136 lines)
  - Doc: Catalog endpoints (reuse /api/v1 transformers) plus search and list screens.
  - Actions/methods: `search()`, `autocomplete()`, `lists()`, `product()`, `related()`
- **`Http\Controllers\Tenant\ReviewController`** — [`app/Http/Controllers/Tenant/ReviewController.php`](../../app/Http/Controllers/Tenant/ReviewController.php) (87 lines)
  - Actions/methods: `store()`, `index()`
  - Validation: `stars`: required|integer|min:1|max:5; `comment`: nullable|string|max:2000

### `profile`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| PUT | `/api/mobile/v1/profile` | `mobile.profile.update` | — | `Tenant\ProfileController@update` |
| POST | `/api/mobile/v1/profile/password` | `mobile.profile.password` | — | `Tenant\ProfileController@updatePassword` |

- **`Http\Controllers\Tenant\ProfileController`** — [`app/Http/Controllers/Tenant/ProfileController.php`](../../app/Http/Controllers/Tenant/ProfileController.php) (94 lines)
  - Actions/methods: `show()`, `update()`, `updatePassword()`, `logout()`
  - Validation: `full_name`: required|string|max:120; `phone`: nullable|string|max:30; `email`: required', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customer->id); `language`: nullable|string|max:10; `current_password`: required|string; `password`: required', 'string', 'confirmed', Password::defaults()

### `resolve`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/resolve` | `mobile.resolve` | — | `Mobile\ResolveController` |

- **`Http\Controllers\Mobile\ResolveController`** — [`app/Http/Controllers/Mobile/ResolveController.php`](../../app/Http/Controllers/Mobile/ResolveController.php) (73 lines)
  - Doc: GET /resolve?host=  (central, throttled): registered domain -> tenant summary.

### `returns`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/returns` | `mobile.returns.index` | — | `Mobile\ReturnController@index` |
| GET | `/api/mobile/v1/returns/options` | `mobile.returns.options` | — | `Mobile\ReturnController@options` |
| GET | `/api/mobile/v1/returns/{id}` | `mobile.returns.show` | — | `Mobile\ReturnController@show` |

- **`Http\Controllers\Mobile\ReturnController`** — [`app/Http/Controllers/Mobile/ReturnController.php`](../../app/Http/Controllers/Mobile/ReturnController.php) (142 lines)
  - Doc: Returns / exchanges: same validation + service path as the storefront RequestReturnForm.
  - Actions/methods: `options()`, `index()`, `show()`, `store()`
  - Validation: `order_item_id`: required|integer; `reason`: required', Rule::in(array_column(ReturnReason::cases(), 'value')); `quantity`: required|integer|min:1; `type`: required', Rule::in(array_column(ReturnType::cases(), 'value')); `return_method`: required', Rule::in(array_column(ReturnMethod::cases(), 'value')); `description`: nullable|string|max:2000; `customer_note`: nullable|string|max:1000; `replacement_variant_id`: nullable|integer|required_if:type,; `photos`: nullable|array; `photos.*`: image|max:; `video`: nullable|file|mimetypes:video/mp4,video/quicktime,video/webm|max:

### `reviews`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| POST | `/api/mobile/v1/reviews` | `mobile.reviews.store` | — | `Tenant\ReviewController@store` |

- **`Http\Controllers\Tenant\ReviewController`** — [`app/Http/Controllers/Tenant/ReviewController.php`](../../app/Http/Controllers/Tenant/ReviewController.php) (87 lines)
  - Actions/methods: `store()`, `index()`
  - Validation: `stars`: required|integer|min:1|max:5; `comment`: nullable|string|max:2000

### `search`

| Method | URI | Name | Permission | Handler |
|---|---|---|---|---|
| GET | `/api/mobile/v1/search` | `mobile.search` | — | `Mobile\CatalogController@search` |
| GET | `/api/mobile/v1/search/autocomplete` | `mobile.search.autocomplete` | — | `Mobile\CatalogController@autocomplete` |

- **`Http\Controllers\Mobile\CatalogController`** — [`app/Http/Controllers/Mobile/CatalogController.php`](../../app/Http/Controllers/Mobile/CatalogController.php) (136 lines)
  - Doc: Catalog endpoints (reuse /api/v1 transformers) plus search and list screens.
  - Actions/methods: `search()`, `autocomplete()`, `lists()`, `product()`, `related()`
