# Database Schema Reference (generated)

> Generated 2026-11-24. **Central DB**: read from the live local MySQL central schema via `information_schema` (read-only SELECT). **Tenant DB**: no tenant database exists on this machine (the 5 registered tenants point to `dokan_v2_{tenant_id}` schemas that are absent), so the tenant section is **derived by parsing `database/migrations/tenant/*.php`** (create/alter/drop/rename handled; columns added inside dynamic loops or raw SQL may be missing — verify against a real tenant DB). Accuracy check of the same parser against the live central DB: 932 of 998 live columns reproduced. Model mapping comes from reflecting every class in `app/Models` (`CentralConnection` trait = always uses the central DB, even inside tenant context).

Legend: `PK` primary, `UNI` unique, `MUL` indexed; `?` = nullable.


## A. Central database — 104 tables

| Table | ~Rows (dev) | Model(s) |
|---|---|---|
| `admin_notifications` | 220 | `AdminNotification` |
| `admin_roles` | 1 | `AdminRole` |
| `admins` | 1 | `AdminUser` |
| `affiliate_conversions` | 1 | `AffiliateConversion` |
| `affiliate_coupons` | 0 | `AffiliateCoupon` |
| `affiliate_login_tokens` | 0 | `AffiliateLoginToken` |
| `affiliate_payout_requests` | 0 | `AffiliatePayoutRequest` |
| `affiliate_payouts` | 0 | `AffiliatePayout` |
| `affiliate_referrals` | 1 | `AffiliateReferral` |
| `affiliates` | 5 | `Affiliate` |
| `app_settings` | 32 | `AppSetting` |
| `blade_themes` | 1 | `BladeTheme` |
| `blog_categories` | 1 | `BlogCategory` |
| `blog_posts` | 2 | `BlogPost` |
| `branch_products` | 0 | `BranchProduct` |
| `branches` | 1 | `Branch` |
| `brand_payment_requests` | 0 | `BrandPaymentRequest` |
| `brand_request_messages` | 0 | `BrandRequestMessage` |
| `brand_requests` | 0 | `BrandRequest` |
| `cache` | 0 | — |
| `cache_locks` | 0 | — |
| `catalog_ai_batch_jobs` | 0 | — |
| `catalog_dedup_runs` | 817 | `CatalogDedupRun` |
| `categories` | 9 | `Category` |
| `category_product` | 162 | — |
| `central_coupon_country` | 0 | — |
| `central_coupons` | 7 | `CentralCoupon` |
| `central_flash_sale_country` | 0 | — |
| `central_flash_sale_product` | 2 | — |
| `central_flash_sales` | 6 | `CentralFlashSale` |
| `cities` | 1028 | `City` |
| `countries` | 198 | `Country` |
| `currencies` | 155 | `Currency` |
| `default_banner_country` | 0 | — |
| `default_banners` | 0 | `DefaultBanner` |
| `delivery_popup_days` | 0 | `DeliveryPopupDay` |
| `dns_records` | 1 | `DnsRecord` |
| `domain_requests` | 0 | `DomainRequest` |
| `domains` | 5 | — |
| `email_template_translations` | 53 | `EmailTemplateTranslation` |
| `email_templates` | 21 | `EmailTemplate` |
| `failed_jobs` | 0 | — |
| `faqs` | 3 | `Faq` |
| `files` | 14335 | `File` |
| `fixed_shipping_costs` | 3 | `FixedShippingCost` |
| `home_variants` | 15 | `HomeVariant` |
| `invoices` | 46 | `Invoice` |
| `job_batches` | 0 | — |
| `jobs` | 0 | — |
| `languages` | 4 | `Language` |
| `maintenance_windows` | 1 | `MaintenanceWindow` |
| `manufacturing_payment_requests` | 1 | `ManufacturingPaymentRequest` |
| `manufacturing_request_messages` | 0 | `ManufacturingRequestMessage` |
| `manufacturing_requests` | 1 | `ManufacturingRequest` |
| `migrations` | 156 | — |
| `newsletter_subscribers` | 0 | `NewsletterSubscriber` |
| `order_attachments` | 0 | `OrderAttachment` |
| `packages` | 2 | `Package` |
| `payment_gateways` | 56 | `PaymentGateway` |
| `payment_logs` | 10 | `PaymentLog` |
| `pending_registrations` | 8 | `PendingRegistration` |
| `product_badge_product` | 15 | — |
| `product_badges` | 5 | `ProductBadge` |
| `product_country` | 0 | — |
| `product_edit_requests` | 8 | `ProductEditRequest` |
| `product_image_embeddings` | 0 | `ProductImageEmbedding` |
| `product_label_product` | 150 | — |
| `product_labels` | 8 | `ProductLabel` |
| `product_request_messages` | 2 | `ProductRequestMessage` |
| `product_requests` | 2 | `ProductRequest` |
| `product_shipping_zone` | 0 | — |
| `product_tenant_assignments` | 6 | `ProductTenantAssignment` |
| `product_variant_option` | 1275 | — |
| `product_variants` | 1266 | `ProductVariant` |
| `product_variation` | 147 | — |
| `products` | 138 | `Product` |
| `refunds` | 26 | `Refund` |
| `return_request_media` | 0 | `ReturnRequestMedia` |
| `return_request_notes` | 100 | `ReturnRequestNote` |
| `return_requests` | 17 | `ReturnRequest` |
| `sessions` | 0 | — |
| `shipping_day_rules` | 0 | `ShippingDayRule` |
| `shipping_stops` | 0 | `ShippingStop` |
| `shipping_zone_rates` | 6 | `ShippingZoneRate` |
| `shipping_zones` | 6 | `ShippingZone` |
| `static_pages` | 15 | `StaticPage` |
| `support_ticket_messages` | 4 | `SupportTicketMessage` |
| `support_tickets` | 4 | `SupportTicket` |
| `template_country` | 0 | — |
| `template_parts` | 0 | `TemplatePart` |
| `templates` | 3 | `Template` |
| `tenant_ai_translation_purchases` | 0 | `TenantAiTranslationPurchase` |
| `tenant_change_requests` | 0 | `TenantChangeRequest` |
| `tenant_countries` | 594 | `TenantCountry` |
| `tenant_database_pool` | 0 | — |
| `tenant_language_purchases` | 0 | `TenantLanguagePurchase` |
| `tenant_mobile_apps` | 0 | `TenantMobileApp` |
| `tenant_owners` | 0 | `TenantOwner` |
| `tenant_payouts` | 0 | `TenantPayout` |
| `tenants` | 5 | `Tenant` |
| `translations` | 9815 | `Translation` |
| `variation_options` | 1204 | `VariationOption` |
| `variations` | 2 | `Variation` |
| `vendor_settlements` | 0 | `VendorSettlement` |

### central.`admin_notifications`

Model `App\Models\AdminNotification`

Columns: `id` bigint unsigned PRI; `type` varchar(100); `title` varchar(255); `message` text; `data` json?; `is_read` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

### central.`admin_roles`

Model `App\Models\AdminRole` — traits: HasFactory — relations: admins:hasMany(AdminUser)

Columns: `id` bigint unsigned PRI; `name` varchar(255) UNI; `permissions` json?; `permissions_count` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

Indexes: admin_roles_name_unique UNIQUE(name)

### central.`admins`

Model `App\Models\AdminUser` — traits: HasFactory — relations: role:belongsTo(AdminRole)

Columns: `id` bigint unsigned PRI; `role_id` bigint unsigned? MUL; `name` varchar(255); `email` varchar(255) UNI; `password` varchar(255)?; `status` varchar(255); `last_login_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?; `remember_token` varchar(100)?

FKs: `role_id` → admin_roles.id

Indexes: admins_email_unique UNIQUE(email); admins_role_id_foreign(role_id)

### central.`affiliate_conversions`

Model `App\Models\AffiliateConversion` — traits: CentralConnection — relations: affiliate:belongsTo(Affiliate), referral:belongsTo(AffiliateReferral), affiliateCoupon:belongsTo(AffiliateCoupon), paymentLog:belongsTo(PaymentLog), package:belongsTo(Package)

Columns: `id` bigint unsigned PRI; `affiliate_id` bigint unsigned MUL; `affiliate_referral_id` bigint unsigned? MUL; `affiliate_coupon_id` bigint unsigned? MUL; `source` varchar(20); `tenant_id` varchar(255) MUL; `payment_log_id` bigint unsigned? UNI; `package_id` bigint unsigned? MUL; `sale_amount` decimal(12,2); `commission_amount` decimal(12,2); `paid_amount` decimal(12,2); `commission_type` varchar(20); `commission_value` decimal(10,2); `status` varchar(20); `approved_at` timestamp?; `paid_at` timestamp?; `refunded_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `affiliate_coupon_id` → affiliate_coupons.id; `affiliate_id` → affiliates.id; `affiliate_referral_id` → affiliate_referrals.id; `package_id` → packages.id; `payment_log_id` → payment_logs.id

Indexes: affiliate_conversions_affiliate_coupon_id_foreign(affiliate_coupon_id); affiliate_conversions_affiliate_id_status_index(affiliate_id,status); affiliate_conversions_affiliate_referral_id_foreign(affiliate_referral_id); affiliate_conversions_package_id_foreign(package_id); affiliate_conversions_payment_log_id_foreign(payment_log_id); affiliate_conversions_payment_log_id_unique UNIQUE(payment_log_id); affiliate_conversions_tenant_id_index(tenant_id); affiliate_conversions_tenant_id_status_index(tenant_id,status)

### central.`affiliate_coupons`

Model `App\Models\AffiliateCoupon` — traits: CentralConnection — relations: affiliate:belongsTo(Affiliate), conversions:hasMany(AffiliateConversion)

Columns: `id` bigint unsigned PRI; `affiliate_id` bigint unsigned MUL; `code` varchar(64) UNI; `discount_type` varchar(20); `discount_value` decimal(10,2); `commission_value` decimal(8,2)?; `start_date` timestamp?; `end_date` timestamp?; `minimum_spend` decimal(10,2); `active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

FKs: `affiliate_id` → affiliates.id

Indexes: affiliate_coupons_affiliate_id_active_index(affiliate_id,active); affiliate_coupons_code_active_index(code,active); affiliate_coupons_code_unique UNIQUE(code)

### central.`affiliate_login_tokens`

Model `App\Models\AffiliateLoginToken` — traits: CentralConnection — relations: affiliate:belongsTo(Affiliate)

Columns: `id` bigint unsigned PRI; `affiliate_id` bigint unsigned MUL; `token_hash` varchar(64) UNI; `expires_at` timestamp; `used_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `affiliate_id` → affiliates.id

Indexes: affiliate_login_tokens_affiliate_id_foreign(affiliate_id); affiliate_login_tokens_token_hash_unique UNIQUE(token_hash)

### central.`affiliate_payout_requests`

Model `App\Models\AffiliatePayoutRequest` — traits: CentralConnection — relations: affiliate:belongsTo(Affiliate), payout:belongsTo(AffiliatePayout)

Columns: `id` bigint unsigned PRI; `affiliate_id` bigint unsigned MUL; `amount` decimal(12,2); `status` varchar(20); `note` text?; `admin_note` text?; `affiliate_payout_id` bigint unsigned? MUL; `processed_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `affiliate_id` → affiliates.id; `affiliate_payout_id` → affiliate_payouts.id

Indexes: affiliate_payout_requests_affiliate_id_status_index(affiliate_id,status); affiliate_payout_requests_affiliate_payout_id_foreign(affiliate_payout_id)

### central.`affiliate_payouts`

Model `App\Models\AffiliatePayout` — traits: CentralConnection — relations: affiliate:belongsTo(Affiliate)

Columns: `id` bigint unsigned PRI; `affiliate_id` bigint unsigned MUL; `amount` decimal(12,2); `method` varchar(255); `reference` varchar(255)?; `notes` text?; `attachment_path` varchar(255)?; `paid_at` timestamp; `created_at` timestamp?; `updated_at` timestamp?

FKs: `affiliate_id` → affiliates.id

Indexes: affiliate_payouts_affiliate_id_index(affiliate_id)

### central.`affiliate_referrals`

Model `App\Models\AffiliateReferral` — traits: CentralConnection — relations: affiliate:belongsTo(Affiliate), conversions:hasMany(AffiliateConversion)

Columns: `id` bigint unsigned PRI; `affiliate_id` bigint unsigned MUL; `ip` varchar(45)?; `user_agent` text?; `landing_url` varchar(255)?; `tenant_id` varchar(255)? MUL; `converted_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `affiliate_id` → affiliates.id

Indexes: affiliate_referrals_affiliate_id_index(affiliate_id); affiliate_referrals_tenant_id_index(tenant_id)

