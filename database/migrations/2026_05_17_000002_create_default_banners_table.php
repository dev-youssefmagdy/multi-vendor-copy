<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('default_banners', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('serial_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('default_banners');
    }
};
