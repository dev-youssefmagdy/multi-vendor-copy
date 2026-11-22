<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 4 (§4 `categories` + `category_product` rows, §4.1): what a tenant owns about CENTRAL categories once
 * its `catalog_source.categories` is `central` (App\Repositories\Tenant\Catalog\CategoryCatalog):
 *
 *  - `tenant_category_overrides` (central_category_id PK, active / featured / order_number NULL = inherit central,
 *    thumb_file_id = tenant `files.id` of the vendor's thumb) — the §4.1 shape.
 *  - `tenant_category_product_overrides` (category_type, category_id, product_type, product_id, source, sort_order,
 *    hidden): per-pair state of a CENTRAL category: `source = central` rows override a central pair (sort_order NULL =
 *    central sort, `hidden` = the vendor removed it), `source = tenant` rows are vendor links central does not have.
 *    The tenant pivot `category_product` cannot hold them (PK (category_id, product_id), NOT NULL FK to tenant
 *    categories, no copy exists in central mode). Links of OWN categories stay in `category_product`.
 *  - `categories.parent_type` + `catalog_parent_id`: typed parent of an OWN category (it may sit under a central
 *    category; `parent_id` keeps pointing at tenant rows only).
 *
 * Nothing reads or writes these until a tenant's flag is `central`. Idempotent (hasTable / hasColumn guards), additive
 * (ADD COLUMN nullable = INSTANT, new tables), down() drops only what this file adds.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tenant_category_overrides')) {
            Schema::create('tenant_category_overrides', function (Blueprint $table) {
                $table->unsignedBigInteger('central_category_id')->primary();
                $table->boolean('active')->nullable();
                $table->boolean('featured')->nullable();
                $table->unsignedInteger('order_number')->nullable();
                $table->unsignedBigInteger('thumb_file_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_category_product_overrides')) {
            Schema::create('tenant_category_product_overrides', function (Blueprint $table) {
                $table->id();
                $table->string('category_type', 10);
                $table->unsignedBigInteger('category_id');
                $table->string('product_type', 10);
                $table->unsignedBigInteger('product_id');
                $table->string('source', 10)->default('central'); // central = override of a central pair, tenant = vendor link
                $table->unsignedInteger('sort_order')->nullable();   // NULL = central sort_order
                $table->boolean('hidden')->default(false);
                $table->timestamps();

                $table->unique(['category_type', 'category_id', 'product_type', 'product_id'], 'tcpo_pair_unique');
                $table->index(['product_type', 'product_id'], 'tcpo_product_index');
            });
        }

        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'parent_type')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('parent_type', 10)->nullable();
                $table->unsignedBigInteger('catalog_parent_id')->nullable();
                $table->index(['parent_type', 'catalog_parent_id'], 'categories_parent_ref_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_category_product_overrides');
        Schema::dropIfExists('tenant_category_overrides');

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'parent_type')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('categories_parent_ref_index');
                $table->dropColumn(['parent_type', 'catalog_parent_id']);
            });
        }
    }
};