### central.`affiliates`

Model `App\Models\Affiliate` — traits: CentralConnection — relations: referrals:hasMany(AffiliateReferral), conversions:hasMany(AffiliateConversion), payoutRequests:hasMany(AffiliatePayoutRequest), payouts:hasMany(AffiliatePayout), notifications:morphMany(DatabaseNotification)

Columns: `id` bigint unsigned PRI; `type` varchar(20) MUL; `tenant_id` varchar(255)? UNI; `name` varchar(255); `email` varchar(255) UNI; `password` varchar(255); `code` varchar(32) UNI; `commission_type` enum('fixed','percentage'); `commission_value` decimal(10,2); `balance` decimal(12,2); `total_earned` decimal(12,2); `total_paid` decimal(12,2); `status` enum('pending','active','suspended'); `paypal_email` varchar(255)?; `bank_name` varchar(255)?; `bank_account` varchar(255)?; `bank_iban` varchar(255)?; `notes` text?; `approved_at` timestamp?; `remember_token` varchar(100)?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: affiliates_code_unique UNIQUE(code); affiliates_email_unique UNIQUE(email); affiliates_tenant_id_unique UNIQUE(tenant_id); affiliates_type_index(type)

### central.`app_settings`

Model `App\Models\AppSetting` — traits: HasFactory

Columns: `id` bigint unsigned PRI; `key` varchar(255) UNI; `value` text?; `type` varchar(255); `is_public` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

Indexes: app_settings_key_unique UNIQUE(key)

### central.`blade_themes`

Model `App\Models\BladeTheme` — traits: HasFactory, CentralConnection — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `version` varchar(255); `storage_path` varchar(255); `original_filename` varchar(255)?; `status` varchar(255); `is_active` tinyint(1); `rejection_reason` text?; `uploaded_at` timestamp?; `reviewed_at` timestamp?; `reviewed_by` varchar(255)?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: blade_themes_tenant_id_index(tenant_id)

### central.`blog_categories`

Model `App\Models\BlogCategory` — traits: HasFactory, HasTranslations — relations: posts:hasMany(BlogPost), translations:morphMany

Columns: `id` bigint unsigned PRI; `slug` varchar(255) UNI; `status` varchar(255); `created_at` timestamp?; `updated_at` timestamp?

Indexes: blog_categories_slug_unique UNIQUE(slug)

### central.`blog_posts`

Model `App\Models\BlogPost` — traits: HasFactory, HasTranslations — relations: category:belongsTo(BlogCategory), files:morphMany(File), translations:morphMany

Columns: `id` bigint unsigned PRI; `blog_category_id` bigint unsigned? MUL; `slug` varchar(255) UNI; `status` varchar(255); `published_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `blog_category_id` → blog_categories.id

Indexes: blog_posts_blog_category_id_foreign(blog_category_id); blog_posts_slug_unique UNIQUE(slug)

### central.`branch_products`

Model `App\Models\BranchProduct` — traits: HasFactory, CentralConnection — relations: branch:belongsTo(Branch), product:belongsTo(Product), variation:belongsTo(Variation)

Columns: `id` bigint unsigned PRI; `branch_id` bigint unsigned MUL; `product_id` bigint unsigned MUL; `variation_id` bigint unsigned? MUL; `quantity` int; `created_at` timestamp?; `updated_at` timestamp?

FKs: `branch_id` → branches.id; `product_id` → products.id; `variation_id` → variations.id

Indexes: branch_products_branch_id_product_id_variation_id_unique UNIQUE(branch_id,product_id,variation_id); branch_products_product_id_foreign(product_id); branch_products_variation_id_foreign(variation_id)

### central.`branches`

Model `App\Models\Branch` — traits: HasFactory, CentralConnection — relations: shippingZones:hasMany(ShippingZone), branchProducts:hasMany(BranchProduct), products:belongsToMany(Product)

Columns: `id` bigint unsigned PRI; `name` varchar(255); `code` varchar(255) UNI; `phone` varchar(255)?; `email` varchar(255)?; `address` varchar(255)?; `city` varchar(255)?; `country` varchar(255)?; `is_default` tinyint(1); `is_active` tinyint(1); `default_free_shipping_weight` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

Indexes: branches_code_unique UNIQUE(code)

### central.`brand_payment_requests`

Model `App\Models\BrandPaymentRequest` — traits: CentralConnection — relations: brandRequest:belongsTo(BrandRequest), tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `brand_request_id` bigint unsigned MUL; `tenant_id` varchar(36) MUL; `label` varchar(255); `amount` decimal(12,2); `currency` varchar(10); `status` varchar(30); `gateway_code` varchar(255)?; `transaction_id` varchar(255)?; `paid_at` timestamp?; `notes` text?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `brand_request_id` → brand_requests.id

Indexes: brand_payment_requests_brand_request_id_foreign(brand_request_id); brand_payment_requests_tenant_id_index(tenant_id)

### central.`brand_request_messages`

Model `App\Models\BrandRequestMessage` — traits: CentralConnection — relations: brandRequest:belongsTo(BrandRequest)

Columns: `id` bigint unsigned PRI; `brand_request_id` bigint unsigned MUL; `sender_type` varchar(255); `sender_name` varchar(255); `message` text; `created_at` timestamp?; `updated_at` timestamp?

FKs: `brand_request_id` → brand_requests.id

Indexes: brand_request_messages_brand_request_id_index(brand_request_id)

### central.`brand_requests`

Model `App\Models\BrandRequest` — traits: CentralConnection — relations: tenant:belongsTo(Tenant), messages:hasMany(BrandRequestMessage), paymentRequests:hasMany(BrandPaymentRequest)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(36) MUL; `title` varchar(255); `description` text?; `attachments` json?; `status` varchar(50); `admin_notes` text?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: brand_requests_tenant_id_index(tenant_id)

### central.`cache`

Columns: `key` varchar(255) PRI; `value` mediumtext; `expiration` bigint MUL

Indexes: cache_expiration_index(expiration)

### central.`cache_locks`

Columns: `key` varchar(255) PRI; `owner` varchar(255); `expiration` bigint MUL

Indexes: cache_locks_expiration_index(expiration)

### central.`catalog_ai_batch_jobs`

Columns: `id` bigint unsigned PRI; `openai_batch_id` varchar(255)? UNI; `input_file_id` varchar(255)?; `output_file_id` varchar(255)?; `target_locale` varchar(10); `source_locale` varchar(10); `target_language` varchar(255); `status` varchar(30) MUL; `total_items` int unsigned; `completed` int unsigned; `failed` int unsigned; `errors` json?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: catalog_ai_batch_jobs_openai_batch_id_unique UNIQUE(openai_batch_id); catalog_ai_batch_jobs_status_target_locale_index(status,target_locale)

### central.`catalog_dedup_runs`

Model `App\Models\CatalogDedupRun` — traits: CentralConnection

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(36)? MUL; `section` varchar(40) MUL; `step` varchar(20); `status` varchar(20); `counts` json?; `error` text?; `started_at` timestamp?; `finished_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: catalog_dedup_runs_section_status_index(section,status); catalog_dedup_runs_tenant_section_step_index(tenant_id,section,step)

### central.`categories`

Model `App\Models\Category` — traits: HasFactory, HasTranslations, CentralConnection, SoftDeletes — relations: parent:belongsTo(self), children:hasMany(self), products:belongsToMany(Product), files:morphMany(File), translations:morphMany

Columns: `id` bigint unsigned PRI; `external_id` bigint unsigned? MUL; `parent_id` bigint unsigned? MUL; `status` varchar(255); `is_featured` tinyint(1); `order_number` int unsigned; `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

FKs: `parent_id` → categories.id

Indexes: categories_external_id_index(external_id); categories_external_id_live_unique UNIQUE(None); categories_parent_id_foreign(parent_id)

### central.`category_product`

Columns: `category_id` bigint unsigned PRI; `product_id` bigint unsigned PRI; `sort_order` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

FKs: `category_id` → categories.id; `product_id` → products.id

Indexes: category_product_product_id_foreign(product_id)

### central.`central_coupon_country`

Columns: `central_coupon_id` bigint unsigned PRI; `country_id` bigint unsigned PRI

FKs: `central_coupon_id` → central_coupons.id; `country_id` → countries.id

Indexes: central_coupon_country_country_id_foreign(country_id)

### central.`central_coupons`

Model `App\Models\CentralCoupon` — traits: SoftDeletes

Columns: `id` bigint unsigned PRI; `code` varchar(255) MUL; `name` varchar(255)?; `type` varchar(255); `value` decimal(10,2); `start_date` timestamp?; `end_date` timestamp?; `minimum_spend` decimal(10,2); `active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

Indexes: central_coupons_code_index(code); central_coupons_code_live_unique UNIQUE(None)

### central.`central_flash_sale_country`

Columns: `central_flash_sale_id` bigint unsigned PRI; `country_id` bigint unsigned PRI

FKs: `central_flash_sale_id` → central_flash_sales.id; `country_id` → countries.id

Indexes: central_flash_sale_country_country_id_foreign(country_id)

### central.`central_flash_sale_product`

Columns: `id` bigint unsigned PRI; `central_flash_sale_id` bigint unsigned MUL; `product_id` bigint unsigned MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `central_flash_sale_id` → central_flash_sales.id; `product_id` → products.id

Indexes: central_flash_sale_product_central_flash_sale_id_foreign(central_flash_sale_id); central_flash_sale_product_product_id_foreign(product_id)

### central.`central_flash_sales`

Model `App\Models\CentralFlashSale` — traits: SoftDeletes — relations: products:belongsToMany(Product), files:morphMany(File)

Columns: `id` bigint unsigned PRI; `discount_percentage` decimal(6,2); `start_date` timestamp?; `end_date` timestamp?; `active` tinyint(1); `banner_image` varchar(255)?; `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

### central.`cities`

Model `App\Models\City` — traits: CentralConnection — relations: country:belongsTo(Country)

Columns: `id` bigint unsigned PRI; `country_id` bigint unsigned MUL; `name` varchar(255); `created_at` timestamp?; `updated_at` timestamp?

FKs: `country_id` → countries.id

Indexes: cities_country_id_index(country_id)

### central.`countries`

Model `App\Models\Country` — traits: HasFactory, HasTranslations, CentralConnection — relations: defaultLanguage:belongsTo(Language), translations:morphMany

Columns: `id` bigint unsigned PRI; `iso2` varchar(2)? UNI; `iso3` varchar(3)? UNI; `currency_code` varchar(6)? MUL; `language_code` varchar(12)?; `language_direction` varchar(3)?; `phone_code` varchar(10)?; `flag_emoji` varchar(10)?; `is_active_for_tenants` tinyint(1); `is_free` tinyint(1); `name` varchar(255); `default_language_id` bigint unsigned? MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `default_language_id` → languages.id

Indexes: countries_currency_code_index(currency_code); countries_default_language_id_foreign(default_language_id); countries_iso2_unique UNIQUE(iso2); countries_iso3_unique UNIQUE(iso3)

### central.`currencies`

Model `App\Models\Currency` — traits: HasFactory, CentralConnection

Columns: `id` bigint unsigned PRI; `code` varchar(3)? UNI; `name` varchar(255); `sign` varchar(12)?; `conversion_rate` decimal(20,6); `created_at` timestamp?; `updated_at` timestamp?

Indexes: currencies_code_unique UNIQUE(code)

### central.`default_banner_country`

Columns: `default_banner_id` bigint unsigned PRI; `country_id` bigint unsigned PRI

FKs: `country_id` → countries.id; `default_banner_id` → default_banners.id

Indexes: default_banner_country_country_id_foreign(country_id)

### central.`default_banners`

Model `App\Models\DefaultBanner` — traits: CentralConnection, HasTranslations — relations: translations:morphMany

Columns: `id` bigint unsigned PRI; `url` varchar(500)?; `image_path` varchar(255)?; `serial_number` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

### central.`delivery_popup_days`

Model `App\Models\DeliveryPopupDay` — relations: country:belongsTo(Country)

Columns: `id` bigint unsigned PRI; `country_id` bigint unsigned? MUL; `day_number` tinyint unsigned; `percentage` decimal(5,2); `created_at` timestamp?; `updated_at` timestamp?

FKs: `country_id` → countries.id

Indexes: dpd_country_day_idx(country_id,day_number)

### central.`dns_records`

Model `App\Models\DnsRecord` — traits: CentralConnection

Columns: `id` bigint unsigned PRI; `type` varchar(10); `name` varchar(255); `value` varchar(255); `ttl` int unsigned; `priority` smallint unsigned?; `description` text?; `is_required` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

### central.`domain_requests`

Model `App\Models\DomainRequest` — traits: HasFactory, CentralConnection — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255)? MUL; `domain` varchar(255) MUL; `status` varchar(255); `requested_at` timestamp?; `verified_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: domain_requests_domain_index(domain); domain_requests_tenant_id_index(tenant_id)

