<?php

namespace App\Livewire\Website;

use App\Enums\ActivationStatus;
use App\Enums\PackageStatus;
use App\Enums\PaymentGatewayType;
use App\Models\Package;
use App\Models\PaymentGateway;
use App\Models\PendingRegistration;
use App\Services\Mail\TemplateMailService;
use App\Services\WebsiteRegistrationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

class RegisterPage extends Component
{
    // Steps:
    // 1 = Plan selection
    // 2 = Identity (email + phone + social)
    // 3 = Payment
    public int $step = 1;

    // Step 2 – Identity
    public string $email = '';
    public string $phone = '';

    #[Url]
    public string $packageId = '';

    // Step 3 – Payment gateway (only for paid plans)
    public string $gatewayCode = '';
    public string $stripeToken = '';
    public string $authnetDescriptor = '';
    public string $authnetValue = '';
    public string $twocoToken = '';

    // UI state
    public bool $emailSent = false;
    public ?string $resendStatus = null;

    // Internal
    public string $centralDomain = '';

    protected const RESEND_MAX_ATTEMPTS = 3;
    protected const RESEND_DECAY_SECONDS = 3600;

    public function mount(): void
    {
        $registrationService = app(WebsiteRegistrationService::class);
        $this->centralDomain = $registrationService->centralDomain();

        $pending = session('website.register.pending');

        if (is_array($pending) && isset($pending['data']) && is_array($pending['data'])) {
            $data = $pending['data'];
            $this->email = (string) ($data['email'] ?? '');
            $this->phone = (string) ($data['phone'] ?? '');
            $this->packageId = filled($data['package_id'] ?? null) ? (string) $data['package_id'] : '';
            $this->gatewayCode = (string) ($data['gateway_code'] ?? '');

            if (filled($this->packageId)) {
                $this->updatedPackageId();
            }

            $this->step = 3;
        }

        $sentEmail = session('registration_email_sent');

        if (filled($sentEmail)) {
            $this->email = (string) $sentEmail;
            $this->emailSent = true;
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->step = 2;
        }
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function updatedGatewayCode(): void
    {
        $this->resetPaymentTokens();
        $this->dispatch('registerPaymentMethodChanged');
    }

    public function updatedPackageId(): void
    {
        if (blank($this->packageId)) {
            return;
        }
    }

    public function requiresPayment(): bool
    {
        if (blank($this->packageId)) {
            return false;
        }

        $package = Package::query()->find((int) $this->packageId);

        return $package && (float) $package->price > 0;
    }

    /**
     * Called from the Proceed button on step 2.
     * For paid plans: stash session + redirect to payment gateway.
     * For free plans: create pending registration + send email link.
     */
    public function proceed(): void
    {
        $this->validateIdentity();

        if ($this->requiresPayment()) {
            if ($this->step < 3) {
                $this->step = 3;

                return;
            }

            $this->validateGateway();
            $this->startPayment();

            return;
        }

        // Free plan
        $this->createPendingAndSendEmail(planName: __('Free Plan'));
    }

    public function socialAuthComplete(string $email, string $name, string $provider): void
    {
        $this->email = strtolower(trim($email));

        if ($this->requiresPayment()) {
            $this->step = 3;

            return;
        }

        $package = filled($this->packageId)
            ? Package::query()->find((int) $this->packageId)
            : null;

        $this->createPendingAndSendEmail(planName: $package?->name ?? __('Free Plan'));
    }

    protected function startPayment(): void
    {
        $package = Package::query()->findOrFail((int) $this->packageId);

        $registrationId = (string) Str::uuid();
        $this->stashInlineTokens($this->gatewayCode);

        session([
            'website.register.pending' => [
                'id' => $registrationId,
                'data' => [
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'package_id' => (int) $this->packageId,
                    'package_price' => (float) $package->price,
                    'gateway_code' => $this->gatewayCode,
                ],
            ],
        ]);

        $this->redirect(route('website.register.payment.charge', [
            'gateway' => $this->gatewayCode,
            'registration' => $registrationId,
        ]));
    }

    public function createPendingAndSendEmail(string $planName): void
    {
        $token = Str::random(64);

        $package = filled($this->packageId)
            ? Package::query()->find((int) $this->packageId)
            : null;

        PendingRegistration::create([
            'token' => $token,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'locale' => app()->getLocale(),
            'package_id' => $package?->id,
            'payment_data' => null,
            'expires_at' => now()->addHours(48),
        ]);

        $completeUrl = route('website.register.complete', ['token' => $token]);
        $expiresAt = now()->addHours(48)->format('M d, Y H:i') . ' UTC';

        app(TemplateMailService::class)->sendRegistrationCompleteLink(
            email: $this->email,
            planName: $planName,
            completeUrl: $completeUrl,
            expiresAt: $expiresAt,
            locale: app()->getLocale(),
        );

        $this->emailSent = true;
        $this->dispatch('scrollToTop');

        Session::forget('website.register.pending');
    }

    public function resend(): void
    {
        $key = 'website-register-resend:' . strtolower($this->email);

        if (RateLimiter::tooManyAttempts($key, self::RESEND_MAX_ATTEMPTS)) {
            $this->resendStatus = __('Too many resend attempts. Please try again in :minutes minute(s).', [
                'minutes' => (int) ceil(RateLimiter::availableIn($key) / 60),
            ]);

            return;
        }

        RateLimiter::hit($key, self::RESEND_DECAY_SECONDS);

        $package = filled($this->packageId) ? Package::query()->find((int) $this->packageId) : null;

        $this->createPendingAndSendEmail(planName: $package?->name ?? __('Free Plan'));

        $remaining = RateLimiter::remaining($key, self::RESEND_MAX_ATTEMPTS);

        $this->resendStatus = $remaining > 0
            ? __('Email resent. :count resend(s) left.', ['count' => $remaining])
            : __('Email resent. You have reached the resend limit.');
    }

    protected function validateIdentity(): array
    {
        return $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9]+$/'],
            'packageId' => ['nullable', 'integer', Rule::exists('packages', 'id')],
        ]);
    }

    protected function validateGateway(): array
    {
        return $this->validate([
            'gatewayCode' => [
                'required',
                'string',
                Rule::exists('payment_gateways', 'code')->where('status', ActivationStatus::Active->value),
            ],
        ]);
    }

    protected function resetPaymentTokens(): void
    {
        $this->stripeToken = '';
        $this->authnetDescriptor = '';
        $this->authnetValue = '';
        $this->twocoToken = '';
    }

    protected function stashInlineTokens(string $gatewayCode): void
    {
        if ($gatewayCode === 'stripe' && $this->stripeToken !== '') {
            session(['pgtoken_stripe_stripeToken' => $this->stripeToken]);

            return;
        }

        if ($gatewayCode === 'authorize_net' && $this->authnetDescriptor !== '' && $this->authnetValue !== '') {
            session([
                'pgtoken_authorize_net_opaqueDataDescriptor' => $this->authnetDescriptor,
                'pgtoken_authorize_net_opaqueDataValue' => $this->authnetValue,
            ]);

            return;
        }

        if ($gatewayCode === '2checkout' && $this->twocoToken !== '') {
            session(['pgtoken_2checkout_2co_token' => $this->twocoToken]);
        }
    }

    public function render()
    {
        $selectedPackage = filled($this->packageId)
            ? Package::query()->find((int) $this->packageId)
            : null;

        $gateways = $this->requiresPayment()
            ? PaymentGateway::query()
                ->with('logoFile')
                ->where('status', ActivationStatus::Active->value)
                ->where('type', PaymentGatewayType::VendorPayments->value)
                ->orderBy('name')
                ->get()
            : collect();

        $inlineCardCodes = ['stripe', 'authorize_net', '2checkout'];
        $hasStripe = false;
        $hasAuthorizeNet = false;
        $has2Checkout = false;
        $inlineGatewayMap = [];

        foreach ($gateways as $gateway) {
            if (!in_array($gateway->code, $inlineCardCodes, true)) {
                continue;
            }

            $inlineGatewayMap[$gateway->code] = [
                'code' => $gateway->code,
                'creds' => (array) ($gateway->credentials ?? []),
                'mode' => $gateway->mode?->value ?? 'test',
            ];

            match ($gateway->code) {
                'stripe' => ($hasStripe = true),
                'authorize_net' => ($hasAuthorizeNet = true),
                '2checkout' => ($has2Checkout = true),
                default => null,
            };
        }

        $activeInlineGateway = $inlineGatewayMap[$this->gatewayCode] ?? null;
        $authorizeNetGateway = $gateways->firstWhere('code', 'authorize_net');
        $authNetSandbox = ($authorizeNetGateway?->mode?->value ?? 'test') === 'test';

        return view('livewire.website.register', [
            'packages' => Package::query()
                ->with('translations.language')
                ->where('status', PackageStatus::Published->value)
                ->get(),
            'selectedPackage' => $selectedPackage,
            'gateways' => $gateways,
            'activeInlineGateway' => $activeInlineGateway,
            'hasStripe' => $hasStripe,
            'hasAuthorizeNet' => $hasAuthorizeNet,
            'has2Checkout' => $has2Checkout,
            'authNetSandbox' => $authNetSandbox,
            'stepLabels' => [
                1 => __('Choose Plan'),
                2 => __('Your Details'),
                3 => __('Payment'),
            ],
        ])->layout('layouts.website', ['title' => __('Create Your Store') . ' — Ecommet']);
    }
}
