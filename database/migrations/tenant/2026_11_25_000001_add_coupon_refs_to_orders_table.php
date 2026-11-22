<?php

use App\Support\Catalog\CatalogRefColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 3 (coupons, invariant 3 + 4): an order's coupon is identified without the tenant `coupons` copy.
 *
 *   orders.coupon_code        VARCHAR(255) NULL   snapshot of the code at checkout (central coupons have no tenant row)
 *   orders.coupon_type        VARCHAR(10)  NULL   'central' | 'tenant'
 *   orders.catalog_coupon_id  BIGINT UNSIGNED NULL central id when central, tenant id when tenant
 *   index orders_coupon_ref_index (coupon_type, catalog_coupon_id)   per-tenant usage counts (D4)
 *
 * `discount_id` stays (tenant coupon id; NULL for a central coupon once the tenant reads coupons from central).
 * Written by OrderComposer::create(); old rows by `php artisan catalog:dedup-migrate coupons`. Identity only: no money
 * column changes. Nullable + appended = INSTANT on MySQL 8.0.29+, index INPLACE. Idempotent; down() drops only these.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'coupon_code')) {
            Schema::table('orders', fn (Blueprint $t) => $t->string('coupon_code')->nullable());
        }
        if (! Schema::hasColumn('orders', 'coupon_type')) {
            Schema::table('orders', fn (Blueprint $t) => $t->string('coupon_type', 10)->nullable());
        }
        if (! Schema::hasColumn('orders', 'catalog_coupon_id')) {
            Schema::table('orders', fn (Blueprint $t) => $t->unsignedBigInteger('catalog_coupon_id')->nullable());
        }
        if (! Schema::hasIndex('orders', 'orders_coupon_ref_index')) {
            Schema::table('orders', fn (Blueprint $t) => $t->index(['coupon_type', 'catalog_coupon_id'], 'orders_coupon_ref_index'));
        }

        CatalogRefColumns::flush();
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasIndex('orders', 'orders_coupon_ref_index')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropIndex('orders_coupon_ref_index'));
        }
        foreach (['catalog_coupon_id', 'coupon_type', 'coupon_code'] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                Schema::table('orders', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }

        CatalogRefColumns::flush();
    }
};
