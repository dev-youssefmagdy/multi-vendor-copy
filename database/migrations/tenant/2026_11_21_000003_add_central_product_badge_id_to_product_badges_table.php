<?php

use App\Support\Catalog\CentralTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan B4: tenant badges were matched to central by `text` only, so renaming a central badge created a
 * duplicate, and the sync deleted every tenant badge whose text central lacked (B3, incl. the seeded
 * featured / recommended / new-in / best-selling / trending-now badges).
 *
 * `central_product_badge_id` links a tenant badge to its central row (NULL = tenant-owned, never deleted by sync).
 * One-time backfill by text: central ids are read from the central schema (name from config, never a literal) and
 * written with plain tenant UPDATEs (no cross-schema join, so no collation coupling). If central is unreachable the
 * sync's text fallback links the rows on its next run.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('product_badges')) {
            return;
        }

        if (! Schema::hasColumn('product_badges', 'central_product_badge_id')) {
            Schema::table('product_badges', function (Blueprint $table) {
                $table->unsignedBigInteger('central_product_badge_id')->nullable()->after('id')->index();
            });
        }

        try {
            $central = DB::table(CentralTable::qualified('product_badges'))->pluck('id', 'text')->all();
        } catch (\Throwable $e) {
            Log::warning('product_badges.central_product_badge_id backfill skipped: central product_badges not readable', ['error' => $e->getMessage()]);

            return;
        }

        $linked = DB::table('product_badges')->whereNotNull('central_product_badge_id')->pluck('central_product_badge_id')->map(fn ($id) => (int) $id)->all();

        foreach ($central as $text => $centralId) {
            if (in_array((int) $centralId, $linked, true)) {
                continue;
            }

            // Oldest unlinked row with that text (text is not unique on the tenant side).
            $tenantId = DB::table('product_badges')->whereNull('central_product_badge_id')->where('text', $text)->orderBy('id')->value('id');
            if ($tenantId !== null) {
                DB::table('product_badges')->where('id', $tenantId)->update(['central_product_badge_id' => $centralId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_badges', 'central_product_badge_id')) {
            Schema::table('product_badges', function (Blueprint $table) {
                $table->dropIndex(['central_product_badge_id']);
                $table->dropColumn('central_product_badge_id');
            });
        }
    }
};
