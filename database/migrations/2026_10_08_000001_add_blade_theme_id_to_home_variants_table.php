<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_variants', function (Blueprint $table) {
            $table->unsignedBigInteger('blade_theme_id')->nullable()->after('is_active');
            $table->foreign('blade_theme_id')->references('id')->on('blade_themes')->nullOnDelete();
            $table->index('blade_theme_id');
        });
    }

    public function down(): void
    {
        Schema::table('home_variants', function (Blueprint $table) {
            $table->dropForeign(['blade_theme_id']);
            $table->dropIndex(['blade_theme_id']);
            $table->dropColumn('blade_theme_id');
        });
    }
};
