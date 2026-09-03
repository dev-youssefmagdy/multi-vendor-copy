<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('payment_gateways', 'use_own')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->boolean('use_own')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payment_gateways', 'use_own')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->dropColumn('use_own');
            });
        }
    }
};
