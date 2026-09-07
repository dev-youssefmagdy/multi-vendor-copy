<?php

namespace App\Services\Tenant;

use App\Models\Package;
use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Category;
use App\Models\Tenant\Language;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\Banner;

class PlanLimitService
{
    public const FEATURE_PRODUCTS = 'products';
    public const FEATURE_CATEGORIES = 'categories';
    public const FEATURE_BANNERS = 'banners';
    public const FEATURE_LANGUAGES = 'languages';
    public const FEATURE_ORDERS_PER_MONTH = 'orders_per_month';
    public const FEATURE_AI_CALLS = 'ai_calls';
    public const FEATURE_IMAGE_SEARCHES = 'image_searches';

    public const FEATURE_LABELS = [
        self::FEATURE_PRODUCTS => 'products',
        self::FEATURE_CATEGORIES => 'categories',
        self::FEATURE_BANNERS => 'banners',
        self::FEATURE_LANGUAGES => 'languages',
        self::FEATURE_ORDERS_PER_MONTH => 'orders this month',
        self::FEATURE_AI_CALLS => 'AI calls',
        self::FEATURE_IMAGE_SEARCHES => 'image searches',
    ];

    private const LIMIT_COLUMNS = [
        self::FEATURE_PRODUCTS => 'products_limit',
        self::FEATURE_CATEGORIES => 'categories_count',
        self::FEATURE_BANNERS => 'banners_limit',
        self::FEATURE_LANGUAGES => 'languages_limit',
        self::FEATURE_ORDERS_PER_MONTH => 'orders_per_month_limit',
        self::FEATURE_AI_CALLS => 'ai_calls_limit',
        self::FEATURE_IMAGE_SEARCHES => 'image_searches_limit',
    ];

    /**
     * Whether the tenant may perform another create action for the given feature.
     */
    public function canPerform(TenantModel $tenant, string $feature): bool
    {
        $limit = $this->limit($tenant, $feature);

        if ($limit === null) {
            return true;
        }

        return $this->usedCount($tenant, $feature) < $limit;
    }

    public function errorMessage(string $feature): string
    {
        $label = self::FEATURE_LABELS[$feature] ?? $feature;

        return "You have reached your plan limit for {$label}. Please upgrade your plan.";
    }

    /**
     * Whether the tenant's plan includes the AI Translation add-on.
     * This is a boolean feature flag, not a numeric usage limit, so it is
     * resolved directly here rather than through the LIMIT_COLUMNS pattern.
     */
    public function aiTranslationEnabled(TenantModel $tenant): bool
    {
        $package = $this->package($tenant);

        return (bool) ($package?->ai_translation_enabled ?? false);
    }

    public function aiTranslationErrorMessage(): string
    {
        return 'AI Translation is a paid add-on. Upgrade your plan to enable it.';
    }

    /**
     * The numeric limit for a feature, or null when unlimited.
     */
    public function limit(TenantModel $tenant, string $feature): ?int
    {
        $package = $this->package($tenant);
        if (!$package) {
            return null;
        }

        $column = self::LIMIT_COLUMNS[$feature] ?? null;
        if (!$column) {
            return null;
        }

        $value = (int) $package->{$column};

        // `categories_count` predates the -1 convention and uses 0 = unlimited.
        if ($feature === self::FEATURE_CATEGORIES) {
            return $value <= 0 ? null : $value;
        }

        return $value < 0 ? null : $value;
    }

    public function usedCount(TenantModel $tenant, string $feature): int
    {
        return match ($feature) {
            self::FEATURE_PRODUCTS => Product::query()->count(),
            self::FEATURE_CATEGORIES => Category::query()->whereNull('parent_id')->count(),
            self::FEATURE_BANNERS => Banner::query()->count(),
            self::FEATURE_LANGUAGES => $this->activePlanLanguagesCount(),
            self::FEATURE_ORDERS_PER_MONTH => Order::query()
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            self::FEATURE_AI_CALLS => $this->counter($tenant, 'ai_calls_count'),
            self::FEATURE_IMAGE_SEARCHES => $this->counter($tenant, 'image_searches_count'),
            default => 0,
        };
    }

    /**
     * Usage summary for display purposes: ['used' => int, 'limit' => int|null].
     */
    public function usage(TenantModel $tenant, string $feature): array
    {
        return [
            'used' => $this->usedCount($tenant, $feature),
            'limit' => $this->limit($tenant, $feature),
        ];
    }

    /**
     * Increment a generic usage counter stored on the central tenant record.
     * Used for features (AI calls, image searches) with no dedicated table to count from.
     */
    public function incrementCounter(TenantModel $tenant, string $key): void
    {
        tenancy()->central(function () use ($tenant, $key) {
            $record = TenantModel::query()->find($tenant->getTenantKey());
            if (!$record) {
                return;
            }

            $data = $record->data ?? [];
            $data[$key] = (int) ($data[$key] ?? 0) + 1;
            $record->update(['data' => $data]);
        });
    }

    private function counter(TenantModel $tenant, string $key): int
    {
        return (int) (tenancy()->central(
            fn() => TenantModel::query()->find($tenant->getTenantKey())?->data[$key] ?? 0
        ) ?? 0);
    }

    /**
     * Active languages counted against the plan quota, excluding separately
     * purchased paid language add-ons (those are already paid for individually).
     */
    private function activePlanLanguagesCount(): int
    {
        $purchasedCentralIds = \App\Models\Tenant\LanguagePurchase::query()
            ->pluck('central_language_id')
            ->filter()
            ->all();

        return Language::query()
            ->where('is_active', true)
            ->when($purchasedCentralIds !== [], fn($q) => $q->whereNotIn('central_language_id', $purchasedCentralIds))
            ->count();
    }

    private function package(TenantModel $tenant): ?Package
    {
        return tenancy()->central(function () use ($tenant) {
            $record = TenantModel::query()->find($tenant->getTenantKey());

            return $record?->package;
        });
    }
}
