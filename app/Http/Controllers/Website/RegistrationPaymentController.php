<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PendingRegistration;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use App\Services\Mail\TemplateMailService;
use App\Services\WebsiteRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationPaymentController extends Controller
{
    public function __construct(
        protected PaymentManager $manager,
        protected WebsiteRegistrationService $registrationService,
        protected TemplateMailService $templateMailService,
    ) {
    }

    public function charge(Request $request, string $gateway, string $registration): RedirectResponse|View
    {
        $pending = $this->pendingRegistration($registration);

        if (!$pending) {
            return $this->redirectToRegisterWithError(__('Your registration session expired. Please try again.'));
        }

        $package = Package::query()->find((int) data_get($pending, 'data.package_id'));

        if (!$package || (float) data_get($pending, 'data.package_price', $package->price) <= 0) {
            return redirect()->route('website.register');
        }

        $charge = PaymentCharge::fromArray([
            'amount' => (float) data_get($pending, 'data.package_price', $package->price),
            'currency' => 'USD',
            'description' => __('Store registration for :plan', ['plan' => $package->name]),
            'order_id' => $registration,
            'email' => (string) data_get($pending, 'data.email'),
            'name' => (string) data_get($pending, 'data.name'),
            'phone' => (string) data_get($pending, 'data.phone'),
            'success_url' => route('website.register.payment.success', ['gateway' => $gateway, 'registration' => $registration]),
            'cancel_url' => route('website.register.payment.cancel', ['gateway' => $gateway, 'registration' => $registration]),
            'metadata' => [
                'registration_id' => $registration,
                'shop_name' => (string) data_get($pending, 'data.shop_name'),
            ],
        ]);

        try {
            $result = $this->manager->centralGateway($gateway)->charge($charge);
        } catch (PaymentException $e) {
            return $this->redirectToRegisterWithError($e->getMessage());
        } catch (\Throwable $e) {
            return $this->redirectToRegisterWithError(__('Payment initiation failed. Please try again.'));
        }

        if ($result->needsRedirect) {
            return redirect()->away((string) $result->redirectUrl);
        }

        if ($result->needsView) {
            return view((string) $result->viewName, $result->viewData);
        }
        if ($result->success) {
            return $this->completeRegistration($request, $registration, $gateway, [
                'gateway_code' => $gateway,
                'transaction_id' => $result->transactionId,
                'raw_response' => $result->rawResponse,
                'amount' => (float) data_get($pending, 'data.package_price', $package->price),
            ]);
        }

        return $this->redirectToRegisterWithError($result->errorMessage ?: __('Payment initiation failed. Please try again.'));
    }

    public function success(Request $request, string $gateway, string $registration): RedirectResponse
    {
        $pending = $this->pendingRegistration($registration);

        if (!$pending) {
            return $this->redirectToRegisterWithError(__('Your registration session expired. Please try again.'));
        }

        try {
            $result = $this->manager->centralGateway($gateway)->verify($request);
        } catch (PaymentException $e) {
            return $this->redirectToRegisterWithError($e->getMessage());
        } catch (\Throwable $e) {
            return $this->redirectToRegisterWithError(__('Payment verification failed. Please contact support.'));
        }

        if (!$result->success) {
            return $this->redirectToRegisterWithError($result->errorMessage ?: __('Payment verification failed.'));
        }

        return $this->completeRegistration($request, $registration, $gateway, [
            'gateway_code' => $gateway,
            'transaction_id' => $result->transactionId,
            'raw_response' => $result->rawResponse,
            'amount' => (float) data_get($pending, 'data.package_price', 0),
        ]);
    }

    public function cancel(Request $request, string $gateway, string $registration): RedirectResponse
    {
        $this->forgetPaymentTokens();

        return redirect()->route('website.register')
            ->with('registration_payment_cancelled', true);
    }

    protected function completeRegistration(Request $request, string $registration, string $gateway, array $payment): RedirectResponse
    {
        $pending = $this->pendingRegistration($registration);

        if (!$pending) {
            return $this->redirectToRegisterWithError(__('Your registration session expired. Please try again.'));
        }

        $data = (array) $pending['data'];
        $email = (string) ($data['email'] ?? '');

        if (!filled($email)) {
            return $this->redirectToRegisterWithError(__('Registration data is invalid. Please try again.'));
        }

        $package = filled($data['package_id'] ?? null)
            ? Package::query()->find((int) $data['package_id'])
            : null;

        $token = Str::random(64);

        $referral = app(\App\Services\AffiliateService::class)->resolveReferral($request);

        try {
            PendingRegistration::create([
                'token' => $token,
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'locale' => app()->getLocale(),
                'package_id' => $package?->id,
                'affiliate_referral_id' => $referral?->id,
                'payment_data' => array_merge($payment, [
                    'package_price' => (float) ($data['package_price'] ?? 0),
                ]),
                'expires_at' => now()->addHours(48),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return $this->redirectToRegisterWithError(__('Payment was confirmed, but registration could not be saved. Please contact support.'));
        }

        $completeUrl = route('website.register.complete', ['token' => $token]);
        $expiresAt = now()->addHours(48)->format('M d, Y H:i') . ' UTC';

        $this->templateMailService->sendRegistrationCompleteLink(
            email: $email,
            planName: $package?->name ?? __('Free Plan'),
            completeUrl: $completeUrl,
            expiresAt: $expiresAt,
            locale: app()->getLocale(),
        );

        $this->clearPendingRegistration();

        return redirect()->route('website.register')
            ->with('registration_email_sent', $email);
    }

    protected function pendingRegistration(string $registration): ?array
    {
        $pending = session('website.register.pending');

        if (!is_array($pending) || ($pending['id'] ?? null) !== $registration) {
            return null;
        }

        return $pending;
    }

    protected function clearPendingRegistration(): void
    {
        session()->forget('website.register.pending');
        $this->forgetPaymentTokens();
    }

    protected function forgetPaymentTokens(): void
    {
        session()->forget([
            'pgtoken_stripe_stripeToken',
            'pgtoken_authorize_net_opaqueDataDescriptor',
            'pgtoken_authorize_net_opaqueDataValue',
            'pgtoken_2checkout_2co_token',
        ]);
    }

    protected function redirectToRegisterWithError(string $message): RedirectResponse
    {
        $this->forgetPaymentTokens();

        return redirect()->route('website.register')
            ->withErrors(['payment' => $message]);
    }
}
