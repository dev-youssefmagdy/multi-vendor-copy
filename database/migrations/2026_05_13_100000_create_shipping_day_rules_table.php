<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Central table that stores the default shipping duration per country.
     * A row with country_code = NULL acts as the global fallback used when
     * no country-specific rule exists.
     */
    public function up(): void
    {
        Schema::create('shipping_day_rules', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->nullable()->unique()->comment('ISO 3166-1 alpha-2; NULL = global default');
            $table->unsignedTinyInteger('min_days')->default(3);
            $table->unsignedTinyInteger('max_days')->default(7);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_day_rules');
    }
};
