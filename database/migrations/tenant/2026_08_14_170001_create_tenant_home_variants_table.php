<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_home_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->cascadeOnDelete();
            // References central countries.id — tenant DB does not have a countries table,
            // so we purposely do not constrain this FK. Null = applies to every country.
            $table->unsignedBigInteger('country_id')->nullable();
            // References central home_variants.id — cross-database, not constrained.
            $table->unsignedBigInteger('home_variant_id');
            $table->timestamps();

            $table->unique(['theme_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_home_variants');
    }
};
