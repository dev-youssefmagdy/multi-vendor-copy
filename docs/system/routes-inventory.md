# Route Inventory (generated)

> Generated from `php artisan route:list --json` on 2026-11-24 (branch `refactor/tenant-central-dedup-plan`). Central-domain routes are registered once per entry in `config/tenancy.php` `central_domains` (dokan-v2.loc, 127.0.0.1, localhost) by `routes/web.php`; only the `localhost` copy is listed here. Middleware column omits the `web` group. `AdminPermission:x` / `TenantPermission:x` = required permission key.

Total unique routes listed: 838 (raw registered routes incl. duplicate central-domain copies: 1272)

| Area | Routes |
|---|---|
| 0. Central marketing website, registration & tenant-owner portal (central domain) | 35 |
| 1. Super Admin panel (central domain, /admin) | 172 |
| 2. Tenant (vendor) admin panel (tenant domain, /admin) | 424 |
| 3. Affiliate panel (central domain, /affiliate) | 10 |
| 4. Tenant storefront (tenant subdomain / custom domain) | 53 |
| 5. Path-based storefront (central domain, /vendor/{tenant}) | 32 |
| 6. Storefront/tenant JSON API (/api) | 32 |
| 7. Mobile API (/api/mobile/v1) | 63 |
| 9. Framework / tooling | 17 |

## 0. Central marketing website, registration & tenant-owner portal (central domain)


### `(home)` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `//` | website.home | `Livewire\Website\LandingPage` | TrackAffiliateReferral |

### `about` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/about` | website.about | `Livewire\Website\AboutPage` | TrackAffiliateReferral |

### `auth` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/auth/apple/callback` | website.social.apple.callback | `Website\CentralSocialAuthController@handleAppleCallback` |  |
| GET | `/auth/apple/{intent}` | website.social.apple | `Website\CentralSocialAuthController@redirectToApple` |  |
| GET | `/auth/google/callback` | website.social.google.callback | `Website\CentralSocialAuthController@handleGoogleCallback` |  |
| GET | `/auth/google/{intent}` | website.social.google | `Website\CentralSocialAuthController@redirectToGoogle` |  |

### `blog` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/blog` | website.blog | `Livewire\Website\BlogListPage` | TrackAffiliateReferral |
| GET | `/blog/{slug}` | website.blog.show | `Livewire\Website\BlogDetailPage` | TrackAffiliateReferral |

### `broadcasting` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/broadcasting/auth` | broadcasting.auth | `Admin\AdminAuthController@broadcastAuth` | AdminAuth |

### `contact` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/contact` | website.contact | `Livewire\Website\ContactPage` | TrackAffiliateReferral |

### `faqs` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/faqs` | website.faqs | `Livewire\Website\FaqsPage` | TrackAffiliateReferral |

### `how-it-works` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/how-it-works` | website.how-it-works | `Livewire\Website\HowItWorksPage` | TrackAffiliateReferral |

### `locale` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/locale/{locale}` | locale.switch | `Website\LocaleController@switch` |  |

### `login` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/login` | owner.login | `Livewire\TenantOwner\Auth\LoginPage` | RedirectIfAuthenticated:tenant_owner |

### `maintenance` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/maintenance` | website.maintenance | `Livewire\Website\MaintenancePage` | TrackAffiliateReferral |

### `my-account` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET|POST|PUT|PATCH|DELETE|OPTIONS | `/my-account` | owner.dashboard | `Illuminate\Routing\RedirectController` | TenantOwnerAuth |
| GET | `/my-account/domains` | owner.domains | `Livewire\TenantOwner\DomainsList` | TenantOwnerAuth |
| POST | `/my-account/logout` | owner.logout | `Website\CentralSocialAuthController@logout` |  |
| GET | `/my-account/select-tenant` | owner.select-tenant | `Livewire\TenantOwner\Auth\SelectTenantPage` | Authenticate:tenant_owner |
| GET | `/my-account/subscriptions` | owner.subscriptions | `Livewire\TenantOwner\SubscriptionsList` | TenantOwnerAuth |

### `pages` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/pages/{slug}` | website.page | `Livewire\Website\StaticPageView` | TrackAffiliateReferral |

### `pricing` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/pricing` | website.pricing | `Livewire\Website\PricingPage` | TrackAffiliateReferral |

### `privacy` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/privacy` | website.privacy | `Livewire\Website\PrivacyPage` | TrackAffiliateReferral |

### `prod` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/prod` |  | `Admin\DevToolsController@importNeozenaProducts` |  |

### `register` (6)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/register` | website.register | `Livewire\Website\RegisterPage` | TrackAffiliateReferral |
| GET | `/register/complete` | website.register.complete | `Livewire\Website\CompleteRegistrationPage` | TrackAffiliateReferral |
| GET | `/register/payment/{gateway}/{registration}/cancel` | website.register.payment.cancel | `Website\RegistrationPaymentController@cancel` | TrackAffiliateReferral |
| GET | `/register/payment/{gateway}/{registration}/charge` | website.register.payment.charge | `Website\RegistrationPaymentController@charge` | TrackAffiliateReferral |
| GET|POST | `/register/payment/{gateway}/{registration}/success` | website.register.payment.success | `Website\RegistrationPaymentController@success` | TrackAffiliateReferral |
| GET | `/register/setup/{tenantId}` | website.store.onboarding | `Livewire\Website\StoreOnboardingWizard` | TrackAffiliateReferral |

### `sitemap.xml` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/sitemap.xml` | website.sitemap | `Website\SitemapController` |  |

### `templates` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/templates` | website.templates | `Livewire\Website\TemplatesPage` | TrackAffiliateReferral |

### `terms` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/terms` | website.terms | `Livewire\Website\TermsPage` | TrackAffiliateReferral |

### `upload-files` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/upload-files` | upload-files | `Website\FileUploadController@store` |  |

### `{fallbackPlaceholder}` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/{fallbackPlaceholder}` | website.not-found | `Livewire\Website\NotFoundPage` |  |

## 1. Super Admin panel (central domain, /admin)


### `(root)` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin` | admin.dashboard | `Livewire\Admin\Dashboard` | AdminAuth, AdminPermission:dashboard.view |

### `admins` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/admins` | admin.admins.index | `Livewire\Admin\Admin\AdminsList` | AdminAuth, AdminPermission:admins.users.view,admins.users.manage |
| GET | `/admin/admins/roles-permissions` | admin.admins.roles-permissions | `Livewire\Admin\Admin\RolesPermissionsList` | AdminAuth, AdminPermission:admins.roles.view,admins.roles.manage |

### `affiliates` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/affiliates` | admin.affiliates.index | `Livewire\Admin\Affiliate\AffiliatesListPage` | AdminAuth, AdminPermission:affiliates.manage |
| GET | `/admin/affiliates/conversions` | admin.affiliates.conversions | `Livewire\Admin\Affiliate\ConversionsListPage` | AdminAuth, AdminPermission:affiliates.manage |
| GET | `/admin/affiliates/coupons` | admin.affiliates.coupons | `Livewire\Admin\Affiliate\AffiliateCouponsPage` | AdminAuth, AdminPermission:affiliates.manage |
| GET | `/admin/affiliates/payouts` | admin.affiliates.payouts | `Livewire\Admin\Affiliate\PayoutsListPage` | AdminAuth, AdminPermission:affiliates.manage |
| GET | `/admin/affiliates/reports` | admin.affiliates.reports | `Livewire\Admin\Affiliate\AffiliateReportsPage` | AdminAuth, AdminPermission:affiliates.manage |

### `badges` (11)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/badges/best-selling` | admin.badges.best-selling | `Admin\BadgeProductsController@show` | AdminAuth, AdminPermission:catalog.badges.manage |
| GET | `/admin/badges/featured` | admin.badges.featured | `Admin\BadgeProductsController@show` | AdminAuth, AdminPermission:catalog.badges.manage |
| GET | `/admin/badges/new-in` | admin.badges.new-in | `Admin\BadgeProductsController@show` | AdminAuth, AdminPermission:catalog.badges.manage |
| GET | `/admin/badges/recommended` | admin.badges.recommended | `Admin\BadgeProductsController@show` | AdminAuth, AdminPermission:catalog.badges.manage |
| GET | `/admin/badges/trending-now` | admin.badges.trending-now | `Admin\BadgeProductsController@show` | AdminAuth, AdminPermission:catalog.badges.manage |
| GET | `/admin/badges/{badge}` | admin.badges.show | `Admin\BadgeProductsController@show` | AdminAuth, AdminPermission:catalog.badges.manage |
| POST | `/admin/badges/{badge}/assign-category` | admin.badges.assign-category | `Admin\BadgeProductsController@assignCategory` | AdminAuth, AdminPermission:catalog.badges.manage |
| POST | `/admin/badges/{badge}/save` | admin.badges.save | `Admin\BadgeProductsController@save` | AdminAuth, AdminPermission:catalog.badges.manage |
| GET | `/admin/badges/{badge}/search` | admin.badges.search | `Admin\BadgeProductsController@searchProducts` | AdminAuth, AdminPermission:catalog.badges.manage |
| GET | `/admin/badges/{badge}/sort` | admin.badges.sort | `Admin\BadgeSortController@index` | AdminAuth, AdminPermission:catalog.badges.manage |
| POST | `/admin/badges/{badge}/sort` | admin.badges.sort.update | `Admin\BadgeSortController@update` | AdminAuth, AdminPermission:catalog.badges.manage |

### `blog` (6)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/blog/categories` | admin.blog.categories | `Livewire\Admin\Blog\BlogCategoriesList` | AdminAuth, AdminPermission:content.blog-categories.view,content.blog-categories.manage |
| GET | `/admin/blog/categories/create` | admin.blog.categories.create | `Livewire\Admin\Blog\AddEditBlogCategory` | AdminAuth, AdminPermission:content.blog-categories.manage |
| GET | `/admin/blog/categories/{blogCategory}/edit` | admin.blog.categories.edit | `Livewire\Admin\Blog\AddEditBlogCategory` | AdminAuth, AdminPermission:content.blog-categories.manage |
| GET | `/admin/blog/posts` | admin.blog.posts | `Livewire\Admin\Blog\BlogPostsList` | AdminAuth, AdminPermission:content.blog-posts.view,content.blog-posts.manage |
| GET | `/admin/blog/posts/create` | admin.blog.posts.create | `Livewire\Admin\Blog\AddEditBlogPost` | AdminAuth, AdminPermission:content.blog-posts.manage |
| GET | `/admin/blog/posts/{post}/edit` | admin.blog.posts.edit | `Livewire\Admin\Blog\AddEditBlogPost` | AdminAuth, AdminPermission:content.blog-posts.manage |

### `branches` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/branches` | admin.branches.index | `Livewire\Admin\Branch\BranchesList` | AdminAuth, AdminPermission:branches.view,branches.manage |
| GET | `/admin/branches/create` | admin.branches.create | `Admin\AddEditBranchController@create` | AdminAuth, AdminPermission:branches.manage |
| POST | `/admin/branches/create` | admin.branches.store | `Admin\AddEditBranchController@store` | AdminAuth, AdminPermission:branches.manage |
| GET | `/admin/branches/{branch}/edit` | admin.branches.edit | `Admin\AddEditBranchController@edit` | AdminAuth, AdminPermission:branches.manage |
| PUT | `/admin/branches/{branch}/edit` | admin.branches.update | `Admin\AddEditBranchController@update` | AdminAuth, AdminPermission:branches.manage |

### `brand-requests` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/brand-requests` | admin.brand-requests.index | `Livewire\Admin\BrandRequest\BrandRequestsList` | AdminAuth, AdminPermission:brand-requests.view,brand-requests.manage |
| GET | `/admin/brand-requests/{id}` | admin.brand-requests.show | `Livewire\Admin\BrandRequest\BrandRequestDetail` | AdminAuth, AdminPermission:brand-requests.view |

### `cache` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/cache/main` | admin.cache.main | `Livewire\Admin\Cache\MainCachePage` | AdminAuth, AdminPermission:system.cache.manage |
| GET | `/admin/cache/tenants` | admin.cache.tenants | `Livewire\Admin\Cache\TenantsCachePage` | AdminAuth, AdminPermission:system.cache.manage |

### `categories` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/categories` | admin.categories.index | `Livewire\Admin\Category\CategoriesList` | AdminAuth, AdminPermission:catalog.categories.view,catalog.categories.manage |
| GET | `/admin/categories/create` | admin.categories.create | `Livewire\Admin\Category\AddEditCategory` | AdminAuth, AdminPermission:catalog.categories.manage |
| GET | `/admin/categories/sort` | admin.categories.sort | `Livewire\Admin\Category\SortCategories` | AdminAuth, AdminPermission:catalog.categories.manage |
| GET | `/admin/categories/{category}/edit` | admin.categories.edit | `Livewire\Admin\Category\AddEditCategory` | AdminAuth, AdminPermission:catalog.categories.manage |
| GET | `/admin/categories/{category}/products` | admin.categories.products | `Livewire\Admin\Category\CategoryProducts` | AdminAuth, AdminPermission:catalog.categories.manage |

### `compliance` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/compliance` | admin.compliance.index | `Livewire\Admin\Compliance\ComplianceOverviewList` | AdminAuth, AdminPermission:compliance.tenants.view |
| GET | `/admin/compliance/{tenant}` | admin.compliance.show | `Livewire\Admin\Compliance\ComplianceDetailPage` | AdminAuth, AdminPermission:compliance.tenants.view |

### `customers` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/customers` | admin.customers.index | `Livewire\Admin\Customer\CentralCustomersList` | AdminAuth, AdminPermission:sales.customers.view |

### `dev-docs` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/dev-docs` | admin.dev-docs.index | `Livewire\Admin\Docs\DocsPage` | AdminAuth, AdminPermission:system.dev-docs.view |
| GET | `/admin/dev-docs/{topic}` | admin.dev-docs.show | `Livewire\Admin\Docs\DocsPage` | AdminAuth, AdminPermission:system.dev-docs.view |

### `domains` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/domains/dns-records` | admin.domains.dns-records | `Livewire\Admin\Domain\DnsRecordsList` | AdminAuth, AdminPermission:domains.dns.view,domains.dns.manage |
| GET | `/admin/domains/requests` | admin.domains.requests | `Livewire\Admin\Domain\DomainRequestsList` | AdminAuth, AdminPermission:domains.requests.view,domains.requests.manage |

### `faqs` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/faqs` | admin.faqs.index | `Livewire\Admin\Faq\FaqsList` | AdminAuth, AdminPermission:content.faqs.view,content.faqs.manage |
| GET | `/admin/faqs/create` | admin.faqs.create | `Livewire\Admin\Faq\AddEditFaq` | AdminAuth, AdminPermission:content.faqs.manage |
| GET | `/admin/faqs/{faq}/edit` | admin.faqs.edit | `Livewire\Admin\Faq\AddEditFaq` | AdminAuth, AdminPermission:content.faqs.manage |

### `finance` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/finance/payouts` | admin.finance.payouts.index | `Livewire\Admin\Finance\PayoutsIndexPage` | AdminAuth, AdminPermission:billing.payouts.manage |
| GET | `/admin/finance/payouts/create/{tenant}` | admin.finance.payouts.create | `Livewire\Admin\Finance\TenantPayoutPage` | AdminAuth, AdminPermission:billing.payouts.manage |
| GET | `/admin/finance/payouts/{payout}/edit` | admin.finance.payouts.edit | `Livewire\Admin\Finance\TenantPayoutEditPage` | AdminAuth, AdminPermission:billing.payouts.manage |
| GET | `/admin/finance/reports` | admin.finance.reports | `Livewire\Admin\Finance\FinanceReportsPage` | AdminAuth, AdminPermission:billing.reports.view |

### `invoices` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/invoices` | admin.invoices.index | `Livewire\Admin\Wallet\InvoicesList` | AdminAuth, AdminPermission:billing.invoices.view |
| GET | `/admin/invoices/{invoiceId}` | admin.invoices.show | `Livewire\Admin\Wallet\InvoiceDetailPage` | AdminAuth, AdminPermission:billing.invoices.view |

