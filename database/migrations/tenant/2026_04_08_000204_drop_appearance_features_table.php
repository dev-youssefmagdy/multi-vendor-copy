<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Remove any translations tied to the appearance feature model
        DB::table('translations')
            ->where('translatable_type', 'App\\Models\\Tenant\\AppearanceFeature')
            ->delete();

        Schema::dropIfExists('appearance_features');
    }

    public function down(): void
    {
        Schema::create('appearance_features', function (Blueprint $table) {
            $table->id();
            $table->string('icon_class', 100)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('serial_number')->default(0);
            $table->timestamps();
        });
    }
};
