<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->integer('products_limit')->default(-1)->after('categories_count');
            $table->integer('banners_limit')->default(-1)->after('products_limit');
            $table->integer('languages_limit')->default(-1)->after('banners_limit');
            $table->integer('orders_per_month_limit')->default(-1)->after('languages_limit');
            $table->integer('ai_calls_limit')->default(-1)->after('orders_per_month_limit');
            $table->integer('image_searches_limit')->default(-1)->after('ai_calls_limit');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'products_limit',
                'banners_limit',
                'languages_limit',
                'orders_per_month_limit',
                'ai_calls_limit',
                'image_searches_limit',
            ]);
        });
    }
};