### central.`domains`

Columns: `id` int unsigned PRI; `domain` varchar(255) UNI; `tenant_id` varchar(255) MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `tenant_id` → tenants.id

Indexes: domains_domain_unique UNIQUE(domain); domains_tenant_id_foreign(tenant_id)

### central.`email_template_translations`

Model `App\Models\EmailTemplateTranslation` — traits: CentralConnection — relations: emailTemplate:belongsTo(EmailTemplate)

Columns: `id` bigint unsigned PRI; `email_template_id` bigint unsigned MUL; `locale` varchar(10); `subject` varchar(255); `body` longtext?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `email_template_id` → email_templates.id

Indexes: email_template_translations_email_template_id_locale_unique UNIQUE(email_template_id,locale)

### central.`email_templates`

Model `App\Models\EmailTemplate` — traits: HasFactory — relations: translations:hasMany(EmailTemplateTranslation)

Columns: `id` bigint unsigned PRI; `name` varchar(255); `action` varchar(255)? MUL; `subject` varchar(255); `body` longtext?; `type` varchar(255) MUL; `status` varchar(255); `created_at` timestamp?; `updated_at` timestamp?

Indexes: email_templates_action_index(action); email_templates_type_index(type)

### central.`failed_jobs`

Columns: `id` bigint unsigned PRI; `uuid` varchar(255) UNI; `connection` text; `queue` text; `payload` longtext; `exception` longtext; `failed_at` timestamp

Indexes: failed_jobs_uuid_unique UNIQUE(uuid)

### central.`faqs`

Model `App\Models\Faq` — traits: HasFactory, HasTranslations — relations: translations:morphMany

Columns: `id` bigint unsigned PRI; `status` varchar(255); `created_at` timestamp?; `updated_at` timestamp?

### central.`files`

Model `App\Models\File` — relations: model:morphTo

Columns: `id` bigint unsigned PRI; `external_id` bigint unsigned? MUL; `key` varchar(255)? MUL; `path` varchar(255); `storage_type` varchar(255); `file_type` varchar(255); `mime_type` varchar(255)?; `extension` varchar(20)?; `size` bigint unsigned?; `model_type` varchar(255)? MUL; `model_id` bigint unsigned?; `sort_order` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

Indexes: files_external_id_index(external_id); files_key_index(key); files_model_type_model_id_index(model_type,model_id)

### central.`fixed_shipping_costs`

Model `App\Models\FixedShippingCost` — traits: HasFactory, CentralConnection — relations: country:belongsTo(Country)

Columns: `id` bigint unsigned PRI; `country_id` bigint unsigned UNI; `price_per_gram` decimal(14,6); `currency_code` varchar(3); `is_active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

FKs: `country_id` → countries.id

Indexes: fixed_shipping_costs_country_id_is_active_index(country_id,is_active); fixed_shipping_costs_country_id_unique UNIQUE(country_id)

### central.`home_variants`

Model `App\Models\HomeVariant` — traits: HasFactory, CentralConnection — relations: bladeTheme:belongsTo(BladeTheme)

Columns: `id` bigint unsigned PRI; `theme_slug` varchar(255) MUL; `key` varchar(255); `name` varchar(255); `description` text?; `preview_image` varchar(255)?; `sections` json?; `colors` json?; `view` varchar(255)?; `is_default` tinyint(1); `is_active` tinyint(1); `blade_theme_id` bigint unsigned? MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `blade_theme_id` → blade_themes.id

Indexes: home_variants_blade_theme_id_index(blade_theme_id); home_variants_theme_slug_key_unique UNIQUE(theme_slug,key)

### central.`invoices`

Model `App\Models\Invoice` — traits: HasFactory, CentralConnection — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255)? MUL; `tenant_order_id` bigint unsigned?; `order_uuid` varchar(255)?; `order_number` varchar(255)?; `invoice_number` varchar(255) UNI; `customer_name` varchar(255)?; `customer_email` varchar(255)?; `currency` varchar(12); `amount` decimal(12,2); `status` varchar(255); `payment_method` varchar(255)?; `payment_reference` varchar(255)?; `pdf_disk` varchar(255)?; `pdf_path` varchar(255)?; `meta` json?; `issued_at` timestamp?; `due_at` timestamp?; `paid_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: invoices_invoice_number_unique UNIQUE(invoice_number); invoices_tenant_id_index(tenant_id); invoices_tenant_id_order_uuid_index(tenant_id,order_uuid)

### central.`job_batches`

Columns: `id` varchar(255) PRI; `name` varchar(255); `total_jobs` int; `pending_jobs` int; `failed_jobs` int; `failed_job_ids` longtext; `options` mediumtext?; `cancelled_at` int?; `created_at` int; `finished_at` int?

### central.`jobs`

Columns: `id` bigint unsigned PRI; `queue` varchar(255) MUL; `payload` longtext; `attempts` tinyint unsigned; `reserved_at` int unsigned?; `available_at` int unsigned; `created_at` int unsigned

Indexes: jobs_queue_index(queue)

### central.`languages`

Model `App\Models\Language` — traits: HasFactory, CentralConnection — relations: translations:hasMany(Translation), imageFile:belongsTo(File)

Columns: `id` bigint unsigned PRI; `name` varchar(255); `code` varchar(12) UNI; `native_name` varchar(255); `direction` varchar(3); `is_default` tinyint(1); `sort_order` int unsigned; `is_active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?; `image_file_id` bigint unsigned? MUL; `is_free` tinyint(1); `price` decimal(10,2)?; `ai_translation_price` decimal(10,2)?; `countries` json?; `translation_progress` tinyint unsigned; `translation_status` varchar(20); `translation_source_locale` varchar(10)?; `translation_error` text?; `ai_tokens_used` bigint unsigned

Indexes: languages_code_unique UNIQUE(code); languages_image_file_id_index(image_file_id)

### central.`maintenance_windows`

Model `App\Models\MaintenanceWindow` — traits: HasFactory

Columns: `id` bigint unsigned PRI; `message` varchar(255)?; `is_active` tinyint(1); `starts_at` timestamp?; `ends_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

### central.`manufacturing_payment_requests`

Model `App\Models\ManufacturingPaymentRequest` — traits: CentralConnection — relations: manufacturingRequest:belongsTo(ManufacturingRequest), tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `manufacturing_request_id` bigint unsigned MUL; `tenant_id` varchar(36) MUL; `label` varchar(255); `amount` decimal(12,2); `currency` varchar(10); `status` varchar(30); `gateway_code` varchar(255)?; `transaction_id` varchar(255)?; `paid_at` timestamp?; `notes` text?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `manufacturing_request_id` → manufacturing_requests.id

Indexes: manufacturing_payment_requests_manufacturing_request_id_foreign(manufacturing_request_id); manufacturing_payment_requests_tenant_id_index(tenant_id)

### central.`manufacturing_request_messages`

Model `App\Models\ManufacturingRequestMessage` — traits: CentralConnection — relations: manufacturingRequest:belongsTo(ManufacturingRequest)

Columns: `id` bigint unsigned PRI; `manufacturing_request_id` bigint unsigned MUL; `sender_type` varchar(255); `sender_name` varchar(255); `message` text; `created_at` timestamp?; `updated_at` timestamp?

FKs: `manufacturing_request_id` → manufacturing_requests.id

Indexes: manufacturing_request_messages_manufacturing_request_id_index(manufacturing_request_id)

### central.`manufacturing_requests`

Model `App\Models\ManufacturingRequest` — traits: CentralConnection — relations: product:belongsTo(Product), tenant:belongsTo(Tenant), messages:hasMany(ManufacturingRequestMessage), paymentRequests:hasMany(ManufacturingPaymentRequest)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(36) MUL; `product_id` bigint unsigned? MUL; `product_name` varchar(255); `description` text?; `reference_url` varchar(2000)?; `images` json?; `quantity` int unsigned; `status` varchar(50); `admin_notes` text?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_id` → products.id

Indexes: manufacturing_requests_product_id_foreign(product_id); manufacturing_requests_tenant_id_index(tenant_id)

### central.`migrations`

Columns: `id` int unsigned PRI; `migration` varchar(255); `batch` int

### central.`newsletter_subscribers`

Model `App\Models\NewsletterSubscriber`

Columns: `id` bigint unsigned PRI; `email` varchar(255) UNI; `name` varchar(255)?; `status` varchar(255); `ip_address` varchar(45)?; `source` varchar(255)?; `subscribed_at` timestamp?; `unsubscribed_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: newsletter_subscribers_email_unique UNIQUE(email)

### central.`order_attachments`

Model `App\Models\OrderAttachment` — traits: CentralConnection — relations: uploader:belongsTo(AdminUser)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(36) MUL; `order_number` varchar(255) MUL; `path` varchar(255); `original_name` varchar(255); `mime_type` varchar(100)?; `extension` varchar(20)?; `size_bytes` bigint unsigned?; `uploaded_by_admin_id` bigint unsigned? MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `uploaded_by_admin_id` → admins.id

Indexes: order_attachments_order_number_index(order_number); order_attachments_tenant_id_index(tenant_id); order_attachments_uploaded_by_admin_id_foreign(uploaded_by_admin_id)

### central.`packages`

Model `App\Models\Package` — traits: HasFactory, HasTranslations, CentralConnection — relations: translations:morphMany

Columns: `id` bigint unsigned PRI; `name` varchar(255); `icon` varchar(255)?; `status` varchar(255); `term` varchar(255); `price` decimal(10,2); `features` json?; `categories_count` int unsigned; `products_limit` int; `banners_limit` int; `languages_limit` int; `orders_per_month_limit` int; `ai_calls_limit` int; `image_searches_limit` int; `ai_translation_enabled` tinyint(1); `trial_days` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

### central.`payment_gateways`

Model `App\Models\PaymentGateway` — traits: HasFactory, CentralConnection — relations: logoFile:belongsTo(File)

Columns: `id` bigint unsigned PRI; `name` varchar(255); `code` varchar(255) MUL; `type` varchar(255); `status` varchar(255); `mode` varchar(255); `credentials` text?; `transaction_fee_percentage` decimal(7,4); `transaction_fee_fixed` decimal(10,2); `logo_file_id` bigint unsigned? MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `logo_file_id` → files.id

Indexes: payment_gateways_code_type_unique UNIQUE(code,type); payment_gateways_logo_file_id_foreign(logo_file_id)

### central.`payment_logs`

Model `App\Models\PaymentLog` — traits: HasFactory, CentralConnection — relations: tenant:belongsTo(Tenant), package:belongsTo(Package)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255)? MUL; `package_id` bigint unsigned? MUL; `gateway` varchar(255); `amount` decimal(12,2); `status` varchar(255); `reference` varchar(255)? MUL; `paid_at` timestamp?; `meta` json?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `package_id` → packages.id

Indexes: payment_logs_package_id_foreign(package_id); payment_logs_reference_index(reference); payment_logs_tenant_id_index(tenant_id)

### central.`pending_registrations`

Model `App\Models\PendingRegistration` — relations: package:belongsTo(Package), affiliateReferral:belongsTo(AffiliateReferral)

Columns: `id` bigint unsigned PRI; `affiliate_referral_id` bigint unsigned? MUL; `token` varchar(64) UNI; `email` varchar(255); `phone` varchar(50)?; `locale` varchar(10)?; `package_id` bigint unsigned?; `category_ids` json?; `country_ids` json?; `payment_data` json?; `expires_at` timestamp; `completed_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?; `compliance_version` varchar(64)?; `compliance_accepted_at` timestamp?

FKs: `affiliate_referral_id` → affiliate_referrals.id

Indexes: pending_registrations_affiliate_referral_id_foreign(affiliate_referral_id); pending_registrations_token_unique UNIQUE(token)

### central.`product_badge_product`

Columns: `id` bigint unsigned PRI; `product_badge_id` bigint unsigned MUL; `product_id` bigint unsigned MUL; `country_id` bigint unsigned? MUL; `sort_order` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

FKs: `country_id` → countries.id; `product_badge_id` → product_badges.id; `product_id` → products.id

Indexes: pbp_badge_id_index(product_badge_id); pbp_product_id_index(product_id); pbp_unique UNIQUE(product_badge_id,product_id,country_id); product_badge_product_country_id_foreign(country_id)

### central.`product_badges`

Model `App\Models\ProductBadge` — traits: CentralConnection — relations: products:belongsToMany(Product)

Columns: `id` bigint unsigned PRI; `text` varchar(255) UNI; `active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

