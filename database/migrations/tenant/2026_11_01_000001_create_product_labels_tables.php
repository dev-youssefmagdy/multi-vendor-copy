<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tenant mirror of the central product labels (filled by TenantCatalogSyncService::syncLabels). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('central_product_label_id')->unique();
            $table->string('text');
            $table->string('icon', 40)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_label_product', function (Blueprint $table) {
            $table->foreignId('product_label_id')->constrained('product_labels')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_label_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_label_product');
        Schema::dropIfExists('product_labels');
    }
};
