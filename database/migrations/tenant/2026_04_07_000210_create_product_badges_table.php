<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_badges', function (Blueprint $table) {
            $table->id();
            $table->string('text'); // e.g. "new-in", "best-selling"
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('badge_id')->nullable()->constrained('product_badges')->nullOnDelete()->after('featured');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['badge_id']);
            $table->dropColumn('badge_id');
        });

        Schema::dropIfExists('product_badges');
    }
};
