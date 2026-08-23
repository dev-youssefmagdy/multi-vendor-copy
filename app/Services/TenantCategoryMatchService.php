<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tenant;

/**
 * Resolves which tenants have category interest in a given set of central
 * product category IDs (leaf categories), based on each tenant's stored
 * root category_ids expanded down the tree — mirrors the expansion in
 * TenantCatalogSyncService::expandCategoryIds().
 */
class TenantCategoryMatchService
{
    /**
     * @param  int[]  $productCategoryIds  Central category IDs assigned to the product
     * @return string[]  Tenant IDs
     */
    public function tenantsMatchingCategories(array $productCategoryIds): array
    {
        if (empty($productCategoryIds)) {
            return [];
        }

        $ancestorAndSelfIds = $this->ancestorsAndSelf($productCategoryIds);

        if (empty($ancestorAndSelfIds)) {
            return [];
        }

        $matchingTenantIds = [];

        Tenant::query()
            ->whereNotNull('data->category_ids')
            ->get(['id', 'data'])
            ->each(function (Tenant $tenant) use ($ancestorAndSelfIds, &$matchingTenantIds) {
                $rootIds = array_map('intval', (array) ($tenant->category_ids ?? []));

                if (empty($rootIds)) {
                    return;
                }

                $expandedIds = $this->expandDownward($rootIds);

                if (array_intersect($ancestorAndSelfIds, $expandedIds)) {
                    $matchingTenantIds[] = $tenant->id;
                }
            });

        return $matchingTenantIds;
    }

    /**
     * Walk up from the given leaf category IDs, collecting each category and
     * all of its ancestors.
     *
     * @param  int[]  $leafIds
     * @return int[]
     */
    protected function ancestorsAndSelf(array $leafIds): array
    {
        $allIds  = array_values(array_unique(array_map('intval', $leafIds)));
        $toCheck = $allIds;

        while (!empty($toCheck)) {
            $parents = Category::query()
                ->whereIn('id', $toCheck)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $new    = array_diff($parents, $allIds);
            $allIds = array_merge($allIds, $new);
            $toCheck = $new;
        }

        return $allIds;
    }

    /**
     * Walk down from the given root IDs, collecting all descendants.
     * Mirrors TenantCatalogSyncService::expandCategoryIds().
     *
     * @param  int[]  $rootIds
     * @return int[]
     */
    protected function expandDownward(array $rootIds): array
    {
        $allIds = $rootIds;
        $check  = $rootIds;

        while (!empty($check)) {
            $children = Category::query()
                ->whereIn('parent_id', $check)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $new    = array_diff($children, $allIds);
            $allIds = array_merge($allIds, $new);
            $check  = $new;
        }

        return $allIds;
    }
}
