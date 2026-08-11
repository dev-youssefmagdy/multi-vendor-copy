<?php

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Enums\VariationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->string('status')->default(ProductStatus::Draft->value);
            $table->string('delivery_scope')->default(DeliveryScope::AllZones->value);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->boolean('manage_stock')->default(true);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('requires_shipping')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('product_shipping_zone', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_id', 'shipping_zone_id']);
        });

        Schema::create('product_variation', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_id', 'variation_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->string('title')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default(VariationStatus::Active->value);
            $table->timestamps();
        });

        Schema::create('product_variant_option', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_variant_id', 'variation_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_option');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_variation');
        Schema::dropIfExists('product_shipping_zone');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};