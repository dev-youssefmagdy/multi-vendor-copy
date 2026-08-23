<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use App\Services\AiTranslationPurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles the payment lifecycle for paid per-language AI translation runs,
 * mirroring App\Http\Controllers\Tenant\LanguagePaymentController.
 */
class AiTranslationPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly AiTranslationPurchaseService $purchaseService,
    ) {
    }

    public function charge(Request $request, string $gateway, int $languageId): RedirectResponse|View
    {
        /** @var \App\Models\Tenant\AdminUser $admin */
        $admin = auth('tenant')->user();

        $language = CentralLanguage::query()
            ->where('id', $languageId)
            ->whereNotNull('ai_translation_price')
            ->where('is_active', true)
            ->firstOrFail();

        $tenant = tenant();

        if (!$this->purchaseService->canPurchase($tenant, $language) || $this->purchaseService->isFree($language)) {
            return redirect()->route('tenant.settings.ai-translation')
                ->with('purchase_error', 'This AI translation cannot be purchased.');
        }

        $amount = (float) $language->ai_translation_price;

        $charge = PaymentCharge::fromArray([
            'amount' => $amount,
            'currency' => 'USD',
            'description' => "AI translation: {$language->name}",
            'order_id' => "ai-translate-{$language->id}-{$tenant->id}-" . now()->timestamp,
            'email' => $admin->email ?? 'tenant@example.com',
            'success_url' => route('tenant.ai-translation-purchase.success', $gateway) . '?language_id=' . $language->id,
            'cancel_url' => route('tenant.ai-translation-purchase.cancel', $gateway),
        ]);

        session([
            'tenant_ai_translation_pending_payment' => [
                'language_id' => $language->id,
                'gateway' => $gateway,
                'amount' => $amount,
            ],
        ]);

        try {
            $result = $this->manager->gateway($gateway)->charge($charge);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.settings.ai-translation')
                ->withErrors(['payment' => $e->getMessage()]);
        }

        if ($result->needsRedirect) {
            return redirect()->away($result->redirectUrl);
        }

        if ($result->needsView) {
            return view($result->viewName, $result->viewData);
        }

        if ($result->success) {
            return $this->handleSuccess($tenant, $language, $gateway, $amount, $result->transactionId);
        }

        return redirect()->route('tenant.settings.ai-translation')
            ->withErrors(['payment' => $result->errorMessage ?? 'Payment initiation failed. Please try again.']);
    }

    public function success(Request $request, string $gateway): RedirectResponse
    {
        try {
            $result = $this->manager->gateway($gateway)->verify($request);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.settings.ai-translation')
                ->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable) {
            return redirect()->route('tenant.settings.ai-translation')
                ->withErrors(['payment' => 'Payment verification failed. Please contact support.']);
        }

        if (!$result->success) {
            return redirect()->route('tenant.settings.ai-translation')
                ->withErrors(['payment' => $result->errorMessage ?? 'Payment was not successful.']);
        }

        $pending = session('tenant_ai_translation_pending_payment');
        $languageId = $request->integer('language_id') ?: ($pending['language_id'] ?? null);
        $amount = (float) ($pending['amount'] ?? 0);

        if (!$languageId) {
            return redirect()->route('tenant.settings.ai-translation')
                ->withErrors(['payment' => 'Payment reference lost. Please contact support.']);
        }

        $language = CentralLanguage::query()->find($languageId);

        if (!$language) {
            return redirect()->route('tenant.settings.ai-translation')
                ->withErrors(['payment' => 'Language no longer available.']);
        }

        $tenant = tenant();

        return $this->handleSuccess($tenant, $language, $gateway, $amount ?: (float) $language->ai_translation_price, $result->transactionId);
    }

    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        session()->forget('tenant_ai_translation_pending_payment');

        return redirect()->route('tenant.settings.ai-translation')
            ->with('purchase_error', 'Payment was cancelled.');
    }

    private function handleSuccess(
        Tenant $tenant,
        CentralLanguage $language,
        string $gateway,
        float $amount,
        ?string $transactionId
    ): RedirectResponse {
        session()->forget('tenant_ai_translation_pending_payment');

        $this->purchaseService->completePurchase($tenant, $language, [
            'amount' => $amount,
            'gateway_code' => $gateway,
            'transaction_uuid' => $transactionId,
        ]);

        return redirect()->route('tenant.settings.ai-translation')
            ->with('purchase_success', "AI translation for \"{$language->name}\" has started. This may take a few minutes.");
    }
}
