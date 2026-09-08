<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_own_product')->default(false)->after('featured');
            $table->boolean('requires_shipping')->default(true)->after('is_own_product');
            $table->integer('stock')->nullable()->after('requires_shipping');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_own_product', 'requires_shipping', 'stock']);
        });
    }
};
