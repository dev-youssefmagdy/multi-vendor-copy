<?php

namespace App\Livewire\Tenant\Finance;

use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\VendorPurchaseService;

/**
 * Dedicated payment page for settling a single vendor purchase with central.
 * Mirrors the BuyLanguagePage inline-card pattern (Stripe / AuthNet / 2Checkout)
 * and redirect gateways, using central credentials via PaymentManager::centralGateway().
 */
class VendorSettleOrderPage extends TenantPage
{
    use InteractsWithTenantUi;

    // Route parameter — set in mount()
    public int $orderId = 0;

    // Selected gateway
    public string $selectedGateway = '';

    // Live-updated fee breakdown
    public array $breakdown = [];

    // Inline card token properties (set by JS before initiateGatewayPayment)
    public string $stripeToken = '';
    public string $authnetDesc = '';
    public string $authnetValue = '';
    public string $twocoToken = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(int $orderId): void
    {
        $order = Order::query()->findOrFail($orderId);
        $this->orderId = $orderId;
        $this->breakdown = app(VendorPurchaseService::class)->previewBreakdown($order, null);
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.finance.vendor-settle-order-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Pay Central',
            'badge' => 'Finance',
            'description' => 'Select a payment gateway and complete the settlement for this order.',
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $gateways = $repository->centralGatewaysForPayment();
        $order = Order::query()
            ->with('customer')
            ->findOrFail($this->orderId);

        $inlineCardCodes = ['stripe', 'authorize_net', '2checkout'];
        $activeInlineGateway = null;
        $hasStripe = false;
        $hasAuthorizeNet = false;
        $has2Checkout = false;

        foreach ($gateways as $gw) {
            if ($gw['code'] === 'stripe')
                $hasStripe = true;
            if ($gw['code'] === 'authorize_net')
                $hasAuthorizeNet = true;
            if ($gw['code'] === '2checkout')
                $has2Checkout = true;
            if ($gw['code'] === $this->selectedGateway && in_array($gw['code'], $inlineCardCodes, true)) {
                $activeInlineGateway = $gw;
            }
        }

        $authNetGw = collect($gateways)->firstWhere('code', 'authorize_net');
        $authNetSandbox = ($authNetGw['mode'] ?? 'live') === 'test';

        return array_merge(parent::pageData(), [
            'order' => $order,
            'breakdown' => $this->breakdown,
            'gateways' => $gateways,
            'selectedGateway' => $this->selectedGateway,
            'activeInlineGateway' => $activeInlineGateway,
            'authNetSandbox' => $authNetSandbox,
            'hasStripe' => $hasStripe,
            'hasAuthorizeNet' => $hasAuthorizeNet,
            'has2Checkout' => $has2Checkout,
        ]);
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function selectGateway(string $code): void
    {
        $this->selectedGateway = $code;
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
        $this->dispatch('vendorSettleGatewayChanged');

        if (!filled($code)) {
            return;
        }

        $gateways = app(TenantPanelRepository::class)->centralGatewaysForPayment();
        $gwData = collect($gateways)->firstWhere('code', $code);

        if ($gwData) {
            $order = Order::query()->findOrFail($this->orderId);
            $centralModel = app(TenantPanelRepository::class)->findCentralGateway((int) $gwData['id']);
            $this->breakdown = app(VendorPurchaseService::class)->previewBreakdown($order, $centralModel);
        }
    }

    public function initiateGatewayPayment(): void
    {
        $this->validate([
            'selectedGateway' => ['required', 'string'],
        ]);

        $this->stashInlineTokens($this->selectedGateway);

        $this->redirect(route('tenant.vendor-settlement.charge', [
            'gateway' => $this->selectedGateway,
            'orderId' => $this->orderId,
        ]));
    }

    // ── Private helpers ───────────────────────────────────────────────────────

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
}