### `labels` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/labels` | admin.labels.index | `Livewire\Admin\Label\LabelsList` | AdminAuth, AdminPermission:catalog.labels.view,catalog.labels.manage |
| GET | `/admin/labels/create` | admin.labels.create | `Livewire\Admin\Label\AddEditLabel` | AdminAuth, AdminPermission:catalog.labels.manage |
| GET | `/admin/labels/{label}/edit` | admin.labels.edit | `Livewire\Admin\Label\AddEditLabel` | AdminAuth, AdminPermission:catalog.labels.manage |

### `login` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/login` | admin.login | `Livewire\Admin\Auth\LoginPage` | RedirectIfAuthenticated:admin |

### `logout` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/logout` | admin.logout | `Admin\AdminAuthController@logout` | AdminAuth |

### `manufacturing` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/manufacturing` | admin.manufacturing.index | `Livewire\Admin\Manufacturing\ManufacturingRequestsList` | AdminAuth, AdminPermission:manufacturing.view,manufacturing.manage |
| GET | `/admin/manufacturing/{id}` | admin.manufacturing.show | `Livewire\Admin\Manufacturing\ManufacturingRequestDetail` | AdminAuth, AdminPermission:manufacturing.view |
| GET | `/admin/manufacturing/{id}/file` | admin.manufacturing.file | `Admin\ManufacturingRequestFileController@show` | AdminAuth, AdminPermission:manufacturing.view |

### `newsletter` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/newsletter` | admin.newsletter.index | `Livewire\Admin\Newsletter\NewsletterSubscribersList` | AdminAuth, AdminPermission:content.newsletter.view,content.newsletter.manage |

### `notifications` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/notifications` | admin.notifications.index | `Livewire\Admin\Notifications\NotificationsPage` | AdminAuth |
| GET | `/admin/notifications/latest` | admin.notifications.latest | `Admin\AdminNotificationController@latest` | AdminAuth |
| POST | `/admin/notifications/read-all` | admin.notifications.read-all | `Admin\AdminNotificationController@readAll` | AdminAuth |
| GET | `/admin/notifications/{id}/open` | admin.notifications.open | `Admin\AdminNotificationController@open` | AdminAuth |
| POST | `/admin/notifications/{id}/read` | admin.notifications.read | `Admin\AdminNotificationController@read` | AdminAuth |

### `orders` (9)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/orders` | admin.orders.index | `Livewire\Admin\Order\OrdersList` | AdminAuth, AdminPermission:sales.orders.view,sales.orders.manage |
| GET | `/admin/orders/refunds` | admin.orders.refunds.index | `Livewire\Admin\Order\RefundsList` | AdminAuth, AdminPermission:sales.orders.view,sales.orders.manage |
| GET | `/admin/orders/report` | admin.orders.report | `Livewire\Admin\Order\OrdersReportPage` | AdminAuth, AdminPermission:sales.orders.report.view,sales.orders.manage |
| GET | `/admin/orders/returns` | admin.orders.returns.index | `Livewire\Admin\Order\OrderReturnsList` | AdminAuth, AdminPermission:sales.orders.view,sales.orders.manage |
| GET | `/admin/orders/returns/analytics` | admin.orders.returns.analytics | `Livewire\Admin\Order\ReturnAnalyticsPage` | AdminAuth, AdminPermission:sales.orders.view,sales.orders.manage |
| GET | `/admin/orders/returns/{id}` | admin.orders.returns.show | `Livewire\Admin\Order\OrderReturnDetailPage` | AdminAuth, AdminPermission:sales.orders.manage |
| GET | `/admin/orders/{tenantId}/{orderNumber}` | admin.orders.show | `Livewire\Admin\Order\OrderDetailPage` | AdminAuth, AdminPermission:sales.orders.view,sales.orders.manage |
| GET | `/admin/orders/{tenantId}/{orderNumber}/receipt` | admin.orders.receipt | `Admin\OrderReceiptController@show` | AdminAuth, AdminPermission:sales.orders.view,sales.orders.manage |
| GET | `/admin/orders/{tenantId}/{orderNumber}/shipping-label` | admin.orders.shipping-label | `Admin\OrderReceiptController@shippingLabel` | AdminAuth, AdminPermission:sales.orders.view,sales.orders.manage |

### `pages` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/pages` | admin.pages.index | `Livewire\Admin\Page\StaticPagesList` | AdminAuth, AdminPermission:content.pages.view,content.pages.manage |
| GET | `/admin/pages/create` | admin.pages.create | `Livewire\Admin\Page\AddEditStaticPage` | AdminAuth, AdminPermission:content.pages.manage |
| GET | `/admin/pages/{staticPage}/edit` | admin.pages.edit | `Livewire\Admin\Page\AddEditStaticPage` | AdminAuth, AdminPermission:content.pages.manage |

### `payment-logs` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/payment-logs` | admin.payment-logs.index | `Livewire\Admin\PaymentLog\PaymentLogsList` | AdminAuth, AdminPermission:billing.payment-logs.view |
| GET | `/admin/payment-logs/{tenantId}/{orderNumber}` | admin.payment-logs.show | `Livewire\Admin\PaymentLog\PaymentLogDetailPage` | AdminAuth, AdminPermission:billing.payment-logs.view |

### `plans` (12)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/plans` | admin.plans.index | `Livewire\Admin\Plan\PlansList` | AdminAuth, AdminPermission:plans.packages.view,plans.packages.manage |
| GET | `/admin/plans/create` | admin.plans.create | `Livewire\Admin\Plan\AddEditPackage` | AdminAuth, AdminPermission:plans.packages.manage |
| GET | `/admin/plans/pending-registrations` | admin.plans.pending-registrations | `Livewire\Admin\Plan\PendingRegistrationsList` | AdminAuth, AdminPermission:plans.pending-registrations.view,plans.pending-registrations.manage |
| GET | `/admin/plans/registered-users` | admin.plans.users | `Livewire\Admin\Plan\RegisteredUsersList` | AdminAuth, AdminPermission:plans.tenants.view,plans.tenants.manage |
| POST | `/admin/plans/registered-users` | admin.plans.users.store | `Admin\TenantEditorController@store` | AdminAuth, AdminPermission:plans.tenants.manage |
| GET | `/admin/plans/registered-users/create` | admin.plans.users.create | `Admin\TenantEditorController@create` | AdminAuth, AdminPermission:plans.tenants.manage |
| GET | `/admin/plans/registered-users/{tenantId}/impersonate` | admin.plans.users.impersonate | `Admin\TenantImpersonateController@generate` | AdminAuth, AdminPermission:plans.tenants.manage |
| PUT | `/admin/plans/registered-users/{tenant}` | admin.plans.users.update | `Admin\TenantEditorController@update` | AdminAuth, AdminPermission:plans.tenants.manage |
| GET | `/admin/plans/registered-users/{tenant}/edit` | admin.plans.users.edit | `Admin\TenantEditorController@edit` | AdminAuth, AdminPermission:plans.tenants.manage |
| PUT | `/admin/plans/registered-users/{tenant}/mobile-app` | admin.plans.users.mobile-app.update | `Admin\TenantMobileAppController@update` | AdminAuth, AdminPermission:plans.tenants.manage |
| GET | `/admin/plans/tenant-change-requests` | admin.plans.tenant-change-requests | `Livewire\Admin\Plan\TenantChangeRequestsList` | AdminAuth, AdminPermission:plans.tenant-change-requests.manage |
| GET | `/admin/plans/{package}/edit` | admin.plans.edit | `Livewire\Admin\Plan\AddEditPackage` | AdminAuth, AdminPermission:plans.packages.manage |

### `product-requests` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/product-requests` | admin.product-requests.index | `Livewire\Admin\ProductRequest\RequestsList` | AdminAuth, AdminPermission:catalog.product-requests.view,catalog.product-requests.manage |
| GET | `/admin/product-requests/{requestId}` | admin.product-requests.show | `Livewire\Admin\ProductRequest\RequestDetail` | AdminAuth, AdminPermission:catalog.product-requests.view,catalog.product-requests.manage |
| GET | `/admin/product-requests/{requestId}/file` | admin.product-requests.file | `Admin\ProductRequestFileController@show` | AdminAuth, AdminPermission:catalog.product-requests.view,catalog.product-requests.manage |

### `products` (14)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/products` | admin.products.index | `Livewire\Admin\Product\ProductsList` | AdminAuth, AdminPermission:catalog.products.view,catalog.products.manage |
| GET | `/admin/products/create` | admin.products.create | `Admin\ProductController@create` | AdminAuth, AdminPermission:catalog.products.manage |
| POST | `/admin/products/create` | admin.products.store | `Admin\ProductController@store` | AdminAuth, AdminPermission:catalog.products.manage |
| GET | `/admin/products/edit-requests` | admin.products.edit-requests | `Admin\ProductEditRequestController@index` | AdminAuth, AdminPermission:catalog.product-edit-requests.manage |
| GET | `/admin/products/edit-requests/{editRequest}` | admin.products.edit-requests.show | `Admin\ProductEditRequestController@show` | AdminAuth, AdminPermission:catalog.product-edit-requests.manage |
| POST | `/admin/products/edit-requests/{editRequest}/approve` | admin.products.edit-requests.approve | `Admin\ProductEditRequestController@approve` | AdminAuth, AdminPermission:catalog.product-edit-requests.manage |
| POST | `/admin/products/edit-requests/{editRequest}/reject` | admin.products.edit-requests.reject | `Admin\ProductEditRequestController@reject` | AdminAuth, AdminPermission:catalog.product-edit-requests.manage |
| POST | `/admin/products/edit-requests/{editRequest}/reset` | admin.products.edit-requests.reset | `Admin\ProductEditRequestController@reset` | AdminAuth, AdminPermission:catalog.product-edit-requests.manage |
| POST | `/admin/products/image-search` | admin.products.image-search | `ImageSearchController@adminPanel` | AdminAuth, AdminPermission:catalog.products.view,catalog.products.manage |
| GET | `/admin/products/sort` | admin.products.sort | `Livewire\Admin\Product\SortProducts` | AdminAuth, AdminPermission:catalog.products.manage |
| POST | `/admin/products/validate` | admin.products.validate | `Admin\ProductController@validateForm` | AdminAuth, AdminPermission:catalog.products.manage |
| GET | `/admin/products/{product}/edit` | admin.products.edit | `Admin\ProductController@edit` | AdminAuth, AdminPermission:catalog.products.manage |
| PUT | `/admin/products/{product}/edit` | admin.products.update | `Admin\ProductController@update` | AdminAuth, AdminPermission:catalog.products.manage |
| POST | `/admin/products/{product}/validate` | admin.products.validate.update | `Admin\ProductController@validateForm` | AdminAuth, AdminPermission:catalog.products.manage |

### `products-run` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/products-run` | admin. | `Admin\DevToolsController@syncProductFixedShippingCosts` | AdminAuth |

### `settings` (34)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/settings/ai` | admin.settings.ai | `Livewire\Admin\Setting\AiSettingsPage` | AdminAuth, AdminPermission:settings.general.manage |
| GET | `/admin/settings/ai-logo-limit` | admin.settings.ai-logo-limit | `Livewire\Admin\Setting\AiLogoLimitPage` | AdminAuth, AdminPermission:settings.ai-logo-limits.manage |
| GET | `/admin/settings/blade-themes` | admin.settings.blade-themes | `Livewire\Admin\Setting\BladeThemeQueuePage` | AdminAuth, AdminPermission:settings.templates.manage |
| GET | `/admin/settings/countries` | admin.settings.countries | `Livewire\Admin\Setting\CountriesPage` | AdminAuth, AdminPermission:settings.countries.view,settings.countries.manage |
| GET | `/admin/settings/currencies` | admin.settings.currencies | `Livewire\Admin\Setting\CurrenciesPage` | AdminAuth, AdminPermission:settings.currencies.view,settings.currencies.manage |
| GET | `/admin/settings/currencies/create` | admin.settings.currencies.create | `Livewire\Admin\Setting\AddEditCurrency` | AdminAuth, AdminPermission:settings.currencies.manage |
| GET | `/admin/settings/currencies/{currency}/edit` | admin.settings.currencies.edit | `Livewire\Admin\Setting\AddEditCurrency` | AdminAuth, AdminPermission:settings.currencies.manage |
| GET | `/admin/settings/default-banners` | admin.settings.default-banners | `Livewire\Admin\Setting\DefaultBannersListPage` | AdminAuth, AdminPermission:settings.default-banners.manage |
| GET | `/admin/settings/default-banners/list/{countryId?}` | admin.settings.default-banners.list | `Closure` | AdminAuth |
| GET | `/admin/settings/email-configuration` | admin.settings.email-configuration | `Livewire\Admin\Setting\EmailConfigurationPage` | AdminAuth, AdminPermission:settings.email-configuration.manage |
| GET | `/admin/settings/email-templates` | admin.settings.email-templates | `Livewire\Admin\Setting\EmailTemplatesPage` | AdminAuth, AdminPermission:settings.email-templates.manage |
| GET | `/admin/settings/email-templates/create` | admin.settings.email-templates.create | `Livewire\Admin\Setting\AddEditEmailTemplate` | AdminAuth, AdminPermission:settings.email-templates.manage |
| GET | `/admin/settings/email-templates/{emailTemplate}/edit` | admin.settings.email-templates.edit | `Livewire\Admin\Setting\AddEditEmailTemplate` | AdminAuth, AdminPermission:settings.email-templates.manage |
| GET | `/admin/settings/general` | admin.settings.general | `Livewire\Admin\Setting\GeneralSettingsPage` | AdminAuth, AdminPermission:settings.general.manage |
| GET | `/admin/settings/home-variants` | admin.settings.home-variants | `Livewire\Admin\Setting\HomeVariantsPage` | AdminAuth, AdminPermission:settings.home-variants.manage |
| GET | `/admin/settings/language-purchases` | admin.settings.language-purchases | `Livewire\Admin\Setting\LanguagePurchasesPage` | AdminAuth, AdminPermission:billing.payment-logs.view,settings.languages.purchases.view |
| GET | `/admin/settings/languages` | admin.settings.languages | `Livewire\Admin\Setting\LanguagesPage` | AdminAuth, AdminPermission:settings.languages.view,settings.languages.manage |
| POST | `/admin/settings/languages` | admin.settings.languages.store | `Admin\LanguageController@store` | AdminAuth, AdminPermission:settings.languages.manage |
| GET | `/admin/settings/languages/create` | admin.settings.languages.create | `Admin\LanguageController@create` | AdminAuth, AdminPermission:settings.languages.manage |
| PUT | `/admin/settings/languages/{language}` | admin.settings.languages.update | `Admin\LanguageController@update` | AdminAuth, AdminPermission:settings.languages.manage |
| GET | `/admin/settings/languages/{language}/edit` | admin.settings.languages.edit | `Admin\LanguageController@edit` | AdminAuth, AdminPermission:settings.languages.manage |
| GET | `/admin/settings/maintenance` | admin.settings.maintenance | `Livewire\Admin\Setting\MaintenanceModePage` | AdminAuth, AdminPermission:settings.maintenance.manage |
| GET | `/admin/settings/payment-gateway-limits` | admin.settings.payment-gateway-limits | `Livewire\Admin\Setting\PaymentGatewayLimitsPage` | AdminAuth, AdminPermission:settings.payment-gateway-limits.manage |
| GET | `/admin/settings/payment-gateways` | admin.settings.payment-gateways | `Livewire\Admin\Setting\PaymentGatewaysPage` | AdminAuth, AdminPermission:settings.payment-gateways.manage |
| GET | `/admin/settings/payment-gateways/{gateway}/edit` | admin.settings.payment-gateways.edit | `Livewire\Admin\Setting\AddEditPaymentGateway` | AdminAuth, AdminPermission:settings.payment-gateways.manage |
| GET | `/admin/settings/return-policy` | admin.settings.return-policy | `Livewire\Admin\Setting\ReturnPolicySettingsPage` | AdminAuth, AdminPermission:settings.general.manage |
| GET | `/admin/settings/social-login` | admin.settings.social-login | `Livewire\Admin\Setting\SocialLoginPage` | AdminAuth, AdminPermission:settings.social-login.manage |
| GET | `/admin/settings/template-parts` | admin.settings.template-parts | `Livewire\Admin\Setting\TemplatePartsPage` | AdminAuth, AdminPermission:settings.templates.manage |
| GET | `/admin/settings/template-parts/create/{templateId?}` | admin.settings.template-parts.create | `Livewire\Admin\Setting\AddEditTemplatePart` | AdminAuth, AdminPermission:settings.templates.manage |
| GET | `/admin/settings/template-parts/{partId}/edit` | admin.settings.template-parts.edit | `Livewire\Admin\Setting\AddEditTemplatePart` | AdminAuth, AdminPermission:settings.templates.manage |
| GET | `/admin/settings/template-parts/{templateId}` | admin.settings.template-parts.show | `Livewire\Admin\Setting\TemplatePartsPage` | AdminAuth, AdminPermission:settings.templates.manage |
| GET | `/admin/settings/templates` | admin.settings.templates | `Livewire\Admin\Setting\TemplateControlPage` | AdminAuth, AdminPermission:settings.templates.manage |
| GET | `/admin/settings/translation-key-access` | admin.settings.translation-key-access | `Livewire\Admin\Setting\TranslationKeyAccessPage` | AdminAuth, AdminPermission:settings.languages.manage |
| GET | `/admin/settings/translations` | admin.settings.translations | `Livewire\Admin\Setting\TranslationsPage` | AdminAuth, AdminPermission:settings.languages.view,settings.languages.manage |

