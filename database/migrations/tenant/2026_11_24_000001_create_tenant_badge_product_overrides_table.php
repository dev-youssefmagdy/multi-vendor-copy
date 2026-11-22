<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 2 (§4 badges row): what a tenant owns about a CENTRAL badge↔product pair once its `badges` section
 * reads central live (App\Repositories\Tenant\Catalog\BadgeCatalog):
 *
 *  - `tenant_badge_product_overrides` (badge_type, badge_id, product_type, product_id, country_id, sort_order, hidden):
 *    per-country sort override of a central pair (SortBadgeProducts) and `hidden` = the vendor removed that central
 *    pair from the badge (without it a central pair could not be detached any more). Typed ids = central ids for
 *    central entities (§3.1). `country_key` = IFNULL(country_id, 0) makes the natural key unique (NULL = "default /
 *    all countries"; a plain UNIQUE treats NULLs as distinct).
 *  - `product_badge_product.product_badge_id` becomes NULLable: a tenant attach of a product to a CENTRAL badge is a
 *    tenant pivot row with `badge_type = 'central'`, `catalog_badge_id = <central id>` and no tenant badge row (the
 *    FK and its ON DELETE CASCADE stay for rows that do point at a tenant badge).
 *
 * Nothing reads or writes these until a tenant's `catalog_source.badges` is `central`. Idempotent; tiny tables
 * (ALTER … NULL is an in-place rebuild of `product_badge_product`, ≤ a few hundred rows live).
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tenant_badge_product_overrides')) {
            Schema::create('tenant_badge_product_overrides', function (Blueprint $table) {
                $table->id();
                $table->string('badge_type', 10);
                $table->unsignedBigInteger('badge_id');
                $table->string('product_type', 10);
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('country_id')->nullable(); // central country id, NULL = default / all countries
                $table->unsignedBigInteger('country_key')->storedAs('IFNULL(`country_id`, 0)');
                $table->unsignedInteger('sort_order')->nullable();    // NULL = inherit central sort_order
                $table->boolean('hidden')->default(false);
                $table->timestamps();

                $table->unique(['badge_type', 'badge_id', 'product_type', 'product_id', 'country_key'], 'tbpo_pair_unique');
                $table->index(['product_type', 'product_id'], 'tbpo_product_index');
            });
        }

        if (Schema::hasTable('product_badge_product') && Schema::hasColumn('product_badge_product', 'product_badge_id')) {
            $nullable = DB::selectOne(
                "SELECT IS_NULLABLE AS n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_badge_product' AND COLUMN_NAME = 'product_badge_id'"
            );

            if ($nullable !== null && $nullable->n !== 'YES') {
                Schema::table('product_badge_product', function (Blueprint $table) {
                    $table->unsignedBigInteger('product_badge_id')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_badge_product_overrides');

        if (! Schema::hasTable('product_badge_product')) {
            return;
        }

        // Central-badge attaches (no tenant badge row) cannot exist under NOT NULL: keep the column nullable if any
        // are left (rolling back the code is enough; they are ignored by the sync-mode readers).
        if (DB::table('product_badge_product')->whereNull('product_badge_id')->exists()) {
            Log::warning('product_badge_product.product_badge_id left NULLable: central-badge attach rows exist.');

            return;
        }

        Schema::table('product_badge_product', function (Blueprint $table) {
            $table->unsignedBigInteger('product_badge_id')->nullable(false)->change();
        });
    }
};
