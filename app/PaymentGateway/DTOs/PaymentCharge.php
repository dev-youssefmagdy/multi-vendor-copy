<?php

namespace App\PaymentGateway\DTOs;

class PaymentCharge
{
    public function __construct(
        public readonly float  $amount,
        public readonly string $currency,
        public readonly string $description,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        public readonly ?string $email       = null,
        public readonly ?string $name        = null,
        public readonly ?string $phone       = null,
        public readonly ?string $orderId     = null,
        public readonly array  $metadata     = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount:      (float) $data['amount'],
            currency:    $data['currency']     ?? 'USD',
            description: $data['description']  ?? 'Payment',
            successUrl:  $data['success_url'],
            cancelUrl:   $data['cancel_url'],
            email:       $data['email']        ?? null,
            name:        $data['name']         ?? null,
            phone:       $data['phone']        ?? null,
            orderId:     $data['order_id']     ?? uniqid('order_'),
            metadata:    $data['metadata']     ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'amount'      => $this->amount,
            'currency'    => $this->currency,
            'description' => $this->description,
            'success_url' => $this->successUrl,
            'cancel_url'  => $this->cancelUrl,
            'email'       => $this->email,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'order_id'    => $this->orderId,
            'metadata'    => $this->metadata,
        ];
    }
}
