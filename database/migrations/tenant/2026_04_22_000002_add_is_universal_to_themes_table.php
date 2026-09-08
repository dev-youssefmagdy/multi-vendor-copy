<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // Mirrors the central template: true when the template has NO country
            // restrictions (i.e. "all countries"). Drives activation semantics:
            //   - Universal themes switch like radio buttons (one primary active).
            //   - Country-specific themes are independent toggles.
            $table->boolean('is_universal')->default(true)->after('is_active');
            $table->index('is_universal');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropIndex(['is_universal']);
            $table->dropColumn('is_universal');
        });
    }
};
