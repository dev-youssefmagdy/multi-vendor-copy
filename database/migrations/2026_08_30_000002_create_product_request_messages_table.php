<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_request_id')
                  ->constrained('product_requests')
                  ->cascadeOnDelete();
            $table->string('sender_type', 10);
            $table->string('sender_name');
            $table->text('body');
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index('product_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_request_messages');
    }
};
