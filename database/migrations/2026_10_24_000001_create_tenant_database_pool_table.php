<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_database_pool', function (Blueprint $table) {
            $table->id();
            $table->string('database_name')->unique();
            // Last tenant migration applied to the database; lets a claim skip `tenants:migrate` when it is current.
            $table->string('migrated_through');
            $table->string('claimed_by')->nullable()->unique();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['claimed_by', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_database_pool');
    }
};