Indexes: product_badges_text_unique UNIQUE(text)

### central.`product_country`

Columns: `product_id` bigint unsigned PRI; `country_id` bigint unsigned PRI

FKs: `country_id` → countries.id; `product_id` → products.id

Indexes: product_country_country_id_foreign(country_id)

### central.`product_edit_requests`

Model `App\Models\ProductEditRequest` — traits: CentralConnection, HasCatalogRefs — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `product_id` bigint unsigned; `product_slug` varchar(255)?; `requested_translations` json; `current_translations` json; `status` varchar(255); `admin_note` varchar(255)?; `reviewed_by` bigint unsigned?; `reviewed_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?; `product_type` varchar(10)?; `catalog_product_id` bigint unsigned?; `central_product_id` bigint unsigned? MUL

FKs: `tenant_id` → tenants.id

Indexes: product_edit_requests_central_product_id_index(central_product_id); product_edit_requests_product_ref_index(tenant_id,product_type,catalog_product_id); product_edit_requests_tenant_id_product_id_unique UNIQUE(tenant_id,product_id); product_edit_requests_tenant_id_status_index(tenant_id,status)

### central.`product_image_embeddings`

Model `App\Models\ProductImageEmbedding` — traits: CentralConnection — relations: product:belongsTo(Product), variant:belongsTo(ProductVariant)

Columns: `id` bigint unsigned PRI; `product_id` bigint unsigned MUL; `variant_id` bigint unsigned? MUL; `source` varchar(255); `embedding` json; `dims` int unsigned; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_id` → products.id; `variant_id` → product_variants.id

Indexes: product_image_embeddings_product_id_variant_id_source_unique UNIQUE(product_id,variant_id,source); product_image_embeddings_variant_id_foreign(variant_id)

### central.`product_label_product`

Columns: `product_label_id` bigint unsigned PRI; `product_id` bigint unsigned PRI; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_id` → products.id; `product_label_id` → product_labels.id

Indexes: product_label_product_product_id_unique UNIQUE(product_id)

### central.`product_labels`

Model `App\Models\ProductLabel` — traits: CentralConnection, SoftDeletes — relations: products:belongsToMany(Product)

Columns: `id` bigint unsigned PRI; `text` varchar(255); `translations` json?; `icon` varchar(40)?; `active` tinyint(1); `sort_order` int unsigned; `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

### central.`product_request_messages`

Model `App\Models\ProductRequestMessage` — relations: productRequest:belongsTo(ProductRequest)

Columns: `id` bigint unsigned PRI; `product_request_id` bigint unsigned MUL; `sender_type` varchar(10); `sender_name` varchar(255); `body` text; `attachments` json?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_request_id` → product_requests.id

Indexes: product_request_messages_product_request_id_index(product_request_id)

### central.`product_requests`

Model `App\Models\ProductRequest` — relations: messages:hasMany(ProductRequestMessage), tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(36) MUL; `title` varchar(255); `description` text; `product_url` varchar(2000)?; `attachments` json?; `status` varchar(30); `tenant_has_unread` tinyint(1); `admin_has_unread` tinyint(1) MUL; `last_reply_at` timestamp?; `priority` varchar(20); `created_at` timestamp?; `updated_at` timestamp?

Indexes: product_requests_admin_has_unread_index(admin_has_unread); product_requests_tenant_id_index(tenant_id); product_requests_tenant_id_status_index(tenant_id,status)

### central.`product_shipping_zone`

Columns: `product_id` bigint unsigned PRI; `shipping_zone_id` bigint unsigned PRI; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_id` → products.id; `shipping_zone_id` → shipping_zones.id

Indexes: product_shipping_zone_shipping_zone_id_foreign(shipping_zone_id)

### central.`product_tenant_assignments`

Model `App\Models\ProductTenantAssignment` — traits: CentralConnection — relations: product:belongsTo(Product), tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `product_id` bigint unsigned MUL; `tenant_id` varchar(255) MUL; `synced_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_id` → products.id

Indexes: product_tenant_assignments_product_id_tenant_id_unique UNIQUE(product_id,tenant_id); product_tenant_assignments_tenant_id_index(tenant_id)

### central.`product_variant_option`

Columns: `product_variant_id` bigint unsigned PRI; `variation_option_id` bigint unsigned PRI; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_variant_id` → product_variants.id; `variation_option_id` → variation_options.id

Indexes: product_variant_option_variation_option_id_foreign(variation_option_id)

### central.`product_variants`

Model `App\Models\ProductVariant` — traits: HasFactory, HasTranslations, CentralConnection, SoftDeletes — relations: product:belongsTo(Product), options:belongsToMany(VariationOption), files:morphMany(File), translations:morphMany

Columns: `id` bigint unsigned PRI; `product_id` bigint unsigned MUL; `sku` varchar(255)? MUL; `title` varchar(255)?; `weight_grams` int unsigned?; `fixed_shipping_costs` json?; `price` decimal(10,2)?; `stock` int; `position` int unsigned; `status` varchar(255); `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

FKs: `product_id` → products.id

Indexes: product_variants_product_id_foreign(product_id); product_variants_sku_index(sku); product_variants_sku_live_unique UNIQUE(None)

### central.`product_variation`

Columns: `product_id` bigint unsigned PRI; `variation_id` bigint unsigned PRI; `created_at` timestamp?; `updated_at` timestamp?

FKs: `product_id` → products.id; `variation_id` → variations.id

Indexes: product_variation_variation_id_foreign(variation_id)

### central.`products`

Model `App\Models\Product` — traits: HasFactory, HasTranslations, CentralConnection, SoftDeletes — relations: categories:belongsToMany(Category), badges:belongsToMany(ProductBadge), labels:belongsToMany(ProductLabel), countries:belongsToMany(Country), shippingZones:belongsToMany(ShippingZone), branches:belongsToMany(Branch), branchProducts:hasMany(BranchProduct), variations:belongsToMany(Variation), variants:hasMany(ProductVariant), files:morphMany(File), tenantAssignments:hasMany(ProductTenantAssignment), translations:morphMany

Columns: `id` bigint unsigned PRI; `external_id` bigint unsigned? UNI; `slug` varchar(255) UNI; `sku` varchar(255) UNI; `status` varchar(255); `weight_grams` int unsigned?; `fixed_shipping_costs` json?; `factory` varchar(255)?; `delivery_scope` varchar(255); `base_price` decimal(20,2); `sale_price` decimal(20,2)?; `cost_price` decimal(20,2)?; `stock` int; `out_of_stock_at` timestamp?; `sold_count` int unsigned; `min_stock` int; `manage_stock` tinyint(1); `is_taxable` tinyint(1); `requires_shipping` tinyint(1); `published_at` timestamp?; `order_number` int unsigned; `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

Indexes: products_external_id_unique UNIQUE(external_id); products_sku_unique UNIQUE(sku); products_slug_unique UNIQUE(slug)

### central.`refunds`

Model `App\Models\Refund` — traits: CentralConnection — relations: tenant:belongsTo(Tenant), returnRequest:belongsTo(ReturnRequest)

Columns: `id` bigint unsigned PRI; `reference` varchar(32) UNI; `tenant_id` varchar(36) MUL; `order_number` varchar(255) MUL; `return_request_id` bigint unsigned? MUL; `source` varchar(20) MUL; `reason` varchar(255); `currency` varchar(3); `items_amount` decimal(12,2); `shipping_amount` decimal(12,2); `return_fee` decimal(12,2); `amount` decimal(12,2); `payment_method` varchar(255)?; `gateway` varchar(255)?; `original_transaction_id` varchar(255)?; `refund_method` varchar(20); `gateway_refund_id` varchar(255)?; `status` varchar(20) MUL; `failure_reason` text?; `requested_by_type` varchar(20)?; `requested_by_id` bigint unsigned?; `approved_by_type` varchar(20)?; `approved_by_id` bigint unsigned?; `approved_by_name` varchar(255)?; `requested_at` timestamp MUL; `approved_at` timestamp?; `processed_at` timestamp?; `notes` text?; `meta` json?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `return_request_id` → return_requests.id

Indexes: refunds_order_number_index(order_number); refunds_reference_unique UNIQUE(reference); refunds_requested_at_index(requested_at); refunds_return_request_id_foreign(return_request_id); refunds_source_index(source); refunds_status_index(status); refunds_tenant_id_index(tenant_id); refunds_tenant_id_order_number_index(tenant_id,order_number); refunds_tenant_id_status_index(tenant_id,status)

### central.`return_request_media`

Model `App\Models\ReturnRequestMedia` — traits: CentralConnection — relations: returnRequest:belongsTo(ReturnRequest)

Columns: `id` bigint unsigned PRI; `return_request_id` bigint unsigned MUL; `file_path` varchar(255); `type` varchar(20); `created_at` timestamp?; `updated_at` timestamp?

FKs: `return_request_id` → return_requests.id

Indexes: return_request_media_return_request_id_foreign(return_request_id)

### central.`return_request_notes`

Model `App\Models\ReturnRequestNote` — traits: CentralConnection — relations: returnRequest:belongsTo(ReturnRequest)

Columns: `id` bigint unsigned PRI; `return_request_id` bigint unsigned MUL; `author_type` varchar(20); `author_id` bigint unsigned?; `note` text; `customer_visible` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

FKs: `return_request_id` → return_requests.id

Indexes: return_request_notes_return_request_id_foreign(return_request_id)

### central.`return_requests`

Model `App\Models\ReturnRequest` — traits: CentralConnection, HasCatalogRefs — relations: media:hasMany(ReturnRequestMedia), notes:hasMany(ReturnRequestNote), refunds:hasMany(Refund), reviewer:belongsTo(AdminUser)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(36) MUL; `order_number` varchar(255) MUL; `type` varchar(20) MUL; `customer_id` bigint unsigned; `order_item_id` bigint unsigned?; `product_id` bigint unsigned?; `product_variant_id` bigint unsigned?; `quantity` int unsigned; `return_method` varchar(30)?; `reason` varchar(255); `description` text?; `customer_note` text?; `status` varchar(30) MUL; `refund_amount` decimal(12,2)?; `inspection_result` varchar(20)?; `inspection_notes` text?; `received_at` timestamp?; `inspected_at` timestamp?; `restocked_at` timestamp?; `replacement_product_variant_id` bigint unsigned?; `replacement_quantity` int unsigned?; `replacement_reserved_at` timestamp?; `exchange_tracking_number` varchar(255)?; `exchange_shipped_at` timestamp?; `exchange_completed_at` timestamp?; `cancelled_at` timestamp?; `forwarded_at` timestamp?; `reviewed_by_admin_id` bigint unsigned? MUL; `reviewed_by_type` varchar(20)?; `reviewed_by_id` bigint unsigned?; `reviewed_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?; `product_type` varchar(10)?; `catalog_product_id` bigint unsigned?; `variant_type` varchar(10)?; `catalog_variant_id` bigint unsigned?; `replacement_variant_type` varchar(10)?; `catalog_replacement_variant_id` bigint unsigned?

FKs: `reviewed_by_admin_id` → admins.id

Indexes: return_requests_order_item_index(tenant_id,order_number,order_item_id); return_requests_order_number_index(order_number); return_requests_product_ref_index(tenant_id,product_type,catalog_product_id); return_requests_replacement_ref_index(tenant_id,replacement_variant_type,catalog_replacement_variant_id); return_requests_reviewed_by_admin_id_foreign(reviewed_by_admin_id); return_requests_status_index(status); return_requests_tenant_id_index(tenant_id); return_requests_type_index(type); return_requests_variant_ref_index(tenant_id,variant_type,catalog_variant_id)

### central.`sessions`

Columns: `id` varchar(255) PRI; `user_id` bigint unsigned? MUL; `ip_address` varchar(45)?; `user_agent` text?; `payload` longtext; `last_activity` int MUL

Indexes: sessions_last_activity_index(last_activity); sessions_user_id_index(user_id)

### central.`shipping_day_rules`

Model `App\Models\ShippingDayRule` — traits: CentralConnection

Columns: `id` bigint unsigned PRI; `country_code` varchar(2)? UNI; `min_days` tinyint unsigned; `max_days` tinyint unsigned; `created_at` timestamp?; `updated_at` timestamp?

Indexes: shipping_day_rules_country_code_unique UNIQUE(country_code)

### central.`shipping_stops`

Model `App\Models\ShippingStop`

