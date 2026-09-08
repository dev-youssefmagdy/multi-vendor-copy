<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('product_variants', 'fixed_shipping_costs')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            // JSON map of country_id (string key) → flat shipping cost (float)
            // e.g. {"1": 5.00, "3": 8.50} — computed from this variant's own weight_grams.
            $table->json('fixed_shipping_costs')->nullable()->after('weight_grams');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('fixed_shipping_costs');
        });
    }
};
