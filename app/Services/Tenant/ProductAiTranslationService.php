<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant as TenantModel;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Services\OpenAiTranslationService;
use RuntimeException;

/**
 * Per-product "Translate with AI" (vendor product forms): translates one product's
 * text fields from a source language into a single target language through the same
 * OpenAiTranslationService + StoreContextService path the bulk store translation uses.
 *
 * Nothing is saved here: the result is returned to the form as a preview and is only
 * stored when the vendor saves the product as usual.
 */
class ProductAiTranslationService
{
    /** Product text fields the vendor forms can translate (subset of StoreTranslatorService::MODEL_FIELDS). */
    public const FIELDS = ['name', 'label', 'summary', 'description', 'meta_keywords', 'meta_description'];

    /** Temperature for "Regenerate" so the vendor gets a different wording (bulk runs use 0.1). */
    public const REGENERATE_TEMPERATURE = 0.7;

    public function __construct(
        protected OpenAiTranslationService $openAi,
        protected StoreContextService $storeContext,
        protected PlanLimitService $limitService,
    ) {
    }

    /*
     * ── Billing: the ONE place that authorises and charges a generation ─────────
     * Per-product AI translation is paid. Current model (pending the owner's final
     * decision): requires the package's AI translation add-on AND a free slot in the
     * plan's AI-calls quota; each successful generation (incl. Regenerate) consumes
     * one AI call. To switch to wallet charging / prepaid credits, change only
     * authorizationError(), chargeFor() and usageSummary(). The bulk AI Translation
     * purchase flow (TenantAiTranslationPurchase / TranslateStoreJob) is never used here.
     */

    /** Null when the tenant may generate a translation now, else a translated message for the vendor. */
    public function authorizationError(TenantModel $tenant): ?string
    {
        if (!$this->limitService->aiTranslationEnabled($tenant)) {
            return $this->limitService->aiTranslationErrorMessage();
        }

        if (!$this->limitService->canPerform($tenant, PlanLimitService::FEATURE_AI_CALLS)) {
            return __('You have used all the AI calls included in your plan. Upgrade your plan to continue.');
        }

        return null;
    }

    /** Charge one successful generation. Called only after the AI returned a result. */
    public function chargeFor(TenantModel $tenant): void
    {
        $this->limitService->incrementCounter($tenant, 'ai_calls_count');
    }

    /**
     * Remaining paid units for the UI next to the button: ['remaining' => int|null (null = unlimited)].
     *
     * @return array{remaining: int|null}
     */
    public function usageSummary(TenantModel $tenant): array
    {
        $usage = $this->limitService->usage($tenant, PlanLimitService::FEATURE_AI_CALLS);

        return ['remaining' => $usage['limit'] === null ? null : max(0, $usage['limit'] - $usage['used'])];
    }

    /**
     * Source text per field: what the vendor has in the form for the source language,
     * falling back to the product's saved source-language text. Empty fields are dropped.
     *
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $formSource
     * @return array<string, string>
     */
    public function resolveSource(array $fields, array $formSource, ?Product $product, string $sourceLocale): array
    {
        $fields = array_values(array_intersect(self::FIELDS, $fields));
        $saved = $product ? ($product->translationsByLocale($fields)[$sourceLocale] ?? []) : [];
        $source = [];

        foreach ($fields as $field) {
            $value = trim((string) ($formSource[$field] ?? ''));

            if ($this->isBlank($value)) {
                $value = trim((string) ($saved[$field] ?? ''));
            }

            if (!$this->isBlank($value)) {
                $source[$field] = $value;
            }
        }

        return $source;
    }

    /**
     * @param  array<string, string>  $source  field => source text (non-empty)
     * @return array<string, string>  field => translated text
     */
    public function translate(array $source, Language $sourceLanguage, Language $targetLanguage, bool $regenerate = false): array
    {
        if ($source === []) {
            return [];
        }

        if (!$this->openAi->configured()) {
            throw new RuntimeException('OpenAI translation is not configured.');
        }

        $context = $this->storeContext->build($targetLanguage->name, $sourceLanguage->name)
            . ' Content: one product listing of this store (name, short label, summary, HTML description, SEO meta keywords and description).'
            . ' Write natural, persuasive storefront copy for shoppers; keep brand names, model numbers and units unchanged.';

        if ($regenerate) {
            $context .= ' The vendor asked for an alternative version: use different wording from a literal translation while keeping the same meaning and facts.';
        }

        $translated = $this->openAi->translateStructuredBatch(
            [['id' => 1, 'translations' => $source]],
            strtolower((string) $sourceLanguage->code),
            strtolower((string) $targetLanguage->code),
            (string) $targetLanguage->name,
            $context,
            $regenerate ? self::REGENERATE_TEMPERATURE : null,
        );

        $row = $translated[1] ?? [];
        $result = [];

        foreach ($source as $field => $text) {
            $result[$field] = trim((string) ($row[$field] ?? $text));
        }

        return $result;
    }

    /** An empty editor still posts markup like "<p></p>" or "<p>&nbsp;</p>". */
    private function isBlank(string $value): bool
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5), " \t\n\r\0\x0B\u{00A0}") === '';
    }
}
