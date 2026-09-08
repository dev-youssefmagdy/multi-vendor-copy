<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('central_flash_sales', function (Blueprint $table) {
            $table->id();
            $table->decimal('discount_percentage', 6, 2)->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('active')->default(true);
            $table->string('banner_image')->nullable();
            $table->timestamps();
        });

        Schema::create('central_flash_sale_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('central_flash_sale_id')->constrained('central_flash_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_flash_sale_product');
        Schema::dropIfExists('central_flash_sales');
    }
};
