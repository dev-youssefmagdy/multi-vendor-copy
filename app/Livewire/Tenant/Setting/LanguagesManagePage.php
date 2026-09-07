<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Language as CentralLanguage;
use App\Models\Tenant\Language;
use App\Models\Tenant\LanguagePurchase;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\LanguagePurchaseService;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantPanelService;

class LanguagesManagePage extends TenantPage
{
    use InteractsWithTenantUi;

    // ── Buy-language state ────────────────────────────────────────────────
    public ?int $selectedLanguageId = null;
    public string $selectedGateway = '';
    public bool $showPaymentModal = false;

    public string $stripeToken = '';
    public string $authnetDesc = '';
    public string $authnetValue = '';
    public string $twocoToken = '';

    // ─────────────────────────────────────────────────────────────────────

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.languages-manage-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Languages',
            'badge' => 'Settings',
            'description' => 'Manage your storefront languages, set the default locale, and purchase new language add-ons.',
        ];
    }

    // ── Installed-languages actions ───────────────────────────────────────

    public function toggleActive(int $languageId): void
    {
        $language = Language::query()->findOrFail($languageId);
        $activating = !$language->is_active;

        if ($activating) {
            $isPurchased = $language->central_language_id
                && LanguagePurchase::query()->where('central_language_id', $language->central_language_id)->exists();

            if (!$isPurchased) {
                $limitService = app(PlanLimitService::class);
                if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_LANGUAGES)) {
                    $this->toast($limitService->errorMessage(PlanLimitService::FEATURE_LANGUAGES), 'error');

                    return;
                }
            }
        }

        $language->update(['is_active' => $activating]);
        $this->toast('Language state updated successfully.');
    }

    public function makeDefault(int $languageId, TenantPanelService $service): void
    {
        $service->markDefaultLanguage(Language::query()->findOrFail($languageId));
        $this->toast('Default language updated successfully.');
    }

    // ── Buy-language actions ──────────────────────────────────────────────

    public function selectLanguage(int $languageId): void
    {
        $this->selectedLanguageId = $languageId;
        $this->selectedGateway = '';
        $this->showPaymentModal = true;
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

    // ── Page data ─────────────────────────────────────────────────────────

    protected function pageData(): array
    {
        $tenant = tenant();
        $service = app(LanguagePurchaseService::class);

        // ── Installed languages (LanguagesPage) ───────────────────────────
        $languages = app(TenantPanelRepository::class)->languages();
        $purchasedIds = LanguagePurchase::query()->pluck('central_language_id')->all();

        $centralLanguageIds = $languages->pluck('central_language_id')->filter()->unique()->values()->all();
        $centralLanguages = CentralLanguage::query()
            ->whereIn('id', $centralLanguageIds)
            ->get(['id', 'is_free', 'countries'])
            ->keyBy('id');

        $installedRows = $languages->map(function (Language $language) use ($purchasedIds, $centralLanguages) {
            $isPurchased = $language->central_language_id
                && in_array($language->central_language_id, $purchasedIds, true);

            $central = $language->central_language_id ? $centralLanguages->get($language->central_language_id) : null;

            $isFree = !$language->central_language_id || !$isPurchased
                ? ($central?->is_free ?? true)
                : false;

            $typeLabel = $isFree
                ? '<span class="badge badge-green">Free</span>'
                : '<span class="badge badge-violet">Purchased</span>';

            return [
                'id' => $language->id,
                'imageUrl' => $language->imageFile?->full_path,
                'name' => $language->name,
                'nativeName' => $language->native_name,
                'code' => $language->code,
                'direction' => strtoupper($language->direction->value),
                'typeLabel' => $typeLabel,
                'isDefault' => $language->is_default,
                'isActive' => $language->is_active,
                'countries' => $central?->countries ?? [],
            ];
        })->all();

        $statistics = [
            ['label' => 'Languages', 'value' => number_format($languages->count()), 'caption' => 'Languages in your store', 'dot' => 'dot-cyan'],
            ['label' => 'Active', 'value' => number_format($languages->where('is_active', true)->count()), 'caption' => 'Locales visible on storefront', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ['label' => 'Default', 'value' => e(optional($languages->firstWhere('is_default', true))->code ?? '-'), 'caption' => 'Current default locale', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ['label' => 'Purchased', 'value' => number_format(count($purchasedIds)), 'caption' => 'Paid language add-ons purchased', 'dot' => 'dot-violet'],
        ];

        // ── Available to purchase (BuyLanguagePage) ───────────────────────
        $availableLanguages = $service->availableForPurchase($tenant);

        // ── Payment gateways + inline detection ───────────────────────────
        $gateways = app(PaymentManager::class)->vendorPaymentGateways();

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
            // installed
            'installedRows' => $installedRows,
            'statistics' => $statistics,
            // purchase
            'availableLanguages' => $availableLanguages,
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
