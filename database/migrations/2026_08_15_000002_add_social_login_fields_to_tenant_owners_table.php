<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenant_owners', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_owners', 'provider')) {
                $table->string('provider')->nullable()->after('password');
            }
            if (!Schema::hasColumn('tenant_owners', 'provider_id')) {
                $table->string('provider_id')->nullable()->after('provider');
            }
            if (!Schema::hasColumn('tenant_owners', 'avatar')) {
                $table->string('avatar')->nullable()->after('provider_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_owners', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_id', 'avatar']);
        });
    }
};
