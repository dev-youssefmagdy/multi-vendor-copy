<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'remember_token')) {
                $table->dropRememberToken();
            }

            if (Schema::hasColumn('tenants', 'password')) {
                $table->dropColumn('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'password')) {
                $table->string('password')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('tenants', 'remember_token')) {
                $table->rememberToken();
            }
        });
    }
};