### `shipping` (11)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/shipping/delivery-charges` | admin.shipping.delivery-charges | `Livewire\Admin\Shipping\DeliveryChargesList` | AdminAuth, AdminPermission:shipping.delivery.view,shipping.delivery.manage |
| GET | `/admin/shipping/delivery-charges/create` | admin.shipping.delivery-charges.create | `Livewire\Admin\Shipping\AddEditShippingZone` | AdminAuth, AdminPermission:shipping.delivery.manage |
| GET | `/admin/shipping/delivery-charges/{shippingZone}/edit` | admin.shipping.delivery-charges.edit | `Livewire\Admin\Shipping\AddEditShippingZone` | AdminAuth, AdminPermission:shipping.delivery.manage |
| GET | `/admin/shipping/delivery-popup` | admin.shipping.delivery-popup.index | `Livewire\Admin\Shipping\DeliveryPopupPage` | AdminAuth, AdminPermission:shipping.settings.view,shipping.settings.manage |
| GET | `/admin/shipping/fixed-costs` | admin.shipping.fixed-costs | `Livewire\Admin\Shipping\FixedShippingCostsList` | AdminAuth, AdminPermission:shipping.delivery.view,shipping.delivery.manage |
| GET | `/admin/shipping/fixed-costs/create` | admin.shipping.fixed-costs.create | `Livewire\Admin\Shipping\AddEditFixedShippingCost` | AdminAuth, AdminPermission:shipping.delivery.manage |
| GET | `/admin/shipping/fixed-costs/{fixedShippingCost}/edit` | admin.shipping.fixed-costs.edit | `Livewire\Admin\Shipping\AddEditFixedShippingCost` | AdminAuth, AdminPermission:shipping.delivery.manage |
| GET | `/admin/shipping/in-delivery` | admin.shipping.in-delivery | `Livewire\Admin\Order\InDeliveryOrdersList` | AdminAuth, AdminPermission:shipping.delivery.view,shipping.delivery.manage |
| GET | `/admin/shipping/in-delivery/{tenantId}/{orderNumber}` | admin.shipping.in-delivery.show | `Livewire\Admin\Order\InDeliveryOrderDetailPage` | AdminAuth, AdminPermission:shipping.delivery.view,shipping.delivery.manage |
| GET | `/admin/shipping/settings` | admin.shipping.settings | `Livewire\Admin\Shipping\ShippingSettingsPage` | AdminAuth, AdminPermission:shipping.settings.view,shipping.settings.manage |
| GET | `/admin/shipping/shipping-days` | admin.shipping.shipping-days.index | `Livewire\Admin\Shipping\ShippingDaysPage` | AdminAuth, AdminPermission:shipping.settings.view,shipping.settings.manage |

### `store` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/store/coupons` | admin.store.coupons.index | `Livewire\Admin\Store\CouponsPage` | AdminAuth, AdminPermission:store.coupons.manage |
| GET | `/admin/store/coupons/list/{countryId?}` | admin.store.coupons.list | `Closure` | AdminAuth |
| GET | `/admin/store/flash-sales` | admin.store.flash-sales.index | `Livewire\Admin\Store\FlashSalesPage` | AdminAuth, AdminPermission:store.flash-sales.manage |
| GET | `/admin/store/flash-sales/list/{countryId?}` | admin.store.flash-sales.list | `Closure` | AdminAuth |
| GET | `/admin/store/tenant-sync` | admin.store.tenant-sync.index | `Livewire\Admin\Store\TenantSyncPage` | AdminAuth, AdminPermission:store.sync.manage |

### `support` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/support` | admin.support.index | `Livewire\Admin\Support\TicketsList` | AdminAuth, AdminPermission:support.tickets.view,support.tickets.manage |
| GET | `/admin/support/{ticketId}` | admin.support.show | `Livewire\Admin\Support\TicketDetail` | AdminAuth, AdminPermission:support.tickets.view,support.tickets.manage |

### `variations` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/variations` | admin.variations.index | `Livewire\Admin\Variation\VariationsList` | AdminAuth, AdminPermission:catalog.variations.view,catalog.variations.manage |
| GET | `/admin/variations/create` | admin.variations.create | `Livewire\Admin\Variation\AddEditVariation` | AdminAuth, AdminPermission:catalog.variations.manage |
| GET | `/admin/variations/{variation}/edit` | admin.variations.edit | `Livewire\Admin\Variation\AddEditVariation` | AdminAuth, AdminPermission:catalog.variations.manage |

### `vendor-settlements` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/vendor-settlements` | admin.vendor-settlements.index | `Livewire\Admin\Finance\VendorSettlementsPage` | AdminAuth, AdminPermission:billing.vendor-settlements.view |

### `wallet-transactions` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/wallet-transactions` | admin.wallet-transactions.index | `Livewire\Admin\Wallet\TransactionsList` | AdminAuth, AdminPermission:billing.transactions.view |

### `wallets` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/wallets` | admin.wallets.index | `Livewire\Admin\Wallet\WalletsList` | AdminAuth, AdminPermission:billing.wallets.view |
| GET | `/admin/wallets/{tenantId}` | admin.wallets.show | `Livewire\Admin\Wallet\WalletDetailPage` | AdminAuth, AdminPermission:billing.wallets.view |

## 2. Tenant (vendor) admin panel (tenant domain, /admin)


### `(root)` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin` |  | `Tenant\Panel\Auth\LoginController@redirectToStart` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale |

### `_ui` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/_ui` | tenant.ui-kit | `Tenant\Panel\UiKitController@__invoke` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/_ui` | tenant.ui-kit.store | `Tenant\Panel\UiKitController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/_ui/data` | tenant.ui-kit.data | `Tenant\Panel\UiKitController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/_ui/validate` | tenant.ui-kit.validate | `Tenant\Panel\UiKitController@validateForm` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |

### `ai-translation-purchase` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/ai-translation-purchase/{gateway}/cancel` | tenant.ai-translation-purchase.cancel | `Tenant\AiTranslationPaymentController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET|POST | `/admin/ai-translation-purchase/{gateway}/success` | tenant.ai-translation-purchase.success | `Tenant\AiTranslationPaymentController@success` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/ai-translation-purchase/{gateway}/{languageId}` | tenant.ai-translation-purchase.charge | `Tenant\AiTranslationPaymentController@charge` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `analytics` (6)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/analytics/customer-lifetime-value/data` | tenant.analytics.clv.data | `Tenant\Panel\Insights\CustomerLifetimeValueController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:analytics.view |
| GET | `/admin/analytics/orders/data/monthly` | tenant.analytics.orders.data.monthly | `Tenant\Panel\Insights\OrderAnalyticsController@dataMonthly` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:analytics.view |
| GET | `/admin/analytics/orders/data/status` | tenant.analytics.orders.data.status | `Tenant\Panel\Insights\OrderAnalyticsController@dataStatus` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:analytics.view |
| GET | `/admin/analytics/profitability/data` | tenant.analytics.profitability.data | `Tenant\Panel\Insights\ProductProfitabilityController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:analytics.view |
| GET | `/admin/analytics/shipping/data/monthly` | tenant.analytics.shipping.data.monthly | `Tenant\Panel\Insights\ShippingAnalyticsController@dataMonthly` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:analytics.view |
| GET | `/admin/analytics/{tab?}` | tenant.analytics | `Tenant\Panel\Insights\AnalyticsController` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:analytics.view |

### `badges` (7)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/badges` | tenant.badges.index | `Tenant\Panel\Catalog\BadgeController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.badges.manage |
| GET | `/admin/badges/{badge}` | tenant.badges.show | `Tenant\Panel\Catalog\BadgeController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.badges.manage |
| POST | `/admin/badges/{badge}/assign-category` | tenant.badges.assign-category | `Tenant\Panel\Catalog\BadgeController@assignCategory` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.badges.manage |
| POST | `/admin/badges/{badge}/save` | tenant.badges.save | `Tenant\Panel\Catalog\BadgeController@save` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.badges.manage |
| GET | `/admin/badges/{badge}/search` | tenant.badges.search | `Tenant\Panel\Catalog\BadgeController@searchProducts` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.badges.manage |
| GET | `/admin/badges/{badge}/sort` | tenant.badges.sort | `Tenant\Panel\Catalog\BadgeSortController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.badges.manage |
| POST | `/admin/badges/{badge}/sort` | tenant.badges.sort | `Tenant\Panel\Catalog\BadgeSortController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.badges.manage |

### `brand-request-payment` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/brand-request-payment/{gateway}/cancel` | tenant.brand-request-payment.cancel | `Tenant\BrandRequestPaymentController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET|POST | `/admin/brand-request-payment/{gateway}/success` | tenant.brand-request-payment.success | `Tenant\BrandRequestPaymentController@success` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/brand-request-payment/{gateway}/{paymentRequestId}` | tenant.brand-request-payment.charge | `Tenant\BrandRequestPaymentController@charge` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `brand-requests` (11)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/brand-requests` | tenant.brand-requests.index | `Tenant\Panel\Requests\BrandRequestController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/brand-requests` | tenant.brand-requests.store | `Tenant\Panel\Requests\BrandRequestController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/brand-requests/create` | tenant.brand-requests.create | `Tenant\Panel\Requests\BrandRequestController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/brand-requests/data` | tenant.brand-requests.data | `Tenant\Panel\Requests\BrandRequestController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/brand-requests/validate` | tenant.brand-requests.validate | `Tenant\Panel\Requests\BrandRequestController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |
| GET | `/admin/brand-requests/{id}` | tenant.brand-requests.show | `Tenant\Panel\Requests\BrandRequestController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| DELETE | `/admin/brand-requests/{id}` | tenant.brand-requests.destroy | `Tenant\Panel\Requests\BrandRequestController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/brand-requests/{id}/messages` | tenant.brand-requests.messages | `Tenant\Panel\Requests\BrandRequestController@sendMessage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/brand-requests/{id}/messages/validate` | tenant.brand-requests.messages.validate | `Tenant\Panel\Requests\BrandRequestController@validateMessage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |
| POST | `/admin/brand-requests/{id}/pay` | tenant.brand-requests.pay | `Tenant\Panel\Requests\BrandRequestController@pay` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/brand-requests/{id}/pay/validate` | tenant.brand-requests.pay.validate | `Tenant\Panel\Requests\BrandRequestController@validatePay` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |

### `categories` (15)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/categories` | tenant.categories.index | `Tenant\Panel\Catalog\CategoryController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| POST | `/admin/categories` | tenant.categories.store | `Tenant\Panel\Catalog\CategoryController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| GET | `/admin/categories/create` | tenant.categories.create | `Tenant\Panel\Catalog\CategoryController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| GET | `/admin/categories/data` | tenant.categories.data | `Tenant\Panel\Catalog\CategoryController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| GET | `/admin/categories/sort` | tenant.categories.sort | `Tenant\Panel\Catalog\CategorySortController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| POST | `/admin/categories/sort` | tenant.categories.sort.save | `Tenant\Panel\Catalog\CategorySortController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| POST | `/admin/categories/validate` | tenant.categories.validate | `Tenant\Panel\Catalog\CategoryController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/categories/{category}` | tenant.categories.update | `Tenant\Panel\Catalog\CategoryController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| DELETE | `/admin/categories/{category}` | tenant.categories.destroy | `Tenant\Panel\Catalog\CategoryController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| PATCH | `/admin/categories/{category}/active` | tenant.categories.toggle-active | `Tenant\Panel\Catalog\CategoryController@toggleActive` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| GET | `/admin/categories/{category}/edit` | tenant.categories.edit | `Tenant\Panel\Catalog\CategoryController@edit` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| PATCH | `/admin/categories/{category}/featured` | tenant.categories.toggle-featured | `Tenant\Panel\Catalog\CategoryController@toggleFeatured` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| GET | `/admin/categories/{category}/products` | tenant.categories.products | `Tenant\Panel\Catalog\CategoryProductsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| POST | `/admin/categories/{category}/products/sort` | tenant.categories.products.sort | `Tenant\Panel\Catalog\CategoryProductsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage |
| POST | `/admin/categories/{category}/validate` | tenant.categories.validate.update | `Tenant\Panel\Catalog\CategoryController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.categories.manage, ThrottleRequests:tenant-validate |

### `cities-by-country` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/cities-by-country/{countryId?}` | tenant.cities.by-country | `Tenant\Panel\Sales\CustomerDetailController@citiesByCountry` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |

### `compliance` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/compliance/accept` | tenant.compliance.accept | `Tenant\Panel\Shell\ComplianceController@accept` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/compliance/accept/validate` | tenant.compliance.accept.validate | `Tenant\Panel\Shell\ComplianceController@validateAccept` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |

### `customers` (17)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/customers` | tenant.customers.index | `Tenant\Panel\Sales\CustomersController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| POST | `/admin/customers` | tenant.customers.store | `Tenant\Panel\Sales\CustomerCreateController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| GET | `/admin/customers/create` | tenant.customers.create | `Tenant\Panel\Sales\CustomerCreateController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| GET | `/admin/customers/data` | tenant.customers.data | `Tenant\Panel\Sales\CustomersController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| GET | `/admin/customers/export` | tenant.customers.export | `Tenant\Panel\Sales\CustomersController@export` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| POST | `/admin/customers/validate` | tenant.customers.validate | `Tenant\Panel\Sales\CustomerCreateController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/customers/{customerId}` | tenant.customers.show | `Tenant\Panel\Sales\CustomerDetailController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| PUT | `/admin/customers/{customerId}` | tenant.customers.update | `Tenant\Panel\Sales\CustomerDetailController@updateProfile` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| DELETE | `/admin/customers/{customerId}` | tenant.customers.destroy | `Tenant\Panel\Sales\CustomersController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| PATCH | `/admin/customers/{customerId}/active` | tenant.customers.toggle-active | `Tenant\Panel\Sales\CustomerDetailController@toggleActive` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| POST | `/admin/customers/{customerId}/addresses` | tenant.customers.addresses.store | `Tenant\Panel\Sales\CustomerDetailController@storeAddress` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| POST | `/admin/customers/{customerId}/addresses/validate` | tenant.customers.addresses.validate | `Tenant\Panel\Sales\CustomerDetailController@validateAddress` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/customers/{customerId}/addresses/{addressId}` | tenant.customers.addresses.show | `Tenant\Panel\Sales\CustomerDetailController@showAddress` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| PUT | `/admin/customers/{customerId}/addresses/{addressId}` | tenant.customers.addresses.update | `Tenant\Panel\Sales\CustomerDetailController@updateAddress` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| DELETE | `/admin/customers/{customerId}/addresses/{addressId}` | tenant.customers.addresses.destroy | `Tenant\Panel\Sales\CustomerDetailController@destroyAddress` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| GET | `/admin/customers/{customerId}/payments/data` | tenant.customers.payments.data | `Tenant\Panel\Sales\CustomerDetailController@paymentsData` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage |
| POST | `/admin/customers/{customerId}/validate` | tenant.customers.validate.update | `Tenant\Panel\Sales\CustomerDetailController@validateProfile` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.customers.manage, ThrottleRequests:tenant-validate |

