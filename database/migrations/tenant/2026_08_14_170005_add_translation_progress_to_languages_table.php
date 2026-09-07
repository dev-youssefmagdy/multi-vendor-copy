<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->unsignedTinyInteger('translation_progress')->default(0)->after('sort_order');
            $table->string('translation_status')->nullable()->after('translation_progress');
            $table->text('translation_summary')->nullable()->after('translation_status');
        });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn(['translation_progress', 'translation_status', 'translation_summary']);
        });
    }
};
