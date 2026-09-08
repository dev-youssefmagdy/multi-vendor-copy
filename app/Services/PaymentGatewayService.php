<?php

namespace App\Services;

use App\Enums\ActivationStatus;
use App\Models\PaymentGateway;
use App\Services\Payments\PaymentGatewayStrategyResolver;

class PaymentGatewayService
{
    public function __construct(
        protected PaymentGatewayStrategyResolver $resolver
    ) {
    }

    public function toggle(PaymentGateway $gateway): PaymentGateway
    {
        $gateway->forceFill([
            'status' => $gateway->status === ActivationStatus::Active ? ActivationStatus::Inactive->value : ActivationStatus::Active->value,
        ])->save();

        return $gateway->fresh();
    }

    public function update(PaymentGateway $gateway, array $data): PaymentGateway
    {
        $gateway->fill($data)->save();

        // Update fee fields if supplied
        if (array_key_exists('transaction_fee_percentage', $data) || array_key_exists('transaction_fee_fixed', $data)) {
            $gateway->forceFill(array_filter([
                'transaction_fee_percentage' => $data['transaction_fee_percentage'] ?? null,
                'transaction_fee_fixed' => $data['transaction_fee_fixed'] ?? null,
            ], fn($v) => $v !== null))->save();
        }

        return $gateway->fresh();
    }

    public function descriptor(PaymentGateway $gateway): array
    {
        $strategy = $this->resolver->resolve($gateway->code, $gateway->name);
        $validation = $strategy->validateCredentials($gateway->credentials ?? []);

        return [
            'strategy' => $strategy->displayName(),
            'required' => $validation['required'],
            'missing' => $validation['missing'],
            'valid' => $validation['valid'],
        ];
    }
}
