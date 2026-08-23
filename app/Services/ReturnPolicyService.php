<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\Tenant\Product as TenantProduct;
use App\Models\Tenant\Setting as TenantSetting;

/**
 * Resolves return-policy values (window days, non-returnable products, fee, conditions,
 * video-required reasons) from either the admin (central catalog) or tenant (own products)
 * settings, so both ReturnRequestService and ReturnRequestValidationService read from a
 * single, consistent source instead of hardcoded constants.
 */
class ReturnPolicyService
{
    public const DEFAULT_WINDOW_DAYS = 14;

    private const ADMIN_KEYS = [
        'window_days' => 'return_policy_window_days',
        'non_returnable_ids' => 'return_policy_non_returnable_ids',
        'fee' => 'return_policy_fee',
        'conditions' => 'return_policy_conditions',
        'video_required_reasons' => 'return_policy_video_required_reasons',
    ];

    private const TENANT_KEYS = [
        'window_days' => 'return_policy_window_days',
        'non_returnable_ids' => 'return_policy_non_returnable_ids',
        'fee' => 'return_policy_fee',
        'conditions' => 'return_policy_conditions',
        'video_required_reasons' => 'return_policy_video_required_reasons',
    ];

    public function getAdminPolicy(): array
    {
        $settings = AppSetting::query()->whereIn('key', array_values(self::ADMIN_KEYS))->get()->keyBy('key');

        return $this->buildPolicy(fn (string $key) => $settings->get(self::ADMIN_KEYS[$key])?->value);
    }

    public function getTenantPolicy(string $tenantId): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return $this->defaultPolicy();
        }

        // Do not call tenancy()->end() here — mirrors ReturnRequestService::deliveredAt's
        // convention of leaving the tenant context initialized for the calling request.
        tenancy()->initialize($tenant);

        $rows = TenantSetting::query()->where('group', 'return_policy')->get()->keyBy('name');

        return $this->buildPolicy(fn (string $key) => $rows->get(self::TENANT_KEYS[$key])?->value);
    }

    public function defaultPolicy(): array
    {
        return [
            'window_days' => self::DEFAULT_WINDOW_DAYS,
            'non_returnable_ids' => [],
            'fee' => 0.0,
            'conditions' => '',
            'video_required_reasons' => [],
        ];
    }

    /**
     * Resolve the applicable policy for a return: admin policy when the order item's product is
     * sourced from the central catalog, tenant policy when it is the tenant's own product.
     */
    public function resolvePolicy(string $tenantId, ?int $productId = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return $this->defaultPolicy();
        }

        tenancy()->initialize($tenant);

        $isCentralProduct = true;

        if ($productId) {
            $product = TenantProduct::find($productId);
            $isCentralProduct = !$product || $product->central_product_id !== null;
        }

        return $isCentralProduct ? $this->getAdminPolicy() : $this->getTenantPolicy($tenantId);
    }

    /**
     * Resolve the effective return policy for a specific product, layering a tenant's own
     * per-product override (if enabled) on top of the store/admin policy resolved by
     * resolvePolicy(). Central-catalog products stay fully admin-controlled — no per-product
     * override is possible for those, since the tenant doesn't own the product record.
     *
     * @return array{window_days:int, non_returnable_ids:int[], fee:float, conditions:string, video_required_reasons:array, is_returnable:bool, video_required:bool}
     */
    public function resolveProductPolicy(string $tenantId, ?int $productId = null): array
    {
        $basePolicy = $this->resolvePolicy($tenantId, $productId);

        if (!$productId) {
            return $this->mergeDefaults($basePolicy);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return $this->mergeDefaults($basePolicy);
        }

        tenancy()->initialize($tenant);

        $product = TenantProduct::withoutGlobalScopes()->find($productId);

        $isOwnProduct = $product && $product->central_product_id === null;

        if (!$isOwnProduct || !$product->return_policy_override) {
            return $this->mergeDefaults($basePolicy, [
                'is_returnable' => !in_array($productId, $basePolicy['non_returnable_ids'], true),
            ]);
        }

        return [
            'window_days' => $product->return_window_days ?? $basePolicy['window_days'],
            'non_returnable_ids' => $basePolicy['non_returnable_ids'],
            'fee' => $product->return_fee !== null ? (float) $product->return_fee : $basePolicy['fee'],
            'conditions' => $product->return_conditions ?: $basePolicy['conditions'],
            'video_required_reasons' => $basePolicy['video_required_reasons'],
            'is_returnable' => (bool) $product->is_returnable,
            'video_required' => (bool) $product->return_video_required,
        ];
    }

    private function mergeDefaults(array $basePolicy, array $extras = []): array
    {
        return array_merge($basePolicy, [
            'is_returnable' => $extras['is_returnable'] ?? true,
            'video_required' => $extras['video_required'] ?? false,
        ]);
    }

    private function buildPolicy(\Closure $read): array
    {
        return [
            'window_days' => (int) ($read('window_days') ?: self::DEFAULT_WINDOW_DAYS),
            'non_returnable_ids' => $this->decodeArray($read('non_returnable_ids')),
            'fee' => (float) ($read('fee') ?: 0),
            'conditions' => $read('conditions') ?: '',
            'video_required_reasons' => $this->decodeArray($read('video_required_reasons')),
        ];
    }

    private function decodeArray(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
