<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentGateway as TenantPaymentGateway;
use App\PaymentGateway\Exceptions\PaymentException;
use App\PaymentGateway\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PaymentWebhookController
 *
 * POST /checkout/payment/{gateway}/webhook
 *
 * Inbound endpoint tenants paste into their own gateway dashboard so the
 * gateway can notify the platform of async events (capture confirmation,
 * refunds, disputes). This is separate from success()/cancel() in
 * PaymentController, which handle the user-facing redirect flow.
 *
 * No 'auth:storefront' middleware — the caller is the gateway's server, not
 * a logged-in customer — and the route is CSRF-exempt (see bootstrap/app.php).
 */
class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentManager $manager,
    ) {
    }

    public function handle(Request $request, string $gateway): JsonResponse
    {
        $tenantGateway = TenantPaymentGateway::query()->where('code', $gateway)->first();

        try {
            $verified = $this->manager->gateway($gateway)->verifyWebhook($request);
        } catch (PaymentException $e) {
            $tenantGateway?->update([
                'webhook_status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $tenantGateway?->update([
                'webhook_status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false, 'message' => 'Webhook verification failed.'], 400);
        }

        if (!$verified) {
            $tenantGateway?->update([
                'webhook_status' => 'failed',
                'last_error' => 'Signature verification failed.',
            ]);

            return response()->json(['ok' => false, 'message' => 'Invalid signature.'], 400);
        }

        $tenantGateway?->update([
            'webhook_status' => 'connected',
            'last_transaction_at' => now(),
            'last_error' => null,
        ]);

        return response()->json(['ok' => true]);
    }
}
