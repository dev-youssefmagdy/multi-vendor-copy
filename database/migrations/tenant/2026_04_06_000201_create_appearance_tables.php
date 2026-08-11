<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('serial_number')->default(0);
            $table->timestamps();
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('icon', 50);
            $table->string('url', 500);
            $table->unsignedInteger('serial_number')->default(0);
            $table->timestamps();
        });

        Schema::create('appearance_features', function (Blueprint $table) {
            $table->id();
            $table->string('icon_class', 100)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('serial_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appearance_features');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('banners');
    }
};
