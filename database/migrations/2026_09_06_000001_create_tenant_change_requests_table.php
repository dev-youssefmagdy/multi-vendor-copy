<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('type');                            // countries | categories
            $table->json('requested_data');                    // requested country_ids / category_ids
            $table->json('current_data');                      // snapshot of existing values at request time
            $table->string('status')->default('pending');      // pending | approved | rejected
            $table->string('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable(); // central admin user id
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_change_requests');
    }
};
