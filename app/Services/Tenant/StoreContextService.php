<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\TenantCountry;
use Illuminate\Support\Str;

/**
 * Builds a brand/store context string injected into OpenAI translation
 * prompts so translations read as on-brand for the specific store rather
 * than generic ecommerce copy.
 */
class StoreContextService
{
    public function build(?string $targetLanguageName = null, ?string $sourceLanguageName = null): string
    {
        $tenant = tenant();

        if (!$tenant) {
            $parts = ['Professional ecommerce marketplace store.'];
            if ($targetLanguageName) {
                $parts[] = "Target language: {$targetLanguageName}.";
            }
            return implode(' ', $parts);
        }

        // ── Store identity ────────────────────────────────────────────────
        $shopName = $tenant->shop_name ?? $tenant->name ?? 'the store';

        $description = $tenant->data['store_description'] ?? $tenant->description ?? null;

        // ── Categories (top 8, root only) ───────────────────────────────────
        $categoryNames = Category::query()
            ->whereNull('parent_id')
            ->where('active', true)
            ->limit(8)
            ->get()
            ->map(fn (Category $category) => $category->translationValue('name') ?? $category->slug ?? null)
            ->filter()
            ->implode(', ');

        // ── Target sale countries ───────────────────────────────────────────
        $targetCountries = TenantCountry::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with('country:id,name')
            ->limit(10)
            ->get()
            ->map(fn (TenantCountry $tenantCountry) => $tenantCountry->country?->name)
            ->filter()
            ->implode(', ');

        // ── Active languages ─────────────────────────────────────────────────
        $activeLanguages = Language::query()
            ->where('is_active', true)
            ->pluck('name')
            ->implode(', ');

        // ── Build prompt context ────────────────────────────────────────────
        $parts = [
            "Store name: \"{$shopName}\".",
            'Platform: Neozena multi-vendor ecommerce marketplace.',
        ];

        if (filled($description)) {
            $parts[] = 'Store description: ' . Str::limit((string) $description, 300);
        }

        if ($categoryNames !== '') {
            $parts[] = "Main product categories: {$categoryNames}.";
        }

        if ($targetCountries !== '') {
            $parts[] = "This store sells to customers in: {$targetCountries}.";
        }

        if ($activeLanguages !== '') {
            $parts[] = "Store operates in these languages: {$activeLanguages}.";
        }

        if ($sourceLanguageName) {
            $parts[] = "Translating FROM: {$sourceLanguageName}.";
        }

        if ($targetLanguageName) {
            $parts[] = "Translating TO: {$targetLanguageName}.";
        }

        $parts[] = 'Translations must be natural, professional, and on-brand for this specific store and its target audience.';
        $parts[] = 'Preserve any brand names, product names, and store name without translating them.';
        $parts[] = 'For product descriptions: be persuasive and commercially appealing to the target market.';

        return implode(' ', $parts);
    }
}
