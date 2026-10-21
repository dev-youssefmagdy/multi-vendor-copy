<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront home-list indexes (see project-management/STOREFRONT_PERFORMANCE_AUDIT.md).
 *
 *  - products(active, created_at): "new in" / newest-first lists, default sort and the
 *    out-of-stock-filtered listing: `where active = 1 order by created_at desc limit N`.
 *  - products(active, orders_count, created_at): best-selling fallback:
 *    `where active = 1 order by orders_count desc, created_at desc limit N`.
 *  - orders(created_at): trending (72h) and best-selling (30d) range scans on orders.created_at.
 *
 * Measured on a 155k-product / 65k-order copy: 124 ms -> 0.06 ms (products), 43 ms -> 0.05-0.35 ms (orders).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->has('products', 'products_active_created_at_index')) {
            Schema::table('products', fn (Blueprint $t) => $t->index(['active', 'created_at'], 'products_active_created_at_index'));
        }

        if (! $this->has('products', 'products_active_orders_count_created_at_index')) {
            Schema::table('products', fn (Blueprint $t) => $t->index(['active', 'orders_count', 'created_at'], 'products_active_orders_count_created_at_index'));
        }

        if (! $this->has('orders', 'orders_created_at_index')) {
            Schema::table('orders', fn (Blueprint $t) => $t->index('created_at', 'orders_created_at_index'));
        }
    }

    public function down(): void
    {
        foreach ([['products', 'products_active_created_at_index'], ['products', 'products_active_orders_count_created_at_index'], ['orders', 'orders_created_at_index']] as [$table, $index]) {
            if ($this->has($table, $index)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
            }
        }
    }

    private function has(string $table, string $index): bool
    {
        return Schema::hasTable($table) && Schema::hasIndex($table, $index);
    }
};
