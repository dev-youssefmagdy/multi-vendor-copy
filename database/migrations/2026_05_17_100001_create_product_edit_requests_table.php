<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->unsignedBigInteger('product_id');         // tenant-scoped product id
            $table->string('product_slug')->nullable();       // snapshot for display
            $table->json('requested_translations');           // { "en": { "name": "...", "description": "..." }, ... }
            $table->json('current_translations');             // snapshot of existing values at request time
            $table->string('status')->default('pending');     // pending | approved | rejected
            $table->string('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable(); // central admin user id
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'product_id']);      // one pending request per product
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_edit_requests');
    }
};
