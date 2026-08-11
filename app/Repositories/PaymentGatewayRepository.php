<?php

namespace App\Repositories;

use App\Enums\ActivationStatus;
use App\Models\PaymentGateway;
use Illuminate\Support\Collection;

class PaymentGatewayRepository
{
    public function all(): Collection
    {
        return PaymentGateway::query()->with('logoFile')->orderBy('type')->orderByDesc('status')->orderBy('name')->get();
    }

    public function stats(): array
    {
        return [
            'total' => PaymentGateway::query()->count(),
            'active' => PaymentGateway::query()->where('status', ActivationStatus::Active->value)->count(),
        ];
    }
}
