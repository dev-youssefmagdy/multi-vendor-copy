<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Language as TenantLanguage;
use App\Services\AiTranslationPurchaseService;
use App\Services\Tenant\PlanLimitService;

class AiTranslationPage extends TenantPage
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

    protected function pageMeta(): array
    {
        return [
            'title' => 'AI Translation',
            'badge' => 'Settings',
            'description' => 'Purchase brand-aware AI translation for your store. Translations are tailored to your store name, products, and style.',
        ];
    }

    public function selectGateway(string $code): void
    {
        $this->selectedGateway = $code;
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
        $this->dispatch('aiTranslationPaymentMethodChanged');
    }

    public function openModal(int $centralLangId): void
    {
        $this->selectedLanguageId = $centralLangId;
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

    public function runFree(AiTranslationPurchaseService $service): void
    {
        $language = CentralLanguage::query()->find($this->selectedLanguageId);

        if (!$language || !$service->isFree($language) || !$service->canPurchase(tenant(), $language)) {
            $this->toast('This language requires payment or is not enabled on your plan.', 'error');
            $this->closeModal();
            return;
        }

        $limitService = app(PlanLimitService::class);
        if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            $this->toast($limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 'error');
            $this->closeModal();
            return;
        }

        $service->completePurchase(tenant(), $language, []);
        $limitService->incrementCounter(tenant(), 'ai_calls_count');
        $this->closeModal();
        $this->toast('AI translation started. This may take a few minutes.');
    }

    public function initiatePayment(): void
    {
        $this->validate([
            'selectedLanguageId' => ['required', 'integer'],
            'selectedGateway' => ['required', 'string'],
        ]);

        $language = CentralLanguage::query()
            ->where('id', $this->selectedLanguageId)
            ->whereNotNull('ai_translation_price')
            ->where('is_active', true)
            ->firstOrFail();

        if ((float) $language->ai_translation_price <= 0) {
            $this->toast('This language is free — no payment required.', 'error');
            $this->closeModal();
            return;
        }

        $limitService = app(PlanLimitService::class);
        if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_AI_CALLS)) {
            $this->toast($limitService->errorMessage(PlanLimitService::FEATURE_AI_CALLS), 'error');
            $this->closeModal();
            return;
        }

        $this->stashInlineTokens($this->selectedGateway);

        session([
            'tenant_ai_translation_pending_payment' => [
                'language_id' => $language->id,
                'gateway' => $this->selectedGateway,
            ],
        ]);

        $this->redirect(route('tenant.ai-translation-purchase.charge', [
            'gateway' => $this->selectedGateway,
            'languageId' => $language->id,
        ]));

        $this->closeModal();
    }

    protected function pageData(): array
    {
        $tenant = tenant();
        $service = app(AiTranslationPurchaseService::class);
        $canUseAi = app(PlanLimitService::class)->aiTranslationEnabled($tenant);

        $languages = $service->availableLanguages($tenant);

        $tenantLanguages = TenantLanguage::query()
            ->whereNotNull('central_language_id')
            ->get()
            ->keyBy('central_language_id');


        $activeTenantCentralIds = $tenantLanguages
            ->filter(fn (TenantLanguage $lang) => $lang->is_active)
            ->keys()
            ->all();

        $history = $service->history($tenant->id);

        $cards = $languages->map(function (CentralLanguage $lang) use ($activeTenantCentralIds, $history, $tenantLanguages) {
            $lastRun = $history->where('central_language_id', $lang->id)->sortByDesc('created_at')->first();
            $tenantLanguage = $tenantLanguages->get($lang->id);

            return [
                'id' => $lang->id,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'code' => $lang->code,
                'is_free' => $lang->aiTranslationIsFree(),
                'price' => $lang->ai_translation_price,
                'is_active' => in_array($lang->id, $activeTenantCentralIds, true),
                'last_run' => $lastRun?->translated_at?->format('M d, Y H:i'),
                'last_status' => $lastRun?->status,
                'translation_status' => $tenantLanguage?->translation_status,
                'translation_progress' => $tenantLanguage?->translation_progress ?? 0,
                'translation_summary' => $tenantLanguage?->translation_summary,
            ];
        });

        $polling = $cards->contains(fn (array $card) => in_array($card['translation_status'], ['queued', 'running'], true));

        $gateways = $canUseAi
            ? app(\App\PaymentGateway\PaymentManager::class)->vendorPaymentGateways()
            : collect();

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

        $selectedCard = $this->selectedLanguageId
            ? $cards->firstWhere('id', $this->selectedLanguageId)
            : null;

        return array_merge(parent::pageData(), [
            'canUseAi' => $canUseAi,
            'cards' => $cards,
            'polling' => $polling,
            'history' => $history,
            'gateways' => $gateways,
            'showPaymentModal' => $this->showPaymentModal,
            'selectedCard' => $selectedCard,
            'selectedGateway' => $this->selectedGateway,
            'activeInlineGateway' => $activeInlineGateway,
            'hasStripe' => $hasStripe,
            'hasAuthorizeNet' => $hasAuthorizeNet,
            'has2Checkout' => $has2Checkout,
            'authNetSandbox' => $authNetSandbox,
        ]);
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.ai-translation-page';
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
