<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            // Nullable = AI translation not offered for this language.
            // 0 = free AI translation (included in plan). > 0 = paid.
            $table->decimal('ai_translation_price', 10, 2)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('ai_translation_price');
        });
    }
};
