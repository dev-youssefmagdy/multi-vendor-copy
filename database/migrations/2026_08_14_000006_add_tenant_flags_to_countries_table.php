<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->boolean('is_active_for_tenants')->default(true)->after('flag_emoji');
            $table->boolean('is_free')->default(false)->after('is_active_for_tenants');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn(['is_active_for_tenants', 'is_free']);
        });
    }
};
