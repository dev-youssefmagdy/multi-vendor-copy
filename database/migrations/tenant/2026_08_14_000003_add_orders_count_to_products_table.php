<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'orders_count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('orders_count')->default(0)->after('stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'orders_count')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('orders_count');
            });
        }
    }
};
