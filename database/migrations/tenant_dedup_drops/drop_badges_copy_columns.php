<?php

use App\Services\Catalog\Dedup\DedupSchemaDrops;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan phase 7: drop the copy-only columns of section [badges] (DedupSchemaDrops::DROPS['badges']).
 *
 * NOT a tenants:migrate migration (this directory is not a migrations path). Run only through
 * `php artisan catalog:dedup-cleanup badges --tenant=<id> --drop-schema`; up() refuses unless
 * DedupSchemaDrops::reasons() is empty for the current tenant (code ready, section central + cleaned, no copy left).
 * down() (`--undrop-schema`) re-adds the columns NULLable / with their old default, so a cleanup backup can be restored.
 */
return new class extends Migration {
    private const SECTION = 'badges';

    public function up(): void
    {
        DedupSchemaDrops::assertAllowed(self::SECTION);
        $schema = Schema::connection('tenant');
        foreach (DedupSchemaDrops::DROPS[self::SECTION] as [$table, $column]) {
            if ($schema->hasColumn($table, $column)) {
                $schema->table($table, fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');
        foreach (DedupSchemaDrops::DROPS[self::SECTION] as [$table, $column, $kind]) {
            if ($schema->hasColumn($table, $column)) {
                continue;
            }
            $schema->table($table, function (Blueprint $t) use ($column, $kind) {
                if ($kind === 'link') {
                    $t->unsignedBigInteger($column)->nullable()->index();
                } else {
                    $t->boolean($column)->default($column === 'central_visible');
                }
            });
        }
    }
};
