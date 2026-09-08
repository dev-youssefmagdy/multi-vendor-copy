<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('compliance_status')->default('pending')->after('data');
            $table->text('compliance_admin_note')->nullable()->after('compliance_status');
            $table->string('compliance_reviewed_by')->nullable()->after('compliance_admin_note');
            $table->timestamp('compliance_reviewed_at')->nullable()->after('compliance_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['compliance_status', 'compliance_admin_note', 'compliance_reviewed_by', 'compliance_reviewed_at']);
        });
    }
};
