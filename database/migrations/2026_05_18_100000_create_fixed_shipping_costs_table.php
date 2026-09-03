<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fixed_shipping_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            // Weight range in grams — null means "no bound"
            $table->unsignedInteger('min_weight_grams')->nullable()->comment('Inclusive lower bound in grams; null = no minimum');
            $table->unsignedInteger('max_weight_grams')->nullable()->comment('Inclusive upper bound in grams; null = no maximum');
            $table->decimal('cost', 10, 2)->default(0)->comment('Flat shipping cost for this range and country');
            $table->string('currency_code', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // A country should ideally not have overlapping ranges, but we don't enforce that at DB level.
            $table->index(['country_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_shipping_costs');
    }
};