### `dashboard` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/dashboard` | tenant.dashboard | `Tenant\Panel\Insights\DashboardController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:dashboard.view |

### `email` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/email/verification-notification` | tenant.verification.send | `Tenant\EmailVerificationController@send` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:6,1 |
| GET | `/admin/email/verify/{id}/{hash}` | tenant.verification.verify | `Tenant\EmailVerificationController@verify` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, ValidateSignature, ThrottleRequests:6,1 |

### `finance` (27)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/finance/billing` | tenant.finance.billing | `Tenant\Panel\Finance\BillingController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.billing.view |
| GET | `/admin/finance/billing/data` | tenant.finance.billing.data | `Tenant\Panel\Finance\BillingController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.billing.view |
| GET | `/admin/finance/billing/{orderId}` | tenant.finance.billing.detail | `Tenant\Panel\Finance\BillingController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.billing.view |
| GET | `/admin/finance/buy-languages` | tenant.finance.buy-languages | `Tenant\Panel\Finance\BuyLanguageController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.languages.purchase |
| POST | `/admin/finance/buy-languages` | tenant.finance.buy-languages.purchase | `Tenant\Panel\Finance\BuyLanguageController@purchase` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.languages.purchase |
| GET | `/admin/finance/buy-languages/data` | tenant.finance.buy-languages.data | `Tenant\Panel\Finance\BuyLanguageController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.languages.purchase |
| POST | `/admin/finance/buy-languages/validate` | tenant.finance.buy-languages.purchase.validate | `Tenant\Panel\Finance\BuyLanguageController@validatePurchase` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.languages.purchase, ThrottleRequests:tenant-validate |
| GET | `/admin/finance/payouts-received` | tenant.finance.payouts | `Tenant\Panel\Finance\PayoutsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| GET | `/admin/finance/payouts-received/data` | tenant.finance.payouts.data | `Tenant\Panel\Finance\PayoutsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| GET | `/admin/finance/settlement-payments` | tenant.finance.settlement-payments | `Tenant\Panel\Finance\SettlementPaymentsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| GET | `/admin/finance/settlement-payments/data` | tenant.finance.settlement-payments.data | `Tenant\Panel\Finance\SettlementPaymentsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| GET | `/admin/finance/vendor-purchases` | tenant.finance.vendor-purchases | `Tenant\Panel\Finance\VendorPurchaseController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| GET | `/admin/finance/vendor-purchases/data` | tenant.finance.vendor-purchases.data | `Tenant\Panel\Finance\VendorPurchaseController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| GET | `/admin/finance/vendor-purchases/export` | tenant.finance.vendor-purchases.export | `Tenant\Panel\Finance\VendorPurchaseController@export` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| GET | `/admin/finance/vendor-purchases/{orderId}/settle` | tenant.finance.vendor-purchase-settle | `Tenant\Panel\Finance\VendorSettleOrderController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| POST | `/admin/finance/vendor-purchases/{orderId}/settle` | tenant.finance.vendor-purchase-settle.pay | `Tenant\Panel\Finance\VendorSettleOrderController@settle` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| GET | `/admin/finance/vendor-purchases/{orderId}/settle/breakdown` | tenant.finance.vendor-purchase-settle.breakdown | `Tenant\Panel\Finance\VendorSettleOrderController@breakdown` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view |
| POST | `/admin/finance/vendor-purchases/{orderId}/settle/validate` | tenant.finance.vendor-purchase-settle.pay.validate | `Tenant\Panel\Finance\VendorSettleOrderController@validateSettle` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.vendor-purchases.view, ThrottleRequests:tenant-validate |
| GET | `/admin/finance/wallet` | tenant.finance.wallet | `Tenant\Panel\Finance\WalletController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| GET | `/admin/finance/wallet/partner/conversions/data` | tenant.finance.wallet.partner.conversions.data | `Tenant\Panel\Finance\PartnerWalletController@conversionsData` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| GET | `/admin/finance/wallet/partner/payouts/data` | tenant.finance.wallet.partner.payouts.data | `Tenant\Panel\Finance\PartnerWalletController@payoutsData` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| POST | `/admin/finance/wallet/partner/sso` | tenant.finance.wallet.partner.sso | `Tenant\Panel\Finance\PartnerWalletController@sso` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view, ThrottleRequests:10,1 |
| POST | `/admin/finance/wallet/partner/withdraw` | tenant.finance.wallet.partner.withdraw | `Tenant\Panel\Finance\PartnerWalletController@withdraw` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| POST | `/admin/finance/wallet/subscription` | tenant.finance.wallet.subscribe | `Tenant\Panel\Finance\WalletController@subscribe` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| POST | `/admin/finance/wallet/subscription/validate` | tenant.finance.wallet.subscribe.validate | `Tenant\Panel\Finance\WalletController@validateSubscribe` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view, ThrottleRequests:tenant-validate |
| GET | `/admin/finance/wallet/subscriptions/data` | tenant.finance.wallet.subscriptions.data | `Tenant\Panel\Finance\WalletController@subscriptionsData` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |
| GET | `/admin/finance/wallet/transactions/data` | tenant.finance.wallet.transactions.data | `Tenant\Panel\Finance\WalletController@transactionsData` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |

### `forgot-password` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/forgot-password` | tenant.password.request | `Tenant\Panel\Auth\PasswordResetController@showForgot` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, RedirectIfAuthenticated:tenant |
| POST | `/admin/forgot-password` | tenant.password.email | `Tenant\Panel\Auth\PasswordResetController@sendLink` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, RedirectIfAuthenticated:tenant, ThrottleRequests:5,1 |

### `help` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/help` | tenant.help.index | `Tenant\Panel\Support\HelpController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/help/articles/{slug}` | tenant.help.article | `Tenant\Panel\Support\HelpController@article` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `impersonate` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/impersonate/{token}` | tenant.impersonate.accept | `Tenant\TenantImpersonateController@accept` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale |

### `language-purchase` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/language-purchase/{gateway}/cancel` | tenant.language-purchase.cancel | `Tenant\LanguagePaymentController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET|POST | `/admin/language-purchase/{gateway}/success` | tenant.language-purchase.success | `Tenant\LanguagePaymentController@success` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/language-purchase/{gateway}/{languageId}` | tenant.language-purchase.charge | `Tenant\LanguagePaymentController@charge` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `locale` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/locale/{locale}` | tenant.locale.switch | `Tenant\Panel\Shell\LocaleController` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale |

### `login` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/login` | tenant.login | `Tenant\Panel\Auth\LoginController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, RedirectIfAuthenticated:tenant |
| POST | `/admin/login` | tenant.login.attempt | `Tenant\Panel\Auth\LoginController@login` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, RedirectIfAuthenticated:tenant, ThrottleRequests:6,1 |
| POST | `/admin/login/validate` | tenant.login.validate | `Tenant\Panel\Auth\LoginController@validateLogin` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, RedirectIfAuthenticated:tenant, ThrottleRequests:tenant-validate |

### `logout` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/logout` | tenant.logout | `Tenant\Panel\Auth\LoginController@logout` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant |

### `manufacturing` (13)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/manufacturing` | tenant.manufacturing.index | `Tenant\Panel\Requests\ManufacturingController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/manufacturing` | tenant.manufacturing.store | `Tenant\Panel\Requests\ManufacturingController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/manufacturing/create` | tenant.manufacturing.create | `Tenant\Panel\Requests\ManufacturingController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/manufacturing/data` | tenant.manufacturing.data | `Tenant\Panel\Requests\ManufacturingController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/manufacturing/export` | tenant.manufacturing.export | `Tenant\Panel\Requests\ManufacturingController@export` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/manufacturing/products/search` | tenant.manufacturing.products.search | `Tenant\Panel\Requests\ManufacturingController@searchProducts` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/manufacturing/validate` | tenant.manufacturing.validate | `Tenant\Panel\Requests\ManufacturingController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |
| GET | `/admin/manufacturing/{id}` | tenant.manufacturing.show | `Tenant\Panel\Requests\ManufacturingController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/manufacturing/{id}/cancel` | tenant.manufacturing.cancel | `Tenant\Panel\Requests\ManufacturingController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/manufacturing/{id}/messages` | tenant.manufacturing.messages | `Tenant\Panel\Requests\ManufacturingController@sendMessage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/manufacturing/{id}/messages/validate` | tenant.manufacturing.messages.validate | `Tenant\Panel\Requests\ManufacturingController@validateMessage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |
| POST | `/admin/manufacturing/{id}/pay` | tenant.manufacturing.pay | `Tenant\Panel\Requests\ManufacturingController@pay` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/manufacturing/{id}/pay/validate` | tenant.manufacturing.pay.validate | `Tenant\Panel\Requests\ManufacturingController@validatePay` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |

### `manufacturing-payment` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/manufacturing-payment/{gateway}/cancel` | tenant.manufacturing-payment.cancel | `Tenant\ManufacturingPaymentController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET|POST | `/admin/manufacturing-payment/{gateway}/success` | tenant.manufacturing-payment.success | `Tenant\ManufacturingPaymentController@success` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/manufacturing-payment/{gateway}/{paymentRequestId}` | tenant.manufacturing-payment.charge | `Tenant\ManufacturingPaymentController@charge` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `notifications` (6)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/notifications` | tenant.notifications.index | `Tenant\Panel\Support\NotificationsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/notifications/feed` | tenant.notifications.feed | `Tenant\Panel\Support\NotificationsController@feed` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/notifications/latest` | tenant.notifications.latest | `Tenant\Panel\Support\NotificationsController@latest` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/notifications/read-all` | tenant.notifications.read-all | `Tenant\Panel\Support\NotificationsController@markAllRead` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/notifications/{id}/open` | tenant.notifications.open | `Tenant\Panel\Support\NotificationsController@open` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| PATCH | `/admin/notifications/{id}/read` | tenant.notifications.mark-read | `Tenant\Panel\Support\NotificationsController@markRead` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `onboarding` (6)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/onboarding/dismiss` | tenant.onboarding.dismiss | `Tenant\Panel\Onboarding\OnboardingController@dismiss` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/onboarding/logo` | tenant.onboarding.logo | `Tenant\Panel\Onboarding\OnboardingController@saveLogo` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/onboarding/logo/validate` | tenant.onboarding.logo.validate | `Tenant\Panel\Onboarding\OnboardingController@validateLogo` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |
| POST | `/admin/onboarding/payment-readiness/skip` | tenant.onboarding.payment-readiness.skip | `Tenant\Panel\Onboarding\OnboardingController@skipPaymentReadiness` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/onboarding/tour/complete` | tenant.onboarding.tour.complete | `Tenant\Panel\Onboarding\OnboardingController@completeTour` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/onboarding/{tab?}` | tenant.onboarding | `Tenant\Panel\Onboarding\OnboardingController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `orders` (10)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/orders` | tenant.orders.index | `Tenant\Panel\Sales\OrdersController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.view, SetupGuard:payment_gateway |
| GET | `/admin/orders/data` | tenant.orders.data | `Tenant\Panel\Sales\OrdersController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.view, SetupGuard:payment_gateway |
| GET | `/admin/orders/export` | tenant.orders.export | `Tenant\Panel\Sales\OrdersController@export` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.view, SetupGuard:payment_gateway |
| GET | `/admin/orders/{orderId}` | tenant.orders.show | `Tenant\Panel\Sales\OrdersController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.view |
| POST | `/admin/orders/{orderId}/cancel` | tenant.orders.cancel | `Tenant\Panel\Sales\OrdersController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.manage |
| POST | `/admin/orders/{orderId}/cancel/validate` | tenant.orders.cancel.validate | `Tenant\Panel\Sales\OrdersController@validateCancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/orders/{orderId}/refunds` | tenant.orders.refunds.store | `Tenant\Panel\Sales\OrdersController@storeRefund` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/orders/{orderId}/refunds/validate` | tenant.orders.refunds.validate | `Tenant\Panel\Sales\OrdersController@validateStoreRefund` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| PATCH | `/admin/orders/{orderId}/shipping-status` | tenant.orders.shipping-status | `Tenant\Panel\Sales\OrdersController@updateShippingStatus` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.view |
| POST | `/admin/orders/{orderId}/shipping-status/validate` | tenant.orders.shipping-status.validate | `Tenant\Panel\Sales\OrdersController@validateUpdateShippingStatus` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.orders.view, ThrottleRequests:tenant-validate |

### `own-products` (9)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/own-products` | tenant.own-products.index | `Tenant\Panel\Catalog\OwnProductsListController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/own-products` | tenant.own-products.store | `Tenant\Panel\Catalog\OwnProductController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/own-products/create` | tenant.own-products.create | `Tenant\Panel\Catalog\OwnProductController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/own-products/data` | tenant.own-products.data | `Tenant\Panel\Catalog\OwnProductsListController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/own-products/validate` | tenant.own-products.validate | `Tenant\Panel\Catalog\OwnProductController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/own-products/{product}` | tenant.own-products.update | `Tenant\Panel\Catalog\OwnProductController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| DELETE | `/admin/own-products/{product}` | tenant.own-products.destroy | `Tenant\Panel\Catalog\OwnProductsListController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/own-products/{product}/edit` | tenant.own-products.edit | `Tenant\Panel\Catalog\OwnProductController@edit` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/own-products/{product}/validate` | tenant.own-products.validate.update | `Tenant\Panel\Catalog\OwnProductController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, ThrottleRequests:tenant-validate |

### `partner-program` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/partner-program` | tenant.partner-program | `Tenant\Panel\Finance\PartnerProgramController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:finance.wallet.view |

### `product-requests` (8)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/product-requests` | tenant.product-requests.index | `Tenant\Panel\Requests\ProductRequestController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/product-requests` | tenant.product-requests.store | `Tenant\Panel\Requests\ProductRequestController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/product-requests/data` | tenant.product-requests.data | `Tenant\Panel\Requests\ProductRequestController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/product-requests/new` | tenant.product-requests.create | `Tenant\Panel\Requests\ProductRequestController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/product-requests/validate` | tenant.product-requests.validate | `Tenant\Panel\Requests\ProductRequestController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/product-requests/{requestId}` | tenant.product-requests.show | `Tenant\Panel\Requests\ProductRequestController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/product-requests/{requestId}/replies` | tenant.product-requests.replies | `Tenant\Panel\Requests\ProductRequestController@reply` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/product-requests/{requestId}/replies/validate` | tenant.product-requests.replies.validate | `Tenant\Panel\Requests\ProductRequestController@validateReply` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, ThrottleRequests:tenant-validate |

### `products` (29)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/products` | tenant.products.index | `Tenant\Panel\Catalog\ProductsListController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, SetupGuard:theme |
| POST | `/admin/products` | tenant.products.store | `Tenant\Panel\Catalog\ProductController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/ai-translate` | tenant.products.ai-translate | `Tenant\Panel\Catalog\ProductAiTranslationController@translate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, ThrottleRequests:tenant-ai-product-translate |
| GET | `/admin/products/central-search` | tenant.products.central-search | `Tenant\Panel\Catalog\ProductController@centralSearch` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/central-snapshot/{centralProduct}` | tenant.products.central-snapshot | `Tenant\Panel\Catalog\ProductController@centralSnapshot` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/create` | tenant.products.create | `Tenant\Panel\Catalog\ProductController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/data` | tenant.products.data | `Tenant\Panel\Catalog\ProductsListController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/edit-requests` | tenant.products.edit-requests | `Tenant\Panel\Catalog\EditRequestsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/edit-requests/data` | tenant.products.edit-requests.data | `Tenant\Panel\Catalog\EditRequestsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/image-search` | tenant.products.image-search | `ImageSearchController@tenantPanel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/new` | tenant.products.new | `Tenant\Panel\Catalog\ProductsListController@newProducts` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/new/{centralProductId}` | tenant.products.new.show | `Tenant\Panel\Catalog\ProductsListController@previewNew` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/sort` | tenant.products.sort | `Tenant\Panel\Catalog\ProductSortController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/sort` | tenant.products.sort.save | `Tenant\Panel\Catalog\ProductSortController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/sync-new` | tenant.products.sync-new | `Tenant\Panel\Catalog\ProductsListController@syncNew` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/validate` | tenant.products.validate | `Tenant\Panel\Catalog\ProductController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/products/{product}` | tenant.products.update | `Tenant\Panel\Catalog\ProductController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| PATCH | `/admin/products/{product}/active` | tenant.products.toggle-active | `Tenant\Panel\Catalog\ProductsListController@toggleActive` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/{product}/ai-price` | tenant.products.ai-price | `Tenant\Panel\Catalog\ProductModalsController@aiPrice` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/{product}/ai-price` | tenant.products.ai-price.fetch | `Tenant\Panel\Catalog\ProductModalsController@fetchAiPrice` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/{product}/edit` | tenant.products.edit | `Tenant\Panel\Catalog\ProductController@edit` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| PATCH | `/admin/products/{product}/featured` | tenant.products.toggle-featured | `Tenant\Panel\Catalog\ProductsListController@toggleFeatured` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/{product}/price-list` | tenant.products.price-list | `Tenant\Panel\Catalog\ProductModalsController@priceListShow` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| PUT | `/admin/products/{product}/price-list` | tenant.products.price-list.save | `Tenant\Panel\Catalog\ProductModalsController@priceListSave` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/{product}/price-list/preview` | tenant.products.price-list.preview | `Tenant\Panel\Catalog\ProductModalsController@priceListPreview` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/{product}/share` | tenant.products.share | `Tenant\Panel\Catalog\ProductModalsController@share` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| GET | `/admin/products/{product}/social` | tenant.products.social | `Tenant\Panel\Catalog\ProductModalsController@social` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/{product}/social/generate` | tenant.products.social.generate | `Tenant\Panel\Catalog\ProductModalsController@generateSocial` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage |
| POST | `/admin/products/{product}/validate` | tenant.products.validate.update | `Tenant\Panel\Catalog\ProductController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:catalog.products.manage, ThrottleRequests:tenant-validate |

