<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Tenant;
use App\PaymentGateway\DTOs\PaymentCharge;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use App\Services\SubscriptionPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly SubscriptionPaymentService $service,
    ) {
    }

    /**
     * Initiate payment for a plan renewal or upgrade.
     *
     * @param  string  $gateway  Payment gateway code
     * @param  int     $packageId  The package being renewed/upgraded to
     * @param  string  $type  'renewal' or 'upgrade'
     */
    public function charge(Request $request, string $gateway, int $packageId, string $type): RedirectResponse|View
    {
        $type = in_array($type, ['renewal', 'upgrade'], true) ? $type : 'renewal';

        /** @var \App\Models\Tenant\AdminUser $admin */
        $admin = auth('tenant')->user();

        $package = Package::query()->where('id', $packageId)->firstOrFail();
        $tenant  = tenant();

        $amount = (float) $package->price;

        $charge = PaymentCharge::fromArray([
            'amount'      => $amount,
            'currency'    => 'USD',
            'description' => ucfirst($type) . " payment: {$package->name}",
            'order_id'    => "sub-{$type}-{$package->id}-{$tenant->id}",
            'email'       => $admin->email ?? 'tenant@example.com',
            'success_url' => route('tenant.subscription-payment.success', $gateway) . "?package_id={$package->id}&type={$type}",
            'cancel_url'  => route('tenant.subscription-payment.cancel', $gateway),
        ]);

        session([
            'tenant_subscription_pending_payment' => [
                'package_id' => $package->id,
                'gateway'    => $gateway,
                'amount'     => $amount,
                'type'       => $type,
            ],
        ]);

        try {
            $result = $this->manager->gateway($gateway)->charge($charge);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.finance.wallet')
                ->withErrors(['payment' => $e->getMessage()]);
        }

        if ($result->needsRedirect) {
            return redirect()->away($result->redirectUrl);
        }

        if ($result->needsView) {
            return view($result->viewName, $result->viewData);
        }

        if ($result->success) {
            return $this->handleSuccess($tenant, $package, $gateway, $amount, $type, $result->transactionId);
        }

        return redirect()->route('tenant.finance.wallet')
            ->withErrors(['payment' => $result->errorMessage ?? 'Payment initiation failed. Please try again.']);
    }

    public function success(Request $request, string $gateway): RedirectResponse
    {
        try {
            $result = $this->manager->gateway($gateway)->verify($request);
        } catch (PaymentException $e) {
            return redirect()->route('tenant.finance.wallet')
                ->withErrors(['payment' => $e->getMessage()]);
        } catch (\Throwable) {
            return redirect()->route('tenant.finance.wallet')
                ->withErrors(['payment' => 'Payment verification failed. Please contact support.']);
        }

        if (!$result->success) {
            return redirect()->route('tenant.finance.wallet')
                ->withErrors(['payment' => $result->errorMessage ?? 'Payment was not successful.']);
        }

        $pending   = session('tenant_subscription_pending_payment');
        $packageId = $request->integer('package_id') ?: ($pending['package_id'] ?? null);
        $type      = $request->string('type')->toString() ?: ($pending['type'] ?? 'renewal');
        $amount    = (float) ($pending['amount'] ?? 0);

        if (!$packageId) {
            return redirect()->route('tenant.finance.wallet')
                ->withErrors(['payment' => 'Subscription payment reference lost. Please contact support.']);
        }

        $package = Package::query()->find($packageId);

        if (!$package) {
            return redirect()->route('tenant.finance.wallet')
                ->withErrors(['payment' => 'Package no longer available.']);
        }

        $tenant = tenant();

        return $this->handleSuccess(
            $tenant,
            $package,
            $gateway,
            $amount ?: (float) $package->price,
            $type,
            $result->transactionId
        );
    }

    public function cancel(Request $request, string $gateway): RedirectResponse
    {
        session()->forget('tenant_subscription_pending_payment');

        return redirect()->route('tenant.finance.wallet')
            ->with('subscription_cancelled', 'Payment was cancelled.');
    }

    private function handleSuccess(
        Tenant $tenant,
        Package $package,
        string $gateway,
        float $amount,
        string $type,
        ?string $transactionId
    ): RedirectResponse {
        session()->forget('tenant_subscription_pending_payment');

        $this->service->completePayment($tenant, $package, [
            'amount'         => $amount,
            'gateway_code'   => $gateway,
            'transaction_id' => $transactionId,
        ], $type);

        $label = $type === 'upgrade' ? 'upgraded' : 'renewed';

        return redirect()->route('tenant.finance.wallet')
            ->with('subscription_success', "Your plan has been {$label} to \"{$package->name}\" successfully.");
    }
}