Columns: `id` bigint unsigned PRI; `from_date` date; `to_date` date; `reason` varchar(255)?; `active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

### central.`shipping_zone_rates`

Model `App\Models\ShippingZoneRate` — traits: HasFactory, CentralConnection — relations: shippingZone:belongsTo(ShippingZone)

Columns: `id` bigint unsigned PRI; `shipping_zone_id` bigint unsigned MUL; `name` varchar(255); `min_weight` decimal(10,2)?; `max_weight` decimal(10,2)?; `price` decimal(10,2); `is_active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

FKs: `shipping_zone_id` → shipping_zones.id

Indexes: shipping_zone_rates_shipping_zone_id_foreign(shipping_zone_id)

### central.`shipping_zones`

Model `App\Models\ShippingZone` — traits: HasFactory, HasTranslations, CentralConnection — relations: branch:belongsTo(Branch), country:belongsTo(Country), rates:hasMany(ShippingZoneRate), products:belongsToMany(Product), translations:morphMany

Columns: `id` bigint unsigned PRI; `branch_id` bigint unsigned? MUL; `country_id` bigint unsigned? MUL; `name` varchar(255); `code` varchar(255); `currency_code` varchar(255); `status` varchar(255); `created_at` timestamp?; `updated_at` timestamp?

FKs: `branch_id` → branches.id; `country_id` → countries.id

Indexes: shipping_zones_branch_id_foreign(branch_id); shipping_zones_country_id_foreign(country_id)

### central.`static_pages`

Model `App\Models\StaticPage` — traits: HasFactory, HasTranslations — relations: translations:morphMany

Columns: `id` bigint unsigned PRI; `slug` varchar(255) UNI; `status` varchar(255); `is_compliance` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

Indexes: static_pages_slug_unique UNIQUE(slug)

### central.`support_ticket_messages`

Model `App\Models\SupportTicketMessage` — traits: HasFactory — relations: ticket:belongsTo(SupportTicket)

Columns: `id` bigint unsigned PRI; `ticket_id` bigint unsigned MUL; `sender_type` varchar(10); `sender_name` varchar(255); `body` text; `attachments` json?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `ticket_id` → support_tickets.id

Indexes: support_ticket_messages_ticket_id_foreign(ticket_id)

### central.`support_tickets`

Model `App\Models\SupportTicket` — traits: HasFactory — relations: messages:hasMany(SupportTicketMessage)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(36) MUL; `subject` varchar(255); `status` varchar(20) MUL; `priority` varchar(20); `category` varchar(20); `tenant_has_unread` tinyint(1); `admin_has_unread` tinyint(1); `last_reply_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: support_tickets_status_index(status); support_tickets_tenant_id_index(tenant_id)

### central.`template_country`

Columns: `id` bigint unsigned PRI; `template_id` bigint unsigned MUL; `country_id` bigint unsigned MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `country_id` → countries.id; `template_id` → templates.id

Indexes: template_country_country_id_index(country_id); template_country_template_id_country_id_unique UNIQUE(template_id,country_id)

### central.`template_parts`

Model `App\Models\TemplatePart` — traits: HasFactory, CentralConnection — relations: template:belongsTo(Template)

Columns: `id` bigint unsigned PRI; `template_id` bigint unsigned MUL; `type` varchar(32); `name` varchar(255); `slug` varchar(255); `content` longtext; `image` varchar(255)?; `is_active` tinyint(1); `is_default` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

FKs: `template_id` → templates.id

Indexes: template_parts_template_id_slug_unique UNIQUE(template_id,slug); template_parts_template_id_type_is_active_index(template_id,type,is_active)

### central.`templates`

Model `App\Models\Template` — traits: HasFactory, CentralConnection — relations: previewFile:belongsTo(File), countries:belongsToMany(Country), parts:hasMany(TemplatePart)

Columns: `id` bigint unsigned PRI; `name` varchar(255); `slug` varchar(255) UNI; `version` varchar(255)?; `author` varchar(255)?; `is_active` tinyint(1); `is_default` tinyint(1); `preview_file_id` bigint unsigned? MUL; `created_at` timestamp?; `updated_at` timestamp?

FKs: `preview_file_id` → files.id

Indexes: templates_preview_file_id_foreign(preview_file_id); templates_slug_unique UNIQUE(slug)

### central.`tenant_ai_translation_purchases`

Model `App\Models\TenantAiTranslationPurchase` — traits: CentralConnection — relations: language:belongsTo(Language), tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `central_language_id` bigint unsigned MUL; `amount` decimal(10,2); `gateway_code` varchar(255)?; `transaction_uuid` varchar(255)? MUL; `translated_at` timestamp?; `status` varchar(255); `created_at` timestamp?; `updated_at` timestamp?

FKs: `central_language_id` → languages.id; `tenant_id` → tenants.id

Indexes: tenant_ai_translation_purchases_central_language_id_index(central_language_id); tenant_ai_translation_purchases_tenant_id_index(tenant_id); tenant_ai_translation_purchases_transaction_uuid_index(transaction_uuid)

### central.`tenant_change_requests`

Model `App\Models\TenantChangeRequest` — traits: CentralConnection — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `type` varchar(255); `requested_data` json; `current_data` json; `status` varchar(255); `admin_note` varchar(255)?; `reviewed_by` bigint unsigned?; `reviewed_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `tenant_id` → tenants.id

Indexes: tenant_change_requests_tenant_id_status_index(tenant_id,status); tenant_change_requests_tenant_id_type_status_index(tenant_id,type,status)

### central.`tenant_countries`

Model `App\Models\TenantCountry` — traits: CentralConnection — relations: tenant:belongsTo(Tenant), country:belongsTo(Country)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `country_id` bigint unsigned MUL; `is_active` tinyint(1); `created_at` timestamp?; `updated_at` timestamp?

FKs: `country_id` → countries.id; `tenant_id` → tenants.id

Indexes: tenant_countries_country_id_foreign(country_id); tenant_countries_tenant_id_country_id_unique UNIQUE(tenant_id,country_id)

### central.`tenant_database_pool`

Columns: `id` bigint unsigned PRI; `database_name` varchar(255) UNI; `migrated_through` varchar(255); `claimed_by` varchar(255)? UNI; `claimed_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: tenant_database_pool_claimed_by_id_index(claimed_by,id); tenant_database_pool_claimed_by_unique UNIQUE(claimed_by); tenant_database_pool_database_name_unique UNIQUE(database_name)

### central.`tenant_language_purchases`

Model `App\Models\TenantLanguagePurchase` — traits: CentralConnection — relations: language:belongsTo(Language), tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `central_language_id` bigint unsigned MUL; `transaction_uuid` varchar(255)?; `amount` decimal(10,2); `gateway_code` varchar(255)?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: tenant_language_purchases_central_language_id_index(central_language_id); tenant_language_purchases_tenant_id_central_language_id_unique UNIQUE(tenant_id,central_language_id); tenant_language_purchases_tenant_id_index(tenant_id)

### central.`tenant_mobile_apps`

Model `App\Models\TenantMobileApp` — traits: CentralConnection — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) UNI; `android_package` varchar(150)?; `android_sha256` json?; `ios_team_id` varchar(10)?; `ios_bundle_id` varchar(155)?; `url_scheme` varchar(64)? UNI; `play_store_url` varchar(500)?; `app_store_url` varchar(500)?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `tenant_id` → tenants.id

Indexes: tenant_mobile_apps_tenant_id_unique UNIQUE(tenant_id); tenant_mobile_apps_url_scheme_unique UNIQUE(url_scheme)

### central.`tenant_owners`

Model `App\Models\TenantOwner` — relations: tenant:belongsTo(Tenant), selectedTenant:belongsTo(Tenant), notifications:morphMany(DatabaseNotification)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `selected_tenant_id` varchar(255)? MUL; `email` varchar(255) UNI; `password` varchar(255); `provider` varchar(255)?; `provider_id` varchar(255)?; `avatar` varchar(255)?; `remember_token` varchar(100)?; `created_at` timestamp?; `updated_at` timestamp?

FKs: `selected_tenant_id` → tenants.id; `tenant_id` → tenants.id

Indexes: tenant_owners_email_unique UNIQUE(email); tenant_owners_selected_tenant_id_foreign(selected_tenant_id); tenant_owners_tenant_id_foreign(tenant_id)

### central.`tenant_payouts`

Model `App\Models\TenantPayout` — traits: CentralConnection — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `invoice_number` varchar(255) UNI; `tenant_name` varchar(255)?; `tenant_email` varchar(255)?; `amount` decimal(12,2); `status` varchar(16); `currency` varchar(8); `method` varchar(32); `bank_name` varchar(255)?; `bank_account` varchar(255)?; `gateway_name` varchar(255)?; `transaction_reference` varchar(255)?; `note` text?; `orders_snapshot` json?; `admin_id` bigint unsigned?; `paid_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: tenant_payouts_invoice_number_unique UNIQUE(invoice_number); tenant_payouts_tenant_id_index(tenant_id); tenant_payouts_tenant_id_status_index(tenant_id,status)

### central.`tenants`

Model `App\Models\Tenant` — traits: CentralConnection, HasDatabase, HasDomains — relations: primaryLanguage:belongsTo(Language), package:belongsTo(Package), paymentLogs:hasMany(PaymentLog), tenantCountries:hasMany(TenantCountry), mobileApp:hasOne(TenantMobileApp), invoices:hasMany(Invoice), domains:hasMany(config)

Columns: `id` varchar(255) PRI; `password` varchar(255)?; `created_at` timestamp?; `updated_at` timestamp?; `data` json?; `remember_token` varchar(100)?

### central.`translations`

Model `App\Models\Translation` — traits: HasFactory — relations: language:belongsTo(Language), translatable:morphTo

Columns: `id` bigint unsigned PRI; `language_id` bigint unsigned MUL; `translatable_type` varchar(255) MUL; `translatable_id` bigint unsigned; `field` varchar(255); `value` longtext; `created_at` timestamp?; `updated_at` timestamp?

FKs: `language_id` → languages.id

Indexes: translations_translatable_type_translatable_id_index(translatable_type,translatable_id); translations_unique_field_per_language UNIQUE(language_id,translatable_type,translatable_id,field)

### central.`variation_options`

Model `App\Models\VariationOption` — traits: HasFactory, HasTranslations, CentralConnection, SoftDeletes — relations: variation:belongsTo(Variation), variants:belongsToMany(ProductVariant), translations:morphMany

Columns: `id` bigint unsigned PRI; `external_id` bigint unsigned? MUL; `variation_id` bigint unsigned MUL; `swatch` varchar(20)?; `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

Indexes: variation_options_external_id_index(external_id); variation_options_external_id_live_unique UNIQUE(None); variation_options_variation_id_index(variation_id)

### central.`variations`

Model `App\Models\Variation` — traits: HasFactory, HasTranslations, CentralConnection, SoftDeletes — relations: options:hasMany(VariationOption), products:belongsToMany(Product), translations:morphMany

Columns: `id` bigint unsigned PRI; `status` varchar(255); `created_at` timestamp?; `updated_at` timestamp?; `deleted_at` timestamp?

### central.`vendor_settlements`

Model `App\Models\VendorSettlement` — traits: CentralConnection — relations: tenant:belongsTo(Tenant)

Columns: `id` bigint unsigned PRI; `tenant_id` varchar(255) MUL; `order_id` bigint unsigned; `order_uuid` varchar(255); `invoice_number` varchar(255) UNI; `tenant_name` varchar(255)?; `tenant_email` varchar(255)?; `product_cost` decimal(10,2); `shipping_cost` decimal(10,2); `gateway_fee` decimal(10,2); `total` decimal(10,2); `gateway_code` varchar(255)?; `transaction_id` varchar(255)?; `status` varchar(50); `meta` json?; `settled_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: vendor_settlements_invoice_number_unique UNIQUE(invoice_number); vendor_settlements_tenant_id_index(tenant_id)

## B. Tenant database (one per tenant) — 64 tables (migration-derived)

Dropped by later migrations (do not rebuild): `appearance_features`, `theme_parts`.

Created in a loop by `2026_10_28_000001_create_country_pivots_for_banners_coupons_flash_sales.php` (not parsed): `banner_country`, `coupon_country`, `flash_sale_country` (`{entity}_id`, `country_id`; replaced the old single `country_id` columns).

