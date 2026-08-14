<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ImageSearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
class EmbedProductImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $productId)
    {
    }

    public function handle(ImageSearchService $imageSearchService): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            return;
        }

        $imageSearchService->backfillForProduct($product);
    }
}
