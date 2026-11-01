<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central product labels: "icon + text" chips shown on storefront product cards
 * (e.g. "Delivered by {delivery_date}", "Only {stock} left – hurry up").
 * Distinct from product_badges, which are home-section product collections.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_labels', function (Blueprint $table) {
            $table->id();
            $table->string('text');
            $table->string('icon', 40)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_label_product', function (Blueprint $table) {
            $table->foreignId('product_label_id')->constrained('product_labels')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_label_id', 'product_id']);
            $table->index('product_id');
        });

        // Seed the strings the cards used to hard-code, assigned to every existing
        // product so the storefront looks the same until an admin changes it.
        $now = now();
        $defaults = [
            ['text' => 'Fast delivery', 'icon' => 'truck'],
            ['text' => 'Delivered by {delivery_date}', 'icon' => 'truck'],
            ['text' => 'Only {stock} left – hurry up', 'icon' => 'bolt'],
        ];

        foreach ($defaults as $i => $row) {
            $labelId = DB::table('product_labels')->insertGetId($row + [
                'active' => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('product_label_product')->insertUsing(
                ['product_label_id', 'product_id', 'created_at', 'updated_at'],
                DB::table('products')->whereNull('deleted_at')
                    ->selectRaw('?, id, ?, ?', [$labelId, $now, $now])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_label_product');
        Schema::dropIfExists('product_labels');
    }
};
