<?php

namespace App\Livewire\Tenant\BrandRequest;

use App\Enums\BrandPaymentRequestStatus;
use App\Events\BrandRequestMessageSent;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\BrandPaymentRequest;
use App\Models\BrandRequest;
use App\Models\BrandRequestMessage;
use App\PaymentGateway\PaymentManager;
use Livewire\Attributes\Locked;

class BrandRequestDetail extends TenantPage
{
    use InteractsWithTenantUi;

    #[Locked]
    public int $requestId;

    // Chat
    public string $chatMessage = '';

    // Inline payment tokens
    public string $stripeToken = '';
    public string $authnetDesc = '';
    public string $authnetValue = '';
    public string $twocoToken = '';

    // Active payment request being paid
    public ?int $payingRequestId = null;
    public string $selectedGateway = '';
    public bool $showPaymentModal = false;

    public function mount(int $id): void
    {
        $request = BrandRequest::where('tenant_id', tenant('id'))
            ->findOrFail($id);

        $this->requestId = $request->id;
    }

    protected function pageMeta(): array
    {
        return [
            'pageTitle' => 'Brand Request',
            'badge' => 'Brands',
            'pageDescription' => 'View details, pay outstanding invoices, and communicate with the admin team.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.brand-request.detail';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getRequest(): BrandRequest
    {
        return BrandRequest::with(['messages', 'paymentRequests'])
            ->where('tenant_id', tenant('id'))
            ->findOrFail($this->requestId);
    }

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function sendMessage(): void
    {
        $this->validate(['chatMessage' => 'required|string|max:2000']);

        $tenantUser = auth('tenant')->user();
        $senderName = $tenantUser?->name ?? tenant('name') ?? 'Vendor';
        $body = trim($this->chatMessage);

        BrandRequestMessage::create([
            'brand_request_id' => $this->requestId,
            'sender_type' => 'tenant',
            'sender_name' => $senderName,
            'message' => $body,
        ]);

        try {
            event(new BrandRequestMessageSent(
                requestId: $this->requestId,
                tenantId: tenant('id'),
                senderType: 'tenant',
                senderName: $senderName,
                body: $body,
                sentAt: now()->toIso8601String(),
            ));
        } catch (\Throwable) {
        }

        $this->chatMessage = '';
        $this->dispatch('chat-scrolled');
    }

    // ── Payment ──────────────────────────────────────────────────────────────

    public function openPaymentModal(int $paymentRequestId): void
    {
        $pr = BrandPaymentRequest::where('tenant_id', tenant('id'))
            ->where('brand_request_id', $this->requestId)
            ->where('status', BrandPaymentRequestStatus::Pending->value)
            ->findOrFail($paymentRequestId);

        $this->payingRequestId = $pr->id;
        $this->selectedGateway = '';
        $this->showPaymentModal = true;
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->payingRequestId = null;
        $this->selectedGateway = '';
    }

    public function selectGateway(string $code): void
    {
        $this->selectedGateway = $code;
        $this->stripeToken = '';
        $this->authnetDesc = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
        $this->dispatch('brPaymentMethodChanged');
    }

    public function initiatePayment(): void
    {
        $this->validate([
            'payingRequestId' => 'required|integer',
            'selectedGateway' => 'required|string',
        ]);

        // Stash inline card tokens to session before redirect
        $this->stashInlineTokens($this->selectedGateway);

        session([
            'br_pending_payment' => [
                'payment_request_id' => $this->payingRequestId,
                'gateway' => $this->selectedGateway,
            ],
        ]);

        $this->redirect(route('tenant.brand-request-payment.charge', [
            'gateway' => $this->selectedGateway,
            'paymentRequestId' => $this->payingRequestId,
        ]));
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

    // ── Page Data ────────────────────────────────────────────────────────────

    protected function pageData(): array
    {
        $request = $this->getRequest();
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

        $payingPaymentRequest = $this->payingRequestId
            ? BrandPaymentRequest::find($this->payingRequestId)
            : null;

        return array_merge(parent::pageData(), [
            'request' => $request,
            'messages' => $request->messages()->oldest()->get(),
            'paymentRequests' => $request->paymentRequests()->latest()->get(),
            'gateways' => $gateways,
            'showPaymentModal' => $this->showPaymentModal,
            'payingPaymentRequest' => $payingPaymentRequest,
            'activeInlineGateway' => $activeInlineGateway,
            'hasStripe' => $hasStripe,
            'hasAuthorizeNet' => $hasAuthorizeNet,
            'has2Checkout' => $has2Checkout,
            'authNetSandbox' => $authNetSandbox,
        ]);
    }
}
