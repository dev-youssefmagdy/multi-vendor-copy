<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tenant font choices per home variant: {"ltr": "outfit", "rtl": "cairo"}. Only overridden directions are stored. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenant_theme_colors', function (Blueprint $table) {
            $table->json('fonts')->nullable()->after('colors');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_theme_colors', function (Blueprint $table) {
            $table->dropColumn('fonts');
        });
    }
};
