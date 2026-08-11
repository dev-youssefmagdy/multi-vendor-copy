<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('flash_sale_product', function (Blueprint $table) {
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['flash_sale_id', 'product_id']);
        });

        DB::table('flash_sales')
            ->select(['id', 'product_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->each(function (object $flashSale): void {
                if (!$flashSale->product_id) {
                    return;
                }

                DB::table('flash_sale_product')->updateOrInsert(
                    [
                        'flash_sale_id' => $flashSale->id,
                        'product_id' => $flashSale->product_id,
                    ],
                    [
                        'created_at' => $flashSale->created_at,
                        'updated_at' => $flashSale->updated_at,
                    ],
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_product');
    }
};
