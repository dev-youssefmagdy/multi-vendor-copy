<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 5 (panel modals): the vendor's generated social posts / image and AI price data of a CENTRAL product are
 * tenant-owned data, stored in `tenant_product_overrides` (same columns the synced copy had). Idempotent.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('tenant_product_overrides')) {
            return;
        }
        Schema::table('tenant_product_overrides', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_product_overrides', 'social_posts')) {
                $table->json('social_posts')->nullable();
            }
            if (! Schema::hasColumn('tenant_product_overrides', 'social_image_b64')) {
                $table->longText('social_image_b64')->nullable();
            }
            if (! Schema::hasColumn('tenant_product_overrides', 'ai_price_data')) {
                $table->json('ai_price_data')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_product_overrides')) {
            Schema::table('tenant_product_overrides', function (Blueprint $table) {
                foreach (['social_posts', 'social_image_b64', 'ai_price_data'] as $column) {
                    if (Schema::hasColumn('tenant_product_overrides', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
