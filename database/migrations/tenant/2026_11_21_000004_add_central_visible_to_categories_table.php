<?php

use App\Support\Catalog\CentralTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan B2/R1: `categories.active` is the vendor's toggle; central's publish state moves to
 * `central_visible` (synced, global scope on Tenant\Category, like products). Tenant-created categories keep 1.
 *
 * Backfill from central `categories.status` (schema name from config). The old sync overwrote `active` with
 * central's status on every run, so `active = 0` on a centrally unpublished category was central's doing: those
 * rows get `active = 1` back (still hidden by central_visible = 0 until central republishes).
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('categories') || Schema::hasColumn('categories', 'central_visible')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('central_visible')->default(true)->after('active');
        });

        try {
            $hidden = DB::table(CentralTable::qualified('categories'))->where('status', '!=', 'published')->pluck('id')->all();
        } catch (\Throwable $e) {
            Log::warning('categories.central_visible backfill skipped: central categories not readable', ['error' => $e->getMessage()]);

            return;
        }

        foreach (array_chunk($hidden, 500) as $chunk) {
            DB::table('categories')->whereIn('central_category_id', $chunk)->update(['central_visible' => false, 'active' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'central_visible')) {
            Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('central_visible'));
        }
    }
};
