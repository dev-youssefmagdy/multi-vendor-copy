<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ProductCategoryNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyTenantsForProductCategories implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  int[]  $previousCatIds
     * @param  string[]  $alreadyNotifiedIds
     */
    public function __construct(
        public readonly int $productId,
        public readonly bool $isNew,
        public readonly array $previousCatIds,
        public readonly int $prevVariantCount,
        public readonly array $alreadyNotifiedIds,
    ) {}

    public function handle(ProductCategoryNotificationService $service): void
    {
        $product = Product::with('categories')->find($this->productId);

        if (!$product) {
            return;
        }

        $service->notifyForProduct(
            product: $product,
            isNew: $this->isNew,
            previousCatIds: $this->previousCatIds,
            alreadyNotifiedIds: $this->alreadyNotifiedIds,
        );

        $service->notifyForVariants(
            product: $product,
            newVariantCount: $product->variants()->count(),
            prevVariantCount: $this->prevVariantCount,
            alreadyNotifiedIds: $this->alreadyNotifiedIds,
        );
    }
}
