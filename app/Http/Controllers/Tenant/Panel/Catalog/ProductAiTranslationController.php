<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Exceptions\AiServiceException;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Catalog\AiTranslateProductRequest;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Services\Tenant\ProductAiTranslationService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * "Translate with AI" on the vendor product forms (own products + platform-catalog products).
 * Returns a translation preview for one target language; nothing is saved until the vendor
 * saves the product. Paid per generation: see ProductAiTranslationService::authorizationError()
 * / chargeFor(). The bulk AI Translation purchase (Settings → AI Translation) is neither required
 * nor touched.
 */
final class ProductAiTranslationController extends PanelController
{
    public function __construct(
        private readonly ProductAiTranslationService $translator,
    ) {
    }

    public function translate(AiTranslateProductRequest $request): JsonResponse
    {
        $tenant = tenant();

        // Paid feature: authorisation + charge live in ProductAiTranslationService (one place).
        if ($error = $this->translator->authorizationError($tenant)) {
            return $this->failure($error);
        }

        $validated = $request->validated();
        $target = Language::query()->where('code', $validated['target_locale'])->firstOrFail();
        $source = $this->sourceLanguage($validated['source_locale'] ?? null, $target);

        if (!$source) {
            return $this->failure(
                __('Choose a language other than the store default language to translate into.'),
                errors: ['target_locale' => [__('Choose a language other than the store default language to translate into.')]],
            );
        }

        $product = isset($validated['product_id']) ? Product::query()->find($validated['product_id']) : null;
        $sourceText = $this->translator->resolveSource($validated['fields'], (array) ($validated['source'] ?? []), $product, (string) $source->code);

        if ($sourceText === []) {
            return $this->failure(__('Add the product text in :language first, then translate it with AI.', ['language' => $source->name]));
        }

        try {
            $translations = $this->translator->translate($sourceText, $source, $target, (bool) ($validated['regenerate'] ?? false));
        } catch (\Throwable $e) {
            // Technical details go to the log; the vendor gets the reason in plain words.
            Log::warning('AI product translation failed', [
                'tenant_id' => $tenant?->getTenantKey(),
                'product_id' => $product?->id,
                'source_locale' => $source->code,
                'target_locale' => $target->code,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return $this->failure($this->friendlyError($e));
        }

        // Charged only after a successful generation (Regenerate counts as a new one).
        $this->translator->chargeFor($tenant);

        return $this->success(__('AI translation is ready. Review it, then save the product to keep it.'), [
            'source_locale' => $source->code,
            'target_locale' => $target->code,
            'translations' => $translations,
            'remaining' => $this->translator->usageSummary($tenant)['remaining'],
        ]);
    }

    /** The requested source language, else the store default language, else another active language. */
    private function sourceLanguage(?string $code, Language $target): ?Language
    {
        $query = Language::query()->where('is_active', true)->where('code', '!=', $target->code);

        if ($code) {
            return (clone $query)->where('code', $code)->first();
        }

        return (clone $query)->where('is_default', true)->first()
            ?? $query->orderBy('sort_order')->first();
    }

    private function friendlyError(\Throwable $e): string
    {
        if ($e instanceof RequestException && $e->response) {
            $status = $e->response->status();
            $reason = $status === 401
                ? new AiServiceException('ai_auth_failed')
                : AiServiceException::fromResponse($status, null, (string) $e->response->body());

            if (in_array($reason->reason, ['ai_quota_exhausted', 'ai_auth_failed', 'ai_rate_limited'], true)) {
                return $reason->userMessage();
            }
        }

        return __('We couldn’t translate this product right now. Please try again in a few minutes. If the problem continues, contact support.');
    }
}
