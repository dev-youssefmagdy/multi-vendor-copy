<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->string('translation_status', 20)->default('completed')->after('translation_progress');
            $table->string('translation_source_locale', 10)->nullable()->after('translation_status');
            $table->text('translation_error')->nullable()->after('translation_source_locale');
        });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn(['translation_status', 'translation_source_locale', 'translation_error']);
        });
    }
};
