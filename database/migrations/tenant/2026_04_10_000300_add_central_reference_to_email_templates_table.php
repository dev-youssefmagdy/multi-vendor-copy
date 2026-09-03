<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('central_email_template_id')->nullable()->after('id');
            $table->index('central_email_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropIndex(['central_email_template_id']);
            $table->dropColumn('central_email_template_id');
        });
    }
};
