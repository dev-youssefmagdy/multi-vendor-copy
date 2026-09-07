<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 36)->index();
            $table->string('title');
            $table->text('description');
            $table->json('attachments')->nullable();
            $table->string('status', 30)->default('pending');
            $table->boolean('tenant_has_unread')->default(false);
            $table->boolean('admin_has_unread')->default(true);
            $table->timestamp('last_reply_at')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['admin_has_unread']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};
