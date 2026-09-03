<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use App\Services\LanguagePurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * LanguagePaymentController
 *
 * Handles the payment lifecycle for tenant language add-on purchases:
 *   1. charge()   — initiate a gateway payment for the selected language
 *   2. success()  — verify callback, record purchase, sync language to tenant
 *   3. cancel()   — redirect back to the buy page
 */
class LanguagePaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly LanguagePurchaseService $purchaseService,
    ) {
    }

    public function charge(Request $request, string $gateway, int $languageId): RedirectResponse|View
    {
        /** @var \App\Models\Tenant\AdminUser $admin */
        $admin = auth('tenant')->user();

        $language = CentralLanguage::query()
            ->where('id', $languageId)
            ->where('is_free', false)
            ->where('is_active', true)
            ->firstOrFail();

        $tenant = tenant();

        if ($this->purchaseService->tenantHasPurchased($tenant, $language->id)) {
            return redirect()->route('tenant.finance.buy-languages')
                ->with('purchase_error', 'You have already purchased this language.');
        }

        $amount = (float) $language->price;

        $charge = PaymentCharge::fromArray([
            'amount' => $amount,
            'currency' => 'USD',
            'description' => "Language purchase: {$language->name}",
            'order_id' => "lang-{$language->id}-{$tenant->id}",
            'email' => $admin->email ?? 'tenant@example.com',
            'success_url' => route('tenant.language-purchase.success', $gateway) . '?language_id=' . $language->id,
            'cancel_url' => route('tenant.language-purchase.cancel', $gateway),
        ]);

        session([
            'tenant_language_pending_payment' => [
                'language_id' => $language->id,
                'gateway' => $gateway,
                'amount' => $amount,
            ],
        ]);

        try {
            $result = $this->manager->gateway($gateway)->charge($charge);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.finance.buy-languages')
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

        return redirect()->route('tenant.finance.buy-languages')
            ->withErrors(['payment' => $result->errorMessage ?? 'Payment initiation failed. Please try again.']);
    }

    public function success(Request $request, string $gateway): RedirectResponse
    {
        try {
            $result = $this->manager->gateway($gateway)->verify($request);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.finance.buy-languages')
                ->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable) {
            return redirect()->route('tenant.finance.buy-languages')
                ->withErrors(['payment' => 'Payment verification failed. Please contact support.']);
        }

        if (!$result->success) {
            return redirect()->route('tenant.finance.buy-languages')
                ->withErrors(['payment' => $result->errorMessage ?? 'Payment was not successful.']);
        }

        $pending = session('tenant_language_pending_payment');
        $languageId = $request->integer('language_id') ?: ($pending['language_id'] ?? null);
        $amount = (float) ($pending['amount'] ?? 0);

        if (!$languageId) {
            return redirect()->route('tenant.finance.buy-languages')
                ->withErrors(['payment' => 'Language payment reference lost. Please contact support.']);
        }

        $language = CentralLanguage::query()->find($languageId);

        if (!$language) {
            return redirect()->route('tenant.finance.buy-languages')
                ->withErrors(['payment' => 'Language no longer available.']);
        }

        $tenant = tenant();

        return $this->handleSuccess($tenant, $language, $gateway, $amount ?: (float) $language->price, $result->transactionId);
    }

    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        session()->forget('tenant_language_pending_payment');

        return redirect()->route('tenant.finance.buy-languages')
            ->with('purchase_error', 'Payment was cancelled.');
    }

    private function handleSuccess(
        Tenant $tenant,
        CentralLanguage $language,
        string $gateway,
        float $amount,
        ?string $transactionId
    ): RedirectResponse {
        session()->forget('tenant_language_pending_payment');

        $this->purchaseService->completePurchase($tenant, $language, [
            'amount' => $amount,
            'gateway_code' => $gateway,
            'transaction_id' => $transactionId,
        ]);

        return redirect()->route('tenant.settings.languages')
            ->with('purchase_success', "Language \"{$language->name}\" has been added to your store.");
    }
}
