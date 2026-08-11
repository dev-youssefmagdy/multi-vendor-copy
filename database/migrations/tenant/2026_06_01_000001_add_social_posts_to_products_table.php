<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Stores per-language, per-platform generated social captions.
            // Structure: { "en": { "instagram": "...", "facebook": "..." }, "ar": { ... } }
            $table->json('social_posts')->nullable()->after('stock');
            // Stores the base64-encoded generated product image from the last social post generation.
            $table->longText('social_image_b64')->nullable()->after('social_posts');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['social_posts', 'social_image_b64']);
        });
    }
};
