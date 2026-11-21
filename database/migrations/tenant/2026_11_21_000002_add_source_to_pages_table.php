<?php

use App\Support\Catalog\CentralTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan B3: the catalog sync deleted every tenant page whose slug central does not have, i.e. the vendor's
 * own pages. `pages.source` tells them apart: `central` = created/claimed by the sync (central static page slug),
 * `tenant` = created by the vendor (default). Only `central` rows are removed when central drops the slug.
 *
 * Backfill: rows whose slug currently exists in central `static_pages` become `central` (read once from the
 * central schema, name from config). If central is unreachable the next sync claims them anyway.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('pages') || Schema::hasColumn('pages', 'source')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->string('source', 16)->default('tenant')->after('slug');
        });

        try {
            $slugs = DB::table(CentralTable::qualified('static_pages'))->pluck('slug')->filter()->unique()->values()->all();
        } catch (\Throwable $e) {
            Log::warning('pages.source backfill skipped: central static_pages not readable', ['error' => $e->getMessage()]);

            return;
        }

        foreach (array_chunk($slugs, 500) as $chunk) {
            DB::table('pages')->whereIn('slug', $chunk)->update(['source' => 'central']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pages', 'source')) {
            Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('source'));
        }
    }
};
