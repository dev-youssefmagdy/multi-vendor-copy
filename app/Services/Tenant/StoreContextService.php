<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Category;
use Illuminate\Support\Str;

/**
 * Builds a brand/store context string injected into OpenAI translation
 * prompts so translations read as on-brand for the specific store rather
 * than generic ecommerce copy.
 */
class StoreContextService
{
    public function build(?string $targetLanguageName = null): string
    {
        $tenant = tenant();

        if (!$tenant) {
            return $targetLanguageName
                ? "Professional ecommerce store. Target language: {$targetLanguageName}."
                : 'Professional ecommerce store.';
        }

        $shopName = $tenant->shop_name ?? $tenant->name ?? 'the store';
        $description = $tenant->description ?? null;

        $categoryNames = Category::query()
            ->whereNull('parent_id')
            ->where('active', true)
            ->limit(5)
            ->get()
            ->map(fn (Category $category) => $category->translationValue('name') ?? $category->slug ?? null)
            ->filter()
            ->implode(', ');

        $parts = ["Store name: \"{$shopName}\"."];

        if (filled($description)) {
            $parts[] = 'Store description: ' . Str::limit((string) $description, 200);
        }

        if ($categoryNames !== '') {
            $parts[] = "Product categories: {$categoryNames}.";
        }

        if ($targetLanguageName) {
            $parts[] = "Target language: {$targetLanguageName}.";
        }

        $parts[] = "Translations must feel natural and on-brand for this specific store.";

        return implode(' ', $parts);
    }
}
