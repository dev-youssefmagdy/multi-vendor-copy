<?php

namespace App\Console\Commands;

use App\Http\Controllers\Tenant\PaymentController;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use Illuminate\Console\Command;

/**
 * Dedup plan Phase 8 (browser E2E, dev / CI only): stands in for a payment gateway's success callback.
 *
 * The E2E tenants cannot reach a real gateway sandbox, so after the browser placed the order this calls the app's own
 * `PaymentController::confirmPayment()` (the code every gateway callback ends in: paid + Processing, stock deducted,
 * lifecycle activity). Only orders of the e2e-* tenants are accepted.
 *
 *   php artisan e2e:pay e2e-sync <order uuid>
 */
class E2ePayCommand extends Command
{
    protected $signature = 'e2e:pay {tenant} {uuid}';

    protected $description = 'Phase 8: confirm an E2E order payment through PaymentController::confirmPayment (gateway callback stand-in)';

    public function handle(): int
    {
        $tenant = Tenant::query()->find((string) $this->argument('tenant'));
        if (! $tenant || ! str_starts_with((string) $tenant->id, 'e2e-')) {
            $this->error('Only the E2E tenants (e2e-*) are accepted.');

            return self::FAILURE;
        }

        $ok = $tenant->run(function () {
            $order = Order::query()->where('uuid', (string) $this->argument('uuid'))->first();
            if (! $order) {
                return false;
            }
            app(PaymentController::class)->confirmPayment($order, 'e2e', ['transaction_id' => 'e2e_' . $order->uuid, 'gateway' => 'e2e'], notifyAdmin: false);

            return true;
        });
        tenancy()->end();

        $this->line(json_encode(['paid' => $ok]));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
