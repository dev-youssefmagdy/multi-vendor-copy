<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenant_owners', function (Blueprint $table) {
            $table->string('selected_tenant_id')->nullable()->after('tenant_id');
            $table->foreign('selected_tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });

        DB::table('tenant_owners')
            ->whereNull('selected_tenant_id')
            ->whereNotNull('tenant_id')
            ->update(['selected_tenant_id' => DB::raw('tenant_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_owners', function (Blueprint $table) {
            $table->dropForeign(['selected_tenant_id']);
            $table->dropColumn('selected_tenant_id');
        });
    }
};
