<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('product_badge_product')) {
            Schema::create('product_badge_product', function (Blueprint $table) {
                $table->foreignId('product_badge_id')->constrained('product_badges')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['product_badge_id', 'product_id']);
            });
        }

        if (Schema::hasTable('product_badges') && !Schema::hasColumn('product_badges', 'text')) {
            return;
        }

        Schema::table('product_badges', function (Blueprint $table) {
            $table->unique('text');
        });

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'badge_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['badge_id']);
                $table->dropColumn('badge_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'badge_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('badge_id')->nullable()->constrained('product_badges')->nullOnDelete()->after('featured');
            });
        }

        if (Schema::hasTable('product_badges')) {
            Schema::table('product_badges', function (Blueprint $table) {
                $table->dropUnique('product_badges_text_unique');
            });
        }

        Schema::dropIfExists('product_badge_product');
    }
};