| Table | Created in | Model(s) |
|---|---|---|
| `sessions` | `0001_01_01_000000_create_users_table.php` | — |
| `cache` | `0001_01_01_000001_create_cache_table.php` | — |
| `cache_locks` | `0001_01_01_000001_create_cache_table.php` | — |
| `jobs` | `0001_01_01_000002_create_jobs_table.php` | — |
| `job_batches` | `0001_01_01_000002_create_jobs_table.php` | — |
| `failed_jobs` | `0001_01_01_000002_create_jobs_table.php` | — |
| `languages` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Language` |
| `translations` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Translation` |
| `files` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\File` |
| `categories` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Category`, `Tenant\Catalog\MergedCategory` |
| `products` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Product`, `Tenant\Catalog\MergedProduct` |
| `category_product` | `2026_03_26_000001_create_tenant_panel_tables.php` | — |
| `product_variants` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\ProductVariant`, `Tenant\Catalog\MergedProductVariant` |
| `customers` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Customer` |
| `orders` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Order` |
| `order_items` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\OrderItem` |
| `order_activities` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\OrderActivity` |
| `transactions` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Transaction` |
| `subscriptions` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Subscription` |
| `themes` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Theme` |
| `settings` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Setting` |
| `pages` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Page` |
| `coupons` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Coupon` |
| `flash_sales` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\FlashSale` |
| `subscribers` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Subscriber` |
| `currencies` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\Currency` |
| `payment_gateways` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\PaymentGateway` |
| `email_templates` | `2026_03_26_000001_create_tenant_panel_tables.php` | `Tenant\EmailTemplate` |
| `admin_roles` | `2026_03_26_000002_create_tenant_admin_auth_tables.php` | `Tenant\AdminRole` |
| `admins` | `2026_03_26_000002_create_tenant_admin_auth_tables.php` | `Tenant\AdminUser` |
| `flash_sale_product` | `2026_04_04_000104_create_flash_sale_product_table.php` | — |
| `banners` | `2026_04_06_000201_create_appearance_tables.php` | `Tenant\Banner` |
| `social_links` | `2026_04_06_000201_create_appearance_tables.php` | `Tenant\SocialLink` |
| `product_badges` | `2026_04_07_000210_create_product_badges_table.php` | `Tenant\ProductBadge`, `Tenant\Catalog\CentralProductBadge` |
| `product_rates` | `2026_04_07_000211_create_product_rates_table.php` | `Tenant\ProductRate`, `Tenant\Catalog\MergedProductRate` |
| `customer_addresses` | `2026_04_07_000212_create_customer_addresses_table.php` | `Tenant\CustomerAddress` |
| `product_badge_product` | `2026_04_08_000221_convert_product_badges_to_pivot.php` | — |
| `language_purchases` | `2026_04_19_000001_create_language_purchases_table.php` | `Tenant\LanguagePurchase` |
| `tenant_notifications` | `2026_04_20_000002_create_tenant_notifications_table.php` | `Tenant\TenantNotification` |
| `theme_country` | `2026_04_22_000001_create_theme_country_table.php` | `Tenant\ThemeCountry` |
| `maintenance_windows` | `2026_05_12_120001_create_maintenance_windows_table.php` | — |
| `email_template_translations` | `2026_06_24_000002_create_email_template_translations_table.php` | `Tenant\EmailTemplateTranslation` |
| `tenant_page_sections` | `2026_08_14_000006_create_tenant_page_sections_table.php` | `Tenant\TenantPageSection` |
| `tenant_home_variants` | `2026_08_14_170001_create_tenant_home_variants_table.php` | `Tenant\TenantHomeVariant` |
| `translation_overrides` | `2026_08_14_170006_create_translation_overrides_table.php` | `Tenant\TranslationOverride` |
| `favorites` | `2026_08_21_000003_create_favorites_table.php` | `Tenant\Favorite` |
| `tenant_theme_colors` | `2026_09_03_000001_create_tenant_theme_colors_table.php` | `Tenant\TenantThemeColor` |
| `password_reset_tokens` | `2026_10_19_000002_create_password_reset_tokens_table.php` | — |
| `mobile_access_tokens` | `2026_10_28_000002_create_mobile_app_tables.php` | `Tenant\MobileAccessToken` |
| `mobile_app_settings` | `2026_10_28_000002_create_mobile_app_tables.php` | `Tenant\MobileAppSetting` |
| `mobile_app_theme` | `2026_10_28_000002_create_mobile_app_tables.php` | `Tenant\MobileAppTheme` |
| `mobile_carts` | `2026_10_28_000003_create_mobile_cart_and_device_tables.php` | `Tenant\MobileCart` |
| `mobile_device_tokens` | `2026_10_28_000003_create_mobile_cart_and_device_tables.php` | `Tenant\MobileDeviceToken` |
| `product_labels` | `2026_11_01_000001_create_product_labels_tables.php` | `Tenant\ProductLabel` |
| `product_label_product` | `2026_11_01_000001_create_product_labels_tables.php` | — |
| `tenant_badge_product_overrides` | `2026_11_24_000001_create_tenant_badge_product_overrides_table.php` | — |
| `tenant_field_overrides` | `2026_11_24_000002_create_tenant_field_overrides_table.php` | — |
| `tenant_category_overrides` | `2026_11_26_000001_create_tenant_category_overrides_tables.php` | — |
| `tenant_category_product_overrides` | `2026_11_26_000001_create_tenant_category_overrides_tables.php` | — |
| `tenant_product_overrides` | `2026_11_27_000001_create_tenant_product_overrides_tables.php` | — |
| `tenant_product_variant_overrides` | `2026_11_27_000001_create_tenant_product_overrides_tables.php` | — |

### tenant.`sessions`

Columns: `id` string; `user_id` foreignId?; `ip_address` string?; `user_agent` text?; `payload` longText; `last_activity` integer

### tenant.`cache`

Columns: `key` string; `value` mediumText; `expiration` bigInteger

### tenant.`cache_locks`

Columns: `key` string; `owner` string; `expiration` bigInteger

### tenant.`jobs`

Columns: `id` bigint PK; `queue` string; `payload` longText; `attempts` unsignedTinyInteger; `reserved_at` unsignedInteger?; `available_at` unsignedInteger; `created_at` unsignedInteger

### tenant.`job_batches`

Columns: `id` string; `name` string; `total_jobs` integer; `pending_jobs` integer; `failed_jobs` integer; `failed_job_ids` longText; `options` mediumText?; `cancelled_at` integer?; `created_at` integer; `finished_at` integer?

### tenant.`failed_jobs`

Columns: `id` bigint PK; `uuid` string UNI; `connection` text; `queue` text; `payload` longText; `exception` longText; `failed_at` timestamp

### tenant.`languages`

Model `App\Models\Tenant\Language` — traits: HasFactory — relations: imageFile:belongsTo(TenantFile)

Columns: `id` bigint PK; `central_language_id` unsignedBigInteger?; `code` string UNI; `name` string; `native_name` string?; `direction` string; `is_active` boolean; `is_default` boolean; `created_at` timestamp?; `updated_at` timestamp?; `image_file_id` unsignedBigInteger?; `sort_order` unsignedInteger; `translation_progress` unsignedTinyInteger; `translation_status` string?; `translation_summary` text?; `last_translation_token_count` unsignedInteger; `last_translation_cost_usd` decimal

Altered by: `2026_04_04_000102_add_image_file_id_to_tenant_languages_table.php`, `2026_05_12_000001_drop_sort_order_from_tenant_languages_table.php`, `2026_08_14_000005_add_sort_order_to_tenant_languages_table.php`, `2026_08_14_170005_add_translation_progress_to_languages_table.php`, `2026_08_26_000001_add_translation_token_tracking_to_languages_table.php`

### tenant.`translations`

Model `App\Models\Tenant\Translation` — traits: HasFactory — relations: translatable:morphTo

Columns: `id` bigint PK; `language_id` foreignId; `translatable_type` string; `translatable_id` bigint; `field` string; `value` longText; `created_at` timestamp?; `updated_at` timestamp?

FKs: language_id → languages.id (cascade)

Indexes: unique(language_id,translatable_type,translatable_id,field)

### tenant.`files`

Model `App\Models\Tenant\File` — relations: model:morphTo

Columns: `id` bigint PK; `key` string?; `path` string; `storage_type` string; `file_type` string; `mime_type` string?; `extension` string?; `size` unsignedBigInteger?; `model_type` string; `model_id` bigint; `created_at` timestamp?; `updated_at` timestamp?; `sort_order` unsignedInteger

Altered by: `2026_08_15_000002_add_sort_order_to_files_table.php`

### tenant.`categories`

Model `App\Models\Tenant\Category` — traits: HasFactory, HasTranslations — relations: parent:belongsTo(self), children:hasMany(self), products:belongsToMany(Product), files:morphMany(File), centralCategory:belongsTo(Category), translations:morphMany

Model `App\Models\Tenant\Catalog\MergedCategory` — traits: HasFactory, HasTranslations — relations: translations:morphMany(MergedCategoryTranslation), files:hasMany(File)

Columns: `id` bigint PK; `central_category_id` unsignedBigInteger?; `parent_id` foreignId?; `order_number` unsignedInteger; `active` boolean; `featured` boolean; `created_at` timestamp?; `updated_at` timestamp?; `central_visible` boolean; `parent_type` string?; `catalog_parent_id` unsignedBigInteger?

FKs: parent_id → categories.id

Indexes: unique(central_category_id,slug); index(parent_type,catalog_parent_id)

Altered by: `2026_05_11_000001_drop_slug_from_tenant_categories_table.php`, `2026_11_21_000004_add_central_visible_to_categories_table.php`, `2026_11_26_000001_create_tenant_category_overrides_tables.php`

### tenant.`products`

Model `App\Models\Tenant\Product` — traits: HasFactory, HasTranslations — relations: categories:belongsToMany(Category), flashSales:hasMany(MergedProductFlashSale), variants:hasMany(ProductVariant), files:morphMany(File), badges:hasMany(MergedProductBadge), labels:hasMany(MergedProductLabel), rates:hasMany(ProductRate), centralProduct:belongsTo(Product), translations:morphMany

Model `App\Models\Tenant\Catalog\MergedProduct` — traits: HasFactory, HasTranslations — relations: variants:hasMany(MergedProductVariant), rates:hasMany(MergedProductRate), files:morphMany(File)

Columns: `id` bigint PK; `central_product_id` unsignedBigInteger?; `slug` string?; `price` decimal; `active` boolean; `featured` boolean; `created_at` timestamp?; `updated_at` timestamp?; `is_own_product` boolean; `requires_shipping` boolean; `stock` integer?; `ai_price_data` json?; `fixed_shipping_costs` json?; `is_tenant_owned` boolean; `social_posts` json?; `social_image_b64` longText?; `sku` string?; `sale_price` decimal?; `cost_price` decimal?; `min_stock` integer; `manage_stock` boolean; `is_taxable` boolean; `weight_grams` integer?; `profit` json?; `central_visible` boolean; `orders_count` unsignedInteger; `order_number` unsignedInteger; `return_policy_override` boolean; `is_returnable` boolean; `return_window_days` unsignedSmallInteger?; `return_fee` decimal?; `return_video_required` boolean; `return_conditions` text?; `has_custom_translations` boolean; `allowed_country_ids` json?; `needs_ai_translation` boolean; `ai_translated_at` timestamp?

FKs: badge_id → product_badges.id

Altered by: `2026_04_07_000210_create_product_badges_table.php`, `2026_04_08_000221_convert_product_badges_to_pivot.php`, `2026_04_20_000003_add_own_product_fields_to_products_table.php`, `2026_04_25_000001_add_ai_price_data_to_products_table.php`, `2026_05_18_100001_add_fixed_shipping_costs_to_products_table.php`, `2026_05_20_000001_add_is_tenant_owned_to_products_table.php`, `2026_06_01_000001_add_social_posts_to_products_table.php`, `2026_06_09_000001_add_admin_fields_to_own_products_table.php`, `2026_06_15_000001_add_profit_to_products_and_variants.php`, `2026_07_21_000001_add_central_visible_to_products_table.php`, `2026_08_14_000003_add_orders_count_to_products_table.php`, `2026_08_22_202113_add_order_number_to_tenant_products_table.php`, `2026_08_23_000001_add_return_policy_to_products_table.php`, `2026_08_23_000004_add_has_custom_translations_to_products_table.php`, `2026_08_27_000001_add_country_ids_to_tenant_products.php`, `2026_08_27_000002_add_needs_ai_translation_to_products.php`

### tenant.`category_product`

Columns: `category_id` foreignId; `product_id` foreignId; `created_at` timestamp?; `updated_at` timestamp?; `sort_order` unsignedInteger; `source` string

FKs: category_id → categorys.id (cascade); product_id → products.id (cascade)

Indexes: primary(category_id,product_id)

Altered by: `2026_08_14_000001_add_sort_order_to_category_product_table.php`, `2026_11_21_000005_add_source_to_category_product_table.php`

### tenant.`product_variants`

