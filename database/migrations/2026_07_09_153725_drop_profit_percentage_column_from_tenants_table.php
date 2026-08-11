<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // profit_percentage is stored inside the tenant's `data` JSON column
        // (via Stancl's VirtualColumn, since it isn't listed in custom columns).
        // The physical column was never populated by application code and only
        // ever held the default 0.00, so drop it to avoid anyone reading stale
        // data from it directly via raw SQL.
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('profit_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('profit_percentage', 8, 2)->default(0)->after('activated_at');
        });
    }
};
