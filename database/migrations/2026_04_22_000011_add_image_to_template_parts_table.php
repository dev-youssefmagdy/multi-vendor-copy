<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('template_parts', function (Blueprint $table) {
            // Full URL of an optional preview/logo image associated with this part.
            $table->string('image')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('template_parts', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