### `refunds` (7)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/refunds` | tenant.refunds.index | `Tenant\Panel\Sales\RefundsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| GET | `/admin/refunds/data` | tenant.refunds.data | `Tenant\Panel\Sales\RefundsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/refunds/{id}/complete` | tenant.refunds.complete | `Tenant\Panel\Sales\RefundController@complete` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/refunds/{id}/complete/validate` | tenant.refunds.complete.validate | `Tenant\Panel\Sales\RefundController@validateComplete` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/refunds/{id}/reject` | tenant.refunds.reject | `Tenant\Panel\Sales\RefundController@reject` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/refunds/{id}/reject/validate` | tenant.refunds.reject.validate | `Tenant\Panel\Sales\RefundController@validateReject` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/refunds/{id}/retry` | tenant.refunds.retry | `Tenant\Panel\Sales\RefundController@retry` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |

### `reset-password` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/admin/reset-password` | tenant.password.update | `Tenant\Panel\Auth\PasswordResetController@reset` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, RedirectIfAuthenticated:tenant, ThrottleRequests:5,1 |
| GET | `/admin/reset-password/{token}` | tenant.password.reset | `Tenant\Panel\Auth\PasswordResetController@showReset` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, RedirectIfAuthenticated:tenant |

### `returns` (25)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/returns` | tenant.returns.index | `Tenant\Panel\Sales\ReturnsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| GET | `/admin/returns/analytics` | tenant.returns.analytics | `Tenant\Panel\Sales\ReturnAnalyticsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| GET | `/admin/returns/analytics/data` | tenant.returns.analytics.data | `Tenant\Panel\Sales\ReturnAnalyticsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| GET | `/admin/returns/data` | tenant.returns.data | `Tenant\Panel\Sales\ReturnsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| GET | `/admin/returns/{id}` | tenant.returns.show | `Tenant\Panel\Sales\ReturnController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/approve` | tenant.returns.approve | `Tenant\Panel\Sales\ReturnController@approve` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/approve/validate` | tenant.returns.approve.validate | `Tenant\Panel\Sales\ReturnController@validateApprove` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/returns/{id}/close` | tenant.returns.close | `Tenant\Panel\Sales\ReturnController@close` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/convert-to-refund` | tenant.returns.convert-to-refund | `Tenant\Panel\Sales\ReturnController@convertToRefund` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/exchange-completed` | tenant.returns.exchange-completed | `Tenant\Panel\Sales\ReturnController@markExchangeCompleted` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/exchange-shipped` | tenant.returns.exchange-shipped | `Tenant\Panel\Sales\ReturnController@markExchangeShipped` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/exchange-shipped/validate` | tenant.returns.exchange-shipped.validate | `Tenant\Panel\Sales\ReturnController@validateExchangeShipped` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/returns/{id}/inspect` | tenant.returns.inspect | `Tenant\Panel\Sales\ReturnController@inspect` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/inspect/validate` | tenant.returns.inspect.validate | `Tenant\Panel\Sales\ReturnController@validateInspect` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/returns/{id}/issue-refund` | tenant.returns.issue-refund | `Tenant\Panel\Sales\ReturnController@issueRefund` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/issue-refund/validate` | tenant.returns.issue-refund.validate | `Tenant\Panel\Sales\ReturnController@validateIssueRefund` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/returns/{id}/notes` | tenant.returns.notes | `Tenant\Panel\Sales\ReturnController@addNote` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/notes/validate` | tenant.returns.notes.validate | `Tenant\Panel\Sales\ReturnController@validateNote` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/returns/{id}/received` | tenant.returns.received | `Tenant\Panel\Sales\ReturnController@markItemReceived` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/refunded` | tenant.returns.refunded | `Tenant\Panel\Sales\ReturnController@markRefunded` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/refunded/validate` | tenant.returns.refunded.validate | `Tenant\Panel\Sales\ReturnController@validateRefunded` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/returns/{id}/reject` | tenant.returns.reject | `Tenant\Panel\Sales\ReturnController@reject` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/reject/validate` | tenant.returns.reject.validate | `Tenant\Panel\Sales\ReturnController@validateReject` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/returns/{id}/request-info` | tenant.returns.request-info | `Tenant\Panel\Sales\ReturnController@requestMoreInfo` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/returns/{id}/request-info/validate` | tenant.returns.request-info.validate | `Tenant\Panel\Sales\ReturnController@validateRequestInfo` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |

### `settings` (91)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/settings/account` | tenant.settings.account | `Tenant\Panel\Settings\AccountSettingsController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| PUT | `/admin/settings/account` | tenant.settings.account.update | `Tenant\Panel\Settings\AccountSettingsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| POST | `/admin/settings/account/validate` | tenant.settings.account.validate | `Tenant\Panel\Settings\AccountSettingsController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/admins` | tenant.settings.admins | `Tenant\Panel\Settings\AdminsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| POST | `/admin/settings/admins` | tenant.settings.admins.store | `Tenant\Panel\Settings\AdminsController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| GET | `/admin/settings/admins/data` | tenant.settings.admins.data | `Tenant\Panel\Settings\AdminsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| POST | `/admin/settings/admins/validate` | tenant.settings.admins.validate | `Tenant\Panel\Settings\AdminsController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/admins/{admin}` | tenant.settings.admins.show | `Tenant\Panel\Settings\AdminsController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| PUT | `/admin/settings/admins/{admin}` | tenant.settings.admins.update | `Tenant\Panel\Settings\AdminsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| DELETE | `/admin/settings/admins/{admin}` | tenant.settings.admins.destroy | `Tenant\Panel\Settings\AdminsController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| POST | `/admin/settings/admins/{admin}/activate` | tenant.settings.admins.activate | `Tenant\Panel\Settings\AdminsController@activate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| POST | `/admin/settings/admins/{admin}/deactivate` | tenant.settings.admins.deactivate | `Tenant\Panel\Settings\AdminsController@deactivate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage |
| POST | `/admin/settings/admins/{admin}/validate` | tenant.settings.admins.validate.update | `Tenant\Panel\Settings\AdminsController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.admins.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/ai-translation` | tenant.settings.ai-translation | `Tenant\Panel\Settings\AiTranslationController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.translations.manage |
| GET | `/admin/settings/ai-translation/history/data` | tenant.settings.ai-translation.history.data | `Tenant\Panel\Settings\AiTranslationController@historyData` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.translations.manage |
| POST | `/admin/settings/ai-translation/purchase/validate` | tenant.settings.ai-translation.purchase.validate | `Tenant\Panel\Settings\AiTranslationController@validatePurchase` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.translations.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/ai-translation/status` | tenant.settings.ai-translation.status | `Tenant\Panel\Settings\AiTranslationController@status` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.translations.manage |
| POST | `/admin/settings/ai-translation/{language}/purchase` | tenant.settings.ai-translation.purchase | `Tenant\Panel\Settings\AiTranslationController@purchase` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.translations.manage |
| POST | `/admin/settings/ai-translation/{language}/run` | tenant.settings.ai-translation.run | `Tenant\Panel\Settings\AiTranslationController@run` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.translations.manage |
| GET | `/admin/settings/compliance` | tenant.settings.compliance | `Tenant\Panel\Settings\ComplianceCenterController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| POST | `/admin/settings/compliance` | tenant.settings.compliance.update | `Tenant\Panel\Settings\ComplianceCenterController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| GET | `/admin/settings/compliance/cities-by-country/{countryId}` | tenant.settings.compliance.cities-by-country | `Tenant\Panel\Settings\ComplianceCenterController@citiesByCountry` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| POST | `/admin/settings/compliance/validate` | tenant.settings.compliance.validate | `Tenant\Panel\Settings\ComplianceCenterController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/currencies` | tenant.settings.currencies | `Tenant\Panel\Settings\CurrenciesController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| GET | `/admin/settings/currencies/data` | tenant.settings.currencies.data | `Tenant\Panel\Settings\CurrenciesController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| PATCH | `/admin/settings/currencies/{currency}/active` | tenant.settings.currencies.toggle-active | `Tenant\Panel\Settings\CurrenciesController@toggleActive` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| POST | `/admin/settings/currencies/{currency}/default` | tenant.settings.currencies.default | `Tenant\Panel\Settings\CurrenciesController@makeDefault` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| GET | `/admin/settings/domains` | tenant.settings.domains | `Tenant\Panel\Settings\DomainsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage |
| POST | `/admin/settings/domains` | tenant.settings.domains.store | `Tenant\Panel\Settings\DomainsController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage |
| POST | `/admin/settings/domains/validate` | tenant.settings.domains.validate | `Tenant\Panel\Settings\DomainsController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/domains/{domainRequest}` | tenant.settings.domains.show | `Tenant\Panel\Settings\DomainsController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage |
| PUT | `/admin/settings/domains/{domainRequest}` | tenant.settings.domains.update | `Tenant\Panel\Settings\DomainsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage |
| DELETE | `/admin/settings/domains/{domainRequest}` | tenant.settings.domains.destroy | `Tenant\Panel\Settings\DomainsController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage |
| POST | `/admin/settings/domains/{domainRequest}/check-dns` | tenant.settings.domains.check-dns | `Tenant\Panel\Settings\DomainsController@checkDns` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage |
| POST | `/admin/settings/domains/{domainRequest}/validate` | tenant.settings.domains.validate.update | `Tenant\Panel\Settings\DomainsController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.domains.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/email-templates` | tenant.settings.email-templates | `Tenant\Panel\Settings\EmailTemplateController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| GET | `/admin/settings/email-templates/data` | tenant.settings.email-templates.data | `Tenant\Panel\Settings\EmailTemplateController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| PUT | `/admin/settings/email-templates/{emailTemplate}` | tenant.settings.email-templates.update | `Tenant\Panel\Settings\EmailTemplateController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| GET | `/admin/settings/email-templates/{emailTemplate}/edit` | tenant.settings.email-templates.edit | `Tenant\Panel\Settings\EmailTemplateController@edit` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| POST | `/admin/settings/email-templates/{emailTemplate}/resync` | tenant.settings.email-templates.resync | `Tenant\Panel\Settings\EmailTemplateController@resync` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| POST | `/admin/settings/email-templates/{emailTemplate}/validate` | tenant.settings.email-templates.validate | `Tenant\Panel\Settings\EmailTemplateController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/general` | tenant.settings.general | `Tenant\Panel\Settings\GeneralSettingsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| PUT | `/admin/settings/general` | tenant.settings.general.update | `Tenant\Panel\Settings\GeneralSettingsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| POST | `/admin/settings/general/category-request` | tenant.settings.general.category-request | `Tenant\Panel\Settings\GeneralSettingsController@submitCategoryRequest` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| POST | `/admin/settings/general/category-request/validate` | tenant.settings.general.category-request.validate | `Tenant\Panel\Settings\GeneralSettingsController@validateCategoryRequest` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/settings/general/country-request` | tenant.settings.general.country-request | `Tenant\Panel\Settings\GeneralSettingsController@submitCountryRequest` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage |
| POST | `/admin/settings/general/country-request/validate` | tenant.settings.general.country-request.validate | `Tenant\Panel\Settings\GeneralSettingsController@validateCountryRequest` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/settings/general/validate` | tenant.settings.general.validate | `Tenant\Panel\Settings\GeneralSettingsController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.account.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/languages` | tenant.settings.languages | `Tenant\Panel\Settings\LanguagesController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| GET | `/admin/settings/languages-manage` | tenant.settings.languages-manage | `Tenant\Panel\Settings\LanguagesManageController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| GET | `/admin/settings/languages-manage/available` | tenant.settings.languages-manage.available.data | `Tenant\Panel\Settings\LanguagesManageController@available` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| GET | `/admin/settings/languages-manage/data` | tenant.settings.languages-manage.data | `Tenant\Panel\Settings\LanguagesManageController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| POST | `/admin/settings/languages-manage/purchase` | tenant.settings.languages-manage.purchase | `Tenant\Panel\Settings\LanguagesManageController@purchase` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| POST | `/admin/settings/languages-manage/purchase/validate` | tenant.settings.languages-manage.purchase.validate | `Tenant\Panel\Settings\LanguagesManageController@validatePurchase` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage, ThrottleRequests:tenant-validate |
| PATCH | `/admin/settings/languages-manage/{language}/active` | tenant.settings.languages-manage.toggle-active | `Tenant\Panel\Settings\LanguagesManageController@toggleActive` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| POST | `/admin/settings/languages-manage/{language}/default` | tenant.settings.languages-manage.default | `Tenant\Panel\Settings\LanguagesManageController@makeDefault` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| GET | `/admin/settings/languages/data` | tenant.settings.languages.data | `Tenant\Panel\Settings\LanguagesController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| PATCH | `/admin/settings/languages/{language}/active` | tenant.settings.languages.toggle-active | `Tenant\Panel\Settings\LanguagesController@toggleActive` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| POST | `/admin/settings/languages/{language}/default` | tenant.settings.languages.default | `Tenant\Panel\Settings\LanguagesController@makeDefault` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.regional.manage |
| GET | `/admin/settings/mail` | tenant.settings.mail | `Tenant\Panel\Settings\MailConfigurationsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| PUT | `/admin/settings/mail` | tenant.settings.mail.update | `Tenant\Panel\Settings\MailConfigurationsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| POST | `/admin/settings/mail/test` | tenant.settings.mail.test | `Tenant\Panel\Settings\MailConfigurationsController@sendTest` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage |
| POST | `/admin/settings/mail/test/validate` | tenant.settings.mail.test.validate | `Tenant\Panel\Settings\MailConfigurationsController@validateSendTest` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage, ThrottleRequests:tenant-validate |
| POST | `/admin/settings/mail/validate` | tenant.settings.mail.validate | `Tenant\Panel\Settings\MailConfigurationsController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.mail.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/payment-gateways` | tenant.settings.payment-gateways | `Tenant\Panel\Settings\PaymentGatewaysController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage |
| GET | `/admin/settings/payment-gateways/data` | tenant.settings.payment-gateways.data | `Tenant\Panel\Settings\PaymentGatewaysController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage |
| GET | `/admin/settings/payment-gateways/{gateway}` | tenant.settings.payment-gateways.show | `Tenant\Panel\Settings\PaymentGatewaysController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage |
| PUT | `/admin/settings/payment-gateways/{gateway}` | tenant.settings.payment-gateways.update | `Tenant\Panel\Settings\PaymentGatewaysController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage |
| POST | `/admin/settings/payment-gateways/{gateway}/check-connection` | tenant.settings.payment-gateways.check | `Tenant\Panel\Settings\PaymentGatewaysController@checkConnection` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage |
| POST | `/admin/settings/payment-gateways/{gateway}/primary` | tenant.settings.payment-gateways.primary | `Tenant\Panel\Settings\PaymentGatewaysController@setPrimary` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage |
| POST | `/admin/settings/payment-gateways/{gateway}/validate` | tenant.settings.payment-gateways.validate.update | `Tenant\Panel\Settings\PaymentGatewaysController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/payment-readiness` | tenant.settings.payment-readiness | `Tenant\Panel\Settings\PaymentReadinessController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.payment-gateways.manage |
| GET | `/admin/settings/return-policy` | tenant.settings.return-policy | `Tenant\Panel\Settings\ReturnPolicyController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| PUT | `/admin/settings/return-policy` | tenant.settings.return-policy.update | `Tenant\Panel\Settings\ReturnPolicyController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage |
| POST | `/admin/settings/return-policy/validate` | tenant.settings.return-policy.validate | `Tenant\Panel\Settings\ReturnPolicyController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:sales.returns.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/roles-permissions` | tenant.settings.roles-permissions | `Tenant\Panel\Settings\RolesPermissionsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage |
| POST | `/admin/settings/roles-permissions` | tenant.settings.roles-permissions.store | `Tenant\Panel\Settings\RolesPermissionsController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage |
| GET | `/admin/settings/roles-permissions/data` | tenant.settings.roles-permissions.data | `Tenant\Panel\Settings\RolesPermissionsController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage |
| POST | `/admin/settings/roles-permissions/validate` | tenant.settings.roles-permissions.validate | `Tenant\Panel\Settings\RolesPermissionsController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/roles-permissions/{role}` | tenant.settings.roles-permissions.show | `Tenant\Panel\Settings\RolesPermissionsController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage |
| PUT | `/admin/settings/roles-permissions/{role}` | tenant.settings.roles-permissions.update | `Tenant\Panel\Settings\RolesPermissionsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage |
| DELETE | `/admin/settings/roles-permissions/{role}` | tenant.settings.roles-permissions.destroy | `Tenant\Panel\Settings\RolesPermissionsController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage |
| POST | `/admin/settings/roles-permissions/{role}/validate` | tenant.settings.roles-permissions.validate.update | `Tenant\Panel\Settings\RolesPermissionsController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.roles.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/subscribers` | tenant.settings.subscribers | `Tenant\Panel\Settings\SubscribersController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.subscribers.manage |
| GET | `/admin/settings/subscribers/data` | tenant.settings.subscribers.data | `Tenant\Panel\Settings\SubscribersController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.subscribers.manage |
| GET | `/admin/settings/subscribers/export` | tenant.settings.subscribers.export | `Tenant\Panel\Settings\SubscribersController@export` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.subscribers.manage |
| DELETE | `/admin/settings/subscribers/{subscriber}` | tenant.settings.subscribers.destroy | `Tenant\Panel\Settings\SubscribersController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.subscribers.manage |
| GET | `/admin/settings/tracking` | tenant.settings.tracking | `Tenant\Panel\Settings\TrackingSettingsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.tracking.manage |
| PUT | `/admin/settings/tracking` | tenant.settings.tracking.update | `Tenant\Panel\Settings\TrackingSettingsController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.tracking.manage |
| POST | `/admin/settings/tracking/validate` | tenant.settings.tracking.validate | `Tenant\Panel\Settings\TrackingSettingsController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.tracking.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/settings/translations` | tenant.settings.translations | `Closure` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:settings.translations.manage |

