<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_theme_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->cascadeOnDelete();
            // References central home_variants.id — cross-database, not constrained.
            // Null = the theme's single/default variant (themes with no variant catalog).
            $table->unsignedBigInteger('home_variant_id')->nullable();
            // References central countries.id — tenant DB does not have a countries table,
            // so we purposely do not constrain this FK. Null = applies to every country.
            $table->unsignedBigInteger('country_id')->nullable();
            // CSS custom property name (e.g. "--color-primary") => hex value override.
            // Only keys the tenant has actually changed are stored; anything missing
            // falls back to the home variant's default colors.
            $table->json('colors');
            $table->timestamps();

            $table->unique(['theme_id', 'home_variant_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_theme_colors');
    }
};
