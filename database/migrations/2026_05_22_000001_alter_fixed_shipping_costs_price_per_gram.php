<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove duplicate country records, keeping the lowest id per country.
        DB::statement('
            DELETE fsc FROM fixed_shipping_costs fsc
            INNER JOIN (
                SELECT MIN(id) AS keep_id, country_id
                FROM fixed_shipping_costs
                GROUP BY country_id
                HAVING COUNT(*) > 1
            ) dup ON fsc.country_id = dup.country_id AND fsc.id != dup.keep_id
        ');

        Schema::table('fixed_shipping_costs', function (Blueprint $table) {
            // Drop the weight-range and flat-cost columns.
            $table->dropColumn(['min_weight_grams', 'max_weight_grams', 'cost']);

            // A single price-per-gram for the country (6 decimal places for precision).
            $table->decimal('price_per_gram', 14, 6)->default(0)->after('country_id')
                ->comment('Shipping cost per gram of product weight for this country');

            // Enforce one record per country.
            $table->unique('country_id');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_shipping_costs', function (Blueprint $table) {
            $table->dropUnique(['country_id']);
            $table->dropColumn('price_per_gram');

            $table->unsignedInteger('min_weight_grams')->nullable()
                ->comment('Inclusive lower bound in grams; null = no minimum');
            $table->unsignedInteger('max_weight_grams')->nullable()
                ->comment('Inclusive upper bound in grams; null = no maximum');
            $table->decimal('cost', 10, 2)->default(0)
                ->comment('Flat shipping cost for this range and country');
        });
    }
};
