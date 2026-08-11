<?php

namespace App\Services;

use App\Enums\WalletTransactionDirection;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant;
use App\Models\Tenant\LanguagePurchase;
use App\Models\Tenant\Transaction;
use App\Models\TenantLanguagePurchase;
use App\Services\Tenant\TenantCatalogSyncService;
use Illuminate\Support\Str;

class LanguagePurchaseService
{
    public function __construct(protected TenantCatalogSyncService $syncService)
    {
    }

    /**
     * Check whether the current tenant has already purchased the given central language.
     */
    public function tenantHasPurchased(Tenant $tenant, int $centralLanguageId): bool
    {
        return TenantLanguagePurchase::query()
            ->where('tenant_id', $tenant->id)
            ->where('central_language_id', $centralLanguageId)
            ->exists();
    }

    /**
     * Retrieve all paid languages that the given tenant has NOT yet purchased.
     */
    public function availableForPurchase(Tenant $tenant): \Illuminate\Support\Collection
    {
        $purchasedIds = TenantLanguagePurchase::query()
            ->where('tenant_id', $tenant->id)
            ->pluck('central_language_id')
            ->all();

        return CentralLanguage::query()
            ->where('is_free', false)
            ->where('is_active', true)
            ->whereNotIn('id', $purchasedIds)
            
            ->get();
    }

    /**
     * Complete a language purchase after successful payment.
     *
     * Creates a Transaction + LanguagePurchase in the tenant DB,
     * records a TenantLanguagePurchase in the central DB,
     * then triggers a language sync so the tenant gets access immediately.
     */
    public function completePurchase(
        Tenant $tenant,
        CentralLanguage $language,
        array $payment
    ): LanguagePurchase {
        // Prevent duplicate purchases
        if ($this->tenantHasPurchased($tenant, $language->id)) {
            return LanguagePurchase::query()
                ->where('central_language_id', $language->id)
                ->firstOrFail();
        }

        tenancy()->initialize($tenant);

        try {
            $transactionUuid = (string) Str::uuid();

            $transaction = Transaction::query()->create([
                'uuid' => $transactionUuid,
                'amount' => (float) $payment['amount'],
                'type' => WalletTransactionDirection::Credit->value,
                'admin_profit' => null,
                'description' => sprintf(
                    'Language purchase: %s via %s',
                    $language->name,
                    str((string) ($payment['gateway_code'] ?? 'unknown'))->replace(['_', '-'], ' ')->headline()->toString()
                ),
            ]);

            $purchase = LanguagePurchase::query()->create([
                'central_language_id' => $language->id,
                'language_code' => $language->code,
                'language_name' => $language->name,
                'amount' => (float) $payment['amount'],
                'gateway_code' => $payment['gateway_code'] ?? null,
                'transaction_uuid' => $transactionUuid,
                'transaction_id' => $transaction->id,
            ]);

            $transaction->update([
                'model_type' => LanguagePurchase::class,
                'model_id' => $purchase->id,
            ]);
        } finally {
            tenancy()->end();
        }

        // Record in central DB so sync knows this tenant has purchased this language
        TenantLanguagePurchase::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'central_language_id' => $language->id],
            [
                'transaction_uuid' => $transactionUuid ?? null,
                'amount' => (float) $payment['amount'],
                'gateway_code' => $payment['gateway_code'] ?? null,
            ]
        );

        // Sync languages for this tenant so the newly purchased language appears immediately
        $this->syncService->syncForTenant($tenant, ['languages']);

        return $purchase;
    }
}
