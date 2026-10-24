/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admin_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions` json DEFAULT NULL,
  `permissions_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `tour_seen_at` timestamp NULL DEFAULT NULL,
  `setup_dismissed_at` timestamp NULL DEFAULT NULL,
  `payment_readiness_skipped_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`),
  KEY `admins_role_id_foreign` (`role_id`),
  CONSTRAINT `admins_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` int unsigned NOT NULL DEFAULT '0',
  `country_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_category_id` bigint unsigned DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `order_number` int unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_parent_id_foreign` (`parent_id`),
  KEY `categories_central_category_id_index` (`central_category_id`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_product` (
  `category_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`category_id`,`product_id`),
  KEY `category_product_product_id_foreign` (`product_id`),
  CONSTRAINT `category_product_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `category_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_coupon_id` bigint unsigned DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `minimum_spend` decimal(10,2) NOT NULL DEFAULT '0.00',
  `country_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coupons_code_unique` (`code`),
  KEY `coupons_central_coupon_id_index` (`central_coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_currency_id` bigint unsigned DEFAULT NULL,
  `code` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conversion_rate` decimal(20,6) NOT NULL DEFAULT '1.000000',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_code_unique` (`code`),
  KEY `currencies_central_currency_id_index` (`central_currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_addresses_customer_id_foreign` (`customer_id`),
  CONSTRAINT `customer_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `language` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_template_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_template_translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email_template_id` bigint unsigned NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_template_translations_email_template_id_locale_unique` (`email_template_id`,`locale`),
  CONSTRAINT `email_template_translations_email_template_id_foreign` FOREIGN KEY (`email_template_id`) REFERENCES `email_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_email_template_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_templates_central_email_template_id_index` (`central_email_template_id`),
  KEY `email_templates_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favorites_customer_id_product_id_unique` (`customer_id`,`product_id`),
  KEY `favorites_product_id_foreign` (`product_id`),
  CONSTRAINT `favorites_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  `file_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'image',
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned DEFAULT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `files_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `files_key_index` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flash_sale_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flash_sale_product` (
  `flash_sale_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`flash_sale_id`,`product_id`),
  KEY `flash_sale_product_product_id_foreign` (`product_id`),
  CONSTRAINT `flash_sale_product_flash_sale_id_foreign` FOREIGN KEY (`flash_sale_id`) REFERENCES `flash_sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `flash_sale_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `flash_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `flash_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_flash_sale_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `discount_percentage` decimal(6,2) NOT NULL DEFAULT '0.00',
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `banner_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `flash_sales_product_id_foreign` (`product_id`),
  KEY `flash_sales_central_flash_sale_id_index` (`central_flash_sale_id`),
  CONSTRAINT `flash_sales_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `language_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_language_id` bigint unsigned NOT NULL,
  `language_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `gateway_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_purchases_central_language_id_unique` (`central_language_id`),
  KEY `language_purchases_transaction_id_foreign` (`transaction_id`),
  KEY `language_purchases_central_language_id_index` (`central_language_id`),
  CONSTRAINT `language_purchases_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_language_id` bigint unsigned DEFAULT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direction` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ltr',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `translation_progress` tinyint unsigned NOT NULL DEFAULT '0',
  `translation_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `translation_summary` text COLLATE utf8mb4_unicode_ci,
  `last_translation_token_count` int unsigned NOT NULL DEFAULT '0',
  `last_translation_cost_usd` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `image_file_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `languages_code_unique` (`code`),
  KEY `languages_central_language_id_index` (`central_language_id`),
  KEY `languages_image_file_id_index` (`image_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_windows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_windows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_activities_order_id_foreign` (`order_id`),
  CONSTRAINT `order_activities_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_variant_id` bigint unsigned DEFAULT NULL,
  `qty` int unsigned NOT NULL DEFAULT '1',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `profit_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `sub_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fixed_shipping` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_source` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `weight` decimal(10,3) NOT NULL DEFAULT '0.000',
  `grand_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_variant_id_foreign` (`product_variant_id`),
  KEY `order_items_product_id_index` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_group_uuid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `shipping_address` json DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancellation_note` text COLLATE utf8mb4_unicode_ci,
  `cancelled_by_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_by_id` bigint unsigned DEFAULT NULL,
  `stock_deducted_at` timestamp NULL DEFAULT NULL,
  `stock_restored_at` timestamp NULL DEFAULT NULL,
  `refunded_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `refunded_at` timestamp NULL DEFAULT NULL,
  `paid` tinyint(1) NOT NULL DEFAULT '0',
  `payout_released` tinyint(1) NOT NULL DEFAULT '0',
  `payment_details` json DEFAULT NULL,
  `discount_id` bigint unsigned DEFAULT NULL,
  `discount_percentage` decimal(6,2) NOT NULL DEFAULT '0.00',
  `coupon_discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `coupon_source` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_percentage` decimal(6,2) NOT NULL DEFAULT '0.00',
  `shipping_zone_rate_id` bigint unsigned DEFAULT NULL,
  `shipping_charge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `platform_profit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vendor_profit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vendor_gateway_id` bigint unsigned DEFAULT NULL COMMENT 'Central payment gateway selected by vendor to pay central',
  `vendor_gateway_fee` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Gateway transaction fee applied when vendor pays central',
  `vendor_settled_at` timestamp NULL DEFAULT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_settlement_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `collected_by` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'platform',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_uuid_unique` (`uuid`),
  KEY `orders_customer_id_foreign` (`customer_id`),
  KEY `orders_discount_id_index` (`discount_id`),
  KEY `orders_payment_gateway_id_index` (`payment_gateway_id`),
  KEY `orders_shipping_zone_rate_id_index` (`shipping_zone_rate_id`),
  KEY `orders_vendor_gateway_id_index` (`vendor_gateway_id`),
  KEY `orders_vendor_settled_at_index` (`vendor_settled_at`),
  KEY `orders_order_group_uuid_index` (`order_group_uuid`),
  KEY `orders_cancelled_at_index` (`cancelled_at`),
  KEY `orders_created_at_index` (`created_at`),
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_payment_gateway_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'orders' COMMENT 'orders = storefront customer payments | vendor_payments = vendor-to-central payments',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `use_own` tinyint(1) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `hide` tinyint(1) NOT NULL DEFAULT '0',
  `mode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'test',
  `connection_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `last_transaction_at` timestamp NULL DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `required_keys` json DEFAULT NULL,
  `required_values` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_gateways_code_type_unique` (`code`,`type`),
  KEY `payment_gateways_central_payment_gateway_id_index` (`central_payment_gateway_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_badge_product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_badge_product` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_badge_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pbp_unique` (`product_badge_id`,`product_id`,`country_id`),
  KEY `pbp_badge_id_index` (`product_badge_id`),
  KEY `pbp_product_id_index` (`product_id`),
  CONSTRAINT `product_badge_product_product_badge_id_foreign` FOREIGN KEY (`product_badge_id`) REFERENCES `product_badges` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_badge_product_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_badges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_badges_text_unique` (`text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `stars` tinyint unsigned NOT NULL DEFAULT '5',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_rates_product_id_foreign` (`product_id`),
  KEY `product_rates_customer_id_foreign` (`customer_id`),
  CONSTRAINT `product_rates_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_rates_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight_grams` int DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `central_product_variant_id` bigint unsigned DEFAULT NULL,
  `option_ids` json DEFAULT NULL,
  `real_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` int DEFAULT NULL,
  `margin_percentage` decimal(6,2) NOT NULL DEFAULT '0.00',
  `thumbnail_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sell_price` json DEFAULT NULL,
  `profit` json DEFAULT NULL,
  `default_sell_price` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  KEY `product_variants_central_product_variant_id_index` (`central_product_variant_id`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `central_product_id` bigint unsigned DEFAULT NULL,
  `has_custom_translations` tinyint(1) NOT NULL DEFAULT '0',
  `needs_ai_translation` tinyint(1) NOT NULL DEFAULT '0',
  `ai_translated_at` timestamp NULL DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `central_visible` tinyint(1) NOT NULL DEFAULT '1',
  `allowed_country_ids` json DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `order_number` int unsigned NOT NULL DEFAULT '0',
  `return_policy_override` tinyint(1) NOT NULL DEFAULT '0',
  `is_returnable` tinyint(1) NOT NULL DEFAULT '1',
  `return_window_days` smallint unsigned DEFAULT NULL,
  `return_fee` decimal(8,2) DEFAULT NULL,
  `return_video_required` tinyint(1) NOT NULL DEFAULT '0',
  `return_conditions` text COLLATE utf8mb4_unicode_ci,
  `is_own_product` tinyint(1) NOT NULL DEFAULT '0',
  `is_tenant_owned` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True when the product was individually assigned from central to this tenant — central profit is shipping only',
  `requires_shipping` tinyint(1) NOT NULL DEFAULT '1',
  `stock` int DEFAULT NULL,
  `orders_count` int unsigned NOT NULL DEFAULT '0',
  `social_posts` json DEFAULT NULL,
  `social_image_b64` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ai_price_data` json DEFAULT NULL,
  `fixed_shipping_costs` json DEFAULT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `min_stock` int NOT NULL DEFAULT '0',
  `manage_stock` tinyint(1) NOT NULL DEFAULT '1',
  `is_taxable` tinyint(1) NOT NULL DEFAULT '1',
  `weight_grams` int DEFAULT NULL,
  `price` json DEFAULT NULL,
  `profit` json DEFAULT NULL,
  `default_price` decimal(20,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `products_central_product_id_index` (`central_product_id`),
  KEY `products_slug_index` (`slug`),
  KEY `products_active_created_at_index` (`active`,`created_at`),
  KEY `products_active_orders_count_created_at_index` (`active`,`orders_count`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `options` json DEFAULT NULL,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serial_number` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscribers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscribers_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_id` bigint unsigned DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trial',
  `term` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `payment_gateway_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'central',
  `payment_gateway_id` bigint unsigned DEFAULT NULL,
  `transaction_id` bigint unsigned DEFAULT NULL,
  `start_date` timestamp NULL DEFAULT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_transaction_id_foreign` (`transaction_id`),
  KEY `subscriptions_package_id_index` (`package_id`),
  CONSTRAINT `subscriptions_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_home_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_home_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` bigint unsigned NOT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `home_variant_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_home_variants_theme_id_country_id_unique` (`theme_id`,`country_id`),
  CONSTRAINT `tenant_home_variants_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_page_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_page_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` bigint unsigned NOT NULL,
  `home_variant_id` bigint unsigned DEFAULT NULL,
  `page` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_page_sections_variant_unique` (`theme_id`,`home_variant_id`,`page`,`section_key`),
  KEY `tenant_page_sections_theme_id_fk_index` (`theme_id`),
  CONSTRAINT `tenant_page_sections_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_theme_colors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_theme_colors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` bigint unsigned NOT NULL,
  `home_variant_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `colors` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_theme_colors_theme_id_home_variant_id_country_id_unique` (`theme_id`,`home_variant_id`,`country_id`),
  CONSTRAINT `tenant_theme_colors_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `theme_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `theme_country` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `theme_id` bigint unsigned NOT NULL,
  `country_id` bigint unsigned NOT NULL,
  `iso2` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag_emoji` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `theme_country_theme_id_country_id_unique` (`theme_id`,`country_id`),
  KEY `theme_country_country_id_index` (`country_id`),
  CONSTRAINT `theme_country_theme_id_foreign` FOREIGN KEY (`theme_id`) REFERENCES `themes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `themes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `central_theme_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_universal` tinyint(1) NOT NULL DEFAULT '1',
  `preview_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `themes_central_theme_id_index` (`central_theme_id`),
  KEY `themes_is_universal_index` (`is_universal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'credit',
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `admin_profit` decimal(12,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_uuid_unique` (`uuid`),
  KEY `transactions_model_type_model_id_index` (`model_type`,`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `translation_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translation_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_id` bigint unsigned NOT NULL,
  `key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translation_overrides_language_id_key_hash_unique` (`language_id`,`key_hash`),
  KEY `translation_overrides_language_id_index` (`language_id`),
  CONSTRAINT `translation_overrides_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `language_id` bigint unsigned NOT NULL,
  `translatable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translatable_id` bigint unsigned NOT NULL,
  `field` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_translations_unique_field_per_language` (`language_id`,`translatable_type`,`translatable_id`,`field`),
  KEY `translations_translatable_type_translatable_id_index` (`translatable_type`,`translatable_id`),
  CONSTRAINT `translations_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_03_26_000001_create_tenant_panel_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_03_26_000002_create_tenant_admin_auth_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_04_04_000102_add_image_file_id_to_tenant_languages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_04_04_000104_create_flash_sale_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_04_06_000201_create_appearance_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_04_06_000202_add_appearance_permission_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_04_06_000203_seed_appearance_default_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_04_06_000204_add_financial_columns_to_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_04_06_000205_seed_ecommet_theme',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_04_07_000210_create_product_badges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_04_07_000211_create_product_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_04_07_000212_create_customer_addresses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_04_08_000001_add_hide_to_tenant_payment_gateways_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_04_08_000204_drop_appearance_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_04_08_000206_add_use_own_to_tenant_payment_gateways_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_04_08_000207_add_product_id_and_nullable_variant_to_order_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_04_08_000221_convert_product_badges_to_pivot',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_04_10_000300_add_central_reference_to_email_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_04_10_001000_add_action_to_email_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_04_11_000101_add_banner_image_to_flash_sales_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_04_19_000001_create_language_purchases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_04_19_000002_add_language_purchase_permission_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_04_20_000001_add_country_id_to_customer_addresses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_04_20_000002_create_tenant_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_04_20_000003_add_own_product_fields_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_04_21_000001_add_central_flash_sale_id_to_flash_sales',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_04_21_100001_add_onboarding_columns_to_admins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_04_22_000001_create_theme_country_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_04_22_000002_add_is_universal_to_themes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_04_22_000010_create_theme_parts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_04_22_000011_add_image_to_theme_parts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_04_25_000001_add_ai_price_data_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_04_25_000002_add_central_coupon_id_to_coupons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_04_25_000002_add_vendor_purchase_to_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_05_11_000001_drop_slug_from_tenant_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_05_12_000001_drop_sort_order_from_tenant_languages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_05_12_100001_add_tracking_number_to_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_05_12_110001_add_stock_and_options_to_product_variants',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_05_12_120001_create_maintenance_windows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_05_18_100001_add_fixed_shipping_costs_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_05_18_200001_add_type_to_tenant_payment_gateways_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_05_20_000001_add_is_tenant_owned_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_06_01_000001_add_social_posts_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_06_09_000001_add_admin_fields_to_own_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_06_09_000002_add_country_id_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_06_10_000001_convert_product_prices_to_json',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_06_15_000001_add_profit_to_products_and_variants',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_06_17_000001_add_order_group_uuid_to_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_06_20_000001_add_payout_released_to_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_06_20_000002_add_connection_to_tenant_payment_gateways',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_06_20_000003_add_status_to_order_activities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_06_24_000001_add_language_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_06_24_000002_create_email_template_translations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_07_21_000001_add_central_visible_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_08_02_000001_widen_products_decimal_precision',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_08_05_000001_add_domains_permission_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_08_06_000001_seed_logo_appearance_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_08_06_000002_seed_logo_style_settings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_08_14_000001_add_sort_order_to_category_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_08_14_000002_seed_featured_recommended_product_badges',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_08_14_000003_add_orders_count_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_08_14_000004_seed_new_in_best_selling_product_badges',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_08_14_000005_add_sort_order_to_tenant_languages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_08_14_000006_create_tenant_page_sections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_08_14_000007_add_page_builder_permission_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_08_14_000008_drop_theme_parts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_08_14_000009_add_country_id_to_banners_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_08_14_170001_create_tenant_home_variants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_08_14_170003_add_home_variants_permission_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_08_14_170005_add_translation_progress_to_languages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_08_14_170006_create_translation_overrides_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_08_14_180010_add_returns_permission_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_08_15_000001_add_custom_template_and_tracking_permissions_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_08_15_000001_add_email_verified_at_to_admins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_08_15_000001_add_social_login_fields_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_08_15_000002_add_sort_order_to_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_08_15_000003_add_monitoring_columns_to_payment_gateways_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_08_15_000004_encrypt_tenant_payment_gateway_credentials',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_08_17_150227_drop_tenant_theme_colors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_08_21_000002_add_blade_theme_permissions_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_08_21_000002_remove_custom_template_permission_from_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_08_21_000003_create_favorites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_08_22_202113_add_order_number_to_tenant_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_08_22_202334_add_sort_order_to_tenant_product_badge_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_08_23_000001_add_return_policy_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_08_23_000002_add_country_id_to_product_badge_product_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_08_23_000003_add_country_id_to_flash_sales_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_08_23_000004_add_has_custom_translations_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_08_24_000001_add_payment_readiness_skipped_to_admins',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_08_26_000001_add_translation_token_tracking_to_languages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_08_27_000001_add_country_ids_to_tenant_products',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_08_27_000002_add_needs_ai_translation_to_products',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_08_28_000001_add_allowed_country_ids_to_coupons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_09_03_000001_create_tenant_theme_colors_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_09_03_000002_widen_translation_overrides_key_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_09_06_000001_add_country_id_to_coupons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_09_06_000002_drop_allowed_country_ids_from_coupons_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_09_07_000000_add_home_variant_id_to_tenant_page_sections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_09_07_000002_seed_trending_now_product_badge',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_10_08_000001_add_cancellation_and_refund_columns_to_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_10_08_000002_add_orders_manage_permission_to_roles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_10_19_000001_refactor_order_financial_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_10_19_000002_create_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_10_19_000003_add_storefront_listing_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_10_22_000001_add_category_swiper_section_to_elora_home',1);
INSERT INTO `product_badges` (`id`, `text`, `active`, `created_at`, `updated_at`) VALUES (1,'featured',1,'2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `product_badges` (`id`, `text`, `active`, `created_at`, `updated_at`) VALUES (2,'recommended',1,'2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `product_badges` (`id`, `text`, `active`, `created_at`, `updated_at`) VALUES (3,'new-in',1,'2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `product_badges` (`id`, `text`, `active`, `created_at`, `updated_at`) VALUES (4,'best-selling',1,'2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `product_badges` (`id`, `text`, `active`, `created_at`, `updated_at`) VALUES (5,'trending-now',1,'2026-10-24 09:32:48','2026-10-24 09:32:48');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (1,'store_name','My Store','string',NULL,'appearance','2026-10-24 09:32:32','2026-10-24 09:32:32');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (2,'logo_path','','string',NULL,'appearance','2026-10-24 09:32:32','2026-10-24 09:32:32');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (3,'footer_text','','string',NULL,'appearance','2026-10-24 09:32:32','2026-10-24 09:32:32');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (4,'footer_copyright','© 2026 My Store. All rights reserved.','string',NULL,'appearance','2026-10-24 09:32:32','2026-10-24 09:32:32');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (5,'logo_mode','image','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (6,'logo_text_ar','My Store','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (7,'logo_text_en','My Store','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (8,'logo_color','#111827','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (9,'logo_font_ar','cairo','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (10,'logo_font_en','poppins','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (11,'logo_path_ar','','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (12,'logo_path_en','','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (13,'logo_bg_color','#ffffff','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
INSERT INTO `settings` (`id`, `name`, `value`, `type`, `options`, `group`, `created_at`, `updated_at`) VALUES (14,'logo_shape','rectangle','string',NULL,'appearance','2026-10-24 09:32:42','2026-10-24 09:32:42');
