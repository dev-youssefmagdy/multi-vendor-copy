<?php

namespace App\Livewire\Tenant\Finance;

use App\Enums\PackageStatus;
use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Package;
use App\Repositories\Tenant\TenantPanelRepository;
use Livewire\WithPagination;

class WalletPage extends ListPage
{
    use WithPagination;
    use InteractsWithTenantUi;

    public bool $showSubscriptionModal = false;
    public string $modalType = 'renewal'; // 'renewal' or 'upgrade'
    public ?int $selectedPackageId = null;
    public string $selectedGateway = '';

    // Inline-card token properties (set by JS before initiatePayment)
    public string $stripeToken = '';
    public string $authnetDesc = '';
    public string $authnetValue = '';
    public string $twocoToken = '';

    protected function pageView(): string
    {
        return 'livewire.tenant.finance.wallet-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title'       => 'Subscriptions & Balance',
            'badge'       => 'Finance',
            'description' => 'Review tenant subscription records, the current subscription-backed balance, and tenant transaction history in one place.',
        ];
    }

    public function openRenewModal(): void
    {
        $tenant = tenant();
        $this->selectedPackageId = $tenant->package_id;
        $this->modalType = 'renewal';
        $this->selectedGateway = '';
        $this->showSubscriptionModal = true;
    }

    public function openUpgradeModal(): void
    {
        $this->selectedPackageId = null;
        $this->modalType = 'upgrade';
        $this->selectedGateway = '';
        $this->showSubscriptionModal = true;
    }

    public function selectGateway(string $code): void
    {
        $this->selectedGateway = $code;
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
        $this->dispatch('subscriptionPaymentMethodChanged');
    }

    public function closeModal(): void
    {
        $this->showSubscriptionModal = false;
        $this->selectedPackageId = null;
        $this->selectedGateway = '';
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
    }

    public function initiatePayment(): void
    {
        $this->validate([
            'selectedPackageId' => ['required', 'integer'],
            'selectedGateway'   => ['required', 'string'],
        ]);

        $package = Package::query()
            ->where('id', $this->selectedPackageId)
            ->where('status', PackageStatus::Published->value)
            ->firstOrFail();

        $this->stashInlineTokens($this->selectedGateway);

        session([
            'tenant_subscription_pending_payment' => [
                'package_id' => $package->id,
                'gateway'    => $this->selectedGateway,
                'type'       => $this->modalType,
            ],
        ]);

        $this->redirect(route('tenant.subscription-payment.charge', [
            'gateway'   => $this->selectedGateway,
            'packageId' => $package->id,
            'type'      => $this->modalType,
        ]));

        $this->closeModal();
    }

    protected function pageData(): array
    {
        $repository    = app(TenantPanelRepository::class);
        $subscriptions = $repository->paginateSubscriptions(6, 'subscriptionsPage');
        $transactions  = $repository->paginateTransactions(10, 'transactionsPage');
        $overview      = $repository->walletOverview();

        $tenant         = tenant();
        $currentPackage = $tenant->package;

        $packages = Package::query()
            ->where('status', PackageStatus::Published->value)
            ->orderBy('price')
            ->get();

        $gateways = app(\App\PaymentGateway\PaymentManager::class)->vendorPaymentGateways();

        $inlineCardCodes  = ['stripe', 'authorize_net', '2checkout'];
        $inlineGatewayMap = [];
        $hasStripe        = false;
        $hasAuthorizeNet  = false;
        $has2Checkout     = false;

        foreach ($gateways as $gw) {
            if (!in_array($gw['code'], $inlineCardCodes, true)) {
                continue;
            }
            $inlineGatewayMap[$gw['code']] = [
                'code'  => $gw['code'],
                'creds' => $gw['creds'],
                'mode'  => $gw['mode'] ?? 'test',
            ];
            match ($gw['code']) {
                'stripe'        => ($hasStripe = true),
                'authorize_net' => ($hasAuthorizeNet = true),
                '2checkout'     => ($has2Checkout = true),
                default         => null,
            };
        }

        $activeInlineGateway = $inlineGatewayMap[$this->selectedGateway] ?? null;
        $authNetGw           = $gateways->firstWhere('code', 'authorize_net');
        $authNetSandbox      = ($authNetGw['mode'] ?? 'test') === 'test';

        $selectedPackage = $this->selectedPackageId
            ? Package::query()->find($this->selectedPackageId)
            : null;

        return array_merge(parent::pageData(), [
            'cards'               => $this->presentMetricCards($overview['cards']),
            'subscriptions'       => $subscriptions,
            'transactions'        => $transactions,
            'currentPackage'      => $currentPackage,
            'packages'            => $packages,
            'gateways'            => $gateways,
            'showSubscriptionModal' => $this->showSubscriptionModal,
            'modalType'           => $this->modalType,
            'selectedPackage'     => $selectedPackage,
            'activeInlineGateway' => $activeInlineGateway,
            'hasStripe'           => $hasStripe,
            'hasAuthorizeNet'     => $hasAuthorizeNet,
            'has2Checkout'        => $has2Checkout,
            'authNetSandbox'      => $authNetSandbox,
        ]);
    }

    public function clearFilters(): void
    {
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
                'pgtoken_authorize_net_opaqueDataValue'      => $this->authnetValue,
            ]);
            return;
        }
        if ($gatewayCode === '2checkout' && $this->twocoToken !== '') {
            session(['pgtoken_2checkout_2co_token' => $this->twocoToken]);
        }
    }
}
