<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_variants', function (Blueprint $table) {
            $table->id();
            $table->string('theme_slug');
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            // Ordered list of section_keys (SectionRegistry). Null = inherit the
            // theme's default/tenant-customized section order.
            $table->json('sections')->nullable();
            // variable_key => hex value (ThemeColorKeys). Null/empty = inherit
            // the theme's resolved default colors.
            $table->json('colors')->nullable();
            // Optional alternate blade view for this variant's home page.
            // Null = use the theme strategy's default home page view.
            $table->string('view')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['theme_slug', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_variants');
    }
};
