<?php

namespace App\Livewire\Tenant\Manufacturing;

use App\Enums\ManufacturingPaymentRequestStatus;
use App\Livewire\Tenant\Base\TenantPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\ManufacturingPaymentRequest;
use App\Models\ManufacturingRequest;
use App\Models\ManufacturingRequestMessage;
use App\PaymentGateway\PaymentManager;
use Livewire\Attributes\Locked;

class ManufacturingRequestDetail extends TenantPage
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
        $request = ManufacturingRequest::where('tenant_id', tenant('id'))
            ->findOrFail($id);

        $this->requestId = $request->id;
    }

    protected function pageMeta(): array
    {
        return [
            'pageTitle' => 'Manufacturing Request',
            'badge' => 'Manufacturing',
            'pageDescription' => 'View details, pay outstanding invoices, and communicate with the admin team.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.manufacturing.manufacturing-request-detail';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function getRequest(): ManufacturingRequest
    {
        return ManufacturingRequest::with(['messages', 'paymentRequests'])
            ->where('tenant_id', tenant('id'))
            ->findOrFail($this->requestId);
    }

    // ── Chat ─────────────────────────────────────────────────────────────────

    public function sendMessage(): void
    {
        $this->validate(['chatMessage' => 'required|string|max:2000']);

        $tenantUser = auth('tenant')->user();

        ManufacturingRequestMessage::create([
            'manufacturing_request_id' => $this->requestId,
            'sender_type' => 'tenant',
            'sender_name' => $tenantUser?->name ?? tenant('name') ?? 'Vendor',
            'message' => trim($this->chatMessage),
        ]);

        $this->chatMessage = '';
        $this->dispatch('chat-scrolled');
    }

    // ── Payment ──────────────────────────────────────────────────────────────

    public function openPaymentModal(int $paymentRequestId): void
    {
        $pr = ManufacturingPaymentRequest::where('tenant_id', tenant('id'))
            ->where('manufacturing_request_id', $this->requestId)
            ->where('status', ManufacturingPaymentRequestStatus::Pending->value)
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
        $this->dispatch('mfPaymentMethodChanged');
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
            'mf_pending_payment' => [
                'payment_request_id' => $this->payingRequestId,
                'gateway' => $this->selectedGateway,
            ],
        ]);

        $this->redirect(route('tenant.manufacturing-payment.charge', [
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
            ? ManufacturingPaymentRequest::find($this->payingRequestId)
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
