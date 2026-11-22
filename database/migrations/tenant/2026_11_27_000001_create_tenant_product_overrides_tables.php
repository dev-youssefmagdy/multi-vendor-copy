<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 5 (§4 `products` + `product_variants`, §4.1): what a tenant owns about CENTRAL products once its
 * `catalog_source.products` is `central` (App\Repositories\Tenant\Catalog\ProductCatalog). One row per adopted central
 * product / variant (adoption = the row exists); only CHANGED columns are stored, NULL = inherit central.
 *
 *  - `tenant_product_overrides`: visibility (`active`, `featured`, `order_number`), vendor pricing (`profit` JSON per
 *    country key like products.profit, `price_override` = vendor-typed default price), `stock_override`,
 *    `weight_grams_override`, `fixed_shipping_costs_override` (JSON), return-policy fields, `allowed_country_ids`.
 *  - `tenant_product_variant_overrides`: `active`, `profit` JSON, `margin_percentage`, `price_override`
 *    (default sell price), `stock_override`, `thumbnail_path`.
 *
 * Text fields stay in `tenant_field_overrides` (phase 2); product<->category / badge / label / flash sale links in the
 * phase 2-4 override tables. Nothing reads or writes these until a tenant's flag is `central`. Idempotent, additive.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tenant_product_overrides')) {
            Schema::create('tenant_product_overrides', function (Blueprint $table) {
                $table->unsignedBigInteger('central_product_id')->primary();
                $table->boolean('active')->nullable();
                $table->boolean('featured')->nullable();
                $table->unsignedInteger('order_number')->nullable();
                $table->json('profit')->nullable();
                $table->decimal('price_override', 14, 4)->nullable();
                $table->integer('stock_override')->nullable();
                $table->unsignedInteger('weight_grams_override')->nullable();
                $table->json('fixed_shipping_costs_override')->nullable();
                $table->json('allowed_country_ids')->nullable();
                $table->string('return_policy_override', 32)->nullable();
                $table->boolean('is_returnable')->nullable();
                $table->unsignedInteger('return_window_days')->nullable();
                $table->decimal('return_fee', 10, 2)->nullable();
                $table->boolean('return_video_required')->nullable();
                $table->text('return_conditions')->nullable();
                $table->timestamp('adopted_at')->nullable();
                $table->timestamps();

                $table->index(['active', 'featured'], 'tpo_visibility_index');
                $table->index('order_number', 'tpo_order_index');
            });
        }

        if (! Schema::hasTable('tenant_product_variant_overrides')) {
            Schema::create('tenant_product_variant_overrides', function (Blueprint $table) {
                $table->unsignedBigInteger('central_variant_id')->primary();
                $table->unsignedBigInteger('central_product_id');
                $table->boolean('active')->nullable();
                $table->json('profit')->nullable();
                $table->decimal('margin_percentage', 8, 2)->nullable();
                $table->decimal('price_override', 14, 4)->nullable();
                $table->integer('stock_override')->nullable();
                $table->string('thumbnail_path')->nullable();
                $table->timestamps();

                $table->index('central_product_id', 'tpvo_product_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_product_variant_overrides');
        Schema::dropIfExists('tenant_product_overrides');
    }
};
