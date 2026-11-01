<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A product carries a single label: keep the first (by sort order) and enforce it. */
return new class extends Migration {
    public function up(): void
    {
        $keep = DB::table('product_label_product as plp')
            ->join('product_labels as pl', 'pl.id', '=', 'plp.product_label_id')
            ->orderBy('pl.sort_order')->orderBy('pl.id')
            ->get(['plp.product_id', 'plp.product_label_id'])
            ->unique('product_id')
            ->mapWithKeys(fn($r) => [$r->product_id => $r->product_label_id]);

        foreach ($keep as $productId => $labelId) {
            DB::table('product_label_product')
                ->where('product_id', $productId)
                ->where('product_label_id', '!=', $labelId)
                ->delete();
        }

        Schema::table('product_label_product', function (Blueprint $table) {
            // Add the unique index first: the product_id FK needs an index at all times.
            $table->unique('product_id');
            $table->dropIndex(['product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('product_label_product', function (Blueprint $table) {
            $table->index('product_id');
            $table->dropUnique(['product_id']);
        });
    }
};
