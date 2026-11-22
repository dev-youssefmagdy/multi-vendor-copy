<?php

namespace App\Services\Catalog;

use App\Enums\ProductEditRequestStatus;
use App\Models\ProductEditRequest;
use App\Models\Tenant;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\Catalog\FieldOverrideStore;
use App\Services\TenantNotificationService;
use App\Support\CacheVersion;
use App\Support\Catalog\EditableCentralFields;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Central admin review of vendor product edit requests (dedup plan §4.2a), replacing the logic of the old Livewire
 * `Admin\Product\ProductEditRequestsList`:
 *
 *  - approve: upsert ONE `tenant_field_overrides` row per changed, whitelisted (field, locale) pair
 *    (+ central_value_at_approval, edit_request_id, approved_at/by) and write just those pairs into the tenant
 *    translations (products are still synced; the sync keeps only overridden pairs, every other field/locale keeps
 *    following central). No more "has_custom_translations freezes all 6 fields".
 *  - reset: delete the override rows (of this request / one field[+locale] / all of the product) and write central's
 *    CURRENT value back into those tenant pairs (incl. central edits made after the request).
 *  - reject: unchanged.
 *
 * `has_custom_translations` is kept as a derived flag (= the product has override rows; phase 7 drops it). A product
 * still frozen by the legacy flag is first converted by LegacyFieldOverrideMigrator, so an older approval survives.
 * Before the tenant DB has the table (tenants:migrate not run) approve/reset write the translation pairs only and keep
 * the legacy flag (old behaviour minus the full-set rewrite that dropped other fields / inactive languages).
 */
final class ProductEditRequestReview
{
    public const SCOPE_REQUEST = 'request';
    public const SCOPE_FIELD = 'field';
    public const SCOPE_ALL = 'all';

    public function __construct(private readonly TenantNotificationService $notifications)
    {
    }

    /**
     * Only the fields that actually differ: [locale => [field => ['current' => , 'requested' => ]]].
     *
     * @return array<string, array<string, array{current: mixed, requested: mixed}>>
     */
    public function changedFields(ProductEditRequest $request): array
    {
        $changes = [];

        foreach ($request->requested_translations ?? [] as $locale => $fields) {
            foreach ((array) $fields as $field => $requested) {
                $current = data_get($request->current_translations, "$locale.$field");

                if ((string) $current !== (string) $requested) {
                    $changes[$locale][$field] = ['current' => $current, 'requested' => $requested];
                }
            }
        }

        return $changes;
    }

