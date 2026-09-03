<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_activities', function (Blueprint $table) {
            $table->string('status')->nullable()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_activities', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
