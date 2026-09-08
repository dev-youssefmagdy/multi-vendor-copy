<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_page_sections', function (Blueprint $table) {
            // MySQL won't drop the unique index while it's the only index backing
            // the theme_id foreign key, so give the FK a plain index to lean on first.
            $table->index('theme_id', 'tenant_page_sections_theme_id_fk_index');
            $table->dropUnique(['theme_id', 'page', 'section_key']);
            // Unconstrained: references the central `home_variants` table, mirroring
            // TenantHomeVariant::home_variant_id. Null means "not tied to a specific
            // variant" (legacy/theme-wide rows).
            $table->unsignedBigInteger('home_variant_id')->nullable()->after('theme_id');
            $table->unique(['theme_id', 'home_variant_id', 'page', 'section_key'], 'tenant_page_sections_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_page_sections', function (Blueprint $table) {
            $table->dropUnique('tenant_page_sections_variant_unique');
            $table->dropColumn('home_variant_id');
            $table->unique(['theme_id', 'page', 'section_key']);
            $table->dropIndex('tenant_page_sections_theme_id_fk_index');
        });
    }
};
