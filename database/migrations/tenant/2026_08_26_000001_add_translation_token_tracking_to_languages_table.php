<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->unsignedInteger('last_translation_token_count')->default(0)->after('translation_summary');
            $table->decimal('last_translation_cost_usd', 10, 4)->default(0)->after('last_translation_token_count');
        });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn(['last_translation_token_count', 'last_translation_cost_usd']);
        });
    }
};
