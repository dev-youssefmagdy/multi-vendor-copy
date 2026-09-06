<?php

namespace App\Services;

use App\Models\Product;

/**
 * Notifies tenants whose selected category tree covers a product's
 * categories, even when they have no explicit ProductTenantAssignment.
 */
class ProductCategoryNotificationService
{
    public function __construct(
        private readonly TenantCategoryMatchService $matcher,
        private readonly TenantNotificationService $notifier,
    ) {}

    /**
     * @param  int[]  $previousCatIds  Category IDs the product had before this save
     * @param  string[]  $alreadyNotifiedIds  Tenant IDs already notified via explicit assignment
     */
    public function notifyForProduct(
        Product $product,
        bool $isNew,
        array $previousCatIds = [],
        array $alreadyNotifiedIds = [],
    ): void {
        if (!$product->isVisibleToTenants()) {
            return;
        }

        $currentCatIds = $product->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();

        if (empty($currentCatIds)) {
            return;
        }

        if (!$isNew && empty(array_diff($currentCatIds, $previousCatIds))) {
            return;
        }

        $matchingTenantIds = $this->matcher->tenantsMatchingCategories($currentCatIds);

        if (empty($matchingTenantIds)) {
            return;
        }

        $productName = $product->translationValue('name') ?: $product->sku;
        $alreadyNotified = array_map('strval', $alreadyNotifiedIds);

        foreach ($matchingTenantIds as $tenantId) {
            if (in_array((string) $tenantId, $alreadyNotified, true)) {
                continue;
            }

            if ($isNew) {
                $this->notifier->notifyById(
                    tenantId: (string) $tenantId,
                    type: 'product',
                    title: 'New Product Available',
                    message: "A new product \"{$productName}\" has been added to your catalog categories.",
                    data: [
                        'product_id' => $product->id,
                        'product_name' => $productName,
                        'category_ids' => $currentCatIds,
                    ],
                );
            } else {
                $this->notifier->notifyById(
                    tenantId: (string) $tenantId,
                    type: 'product',
                    title: 'Product Updated in Your Categories',
                    message: "The product \"{$productName}\" has been updated and matches your store categories.",
                    data: [
                        'product_id' => $product->id,
                        'product_name' => $productName,
                    ],
                );
            }
        }
    }

    /**
     * @param  string[]  $alreadyNotifiedIds
     */
    public function notifyForVariants(
        Product $product,
        int $newVariantCount,
        int $prevVariantCount,
        array $alreadyNotifiedIds = [],
    ): void {
        if ($newVariantCount <= $prevVariantCount || !$product->isVisibleToTenants()) {
            return;
        }

        $currentCatIds = $product->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();

        if (empty($currentCatIds)) {
            return;
        }

        $matchingTenantIds = $this->matcher->tenantsMatchingCategories($currentCatIds);

        if (empty($matchingTenantIds)) {
            return;
        }

        $productName = $product->translationValue('name') ?: $product->sku;
        $addedCount = $newVariantCount - $prevVariantCount;
        $alreadyNotified = array_map('strval', $alreadyNotifiedIds);

        foreach ($matchingTenantIds as $tenantId) {
            if (in_array((string) $tenantId, $alreadyNotified, true)) {
                continue;
            }

            $this->notifier->notifyById(
                tenantId: (string) $tenantId,
                type: 'product',
                title: 'New Variants Available',
                message: "{$addedCount} new variant(s) were added to \"{$productName}\" in your catalog.",
                data: [
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'added_variants' => $addedCount,
                ],
            );
        }
    }
}
