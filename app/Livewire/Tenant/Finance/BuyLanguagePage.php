<?php

namespace App\Livewire\Tenant\Finance;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant;
use App\Services\LanguagePurchaseService;

class BuyLanguagePage extends TenantPage
{
    use InteractsWithTenantUi;

    public ?int $selectedLanguageId = null;
    public string $selectedGateway = '';
    public bool $showPaymentModal = false;

    // ── Inline-card token properties (set by JS before initiatePayment) ──────
    public string $stripeToken = '';
    public string $authnetDesc = '';
    public string $authnetValue = '';
    public string $twocoToken = '';

    protected function pageView(): string
    {
        return 'livewire.tenant.finance.buy-language-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Buy Languages',
            'badge' => 'Finance',
            'description' => 'Purchase premium language add-ons to expand your store\'s multilingual reach.',
        ];
    }

    public function selectGateway(string $code): void
    {
        $this->selectedGateway = $code;
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
        $this->dispatch('buyLanguagePaymentMethodChanged');
    }

    public function selectLanguage(int $languageId): void
    {
        $this->selectedLanguageId = $languageId;
        $this->selectedGateway = '';
        $this->showPaymentModal = true;
    }

    public function closeModal(): void
    {
        $this->showPaymentModal = false;
        $this->selectedLanguageId = null;
        $this->selectedGateway = '';
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
    }

    public function initiatePayment(): void
    {
        $this->validate([
            'selectedLanguageId' => ['required', 'integer'],
            'selectedGateway' => ['required', 'string'],
        ]);

        $language = CentralLanguage::query()
            ->where('id', $this->selectedLanguageId)
            ->where('is_free', false)
            ->where('is_active', true)
            ->firstOrFail();

        $tenant = tenant();

        if (app(LanguagePurchaseService::class)->tenantHasPurchased($tenant, $language->id)) {
            $this->toast('You have already purchased this language.', 'error');
            $this->closeModal();
            return;
        }

        // Store pending purchase context and redirect to the payment controller
        $this->stashInlineTokens($this->selectedGateway);

        session([
            'tenant_language_pending_payment' => [
                'language_id' => $language->id,
                'gateway' => $this->selectedGateway,
            ],
        ]);


        $this->redirect(route('tenant.language-purchase.charge', [
            'gateway' => $this->selectedGateway,
            'languageId' => $language->id,
        ]));

        $this->closeModal();
    }

    protected function pageData(): array
    {
        $tenant = tenant();
        $service = app(LanguagePurchaseService::class);
        $available = $service->availableForPurchase($tenant);

        $gateways = app(\App\PaymentGateway\PaymentManager::class)->vendorPaymentGateways();

        // ── Inline-card gateway detection (same logic as CheckoutPage) ────────
        $inlineCardCodes = ['stripe', 'authorize_net', '2checkout'];
        $hasStripe = false;
        $hasAuthorizeNet = false;
        $has2Checkout = false;
        $inlineGatewayMap = [];

        foreach ($gateways as $gw) {
            if (!in_array($gw['code'], $inlineCardCodes, true)) {
                continue;
            }
            $inlineGatewayMap[$gw['code']] = [
                'code' => $gw['code'],
                'creds' => $gw['creds'],
                'mode' => $gw['mode'] ?? 'test',
            ];
            match ($gw['code']) {
                'stripe' => ($hasStripe = true),
                'authorize_net' => ($hasAuthorizeNet = true),
                '2checkout' => ($has2Checkout = true),
                default => null,
            };
        }

        $activeInlineGateway = $inlineGatewayMap[$this->selectedGateway] ?? null;
        $authNetGw = $gateways->firstWhere('code', 'authorize_net');
        $authNetSandbox = ($authNetGw['mode'] ?? 'test') === 'test';

        $selectedLanguage = $this->selectedLanguageId
            ? CentralLanguage::query()->find($this->selectedLanguageId)
            : null;

        return array_merge(parent::pageData(), [
            'availableLanguages' => $available,
            'gateways' => $gateways,
            'selectedLanguage' => $selectedLanguage,
            'showPaymentModal' => $this->showPaymentModal,
            'activeInlineGateway' => $activeInlineGateway,
            'hasStripe' => $hasStripe,
            'hasAuthorizeNet' => $hasAuthorizeNet,
            'has2Checkout' => $has2Checkout,
            'authNetSandbox' => $authNetSandbox,
        ]);
    }

    private function stashInlineTokens(string $gatewayCode): void
    {
        if ($gatewayCode === 'stripe' && $this->stripeToken !== '') {
            session(['pgtoken_stripe_stripeToken' => $this->stripeToken]);
            return;
        }
        if ($gatewayCode === 'authorize_net' && $this->authnetDesc !== '' && $this->authnetValue !== '') {
            session([
                'pgtoken_authorize_net_opaqueDataDescriptor' => $this->authnetDesc,
                'pgtoken_authorize_net_opaqueDataValue' => $this->authnetValue,
            ]);
            return;
        }
        if ($gatewayCode === '2checkout' && $this->twocoToken !== '') {
            session(['pgtoken_2checkout_2co_token' => $this->twocoToken]);
        }
    }

    public function clearFilters(): void
    {
    }
}

