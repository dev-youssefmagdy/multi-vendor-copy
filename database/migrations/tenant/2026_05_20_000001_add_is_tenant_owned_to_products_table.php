<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add is_tenant_owned flag to tenant products table.
 *
 * is_tenant_owned = true  → product was assigned from central to this tenant specifically.
 *                           Central profit = shipping fees only (no product cost).
 * is_tenant_owned = false → product comes from the shared catalog; central earns
 *                           full product cost + shipping.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_tenant_owned')
                ->default(false)
                ->after('is_own_product')
                ->comment('True when the product was individually assigned from central to this tenant — central profit is shipping only');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_tenant_owned');
        });
    }
};
