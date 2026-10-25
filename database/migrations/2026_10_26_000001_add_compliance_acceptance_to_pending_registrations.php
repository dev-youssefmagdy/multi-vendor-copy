<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            // Compliance documents accepted on the register page, before payment / the registration link.
            $table->string('compliance_version', 64)->nullable();
            $table->timestamp('compliance_accepted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(['compliance_version', 'compliance_accepted_at']);
        });
    }
};