    /** @return string message for the admin */
    public function approve(ProductEditRequest $request, ?int $adminId): string
    {
        if ($request->status !== ProductEditRequestStatus::Pending) {
            throw new RuntimeException('This request has already been reviewed.');
        }

        $tenant = $this->tenant($request);
        $changes = $this->changedFields($request);

        $this->inTenant($tenant, function (FieldOverrideStore $store) use ($request, $changes, $adminId) {
            $product = $this->product($request);
            if ($product === null) {
                return;
            }

            $type = EditableCentralFields::CENTRAL_PRODUCT;
            $centralId = $this->centralId($product);

            if ($centralId !== null && $store->ready()) {
                $this->migrateLegacy($store, $product, $request->tenant_id);
                $central = $store->centralProductValues([$centralId], EditableCentralFields::for($type))[$centralId] ?? [];

                foreach ($changes as $locale => $fields) {
                    foreach ($fields as $field => $values) {
                        if (EditableCentralFields::allows($type, $field)) {
                            $store->put($type, $centralId, $field, (string) $locale, (string) $values['requested'],
                                $central[$locale][$field] ?? null, (int) $request->id, now(), $adminId);
                        }
                    }
                }
            }

            foreach ($changes as $locale => $fields) {
                foreach ($fields as $field => $values) {
                    if (EditableCentralFields::allows($type, $field)) {
                        $store->putTenantTranslation($product->getMorphClass(), (int) $product->id, (string) $locale, $field, (string) $values['requested']);
                    }
                }
            }

            $product->forceFill(['has_custom_translations' => true])->saveQuietly();
            CacheVersion::bumpMany(['Product', 'Translation']);
        });

        $request->update([
            'status' => ProductEditRequestStatus::Approved,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);

        $this->notifications->notify(
            $tenant,
            'product_edit_approved',
            'Product Edit Approved',
            "Your edit request for product \"{$request->product_slug}\" has been approved and the changes are now live.",
        );

        return 'Request approved: only the changed fields now override the central product.';
    }

    public function reject(ProductEditRequest $request, ?string $note, ?int $adminId): string
    {
        if ($request->status !== ProductEditRequestStatus::Pending) {
            throw new RuntimeException('This request has already been reviewed.');
        }

        $request->update([
            'status' => ProductEditRequestStatus::Rejected,
            'admin_note' => filled($note) ? $note : null,
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);

        $tenant = Tenant::query()->find($request->tenant_id);
        if ($tenant) {
            $noteText = filled($note) ? " Reason: {$note}" : '';
            $this->notifications->notify(
                $tenant,
                'product_edit_rejected',
                'Product Edit Rejected',
                "Your edit request for product \"{$request->product_slug}\" has been rejected.{$noteText}",
            );
        }

        return 'Request rejected.';
    }

    /**
     * Back to the live central value for: the pairs of this request (SCOPE_REQUEST), one field (+ optional locale,
     * SCOPE_FIELD) or every override of the product (SCOPE_ALL).
     */
    public function reset(ProductEditRequest $request, string $scope = self::SCOPE_REQUEST, ?string $field = null, ?string $locale = null): string
    {
        if ($request->status !== ProductEditRequestStatus::Approved) {
            throw new RuntimeException('Only approved requests can be reset.');
        }
        if (! in_array($scope, [self::SCOPE_REQUEST, self::SCOPE_FIELD, self::SCOPE_ALL], true)) {
            throw new RuntimeException('Unknown reset scope.');
        }
        if ($scope === self::SCOPE_FIELD && ! EditableCentralFields::allows(EditableCentralFields::CENTRAL_PRODUCT, (string) $field)) {
            throw new RuntimeException('Choose a field to reset.');
        }

        $tenant = $this->tenant($request);

        $count = $this->inTenant($tenant, function (FieldOverrideStore $store) use ($request, $scope, $field, $locale) {
            $product = $this->product($request);
            if ($product === null) {
                return 0;
            }

            $type = EditableCentralFields::CENTRAL_PRODUCT;
            $centralId = $this->centralId($product);
            $morph = $product->getMorphClass();

            if ($centralId === null || ! $store->ready()) {
                // Legacy path (tenant DB not migrated yet): restore the values recorded at request time, as before.
                foreach ((array) $request->current_translations as $loc => $fields) {
                    foreach ((array) $fields as $f => $value) {
                        if (EditableCentralFields::allows($type, $f)) {
                            $store->putTenantTranslation($morph, (int) $product->id, (string) $loc, $f, (string) $value);
                        }
                    }
                }
                $product->forceFill(['has_custom_translations' => false])->saveQuietly();
                CacheVersion::bumpMany(['Product', 'Translation']);

                return count((array) $request->current_translations);
            }

            $this->migrateLegacy($store, $product, $request->tenant_id);

            $pairs = match ($scope) {
                self::SCOPE_ALL => $store->forget($type, $centralId),
                self::SCOPE_FIELD => $store->forget($type, $centralId, $field, $locale),
                default => $this->forgetRequestPairs($store, $request, $centralId),
            };

            $central = $store->centralProductValues([$centralId], EditableCentralFields::for($type))[$centralId] ?? [];
            foreach ($pairs as $pair) {
                if ($pair['locale'] !== null) {
                    $store->putTenantTranslation($morph, (int) $product->id, $pair['locale'], $pair['field'], $central[$pair['locale']][$pair['field']] ?? '');
                }
            }

            $product->forceFill(['has_custom_translations' => $store->has($type, $centralId)])->saveQuietly();
            CacheVersion::bumpMany(['Product', 'Translation']);

            return count($pairs);
        });

        return $count > 0
            ? "Reset {$count} field(s) to the live central value."
            : 'Nothing to reset: no override is stored for that selection.';
    }

    /**
     * Active overrides of the request's product with a "central changed since approval" flag (review screen).
     *
     * @return list<array<string, mixed>>
     */
    public function overrides(ProductEditRequest $request): array
    {
        $tenant = Tenant::query()->find($request->tenant_id);
        if ($tenant === null) {
            return [];
        }

        return $this->inTenant($tenant, function (FieldOverrideStore $store) use ($request) {
            $product = $this->product($request);
            $centralId = $product ? $this->centralId($product) : null;
            if ($centralId === null) {
                return [];
            }

            $type = EditableCentralFields::CENTRAL_PRODUCT;
            $central = $store->centralProductValues([$centralId], EditableCentralFields::for($type))[$centralId] ?? [];

            return $store->rowsFor($type, $centralId)->map(fn (object $row) => [
                'field' => $row->field,
                'locale' => $row->locale,
                'value' => $row->value,
                'central_value' => $central[$row->locale][$row->field] ?? null,
                'central_changed' => trim((string) $row->central_value_at_approval) !== trim((string) ($central[$row->locale][$row->field] ?? '')),
                'this_request' => (int) $row->edit_request_id === (int) $request->id,
                'approved_at' => $row->approved_at,
            ])->values()->all();
        });
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /** @return list<array{field: string, locale: ?string}> */
    private function forgetRequestPairs(FieldOverrideStore $store, ProductEditRequest $request, int $centralId): array
    {
        $type = EditableCentralFields::CENTRAL_PRODUCT;
        $pairs = $store->forget($type, $centralId, editRequestId: (int) $request->id);

        // Rows migrated from a legacy approval may not carry the request id: match them by (field, locale) too.
        foreach ((array) $request->requested_translations as $locale => $fields) {
            foreach (array_keys((array) $fields) as $field) {
                if (EditableCentralFields::allows($type, $field)) {
                    array_push($pairs, ...$store->forget($type, $centralId, $field, (string) $locale));
                }
            }
        }

        return array_values(array_unique($pairs, SORT_REGULAR));
    }

    private function migrateLegacy(FieldOverrideStore $store, Product $product, string $tenantId): void
    {
        if (! $product->has_custom_translations || $store->has(EditableCentralFields::CENTRAL_PRODUCT, (int) $this->centralId($product))) {
            return;
        }

        // The admin is acting on this product now: keep stale pairs as overrides (no silent revert; resettable here).
        app(LegacyFieldOverrideMigrator::class)->migrate([$product], LegacyFieldOverrideMigrator::approvedRequests($tenantId), stale: LegacyFieldOverrideMigrator::STALE_KEEP);
    }

    private function tenant(ProductEditRequest $request): Tenant
    {
        return Tenant::query()->find($request->tenant_id) ?? throw new RuntimeException('Tenant not found.');
    }

    private function product(ProductEditRequest $request): ?Product
    {
        // Also centrally hidden products (draft/archived): the request is still about them.
        return Product::withoutGlobalScope('centralVisible')->find($request->product_id);
    }

    private function centralId(Product $product): ?int
    {
        return $product->central_product_id !== null && ! $product->is_own_product ? (int) $product->central_product_id : null;
    }

    /**
     * Runs $callback inside the tenant (one tenant transaction), always ending tenancy.
     *
     * @template T
     * @param  callable(FieldOverrideStore): T  $callback
     * @return T
     */
    private function inTenant(Tenant $tenant, callable $callback): mixed
    {
        try {
            tenancy()->initialize($tenant);

            return DB::connection('tenant')->transaction(fn () => $callback(new FieldOverrideStore()));
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to apply changes: ' . $e->getMessage(), 0, $e);
        } finally {
            tenancy()->end();
        }
    }
}
