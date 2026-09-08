<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_tenant_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('tenant_id', 36)->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tenant_assignments');
    }
};
