<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_popup_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_number');  // 1–30
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique('day_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_popup_days');
    }
};
