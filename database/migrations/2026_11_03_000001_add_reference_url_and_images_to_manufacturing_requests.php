<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PANELS-UPDATES-MANUFACTURING: a manufacturing request can carry reference images and a product link.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('manufacturing_requests', function (Blueprint $table) {
            $table->string('reference_url', 2000)->nullable()->after('description');
            $table->json('images')->nullable()->after('reference_url');
        });
    }

    public function down(): void
    {
        Schema::table('manufacturing_requests', function (Blueprint $table) {
            $table->dropColumn(['reference_url', 'images']);
        });
    }
};
