<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentLogStatus;
use App\Enums\Tenant\SubscriptionGatewayType;
use App\Enums\Tenant\SubscriptionStatus;
use App\Enums\WalletTransactionDirection;
use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\PaymentGateway;
use App\Models\Tenant;
use App\Models\Tenant\Subscription;
use App\Models\Tenant\Transaction;
use Illuminate\Support\Str;
use App\Services\AffiliateService;

class SubscriptionPaymentService
{
    /**
     * Resolve end date for a new subscription period starting now.
     */
    public function resolveEndDate(Package $package): ?\DateTimeInterface
    {
        return match ($package->term?->value ?? $package->term) {
            'monthly'   => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly'    => now()->addYear(),
            default     => null,
        };
    }

    /**
     * Record a renewal or upgrade payment:
     *   - Creates a central PaymentLog
     *   - Within the tenant DB: creates a Transaction + Subscription
     *   - Updates the tenant's package_id (for upgrades)
     */
    public function completePayment(
        Tenant $tenant,
        Package $package,
        array $payment,
        string $type = 'renewal'
    ): Subscription {
        $gateway = PaymentGateway::query()->where('code', $payment['gateway_code'])->first();

        // Central payment log
        $paymentLog = PaymentLog::create([
            'tenant_id'  => $tenant->id,
            'package_id' => $package->id,
            'gateway'    => (string) $payment['gateway_code'],
            'amount'     => (float) $payment['amount'],
            'status'     => PaymentLogStatus::Paid,
            'reference'  => (string) ($payment['transaction_id'] ?? ''),
            'paid_at'    => now(),
            'meta'       => ['type' => $type],
        ]);

        app(AffiliateService::class)->approveConversion($tenant->id, $paymentLog);

        // Update central tenant record with new package
        $tenant->update(['package_id' => $package->id]);

        tenancy()->initialize($tenant);

        try {
            $gatewayLabel = str((string) $payment['gateway_code'])
                ->replace(['_', '-'], ' ')
                ->headline()
                ->toString();

            $description = $type === 'upgrade'
                ? "Plan upgrade to {$package->name} via {$gatewayLabel}"
                : "Subscription renewal for {$package->name} via {$gatewayLabel}";

            $transaction = Transaction::query()->create([
                'uuid'         => (string) Str::uuid(),
                'amount'       => (float) $payment['amount'],
                'type'         => WalletTransactionDirection::Credit->value,
                'admin_profit' => null,
                'description'  => $description,
            ]);

            $subscription = Subscription::query()->create([
                'package_id'           => $package->id,
                'price'                => (float) $payment['amount'],
                'status'               => SubscriptionStatus::Active->value,
                'term'                 => $package->term?->value ?? $package->term,
                'payment_gateway_type' => SubscriptionGatewayType::Central->value,
                'payment_gateway_id'   => $gateway?->id,
                'transaction_id'       => $transaction->id,
                'start_date'           => now(),
                'end_date'             => $this->resolveEndDate($package),
            ]);

            $transaction->update([
                'model_type' => Subscription::class,
                'model_id'   => $subscription->id,
            ]);
        } finally {
            tenancy()->end();
        }

        return $subscription;
    }
}
