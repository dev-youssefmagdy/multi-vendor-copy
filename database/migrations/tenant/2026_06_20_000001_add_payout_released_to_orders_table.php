<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Tracks whether the central platform has paid out the tenant's share
            // for central-gateway orders. Defaults false; set to true when admin
            // records a TenantPayout covering this order.
            $table->boolean('payout_released')->default(false)->after('paid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payout_released');
        });
    }
};
