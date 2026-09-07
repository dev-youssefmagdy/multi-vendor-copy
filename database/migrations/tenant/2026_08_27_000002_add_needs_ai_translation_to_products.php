<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Set to true when a new central product is synced and the tenant has
            // active paid-translation languages. The weekly command picks these up.
            $table->boolean('needs_ai_translation')->default(false)->after('has_custom_translations');
            $table->timestamp('ai_translated_at')->nullable()->after('needs_ai_translation');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['needs_ai_translation', 'ai_translated_at']);
        });
    }
};
