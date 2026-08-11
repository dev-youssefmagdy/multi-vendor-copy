<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'stock')) {
                $table->integer('stock')->nullable()->after('sell_price');
            }
            if (!Schema::hasColumn('product_variants', 'option_ids')) {
                $table->json('option_ids')->nullable()->after('central_product_variant_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'stock')) {
                $table->dropColumn('stock');
            }
            if (Schema::hasColumn('product_variants', 'option_ids')) {
                $table->dropColumn('option_ids');
            }
        });
    }
};
