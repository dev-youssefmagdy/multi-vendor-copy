<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL migration, dedup plan phase 0 (§0.3-Q3, invariant 6): central catalog rows that tenants reference are
 * soft-deleted from now on (models use App\Models\Concerns\SoftDeletesCatalogRow, which hard-deletes exactly as
 * before until this migration has run).
 *
 * Unique columns become "unique among live rows" (MySQL functional index on IF(deleted_at IS NULL, col, NULL)), so
 * a soft-deleted SKU / coupon code / import external id can be reused exactly like after a hard delete. A plain
 * index keeps the lookups by that column fast.
 *
 * Online DDL on MySQL 8.0: ADD COLUMN = INSTANT, ADD/DROP INDEX = INPLACE (no write lock). Idempotent.
 * product_badges is not included: central has no delete path for badges.
 */
return new class extends Migration {
    private const TABLES = [
        'product_variants',
        'categories',
        'central_coupons',
        'central_flash_sales',
        'product_labels',
        'variations',
        'variation_options',
    ];

    /** table => column whose UNIQUE index becomes live-rows-only */
    private const LIVE_UNIQUE = [
        'product_variants' => 'sku',
        'categories' => 'external_id',
        'central_coupons' => 'code',
        'variation_options' => 'external_id',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->softDeletes());
            }
        }

        if (DB::connection()->getDriverName() !== 'mysql') {
            return; // functional indexes are MySQL-only; other drivers keep the plain unique index
        }

        foreach (self::LIVE_UNIQUE as $table => $column) {
            [$old, $live, $plain] = $this->indexNames($table, $column);

            if (! Schema::hasIndex($table, $live)) {
                DB::statement("ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$live}` ((IF(`deleted_at` IS NULL, `{$column}`, NULL)))");
            }
            if (! Schema::hasIndex($table, $plain)) {
                Schema::table($table, fn (Blueprint $t) => $t->index($column, $plain));
            }
            if (Schema::hasIndex($table, $old)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropUnique($old));
            }
        }
    }

    /**
     * Rolls back to hard deletes: rows that are soft-deleted now are hard-deleted (what the old code did; the real
     * FKs cascade), then the plain unique indexes and the old schema are restored.
     */
    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                DB::table($table)->whereNotNull('deleted_at')->delete();
            }
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            foreach (self::LIVE_UNIQUE as $table => $column) {
                [$old, $live, $plain] = $this->indexNames($table, $column);

                if (! Schema::hasIndex($table, $old)) {
                    Schema::table($table, fn (Blueprint $t) => $t->unique($column, $old));
                }
                if (Schema::hasIndex($table, $live)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($live));
                }
                if (Schema::hasIndex($table, $plain)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($plain));
                }
            }
        }

        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropSoftDeletes());
            }
        }
    }

    /** @return array{0: string, 1: string, 2: string} [old unique, live unique, plain index] */
    private function indexNames(string $table, string $column): array
    {
        return ["{$table}_{$column}_unique", "{$table}_{$column}_live_unique", "{$table}_{$column}_index"];
    }
};
