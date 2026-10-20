<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->string('type', 20)->default('external')->after('id')->index();
            $table->string('tenant_id')->nullable()->unique()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropUnique(['tenant_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'tenant_id']);
        });
    }
};
