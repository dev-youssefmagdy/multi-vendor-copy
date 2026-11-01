<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The product-page slider "Free shipping" badge used to be hard-coded in the themes.
 * It is now a normal product label; assign it to every existing product so nothing disappears.
 */
return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $labelId = DB::table('product_labels')->insertGetId([
            'text' => 'Free shipping',
            'icon' => 'package',
            'active' => true,
            'sort_order' => (int) DB::table('product_labels')->max('sort_order') + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('product_label_product')->insertUsing(
            ['product_label_id', 'product_id', 'created_at', 'updated_at'],
            DB::table('products')->whereNull('deleted_at')->selectRaw('?, id, ?, ?', [$labelId, $now, $now])
        );
    }

    public function down(): void
    {
        DB::table('product_labels')->where('text', 'Free shipping')->delete();
    }
};
