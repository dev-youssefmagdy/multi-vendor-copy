<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_template_id');
            $table->string('locale', 10);
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->timestamps();

            $table->unique(['email_template_id', 'locale']);
            $table->foreign('email_template_id')->references('id')->on('email_templates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_translations');
    }
};
