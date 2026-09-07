<?php

namespace App\Services;

use App\Jobs\Tenant\TranslateStoreJob;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant;
use App\Models\Tenant\Language as TenantLanguage;
use App\Models\TenantAiTranslationPurchase;
use App\Services\Tenant\PlanLimitService;
use Illuminate\Support\Collection;

class AiTranslationPurchaseService
{
    public function __construct(
        private readonly PlanLimitService $planLimitService,
    ) {
    }

    /**
     * Whether this tenant's plan and the language configuration allow
     * purchasing/running AI translation for a given central language.
     */
    public function canPurchase(Tenant $tenant, CentralLanguage $centralLanguage): bool
    {
        return $this->planLimitService->aiTranslationEnabled($tenant)
            && $centralLanguage->offersAiTranslation();
    }

    public function isFree(CentralLanguage $centralLanguage): bool
    {
        return $centralLanguage->aiTranslationIsFree();
    }

    /**
     * Record a purchase and dispatch the translation job. Pass an empty
     * $payment array for free languages.
     */
    public function completePurchase(Tenant $tenant, CentralLanguage $centralLanguage, array $payment = []): TenantAiTranslationPurchase
    {
        $record = TenantAiTranslationPurchase::create([
            'tenant_id' => $tenant->id,
            'central_language_id' => $centralLanguage->id,
            'amount' => $payment['amount'] ?? 0,
            'gateway_code' => $payment['gateway_code'] ?? null,
            'transaction_uuid' => $payment['transaction_uuid'] ?? null,
            'status' => 'pending',
        ]);

        tenancy()->initialize($tenant);
        $tenantLanguage = TenantLanguage::query()->where('central_language_id', $centralLanguage->id)->first();
        tenancy()->end();

        if ($tenantLanguage) {
            TranslateStoreJob::dispatch($tenant->id, $tenantLanguage->id)->onQueue('translations');
        }

        return $record;
    }

    public function markCompleted(string $tenantId, int $centralLanguageId): void
    {
        TenantAiTranslationPurchase::query()
            ->where('tenant_id', $tenantId)
            ->where('central_language_id', $centralLanguageId)
            ->where('status', 'pending')
            ->latest()
            ->first()
            ?->update(['status' => 'completed', 'translated_at' => now()]);
    }

    public function markFailed(string $tenantId, int $centralLanguageId): void
    {
        TenantAiTranslationPurchase::query()
            ->where('tenant_id', $tenantId)
            ->where('central_language_id', $centralLanguageId)
            ->where('status', 'pending')
            ->latest()
            ->first()
            ?->update(['status' => 'failed']);
    }

    /**
     * Central languages offering AI translation.
     */
    public function availableLanguages(Tenant $tenant): Collection
    {
        return CentralLanguage::query()
            ->whereNotNull('ai_translation_price')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function history(string $tenantId): Collection
    {
        return TenantAiTranslationPurchase::query()
            ->where('tenant_id', $tenantId)
            ->with('language')
            ->latest()
            ->get();
    }
}
