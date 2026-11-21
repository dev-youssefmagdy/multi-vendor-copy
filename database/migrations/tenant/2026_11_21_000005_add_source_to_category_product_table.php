<?php

use App\Support\Catalog\CentralTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan B2/R2: `category_product.source` = `central` for links the catalog sync manages (detached when central
 * drops them), `tenant` (default) for the vendor's own picks (never touched by sync).
 *
 * Backfill: a link whose (central product, central category) pair exists in central `category_product` today is
 * `central`; all others stay `tenant`. Integer-only join against the central schema (name from config); only the
 * tenant table is written.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('category_product') || Schema::hasColumn('category_product', 'source')) {
            return;
        }

        Schema::table('category_product', function (Blueprint $table) {
            $table->string('source', 16)->default('tenant');
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement(
                'UPDATE category_product cp'
                . ' INNER JOIN products p ON p.id = cp.product_id AND p.central_product_id IS NOT NULL'
                . ' INNER JOIN categories c ON c.id = cp.category_id AND c.central_category_id IS NOT NULL'
                . ' INNER JOIN ' . CentralTable::name('category_product') . ' ccp'
                . '   ON ccp.product_id = p.central_product_id AND ccp.category_id = c.central_category_id'
                . " SET cp.source = 'central'"
            );
        } catch (\Throwable $e) {
            Log::warning('category_product.source backfill skipped: central category_product not readable', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('category_product', 'source')) {
            Schema::table('category_product', fn (Blueprint $table) => $table->dropColumn('source'));
        }
    }
};