Model `App\Models\Tenant\ProductVariant` — traits: HasFactory, HasTranslations — relations: product:belongsTo(Product), centralVariant:belongsTo(ProductVariant), translations:morphMany

Model `App\Models\Tenant\Catalog\MergedProductVariant` — traits: HasFactory, HasTranslations — relations: product:belongsTo(MergedProduct)

Columns: `id` bigint PK; `product_id` foreignId; `central_product_variant_id` unsignedBigInteger?; `option_ids` json?; `real_price` decimal; `sell_price` decimal; `stock` integer?; `margin_percentage` decimal; `thumbnail_path` string?; `active` boolean; `created_at` timestamp?; `updated_at` timestamp?; `title` string?; `sku` string?; `weight_grams` integer?; `profit` json?

FKs: product_id → products.id (cascade)

Altered by: `2026_05_12_110001_add_stock_and_options_to_product_variants.php`, `2026_06_09_000001_add_admin_fields_to_own_products_table.php`, `2026_06_15_000001_add_profit_to_products_and_variants.php`

### tenant.`customers`

Model `App\Models\Tenant\Customer` — traits: HasFactory — relations: orders:hasMany(Order), addresses:hasMany(CustomerAddress)

Columns: `id` bigint PK; `full_name` string; `email` string UNI; `phone` string?; `address` string?; `city_id` unsignedBigInteger?; `password` string; `active` boolean; `remember_token` string?; `created_at` timestamp?; `updated_at` timestamp?; `country_id` unsignedBigInteger?; `language` string?; `provider` string?; `provider_id` string?; `avatar` string?

Altered by: `2026_06_09_000002_add_country_id_to_customers_table.php`, `2026_06_24_000001_add_language_to_customers_table.php`, `2026_08_15_000001_add_social_login_fields_to_customers_table.php`

### tenant.`orders`

Model `App\Models\Tenant\Order` — traits: HasFactory — relations: customer:belongsTo(Customer), coupon:belongsTo(Coupon), paymentGateway:belongsTo(PaymentGateway), items:hasMany(OrderItem), activities:hasMany(OrderActivity)

Columns: `id` bigint PK; `uuid` string UNI; `customer_id` foreignId?; `shipping_address` json?; `payment_method` string?; `status` string; `paid` boolean; `payment_details` json?; `created_at` timestamp?; `updated_at` timestamp?; `discount_id` unsignedBigInteger?; `discount_percentage` decimal; `tax_percentage` decimal; `shipping_zone_rate_id` unsignedBigInteger?; `shipping_charge` decimal; `payment_gateway_id` unsignedBigInteger?; `vendor_gateway_id` unsignedBigInteger?; `vendor_gateway_fee` decimal; `vendor_settled_at` timestamp?; `vendor_settlement_ref` string?; `tracking_number` string?; `order_group_uuid` string?; `payout_released` boolean; `cancelled_at` timestamp?; `cancellation_reason` string?; `cancellation_note` text?; `cancelled_by_type` string?; `cancelled_by_id` unsignedBigInteger?; `stock_deducted_at` timestamp?; `stock_restored_at` timestamp?; `refunded_amount` decimal; `refunded_at` timestamp?; `platform_profit` decimal; `vendor_profit` decimal; `coupon_discount` decimal; `coupon_source` string?; `collected_by` string

FKs: customer_id → customers.id

Indexes: index(discount_id); index(payment_gateway_id); index(shipping_zone_rate_id); index(vendor_gateway_id); index(vendor_settled_at); index(cancelled_at)

Altered by: `2026_04_06_000204_add_financial_columns_to_orders_table.php`, `2026_04_25_000002_add_vendor_purchase_to_orders.php`, `2026_05_12_100001_add_tracking_number_to_orders_table.php`, `2026_06_17_000001_add_order_group_uuid_to_orders_table.php`, `2026_06_20_000001_add_payout_released_to_orders_table.php`, `2026_10_08_000001_add_cancellation_and_refund_columns_to_orders_table.php`, `2026_10_19_000001_refactor_order_financial_columns.php`, `2026_10_19_000001_refactor_order_financial_columns.php`

### tenant.`order_items`

Model `App\Models\Tenant\OrderItem` — traits: HasCatalogRefs, HasFactory — relations: order:belongsTo(Order), variant:belongsTo(MergedProductVariant), product:belongsTo(MergedProduct)

Columns: `id` bigint PK; `order_id` foreignId; `product_variant_id` foreignId; `qty` unsignedInteger; `price` decimal; `sub_total` decimal; `discount` decimal; `tax` decimal; `weight` decimal; `shipping_fee` decimal; `created_at` timestamp?; `updated_at` timestamp?; `product_id` unsignedBigInteger?; `cost` decimal; `profit_amount` decimal; `fixed_shipping` decimal; `discount_source` string?; `grand_total` decimal

FKs: order_id → orders.id (cascade); product_variant_id → product_variants.id (cascade); product_variant_id → product_variants.id

Altered by: `2026_04_08_000207_add_product_id_and_nullable_variant_to_order_items_table.php`, `2026_10_19_000001_refactor_order_financial_columns.php`, `2026_11_21_000001_order_items_keep_lines_when_variant_deleted.php`

### tenant.`order_activities`

Model `App\Models\Tenant\OrderActivity` — traits: HasFactory — relations: order:belongsTo(Order)

Columns: `id` bigint PK; `order_id` foreignId; `title` string; `description` text?; `created_at` timestamp?; `updated_at` timestamp?; `status` string?

FKs: order_id → orders.id (cascade)

Altered by: `2026_06_20_000003_add_status_to_order_activities_table.php`

### tenant.`transactions`

Model `App\Models\Tenant\Transaction` — traits: HasFactory — relations: model:morphTo

Columns: `id` bigint PK; `uuid` string UNI; `amount` decimal; `type` string; `model_type` string; `model_id` bigint; `admin_profit` decimal?; `description` text?; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`subscriptions`

Model `App\Models\Tenant\Subscription` — traits: HasFactory — relations: transaction:belongsTo(Transaction)

Columns: `id` bigint PK; `package_id` unsignedBigInteger?; `price` decimal; `status` string; `term` string; `payment_gateway_type` string; `payment_gateway_id` unsignedBigInteger?; `transaction_id` foreignId?; `start_date` timestamp?; `end_date` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

FKs: transaction_id → transactions.id

### tenant.`themes`

Model `App\Models\Tenant\Theme` — traits: HasFactory — relations: countries:hasMany(ThemeCountry)

Columns: `id` bigint PK; `central_theme_id` unsignedBigInteger?; `name` string?; `slug` string?; `is_active` boolean; `preview_path` string?; `created_at` timestamp?; `updated_at` timestamp?; `is_universal` boolean

Indexes: index(is_universal)

Altered by: `2026_04_22_000002_add_is_universal_to_themes_table.php`

### tenant.`settings`

Model `App\Models\Tenant\Setting` — traits: HasFactory, HasTranslations — relations: translations:morphMany

Columns: `id` bigint PK; `name` string UNI; `value` string?; `type` string; `options` json?; `group` string; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`pages`

Model `App\Models\Tenant\Page` — traits: HasFactory, HasTranslations — relations: translations:morphMany

Columns: `id` bigint PK; `slug` string UNI; `active` boolean; `created_at` timestamp?; `updated_at` timestamp?; `source` string

Altered by: `2026_11_21_000002_add_source_to_pages_table.php`

### tenant.`coupons`

Model `App\Models\Tenant\Coupon` — traits: HasFactory, HasTranslations — relations: translations:morphMany

Columns: `id` bigint PK; `code` string UNI; `type` string; `value` decimal; `start_date` timestamp?; `end_date` timestamp?; `minimum_spend` decimal; `created_at` timestamp?; `updated_at` timestamp?; `central_coupon_id` unsignedBigInteger?; `country_id` unsignedBigInteger?

Altered by: `2026_04_25_000002_add_central_coupon_id_to_coupons_table.php`, `2026_08_28_000001_add_allowed_country_ids_to_coupons_table.php`, `2026_09_06_000001_add_country_id_to_coupons_table.php`, `2026_09_06_000002_drop_allowed_country_ids_from_coupons_table.php`

### tenant.`flash_sales`

Model `App\Models\Tenant\FlashSale` — traits: HasCatalogRefs, HasFactory — relations: product:belongsTo(Product), products:belongsToMany(Product), files:morphMany(File)

Columns: `id` bigint PK; `product_id` foreignId; `discount_percentage` decimal; `start_date` timestamp?; `end_date` timestamp?; `active` boolean; `created_at` timestamp?; `updated_at` timestamp?; `banner_image` string?; `central_flash_sale_id` unsignedBigInteger?; `country_id` unsignedBigInteger?

FKs: product_id → products.id (cascade)

Altered by: `2026_04_11_000101_add_banner_image_to_flash_sales_table.php`, `2026_04_21_000001_add_central_flash_sale_id_to_flash_sales.php`, `2026_08_23_000003_add_country_id_to_flash_sales_table.php`

### tenant.`subscribers`

Model `App\Models\Tenant\Subscriber` — traits: HasFactory

Columns: `id` bigint PK; `email` string UNI; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`currencies`

Model `App\Models\Tenant\Currency` — traits: HasFactory

Columns: `id` bigint PK; `central_currency_id` unsignedBigInteger?; `code` string UNI; `name` string; `symbol` string?; `conversion_rate` decimal; `is_active` boolean; `is_default` boolean; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`payment_gateways`

Model `App\Models\Tenant\PaymentGateway` — traits: HasFactory — relations: centralPaymentGateway:belongsTo(CentralPaymentGateway)

Columns: `id` bigint PK; `central_payment_gateway_id` unsignedBigInteger?; `name` string; `code` string UNI; `is_active` boolean; `mode` string; `required_keys` json?; `required_values` text?; `created_at` timestamp?; `updated_at` timestamp?; `hide` boolean; `use_own` boolean; `type` string; `connection_status` string?; `is_primary` boolean; `webhook_url` string?; `webhook_status` string?; `last_synced_at` timestamp?; `last_transaction_at` timestamp?; `last_error` text?

Indexes: unique(code,type)

Altered by: `2026_04_08_000001_add_hide_to_tenant_payment_gateways_table.php`, `2026_04_08_000206_add_use_own_to_tenant_payment_gateways_table.php`, `2026_05_18_200001_add_type_to_tenant_payment_gateways_table.php`, `2026_05_18_200001_add_type_to_tenant_payment_gateways_table.php`, `2026_06_20_000002_add_connection_to_tenant_payment_gateways.php`, `2026_08_15_000003_add_monitoring_columns_to_payment_gateways_table.php`, `2026_08_15_000004_encrypt_tenant_payment_gateway_credentials.php`

### tenant.`email_templates`

Model `App\Models\Tenant\EmailTemplate` — traits: HasFactory — relations: translations:hasMany(EmailTemplateTranslation)

Columns: `id` bigint PK; `name` string; `subject` string; `body` longText?; `is_active` boolean; `created_at` timestamp?; `updated_at` timestamp?; `central_email_template_id` unsignedBigInteger?; `action` string?

Indexes: index(central_email_template_id); index(action)

Altered by: `2026_04_10_000300_add_central_reference_to_email_templates_table.php`, `2026_04_10_001000_add_action_to_email_templates_table.php`

### tenant.`admin_roles`

Model `App\Models\Tenant\AdminRole` — traits: HasFactory — relations: admins:hasMany(AdminUser)

Columns: `id` bigint PK; `name` string UNI; `permissions` json?; `permissions_count` unsignedInteger; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`admins`

Model `App\Models\Tenant\AdminUser` — traits: HasFactory — relations: role:belongsTo(AdminRole), notifications:morphMany(DatabaseNotification)

Columns: `id` bigint PK; `role_id` foreignId?; `name` string; `email` string UNI; `password` string; `status` string; `last_login_at` timestamp?; `remember_token` string?; `created_at` timestamp?; `updated_at` timestamp?; `tour_seen_at` timestamp?; `setup_dismissed_at` timestamp?; `email_verified_at` timestamp?; `payment_readiness_skipped_at` timestamp?

FKs: role_id → admin_roles.id

Altered by: `2026_04_21_100001_add_onboarding_columns_to_admins_table.php`, `2026_08_15_000001_add_email_verified_at_to_admins_table.php`, `2026_08_24_000001_add_payment_readiness_skipped_to_admins.php`

### tenant.`flash_sale_product`

Columns: `flash_sale_id` foreignId; `product_id` foreignId; `created_at` timestamp?; `updated_at` timestamp?

FKs: flash_sale_id → flash_sales.id (cascade); product_id → products.id (cascade)

Indexes: primary(flash_sale_id,product_id)

### tenant.`banners`

