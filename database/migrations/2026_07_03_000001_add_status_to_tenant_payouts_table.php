<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenant_payouts', function (Blueprint $table) {
            $table->string('status', 16)->default('paid')->after('amount');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tenant_payouts', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn('status');
        });
    }
};