### `store` (83)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/store/appearance` | tenant.store.appearance | `Tenant\Panel\Store\AppearanceController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| PUT | `/admin/store/appearance/colors/{theme}/{variant}` | tenant.store.appearance.colors | `Tenant\Panel\Store\AppearanceController@saveColors` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/colors/{theme}/{variant}/reset` | tenant.store.appearance.colors.reset | `Tenant\Panel\Store\AppearanceController@resetColors` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/colors/{theme}/{variant}/validate` | tenant.store.appearance.colors.validate | `Tenant\Panel\Store\AppearanceController@validateColors` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/store/appearance/footer` | tenant.store.appearance.footer | `Tenant\Panel\Store\AppearanceController@saveFooter` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/footer/validate` | tenant.store.appearance.footer.validate | `Tenant\Panel\Store\AppearanceController@validateFooter` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/store/appearance/general` | tenant.store.appearance.general | `Tenant\Panel\Store\AppearanceController@saveGeneral` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/general/validate` | tenant.store.appearance.general.validate | `Tenant\Panel\Store\AppearanceController@validateGeneral` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/store/appearance/promo-banner` | tenant.store.appearance.promo-banner | `Tenant\Panel\Store\AppearanceController@savePromoBanner` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/promo-banner/validate` | tenant.store.appearance.promo-banner.validate | `Tenant\Panel\Store\AppearanceController@validatePromoBanner` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/appearance/social` | tenant.store.appearance.social.index | `Tenant\Panel\Store\SocialLinkController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/social` | tenant.store.appearance.social.store | `Tenant\Panel\Store\SocialLinkController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/social/validate` | tenant.store.appearance.social.validate | `Tenant\Panel\Store\SocialLinkController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/appearance/social/{link}` | tenant.store.appearance.social.show | `Tenant\Panel\Store\SocialLinkController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| PUT | `/admin/store/appearance/social/{link}` | tenant.store.appearance.social.update | `Tenant\Panel\Store\SocialLinkController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| DELETE | `/admin/store/appearance/social/{link}` | tenant.store.appearance.social.destroy | `Tenant\Panel\Store\SocialLinkController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/social/{link}/validate` | tenant.store.appearance.social.validate.update | `Tenant\Panel\Store\SocialLinkController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/store/appearance/top-header-links` | tenant.store.appearance.top-header-links | `Tenant\Panel\Store\AppearanceController@saveTopHeaderLinks` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/appearance/top-header-links/validate` | tenant.store.appearance.top-header-links.validate | `Tenant\Panel\Store\AppearanceController@validateTopHeaderLinks` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/banners` | tenant.store.banners.index | `Tenant\Panel\Store\BannerController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/banners` | tenant.store.banners.store | `Tenant\Panel\Store\BannerController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| GET | `/admin/store/banners/item/{banner}` | tenant.store.banners.show | `Tenant\Panel\Store\BannerController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| PUT | `/admin/store/banners/item/{banner}` | tenant.store.banners.update | `Tenant\Panel\Store\BannerController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| DELETE | `/admin/store/banners/item/{banner}` | tenant.store.banners.destroy | `Tenant\Panel\Store\BannerController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/banners/item/{banner}/validate` | tenant.store.banners.validate.update | `Tenant\Panel\Store\BannerController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/banners/list/{countryId?}` | tenant.store.banners | `Tenant\Panel\Store\BannerController@list` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/banners/order` | tenant.store.banners.order | `Tenant\Panel\Store\BannerController@updateOrder` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage |
| POST | `/admin/store/banners/validate` | tenant.store.banners.validate | `Tenant\Panel\Store\BannerController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.appearance.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/blade-theme` | tenant.store.blade-theme | `Tenant\Panel\Store\BladeThemeController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.blade-theme.manage |
| POST | `/admin/store/blade-theme` | tenant.store.blade-theme.upload | `Tenant\Panel\Store\BladeThemeController@upload` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.blade-theme.manage |
| POST | `/admin/store/blade-theme/deactivate` | tenant.store.blade-theme.deactivate | `Tenant\Panel\Store\BladeThemeController@deactivate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.blade-theme.manage |
| GET | `/admin/store/blade-theme/starter-kit` | tenant.store.blade-theme.starter-kit | `Tenant\BladeThemeStarterKitController@download` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.blade-theme.manage |
| POST | `/admin/store/blade-theme/validate` | tenant.store.blade-theme.upload.validate | `Tenant\Panel\Store\BladeThemeController@validateUpload` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.blade-theme.manage, ThrottleRequests:tenant-validate |
| DELETE | `/admin/store/blade-theme/{upload}` | tenant.store.blade-theme.destroy | `Tenant\Panel\Store\BladeThemeController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.blade-theme.manage |
| GET | `/admin/store/coupons` | tenant.store.coupons.index | `Tenant\Panel\Store\CouponController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage |
| POST | `/admin/store/coupons` | tenant.store.coupons.store | `Tenant\Panel\Store\CouponController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage |
| GET | `/admin/store/coupons/data` | tenant.store.coupons.data | `Tenant\Panel\Store\CouponController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage |
| GET | `/admin/store/coupons/item/{coupon}` | tenant.store.coupons.show | `Tenant\Panel\Store\CouponController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage |
| PUT | `/admin/store/coupons/item/{coupon}` | tenant.store.coupons.update | `Tenant\Panel\Store\CouponController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage |
| DELETE | `/admin/store/coupons/item/{coupon}` | tenant.store.coupons.destroy | `Tenant\Panel\Store\CouponController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage |
| POST | `/admin/store/coupons/item/{coupon}/validate` | tenant.store.coupons.validate.update | `Tenant\Panel\Store\CouponController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/coupons/list/{countryId?}` | tenant.store.coupons.list | `Tenant\Panel\Store\CouponController@list` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage |
| POST | `/admin/store/coupons/validate` | tenant.store.coupons.validate | `Tenant\Panel\Store\CouponController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.coupons.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/flash-sales` | tenant.store.flash-sales.index | `Tenant\Panel\Store\FlashSaleController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| POST | `/admin/store/flash-sales` | tenant.store.flash-sales.store | `Tenant\Panel\Store\FlashSaleController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| GET | `/admin/store/flash-sales/available-for-product` | tenant.store.flash-sales.available-for-product | `Tenant\Panel\Store\FlashSaleController@availableForProduct` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| GET | `/admin/store/flash-sales/data` | tenant.store.flash-sales.data | `Tenant\Panel\Store\FlashSaleController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| GET | `/admin/store/flash-sales/item/{flashSale}` | tenant.store.flash-sales.show | `Tenant\Panel\Store\FlashSaleController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| PUT | `/admin/store/flash-sales/item/{flashSale}` | tenant.store.flash-sales.update | `Tenant\Panel\Store\FlashSaleController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| DELETE | `/admin/store/flash-sales/item/{flashSale}` | tenant.store.flash-sales.destroy | `Tenant\Panel\Store\FlashSaleController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| POST | `/admin/store/flash-sales/item/{flashSale}/attach-product` | tenant.store.flash-sales.attach-product | `Tenant\Panel\Store\FlashSaleController@attachProduct` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| POST | `/admin/store/flash-sales/item/{flashSale}/validate` | tenant.store.flash-sales.validate.update | `Tenant\Panel\Store\FlashSaleController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/flash-sales/list/{countryId?}` | tenant.store.flash-sales | `Tenant\Panel\Store\FlashSaleController@list` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| GET | `/admin/store/flash-sales/products/search` | tenant.store.flash-sales.products.search | `Tenant\Panel\Store\FlashSaleController@searchProducts` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage |
| POST | `/admin/store/flash-sales/validate` | tenant.store.flash-sales.validate | `Tenant\Panel\Store\FlashSaleController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.flash-sales.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/home-variants` | tenant.store.home-variants | `Tenant\Panel\Store\HomeVariantsController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.home-variants.manage |
| POST | `/admin/store/home-variants` | tenant.store.home-variants.select | `Tenant\Panel\Store\HomeVariantsController@selectVariant` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.home-variants.manage |
| GET | `/admin/store/mobile-app` | tenant.store.mobile-app | `Tenant\Panel\Store\MobileAppController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.mobile-app.manage |
| PUT | `/admin/store/mobile-app` | tenant.store.mobile-app.save | `Tenant\Panel\Store\MobileAppController@save` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.mobile-app.manage |
| POST | `/admin/store/mobile-app/validate` | tenant.store.mobile-app.validate | `Tenant\Panel\Store\MobileAppController@validateSave` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.mobile-app.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/mobile-themes` | tenant.store.mobile-themes | `Tenant\Panel\Store\MobileAppController@themes` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.mobile-app.manage |
| PUT | `/admin/store/mobile-themes/colors` | tenant.store.mobile-themes.colors | `Tenant\Panel\Store\MobileAppController@saveColors` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.mobile-app.manage |
| POST | `/admin/store/mobile-themes/{themeSlug}/{variantKey}/activate` | tenant.store.mobile-themes.activate | `Tenant\Panel\Store\MobileAppController@activate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.mobile-app.manage |
| GET | `/admin/store/page-builder` | tenant.store.page-builder | `Tenant\Panel\Store\PageBuilderController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.page-builder.manage |
| POST | `/admin/store/page-builder/order` | tenant.store.page-builder.order | `Tenant\Panel\Store\PageBuilderController@updateOrder` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.page-builder.manage |
| PATCH | `/admin/store/page-builder/sections/{section}/visibility` | tenant.store.page-builder.sections.visibility | `Tenant\Panel\Store\PageBuilderController@toggleVisibility` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.page-builder.manage |
| GET | `/admin/store/pages` | tenant.store.pages | `Tenant\Panel\Store\PagesController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage |
| POST | `/admin/store/pages` | tenant.store.pages.store | `Tenant\Panel\Store\PageFormController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage |
| GET | `/admin/store/pages/create` | tenant.store.pages.create | `Tenant\Panel\Store\PageFormController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage |
| GET | `/admin/store/pages/data` | tenant.store.pages.data | `Tenant\Panel\Store\PagesController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage |
| POST | `/admin/store/pages/validate` | tenant.store.pages.validate | `Tenant\Panel\Store\PageFormController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage, ThrottleRequests:tenant-validate |
| PUT | `/admin/store/pages/{page}` | tenant.store.pages.update | `Tenant\Panel\Store\PageFormController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage |
| DELETE | `/admin/store/pages/{page}` | tenant.store.pages.destroy | `Tenant\Panel\Store\PagesController@destroy` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage |
| GET | `/admin/store/pages/{page}/edit` | tenant.store.pages.edit | `Tenant\Panel\Store\PageFormController@edit` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage |
| POST | `/admin/store/pages/{page}/validate` | tenant.store.pages.validate.update | `Tenant\Panel\Store\PageFormController@validateUpdate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.pages.manage, ThrottleRequests:tenant-validate |
| GET | `/admin/store/themes` | tenant.store.themes | `Tenant\Panel\Store\ThemesController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |
| POST | `/admin/store/themes/{theme}/activate` | tenant.store.themes.activate | `Tenant\Panel\Store\ThemesController@activate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |
| GET | `/admin/store/themes/{theme}/countries` | tenant.store.themes.countries | `Tenant\Panel\Store\ThemesController@countries` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |
| PUT | `/admin/store/themes/{theme}/countries` | tenant.store.themes.countries.update | `Tenant\Panel\Store\ThemesController@updateCountries` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |
| GET | `/admin/store/themes/{theme}/country-variants` | tenant.store.themes.country-variants | `Tenant\Panel\Store\ThemesController@countryVariants` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |
| PUT | `/admin/store/themes/{theme}/country-variants` | tenant.store.themes.country-variants.update | `Tenant\Panel\Store\ThemesController@updateCountryVariants` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |
| POST | `/admin/store/themes/{theme}/deactivate` | tenant.store.themes.deactivate | `Tenant\Panel\Store\ThemesController@deactivate` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |
| POST | `/admin/store/themes/{theme}/variants/{variant}/activate` | tenant.store.themes.variants.activate | `Tenant\Panel\Store\ThemesController@activateVariant` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:store.themes.manage |

### `subscription-payment` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/subscription-payment/{gateway}/cancel` | tenant.subscription-payment.cancel | `Tenant\SubscriptionPaymentController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET|POST | `/admin/subscription-payment/{gateway}/success` | tenant.subscription-payment.success | `Tenant\SubscriptionPaymentController@success` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/subscription-payment/{gateway}/{packageId}/{type}` | tenant.subscription-payment.charge | `Tenant\SubscriptionPaymentController@charge` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `support` (9)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/support` | tenant.support.index | `Tenant\Panel\Support\TicketController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/support` | tenant.support.store | `Tenant\Panel\Support\TicketController@store` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/support/data` | tenant.support.data | `Tenant\Panel\Support\TicketController@data` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/support/new` | tenant.support.create | `Tenant\Panel\Support\TicketController@create` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/support/validate` | tenant.support.validate | `Tenant\Panel\Support\TicketController@validateStore` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |
| GET | `/admin/support/{ticketId}` | tenant.support.show | `Tenant\Panel\Support\TicketController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/support/{ticketId}/replies` | tenant.support.replies | `Tenant\Panel\Support\TicketController@reply` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/support/{ticketId}/replies/validate` | tenant.support.replies.validate | `Tenant\Panel\Support\TicketController@validateReply` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, ThrottleRequests:tenant-validate |
| GET | `/admin/support/{ticketId}/thread` | tenant.support.thread | `Tenant\Panel\Support\TicketController@thread` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `todays-chances` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/todays-chances` | tenant.todays-chances | `Tenant\Panel\Catalog\TodayChancesController@index` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen, TenantPermission:dashboard.view |

