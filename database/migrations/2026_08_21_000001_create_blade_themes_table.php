<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blade_themes', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('version');
            $table->string('storage_path');
            $table->string('original_filename')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->boolean('is_active')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blade_themes');
    }
};
