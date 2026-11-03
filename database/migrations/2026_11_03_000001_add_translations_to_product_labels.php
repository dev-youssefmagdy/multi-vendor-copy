<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-language label text: {"ar": "...", "fr": "..."}; `text` stays the default-language fallback. */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_labels', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('text');
        });
    }

    public function down(): void
    {
        Schema::table('product_labels', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