### `vendor-settlement` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/vendor-settlement/{gateway}/cancel` | tenant.vendor-settlement.cancel | `Tenant\VendorSettlementPaymentController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET|POST | `/admin/vendor-settlement/{gateway}/success` | tenant.vendor-settlement.success | `Tenant\VendorSettlementPaymentController@success` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| GET | `/admin/vendor-settlement/{gateway}/{orderId}` | tenant.vendor-settlement.charge | `Tenant\VendorSettlementPaymentController@charge` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

### `widgets` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/admin/widgets/setup-progress` | tenant.widgets.setup-progress | `Tenant\Panel\Shell\SetupProgressController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |
| POST | `/admin/widgets/setup-progress/pages-reviewed` | tenant.widgets.setup-progress.pages-reviewed | `Tenant\Panel\Shell\SetupProgressController@pagesReviewed` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, SetTenantPanelLocale, Authenticate:tenant, EnsureTenantTourSeen |

## 3. Affiliate panel (central domain, /affiliate)


### `(root)` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET|POST|PUT|PATCH|DELETE|OPTIONS | `/affiliate` | affiliate. | `Illuminate\Routing\RedirectController` |  |

### `conversions` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/conversions` | affiliate.conversions | `Livewire\Affiliate\ConversionsPage` | Authenticate:affiliate |

### `dashboard` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/dashboard` | affiliate.dashboard | `Livewire\Affiliate\DashboardPage` | Authenticate:affiliate |

### `links` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/links` | affiliate.links | `Livewire\Affiliate\LinksPage` | Authenticate:affiliate |

### `login` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/login` | affiliate.login | `Livewire\Affiliate\Auth\LoginPage` | RedirectIfAuthenticated:affiliate |

### `logout` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/affiliate/logout` | affiliate.logout | `Affiliate\AuthController@logout` | Authenticate:affiliate |

### `payouts` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/payouts` | affiliate.payouts | `Livewire\Affiliate\PayoutsPage` | Authenticate:affiliate |

### `profile` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/profile` | affiliate.profile | `Livewire\Affiliate\ProfilePage` | Authenticate:affiliate |

### `register` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/register` | affiliate.register | `Livewire\Affiliate\Auth\RegisterPage` | RedirectIfAuthenticated:affiliate |

### `sso` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/affiliate/sso/{token}` | affiliate.sso | `Affiliate\SsoController` | ThrottleRequests:20,1 |

## 4. Tenant storefront (tenant subdomain / custom domain)


### `.well-known` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/.well-known/apple-app-site-association` |  | `Mobile\WellKnownController@appleAssociation` |  |
| GET | `/.well-known/assetlinks.json` |  | `Mobile\WellKnownController@assetLinks` |  |

### `(home)` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `//` | tenant.home | `Livewire\Tenant\Storefront\HomePage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `account` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/account/login` | tenant.storefront.login | `Livewire\Tenant\Storefront\AuthPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, RedirectIfAuthenticated:storefront |
| POST | `/account/logout` | tenant.storefront.logout | `Tenant\StorefrontSocialAuthController@logout` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| GET | `/account/returns/{id}` | tenant.storefront.return-detail | `Livewire\Tenant\Storefront\ReturnDetailPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |

### `app` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/app` | tenant.app-landing | `Mobile\AppLandingController` | PreventAccessFromCentralDomains, InitializeTenancyByDomain |

### `apple-app-site-association` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/apple-app-site-association` |  | `Mobile\WellKnownController@appleAssociation` |  |

### `auth` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/auth/apple` | tenant.storefront.social.apple | `Tenant\StorefrontSocialAuthController@redirectToApple` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, RedirectIfAuthenticated:storefront |
| POST | `/auth/apple/callback` | tenant.storefront.social.apple.callback | `Tenant\StorefrontSocialAuthController@handleAppleCallback` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, RedirectIfAuthenticated:storefront |
| GET | `/auth/google` | tenant.storefront.social.google | `Tenant\StorefrontSocialAuthController@redirectToGoogle` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, RedirectIfAuthenticated:storefront |
| GET | `/auth/google/callback` | tenant.storefront.social.google.callback | `Tenant\StorefrontSocialAuthController@handleGoogleCallback` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, RedirectIfAuthenticated:storefront |

### `best-selling` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/best-selling` | tenant.storefront.best-selling | `Livewire\Tenant\Storefront\BestSellingPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `broadcasting` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET|POST | `/broadcasting/auth` |  | `Illuminate\Broadcasting\BroadcastController@authenticate` |  |

### `cart` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/cart` | tenant.storefront.cart | `Livewire\Tenant\Storefront\CartPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| POST | `/cart/add` | tenant.storefront.cart.add | `Tenant\CartController@add` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| POST | `/cart/remove` | tenant.storefront.cart.remove | `Tenant\CartController@remove` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| POST | `/cart/update` | tenant.storefront.cart.update | `Tenant\CartController@update` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `categories-products` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/categories-products/{slug?}` | tenant.storefront.category.products.json | `Tenant\StorefrontSearchController@categoryProducts` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `categories` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/categories/{slug?}` | tenant.storefront.category | `Livewire\Tenant\Storefront\CategoryPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `checkout` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/checkout` | tenant.storefront.checkout | `Livewire\Tenant\Storefront\CheckoutPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| GET | `/checkout/payment/{gateway}/cancel` | tenant.payment.cancel | `Tenant\PaymentController@cancel` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| GET|POST | `/checkout/payment/{gateway}/success` | tenant.payment.success | `Tenant\PaymentController@success` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| POST | `/checkout/payment/{gateway}/webhook` | tenant.payment.webhook | `Tenant\PaymentWebhookController@handle` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| GET | `/checkout/payment/{gateway}/{orderUuid}` | tenant.payment.charge | `Tenant\PaymentController@charge` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |

### `favorites` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/favorites` | tenant.storefront.favorites | `Livewire\Tenant\Storefront\FavoritesPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| GET | `/favorites/ids` | tenant.storefront.favorites.ids | `Tenant\FavoriteController@ids` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| GET | `/favorites/list` | tenant.storefront.favorites.list | `Tenant\FavoriteController@list` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| POST | `/favorites/toggle` | tenant.storefront.favorites.toggle | `Tenant\FavoriteController@toggle` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |

### `full-star` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/full-star` | tenant.storefront.full-star | `Livewire\Tenant\Storefront\FullStarPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `new-in` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/new-in` | tenant.storefront.new-in | `Livewire\Tenant\Storefront\NewInPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `not-found` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/not-found` | tenant.storefront.not-found | `Livewire\Tenant\Storefront\NotFoundPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `offers` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/offers` | tenant.storefront.offers | `Livewire\Tenant\Storefront\OffersPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `orders` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/orders/{uuid}/invoice` | tenant.storefront.order-invoice | `Tenant\StorefrontInvoiceController@show` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| GET | `/orders/{uuid}/return` | tenant.storefront.order-return | `Livewire\Tenant\Storefront\RequestReturnForm` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |
| GET | `/orders/{uuid}/status` | tenant.storefront.order-status | `Livewire\Tenant\Storefront\OrderStatusPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| GET | `/orders/{uuid}/tracking` | tenant.storefront.order-tracking | `Livewire\Tenant\Storefront\OrderTrackingPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `pages` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/pages/{slug}` | tenant.storefront.page | `Livewire\Tenant\Storefront\PageView` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `preview` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/preview` | preview | `Livewire\Tenant\Storefront\HomePage` | InitializeTenancyForPreview, ForcePreviewTemplate, ApplyStorefrontSessionContext |
| GET | `/preview/api/home/tabbed-products` | preview.home.tabbed-products | `Tenant\HomePageController` | InitializeTenancyForPreview, ApplyStorefrontSessionContext |
| GET | `/preview/api/products` | preview.products.json | `Tenant\StorefrontSearchController@products` | InitializeTenancyForPreview, ApplyStorefrontSessionContext |
| GET | `/preview/categories-products/{slug?}` | preview.category.products.json | `Tenant\StorefrontSearchController@categoryProducts` | InitializeTenancyForPreview, ApplyStorefrontSessionContext |
| GET | `/preview/search/autocomplete` | preview.search.autocomplete | `Tenant\StorefrontSearchController@autocomplete` | InitializeTenancyForPreview, ApplyStorefrontSessionContext |

### `products` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/products/{slug}` | tenant.storefront.product | `Livewire\Tenant\Storefront\ProductPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `profile` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/profile` | tenant.storefront.profile | `Livewire\Tenant\Storefront\ProfilePage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate, Authenticate:storefront |

### `robots.txt` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/robots.txt` | tenant.robots | `Tenant\RobotsController` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext |

### `search` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/search` | tenant.storefront.search | `Livewire\Tenant\Storefront\BestSellingPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| GET | `/search/autocomplete` | tenant.storefront.search.autocomplete | `Tenant\StorefrontSearchController@autocomplete` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |
| POST | `/search/image` | tenant.storefront.search.image | `ImageSearchController@storefront` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `sitemap.xml` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/sitemap.xml` | tenant.sitemap | `Tenant\SitemapController` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext |

### `tenancy` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/tenancy/assets/{path?}` | stancl.tenancy.asset | `Stancl\Tenancy\Controllers\TenantAssetsController@asset` | InitializeTenancyByDomain |

### `tenant` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/tenant/broadcasting/auth` | tenant.broadcasting.auth | `Tenant\BroadcastAuthController` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, Authenticate:tenant |

### `{fallbackPlaceholder}` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/{fallbackPlaceholder}` | tenant.storefront.404 | `Livewire\Tenant\Storefront\NotFoundPage` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

## 5. Path-based storefront (central domain, /vendor/{tenant})


### `livewire-5b0eb82b` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/s/{tenant}/livewire-5b0eb82b/update` | tenant.path.livewire.update | `Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate` | InitializeTenancyBySlug |

### `livewire` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/s/{tenant}/livewire/update` | tenant.path.livewire.update | `Tenant\TenantPathController@livewireUpdate` | InitializeTenancyBySlug |

### `(home)` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}` | tenant.path.home | `Livewire\Tenant\Storefront\HomePage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome |

### `account` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/account/login` | tenant.path.storefront.login | `Livewire\Tenant\Storefront\AuthPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, RedirectIfAuthenticated:storefront |
| POST | `/vendor/{tenant}/account/logout` | tenant.path.storefront.logout | `Tenant\StorefrontSocialAuthController@logoutPath` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |

### `admin{any}` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/admin{any}` | tenant.path.admin.redirect | `Tenant\TenantPathController@adminRedirect` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `api` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/api/home/tabbed-products` | tenant.path.storefront.home.tabbed-products | `Tenant\HomePageController` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |
| GET | `/vendor/{tenant}/api/products` | tenant.path.storefront.products.json | `Tenant\StorefrontSearchController@productsPath` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `best-selling` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/best-selling` | tenant.path.storefront.best-selling | `Livewire\Tenant\Storefront\BestSellingPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `cart` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/cart` | tenant.path.storefront.cart | `Livewire\Tenant\Storefront\CartPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |
| POST | `/vendor/{tenant}/cart/add` | tenant.path.storefront.cart.add | `Tenant\CartController@add` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `categories-products` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/categories-products/{slug?}` | tenant.path.storefront.category.products.json | `Tenant\StorefrontSearchController@categoryProductsPath` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `categories` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/categories/{slug?}` | tenant.path.storefront.category | `Livewire\Tenant\Storefront\CategoryPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `checkout` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/checkout` | tenant.path.storefront.checkout | `Livewire\Tenant\Storefront\CheckoutPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |
| GET | `/vendor/{tenant}/checkout/payment/{gateway}/cancel` | tenant.path.payment.cancel | `Tenant\PaymentController@cancel` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |
| GET|POST | `/vendor/{tenant}/checkout/payment/{gateway}/success` | tenant.path.payment.success | `Tenant\PaymentController@success` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |
| GET | `/vendor/{tenant}/checkout/payment/{gateway}/{orderUuid}` | tenant.path.payment.charge | `Tenant\PaymentController@charge` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |

### `favorites` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/favorites` | tenant.path.storefront.favorites | `Livewire\Tenant\Storefront\FavoritesPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |

### `full-star` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/full-star` | tenant.path.storefront.full-star | `Livewire\Tenant\Storefront\FullStarPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `new-in` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/new-in` | tenant.path.storefront.new-in | `Livewire\Tenant\Storefront\NewInPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `offers` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/offers` | tenant.path.storefront.offers | `Livewire\Tenant\Storefront\OffersPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `orders` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/orders/{uuid}/invoice` | tenant.path.storefront.order-invoice | `Tenant\StorefrontInvoiceController@download` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |
| GET | `/vendor/{tenant}/orders/{uuid}/status` | tenant.path.storefront.order-status | `Livewire\Tenant\Storefront\OrderStatusPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |
| GET | `/vendor/{tenant}/orders/{uuid}/tracking` | tenant.path.storefront.order-tracking | `Livewire\Tenant\Storefront\OrderTrackingPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `pages` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/pages/{slug}` | tenant.path.storefront.page | `Livewire\Tenant\Storefront\PageView` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `products` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/products/{slug}` | tenant.path.storefront.product | `Livewire\Tenant\Storefront\ProductPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `profile` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/profile` | tenant.path.storefront.profile | `Livewire\Tenant\Storefront\ProfilePage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate, Authenticate:storefront |

### `robots.txt` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/robots.txt` | tenant.path.robots | `Tenant\RobotsController` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `search` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/search` | tenant.path.storefront.search | `Livewire\Tenant\Storefront\BestSellingPage` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |
| GET | `/vendor/{tenant}/search/autocomplete` | tenant.path.storefront.search.autocomplete | `Tenant\StorefrontSearchController@autocompletePath` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |
| POST | `/vendor/{tenant}/search/image` | tenant.path.storefront.search.image | `ImageSearchController@storefront` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

### `sitemap.xml` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/vendor/{tenant}/sitemap.xml` | tenant.path.sitemap | `Tenant\SitemapController` | InitializeTenancyBySlug, ApplyStorefrontSessionContext, CheckTenantGatewayBlocked, ForcePreviewTemplate |

