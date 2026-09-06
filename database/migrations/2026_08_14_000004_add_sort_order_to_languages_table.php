<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('languages', 'sort_order')) {
            Schema::table('languages', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_default');
            });
        }

        DB::table('languages')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->pluck('id')
            ->each(function ($id, $index) {
                DB::table('languages')->where('id', $id)->update(['sort_order' => $index]);
            });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
