<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('central_flash_sale_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropColumn('central_flash_sale_id');
        });
    }
};