## 6. Storefront/tenant JSON API (/api)


### `home` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/home/tabbed-products` | tenant.storefront.home.tabbed-products | `Tenant\HomePageController` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `products` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/products` | tenant.storefront.products.json | `Tenant\StorefrontSearchController@products` | PreventAccessFromCentralDomains, InitializeTenancyByDomain, ApplyStorefrontSessionContext, IdentifyTenantTheme, CheckTenantGatewayBlocked, ForcePreviewTemplate, ServeBladeThemeHome, StoreLaunchGate |

### `v1` (30)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/v1/addresses` | api.v1.addresses.index | `Tenant\AddressController@index` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/addresses` | api.v1.addresses.store | `Tenant\AddressController@store` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| PUT | `/api/v1/addresses/{address}` | api.v1.addresses.update | `Tenant\AddressController@update` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| DELETE | `/api/v1/addresses/{address}` | api.v1.addresses.destroy | `Tenant\AddressController@destroy` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/addresses/{address}/default` | api.v1.addresses.default | `Tenant\AddressController@setDefault` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/cart/add` | api.v1.cart.add | `Tenant\CartController@add` | api, IdentifyTenantByApiToken |
| POST | `/api/v1/cart/remove` | api.v1.cart.remove | `Tenant\CartController@remove` | api, IdentifyTenantByApiToken |
| POST | `/api/v1/cart/update` | api.v1.cart.update | `Tenant\CartController@update` | api, IdentifyTenantByApiToken |
| GET | `/api/v1/categories` | api.v1.categories.index | `Tenant\CatalogController@categories` | api, IdentifyTenantByApiToken |
| GET | `/api/v1/categories/{slug}` | api.v1.categories.show | `Tenant\CatalogController@category` | api, IdentifyTenantByApiToken |
| POST | `/api/v1/checkout` | api.v1.checkout.store | `Tenant\CheckoutController@store` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| GET | `/api/v1/coupon` | api.v1.coupon.show | `Tenant\CouponController@show` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/coupon/apply` | api.v1.coupon.apply | `Tenant\CouponController@apply` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/coupon/remove` | api.v1.coupon.remove | `Tenant\CouponController@remove` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| GET | `/api/v1/favorites/ids` | api.v1.favorites.ids | `Tenant\FavoriteController@ids` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| GET | `/api/v1/favorites/list` | api.v1.favorites.list | `Tenant\FavoriteController@list` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/favorites/toggle` | api.v1.favorites.toggle | `Tenant\FavoriteController@toggle` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/logout` | api.v1.logout | `Tenant\ProfileController@logout` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| GET | `/api/v1/orders` | api.v1.orders.index | `Tenant\OrderController@index` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| GET | `/api/v1/orders/cancellation-reasons` | api.v1.orders.cancellation-reasons | `Tenant\OrderController@cancellationReasons` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| GET | `/api/v1/orders/{uuid}` | api.v1.orders.show | `Tenant\OrderController@show` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/orders/{uuid}/cancel` | api.v1.orders.cancel | `Tenant\OrderController@cancel` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/orders/{uuid}/reorder` | api.v1.orders.reorder | `Tenant\OrderController@reorder` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| GET | `/api/v1/products` | api.v1.products.index | `Tenant\CatalogController@products` | api, IdentifyTenantByApiToken |
| GET | `/api/v1/products/{product}/reviews` | api.v1.reviews.index | `Tenant\ReviewController@index` | api, IdentifyTenantByApiToken |
| GET | `/api/v1/products/{slug}` | api.v1.products.show | `Tenant\CatalogController@product` | api, IdentifyTenantByApiToken |
| GET | `/api/v1/profile` | api.v1.profile.show | `Tenant\ProfileController@show` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| PUT | `/api/v1/profile` | api.v1.profile.update | `Tenant\ProfileController@update` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/profile/password` | api.v1.profile.password | `Tenant\ProfileController@updatePassword` | api, IdentifyTenantByApiToken, Authenticate:storefront |
| POST | `/api/v1/reviews` | api.v1.reviews.store | `Tenant\ReviewController@store` | api, IdentifyTenantByApiToken, Authenticate:storefront |

## 7. Mobile API (/api/mobile/v1)


### `addresses` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/addresses` | mobile.addresses.index | `Tenant\AddressController@index` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/addresses` | mobile.addresses.store | `Tenant\AddressController@store` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| PUT | `/api/mobile/v1/addresses/{address}` | mobile.addresses.update | `Tenant\AddressController@update` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| DELETE | `/api/mobile/v1/addresses/{address}` | mobile.addresses.destroy | `Tenant\AddressController@destroy` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/addresses/{address}/default` | mobile.addresses.default | `Tenant\AddressController@setDefault` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `auth` (7)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| DELETE | `/api/mobile/v1/auth/account` | mobile.auth.account.delete | `Mobile\AuthController@deleteAccount` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/auth/forgot-password` | mobile.auth.forgot | `Mobile\AuthController@forgotPassword` | WrapMobileEnvelope, ResolveMobileTenant, ThrottleRequests:10,1 |
| POST | `/api/mobile/v1/auth/login` | mobile.auth.login | `Mobile\AuthController@login` | WrapMobileEnvelope, ResolveMobileTenant, ThrottleRequests:10,1 |
| POST | `/api/mobile/v1/auth/logout` | mobile.auth.logout | `Mobile\AuthController@logout` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/auth/refresh` | mobile.auth.refresh | `Mobile\AuthController@refresh` | WrapMobileEnvelope, ResolveMobileTenant, ThrottleRequests:10,1 |
| POST | `/api/mobile/v1/auth/register` | mobile.auth.register | `Mobile\AuthController@register` | WrapMobileEnvelope, ResolveMobileTenant, ThrottleRequests:10,1 |
| POST | `/api/mobile/v1/auth/reset-password` | mobile.auth.reset | `Mobile\AuthController@resetPassword` | WrapMobileEnvelope, ResolveMobileTenant, ThrottleRequests:10,1 |

### `bootstrap` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/bootstrap` | mobile.bootstrap | `Mobile\BootstrapController` | WrapMobileEnvelope, ResolveMobileTenant |

### `cart` (6)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/cart` | mobile.cart.show | `Mobile\CartController@show` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| DELETE | `/api/mobile/v1/cart` | mobile.cart.clear | `Mobile\CartController@clear` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/cart/add` | mobile.cart.add | `Mobile\CartController@add` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/cart/merge` | mobile.cart.merge | `Mobile\CartController@merge` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/cart/remove` | mobile.cart.remove | `Mobile\CartController@remove` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/cart/update` | mobile.cart.update | `Mobile\CartController@update` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `categories` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/categories` | mobile.categories.index | `Mobile\CatalogController@categories` | WrapMobileEnvelope, ResolveMobileTenant |
| GET | `/api/mobile/v1/categories/{slug}` | mobile.categories.show | `Mobile\CatalogController@category` | WrapMobileEnvelope, ResolveMobileTenant |

### `checkout` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/api/mobile/v1/checkout` | mobile.checkout.store | `Mobile\CheckoutController@store` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken, ThrottleRequests:20,1 |
| GET | `/api/mobile/v1/checkout/gateways` | mobile.checkout.gateways | `Mobile\CheckoutController@gateways` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/checkout/quote` | mobile.checkout.quote | `Mobile\CheckoutController@quote` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `coupon` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/coupon` | mobile.coupon.show | `Tenant\CouponController@show` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/coupon/apply` | mobile.coupon.apply | `Tenant\CouponController@apply` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/coupon/remove` | mobile.coupon.remove | `Tenant\CouponController@remove` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `device-tokens` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/api/mobile/v1/device-tokens` | mobile.device-tokens.store | `Mobile\PreferencesController@storeDeviceToken` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| DELETE | `/api/mobile/v1/device-tokens` | mobile.device-tokens.destroy | `Mobile\PreferencesController@deleteDeviceToken` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `favorites` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/favorites/ids` | mobile.favorites.ids | `Tenant\FavoriteController@ids` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| GET | `/api/mobile/v1/favorites/list` | mobile.favorites.list | `Tenant\FavoriteController@list` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/favorites/toggle` | mobile.favorites.toggle | `Tenant\FavoriteController@toggle` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `home` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/home/sections/{key}` | mobile.home.section | `Mobile\HomeController@section` | WrapMobileEnvelope, ResolveMobileTenant |

### `lists` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/lists/{list}` | mobile.lists | `Mobile\CatalogController@lists` | WrapMobileEnvelope, ResolveMobileTenant |

### `me` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/me` | mobile.profile.show | `Tenant\ProfileController@show` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `menus` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/menus` | mobile.menus | `Mobile\PageController@menus` | WrapMobileEnvelope, ResolveMobileTenant |

### `orders` (10)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/orders` | mobile.orders.index | `Tenant\OrderController@index` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| GET | `/api/mobile/v1/orders/cancellation-reasons` | mobile.orders.cancellation-reasons | `Tenant\OrderController@cancellationReasons` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| GET | `/api/mobile/v1/orders/{uuid}` | mobile.orders.show | `Tenant\OrderController@show` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/orders/{uuid}/cancel` | mobile.orders.cancel | `Tenant\OrderController@cancel` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| GET | `/api/mobile/v1/orders/{uuid}/invoice` | mobile.orders.invoice | `Tenant\StorefrontInvoiceController@show` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/orders/{uuid}/pay` | mobile.orders.pay | `Mobile\PaymentController@pay` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken, ThrottleRequests:20,1 |
| GET | `/api/mobile/v1/orders/{uuid}/payment-status` | mobile.orders.payment-status | `Mobile\PaymentController@status` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/orders/{uuid}/reorder` | mobile.orders.reorder | `Tenant\OrderController@reorder` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/orders/{uuid}/return` | mobile.returns.store | `Mobile\ReturnController@store` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| GET | `/api/mobile/v1/orders/{uuid}/tracking` | mobile.orders.tracking | `Mobile\OrderExtrasController@tracking` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `pages` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/pages/{slug}` | mobile.pages.show | `Mobile\PageController@show` | WrapMobileEnvelope, ResolveMobileTenant |

### `payments` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/payments/{tenant}/{gateway}/cancel` | mobile.payments.cancel | `Mobile\PaymentController@cancel` | WrapMobileEnvelope, ResolveMobileTenant |
| GET|POST | `/api/mobile/v1/payments/{tenant}/{gateway}/success` | mobile.payments.success | `Mobile\PaymentController@success` | WrapMobileEnvelope, ResolveMobileTenant |

### `preferences` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/preferences/options` | mobile.preferences.options | `Mobile\PreferencesController@options` | WrapMobileEnvelope, ResolveMobileTenant |

### `products` (4)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/products` | mobile.products.index | `Mobile\CatalogController@products` | WrapMobileEnvelope, ResolveMobileTenant |
| GET | `/api/mobile/v1/products/{product}/reviews` | mobile.reviews.index | `Tenant\ReviewController@index` | WrapMobileEnvelope, ResolveMobileTenant |
| GET | `/api/mobile/v1/products/{slug}` | mobile.products.show | `Mobile\CatalogController@product` | WrapMobileEnvelope, ResolveMobileTenant |
| GET | `/api/mobile/v1/products/{slug}/related` | mobile.products.related | `Mobile\CatalogController@related` | WrapMobileEnvelope, ResolveMobileTenant |

### `profile` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| PUT | `/api/mobile/v1/profile` | mobile.profile.update | `Tenant\ProfileController@update` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| POST | `/api/mobile/v1/profile/password` | mobile.profile.password | `Tenant\ProfileController@updatePassword` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `resolve` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/resolve` | mobile.resolve | `Mobile\ResolveController` | WrapMobileEnvelope, ThrottleRequests:30,1 |

### `returns` (3)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/returns` | mobile.returns.index | `Mobile\ReturnController@index` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| GET | `/api/mobile/v1/returns/options` | mobile.returns.options | `Mobile\ReturnController@options` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |
| GET | `/api/mobile/v1/returns/{id}` | mobile.returns.show | `Mobile\ReturnController@show` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `reviews` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| POST | `/api/mobile/v1/reviews` | mobile.reviews.store | `Tenant\ReviewController@store` | WrapMobileEnvelope, ResolveMobileTenant, AuthenticateMobileToken |

### `search` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/api/mobile/v1/search` | mobile.search | `Mobile\CatalogController@search` | WrapMobileEnvelope, ResolveMobileTenant |
| GET | `/api/mobile/v1/search/autocomplete` | mobile.search.autocomplete | `Mobile\CatalogController@autocomplete` | WrapMobileEnvelope, ResolveMobileTenant |

## 9. Framework / tooling


### `_debugbar` (5)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/_debugbar/assets` | debugbar.assets | `Fruitcake\LaravelDebugbar\Controllers\AssetController@getAssets` | DebugbarEnabled, StopRecordingTelescope |
| DELETE | `/_debugbar/cache/{key}` | debugbar.cache.delete | `Fruitcake\LaravelDebugbar\Controllers\CacheController@delete` | DebugbarEnabled, StopRecordingTelescope |
| GET | `/_debugbar/clockwork/{id}` | debugbar.clockwork | `Fruitcake\LaravelDebugbar\Controllers\OpenHandlerController@clockwork` | DebugbarEnabled, StopRecordingTelescope |
| GET | `/_debugbar/open` | debugbar.openhandler | `Fruitcake\LaravelDebugbar\Controllers\OpenHandlerController@handle` | DebugbarEnabled, StopRecordingTelescope |
| POST | `/_debugbar/queries/explain` | debugbar.queries.explain | `Fruitcake\LaravelDebugbar\Controllers\QueriesController@explain` | DebugbarEnabled, StopRecordingTelescope |

### `livewire-5b0eb82b` (9)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/livewire-5b0eb82b/css/{component}.css` |  | `Closure` |  |
| GET | `/livewire-5b0eb82b/css/{component}.global.css` |  | `Closure` |  |
| GET | `/livewire-5b0eb82b/js/{component}.js` |  | `Closure` |  |
| GET | `/livewire-5b0eb82b/livewire.csp.min.js.map` |  | `Livewire\Mechanisms\FrontendAssets\FrontendAssets@cspMaps` |  |
| GET | `/livewire-5b0eb82b/livewire.js` |  | `Livewire\Mechanisms\FrontendAssets\FrontendAssets@returnJavaScriptAsFile` |  |
| GET | `/livewire-5b0eb82b/livewire.min.js.map` |  | `Livewire\Mechanisms\FrontendAssets\FrontendAssets@maps` |  |
| GET | `/livewire-5b0eb82b/preview-file/{filename}` | livewire.preview-file | `Livewire\Features\SupportFileUploads\FilePreviewController@handle` | InitializeTenancyByDomainForLivewire, universal |
| POST | `/livewire-5b0eb82b/update` | livewire.update | `Livewire\Mechanisms\HandleRequests\HandleRequests@handleUpdate` | universal, InitializeTenancyByDomainForLivewire, RequireLivewireHeaders |
| POST | `/livewire-5b0eb82b/upload-file` | livewire.upload-file | `Livewire\Features\SupportFileUploads\FileUploadController@handle` | ThrottleRequests:60,1, universal |

### `storage` (2)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/storage/{path}` | storage.local | `Closure` |  |
| PUT | `/storage/{path}` | storage.local.upload | `Closure` |  |

### `up` (1)

| Method | URI | Name | Action | Middleware |
|---|---|---|---|---|
| GET | `/up` |  | `Closure` |  |
