<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dedup plan B1 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §2a): order history must survive catalog deletes.
 *
 * `order_items.product_variant_id` was created with ON DELETE CASCADE, so the catalog sync deleting a variant
 * that disappeared from central (or deleting a stale product, whose variants cascade) silently deleted the
 * customer's order lines. The FK becomes ON DELETE SET NULL: the line keeps its money snapshot
 * (cost / profit_amount / price / grand_total ...) and `product_id`.
 *
 * `order_items.product_id` has no FK (plain index, 2026_04_08_000207), `order_items.order_id` cascades from
 * `orders` (wanted). No other order-history table references the catalog.
 *
 * Idempotent: inspects the existing FK and only rebuilds it when its delete rule is not already SET NULL.
 */
return new class extends Migration {
    private const FK = 'order_items_product_variant_id_foreign';

    public function up(): void
    {
        if (! Schema::hasTable('order_items') || ! Schema::hasColumn('order_items', 'product_variant_id')) {
            return;
        }

        $existing = $this->variantForeignKeys();

        if ($existing !== [] && collect($existing)->every(fn (array $fk) => $this->isSetNull($fk))) {
            return; // already migrated
        }

        // Column must be nullable for SET NULL (2026_04_08_000207 already did this; re-assert for old DBs).
        if (DB::getDriverName() === 'mysql') {
            $column = DB::selectOne(
                "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'product_variant_id'"
            );
            if ($column && strtoupper((string) $column->IS_NULLABLE) === 'NO') {
                DB::statement('ALTER TABLE order_items MODIFY product_variant_id BIGINT UNSIGNED NULL');
            }
        }

        foreach ($existing as $fk) {
            Schema::table('order_items', fn (Blueprint $table) => $table->dropForeign($fk['name']));
        }

        // Never null out (= lose) historic variant ids just to be able to add the constraint.
        $orphans = DB::table('order_items')
            ->whereNotNull('product_variant_id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('product_variants')->whereColumn('product_variants.id', 'order_items.product_variant_id'))
            ->count();

        if ($orphans > 0) {
            Log::warning('order_items.product_variant_id FK not re-added: orphan rows present', ['orphans' => $orphans]);

            return;
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_variant_id', self::FK)->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally not restoring ON DELETE CASCADE: it deletes order history (B1).
    }

    /** @return array<int, array{name: string, on_delete: ?string}> */
    private function variantForeignKeys(): array
    {
        return collect(Schema::getForeignKeys('order_items'))
            ->filter(fn (array $fk) => $fk['columns'] === ['product_variant_id'])
            ->values()
            ->all();
    }

    private function isSetNull(array $fk): bool
    {
        return strtolower((string) ($fk['on_delete'] ?? '')) === 'set null';
    }
};
