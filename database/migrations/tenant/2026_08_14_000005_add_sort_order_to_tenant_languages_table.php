<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('languages', 'sort_order')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_default');
            });
        }
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
