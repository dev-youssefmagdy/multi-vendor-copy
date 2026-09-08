<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
// Embeddings are stored as JSON and compared with brute-force cosine
// similarity in ImageSearchService until catalog size warrants an indexed
// vector store.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_image_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->string('source')->default('primary');
            $table->json('embedding');
            $table->unsignedInteger('dims')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'variant_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_image_embeddings');
    }
};