Model `App\Models\Tenant\Banner` — traits: HasTranslations — relations: translations:morphMany

Columns: `id` bigint PK; `url` string?; `image_path` string?; `serial_number` unsignedInteger; `created_at` timestamp?; `updated_at` timestamp?; `country_id` unsignedBigInteger?

Altered by: `2026_08_14_000009_add_country_id_to_banners_table.php`

### tenant.`social_links`

Model `App\Models\Tenant\SocialLink`

Columns: `id` bigint PK; `icon` string; `url` string; `serial_number` unsignedInteger; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`product_badges`

Model `App\Models\Tenant\ProductBadge` — relations: products:belongsToMany(Product)

Model `App\Models\Tenant\Catalog\CentralProductBadge`

Columns: `id` bigint PK; `text` string; `active` boolean; `created_at` timestamp?; `updated_at` timestamp?; `central_product_badge_id` unsignedBigInteger?

Indexes: unique(text)

Altered by: `2026_04_08_000221_convert_product_badges_to_pivot.php`, `2026_11_21_000003_add_central_product_badge_id_to_product_badges_table.php`

### tenant.`product_rates`

Model `App\Models\Tenant\ProductRate` — traits: HasCatalogRefs — relations: product:belongsTo(MergedProduct), customer:belongsTo(Customer)

Model `App\Models\Tenant\Catalog\MergedProductRate` — traits: HasCatalogRefs

Columns: `id` bigint PK; `product_id` foreignId; `customer_id` foreignId?; `stars` unsignedTinyInteger; `comment` text?; `created_at` timestamp?; `updated_at` timestamp?

FKs: product_id → products.id (cascade); customer_id → customers.id

### tenant.`customer_addresses`

Model `App\Models\Tenant\CustomerAddress` — traits: HasFactory — relations: customer:belongsTo(Customer)

Columns: `id` bigint PK; `customer_id` foreignId; `label` string?; `full_name` string; `email` string?; `phone` string?; `address_line_1` string; `city` string?; `state` string?; `country` string?; `is_default` boolean; `created_at` timestamp?; `updated_at` timestamp?; `country_id` unsignedBigInteger?

FKs: customer_id → customers.id (cascade)

Altered by: `2026_04_20_000001_add_country_id_to_customer_addresses_table.php`

### tenant.`product_badge_product`

Columns: `product_badge_id` unsignedBigInteger?; `product_id` foreignId; `created_at` timestamp?; `updated_at` timestamp?; `sort_order` unsignedInteger; `country_id` unsignedBigInteger?; `id` bigint PK

FKs: product_badge_id → product_badges.id (cascade); product_id → products.id (cascade)

Indexes: primary(product_badge_id,product_id); index(product_badge_id,pbp_badge_id_index); index(product_id,pbp_product_id_index); unique(product_badge_id,product_id,country_id)

Altered by: `2026_08_22_202334_add_sort_order_to_tenant_product_badge_product_table.php`, `2026_08_23_000002_add_country_id_to_product_badge_product_table.php`, `2026_08_23_000002_add_country_id_to_product_badge_product_table.php`, `2026_08_23_000002_add_country_id_to_product_badge_product_table.php`, `2026_08_23_000002_add_country_id_to_product_badge_product_table.php`, `2026_11_24_000001_create_tenant_badge_product_overrides_table.php`

### tenant.`language_purchases`

Model `App\Models\Tenant\LanguagePurchase` — relations: transaction:belongsTo(Transaction)

Columns: `id` bigint PK; `central_language_id` unsignedBigInteger; `language_code` string; `language_name` string; `amount` decimal; `gateway_code` string?; `transaction_uuid` string?; `transaction_id` foreignId?; `created_at` timestamp?; `updated_at` timestamp?

FKs: transaction_id → transactions.id

Indexes: unique(central_language_id)

### tenant.`tenant_notifications`

Model `App\Models\Tenant\TenantNotification`

Columns: `id` bigint PK; `type` string; `title` string; `message` text; `data` json?; `is_read` boolean; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`theme_country`

Model `App\Models\Tenant\ThemeCountry` — traits: HasFactory — relations: theme:belongsTo(Theme)

Columns: `id` bigint PK; `theme_id` foreignId; `country_id` unsignedBigInteger; `iso2` string?; `name` string?; `flag_emoji` string?; `is_enabled` boolean; `created_at` timestamp?; `updated_at` timestamp?

FKs: theme_id → themes.id (cascade)

Indexes: unique(theme_id,country_id); index(country_id)

### tenant.`maintenance_windows`

Columns: `id` bigint PK; `message` text?; `is_active` boolean; `starts_at` timestamp?; `ends_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`email_template_translations`

Model `App\Models\Tenant\EmailTemplateTranslation` — relations: emailTemplate:belongsTo(EmailTemplate)

Columns: `id` bigint PK; `email_template_id` unsignedBigInteger; `locale` string; `subject` string; `body` longText?; `created_at` timestamp?; `updated_at` timestamp?

FKs: email_template_id → email_templates.id

Indexes: unique(email_template_id,locale)

### tenant.`tenant_page_sections`

Model `App\Models\Tenant\TenantPageSection` — traits: HasFactory — relations: theme:belongsTo(Theme)

Columns: `id` bigint PK; `theme_id` foreignId; `page` string; `section_key` string; `sort_order` unsignedInteger; `is_visible` boolean; `created_at` timestamp?; `updated_at` timestamp?; `home_variant_id` unsignedBigInteger?

FKs: theme_id → themes.id (cascade)

Indexes: unique(theme_id,page,section_key); index(theme_id,tenant_page_sections_theme_id_fk_index); unique(theme_id,home_variant_id,page,section_key)

Altered by: `2026_09_07_000000_add_home_variant_id_to_tenant_page_sections_table.php`

### tenant.`tenant_home_variants`

Model `App\Models\Tenant\TenantHomeVariant` — traits: HasFactory — relations: theme:belongsTo(Theme)

Columns: `id` bigint PK; `theme_id` foreignId; `country_id` unsignedBigInteger?; `home_variant_id` unsignedBigInteger; `created_at` timestamp?; `updated_at` timestamp?

FKs: theme_id → themes.id (cascade)

Indexes: unique(theme_id,country_id)

### tenant.`translation_overrides`

Model `App\Models\Tenant\TranslationOverride` — relations: language:belongsTo(Language)

Columns: `id` bigint PK; `language_id` foreignId; `key` text; `value` text; `created_at` timestamp?; `updated_at` timestamp?; `key_hash` char

FKs: language_id → languages.id (cascade)

Indexes: unique(language_id,key); index(language_id,translation_overrides_language_id_index); unique(language_id,key_hash)

Altered by: `2026_09_03_000002_widen_translation_overrides_key_column.php`, `2026_09_03_000002_widen_translation_overrides_key_column.php`, `2026_09_03_000002_widen_translation_overrides_key_column.php`

### tenant.`favorites`

Model `App\Models\Tenant\Favorite` — traits: HasCatalogRefs — relations: customer:belongsTo(Customer), product:belongsTo(MergedProduct)

Columns: `id` bigint PK; `customer_id` foreignId; `product_id` foreignId; `created_at` timestamp?; `updated_at` timestamp?

FKs: customer_id → customers.id (cascade); product_id → products.id (cascade)

Indexes: unique(customer_id,product_id)

### tenant.`tenant_theme_colors`

Model `App\Models\Tenant\TenantThemeColor` — traits: HasFactory — relations: theme:belongsTo(Theme)

Columns: `id` bigint PK; `theme_id` foreignId; `home_variant_id` unsignedBigInteger?; `country_id` unsignedBigInteger?; `colors` json; `created_at` timestamp?; `updated_at` timestamp?; `fonts` json?

FKs: theme_id → themes.id (cascade)

Indexes: unique(theme_id,home_variant_id,country_id)

Altered by: `2026_11_03_000002_add_fonts_to_tenant_theme_colors_table.php`

### tenant.`password_reset_tokens`

Columns: `email` string; `token` string; `created_at` timestamp?

### tenant.`mobile_access_tokens`

Model `App\Models\Tenant\MobileAccessToken` — relations: customer:belongsTo(Customer)

Columns: `id` bigint PK; `tenant_id` string; `customer_id` unsignedBigInteger; `device_name` string?; `token_hash` string UNI; `refresh_hash` string UNI; `last_used_at` timestamp?; `expires_at` timestamp?; `revoked_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`mobile_app_settings`

Model `App\Models\Tenant\MobileAppSetting`

Columns: `id` bigint PK; `key` string UNI; `value` json?; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`mobile_app_theme`

Model `App\Models\Tenant\MobileAppTheme`

Columns: `id` bigint PK; `theme_slug` string; `variant_key` string; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`mobile_carts`

Model `App\Models\Tenant\MobileCart`

Columns: `id` bigint PK; `customer_id` unsignedBigInteger UNI; `items` json?; `coupon_code` string?; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`mobile_device_tokens`

Model `App\Models\Tenant\MobileDeviceToken`

Columns: `id` bigint PK; `customer_id` unsignedBigInteger?; `token` string UNI; `platform` string?; `app_version` string?; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`product_labels`

Model `App\Models\Tenant\ProductLabel` — relations: products:belongsToMany(Product)

Columns: `id` bigint PK; `central_product_label_id` unsignedBigInteger UNI; `text` string; `icon` string?; `active` boolean; `sort_order` unsignedInteger; `created_at` timestamp?; `updated_at` timestamp?; `translations` json?

Altered by: `2026_11_03_000001_add_translations_to_product_labels.php`

### tenant.`product_label_product`

Columns: `product_label_id` foreignId; `product_id` foreignId; `created_at` timestamp?; `updated_at` timestamp?

FKs: product_label_id → product_labels.id (cascade); product_id → products.id (cascade)

Indexes: primary(product_label_id,product_id); index(product_id)

### tenant.`tenant_badge_product_overrides`

Columns: `id` bigint PK; `badge_type` string; `badge_id` unsignedBigInteger; `product_type` string; `product_id` unsignedBigInteger; `country_id` unsignedBigInteger?; `country_key` unsignedBigInteger; `sort_order` unsignedInteger?; `hidden` boolean; `created_at` timestamp?; `updated_at` timestamp?

Indexes: unique(badge_type,badge_id,product_type,product_id,country_key); index(product_type,product_id)

### tenant.`tenant_field_overrides`

Columns: `id` bigint PK; `entity_type` string; `entity_id` unsignedBigInteger; `field` string; `locale` string?; `locale_key` string; `value` longText?; `central_value_at_approval` longText?; `edit_request_id` unsignedBigInteger?; `approved_at` timestamp; `approved_by` unsignedBigInteger?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: unique(entity_type,entity_id,field,locale_key); index(edit_request_id,tfo_edit_request_index)

### tenant.`tenant_category_overrides`

Columns: `central_category_id` unsignedBigInteger; `active` boolean?; `featured` boolean?; `order_number` unsignedInteger?; `thumb_file_id` unsignedBigInteger?; `created_at` timestamp?; `updated_at` timestamp?

### tenant.`tenant_category_product_overrides`

Columns: `id` bigint PK; `category_type` string; `category_id` unsignedBigInteger; `product_type` string; `product_id` unsignedBigInteger; `source` string; `sort_order` unsignedInteger?; `hidden` boolean; `created_at` timestamp?; `updated_at` timestamp?

Indexes: unique(category_type,category_id,product_type,product_id); index(product_type,product_id)

### tenant.`tenant_product_overrides`

Columns: `central_product_id` unsignedBigInteger; `active` boolean?; `featured` boolean?; `order_number` unsignedInteger?; `profit` json?; `price_override` decimal?; `stock_override` integer?; `weight_grams_override` unsignedInteger?; `fixed_shipping_costs_override` json?; `allowed_country_ids` json?; `return_policy_override` string?; `is_returnable` boolean?; `return_window_days` unsignedInteger?; `return_fee` decimal?; `return_video_required` boolean?; `return_conditions` text?; `adopted_at` timestamp?; `created_at` timestamp?; `updated_at` timestamp?; `social_posts` json?; `social_image_b64` longText?; `ai_price_data` json?

Indexes: index(active,featured); index(order_number,tpo_order_index)

Altered by: `2026_11_27_000005_add_social_and_ai_price_to_product_overrides.php`

### tenant.`tenant_product_variant_overrides`

Columns: `central_variant_id` unsignedBigInteger; `central_product_id` unsignedBigInteger; `active` boolean?; `profit` json?; `margin_percentage` decimal?; `price_override` decimal?; `stock_override` integer?; `thumbnail_path` string?; `created_at` timestamp?; `updated_at` timestamp?

Indexes: index(central_product_id,tpvo_product_index)
