<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL's PRIMARY KEY forbids nullable columns, so the nullable country_id
        // (NULL = "Default / All Countries") can't join the primary key. Use a
        // surrogate id as PK and a unique index for the natural key instead —
        // MySQL's UNIQUE index (unlike PRIMARY KEY) treats each NULL as distinct,
        // so one default row and any number of per-country rows can coexist.
        if (! Schema::hasColumn('product_badge_product', 'country_id')) {
            Schema::table('product_badge_product', function (Blueprint $table) {
                $table->unsignedBigInteger('country_id')->nullable()->after('product_id');
            });
        }

        $hasPrimary = collect(DB::select("SHOW KEYS FROM product_badge_product WHERE Key_name = 'PRIMARY'"))->isNotEmpty();
        if ($hasPrimary) {
            Schema::table('product_badge_product', function (Blueprint $table) {
                // FK constraints on product_badge_id/product_id need a supporting
                // index before the composite primary key backing them can be dropped.
                if (! collect(DB::select("SHOW KEYS FROM product_badge_product WHERE Key_name = 'pbp_badge_id_index'"))->isNotEmpty()) {
                    $table->index('product_badge_id', 'pbp_badge_id_index');
                }
                if (! collect(DB::select("SHOW KEYS FROM product_badge_product WHERE Key_name = 'pbp_product_id_index'"))->isNotEmpty()) {
                    $table->index('product_id', 'pbp_product_id_index');
                }
                $table->dropPrimary();
            });
        }

        if (! Schema::hasColumn('product_badge_product', 'id')) {
            Schema::table('product_badge_product', function (Blueprint $table) {
                $table->id()->first();
            });
        }

        Schema::table('product_badge_product', function (Blueprint $table) {
            if (! collect(DB::select("SHOW KEYS FROM product_badge_product WHERE Key_name = 'product_badge_product_country_id_foreign'"))->isNotEmpty()) {
                $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            }
            if (! collect(DB::select("SHOW KEYS FROM product_badge_product WHERE Key_name = 'pbp_unique'"))->isNotEmpty()) {
                $table->unique(['product_badge_id', 'product_id', 'country_id'], 'pbp_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_badge_product', function (Blueprint $table) {
            $table->dropUnique('pbp_unique');
            $table->dropForeign(['country_id']);
            $table->dropColumn(['country_id', 'id']);
            $table->primary(['product_badge_id', 'product_id']);
        });
    }
};
