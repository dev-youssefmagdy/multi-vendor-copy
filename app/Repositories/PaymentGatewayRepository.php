<?php

namespace App\Repositories;

use App\Enums\ActivationStatus;
use App\Models\PaymentGateway;
use Illuminate\Support\Collection;

class PaymentGatewayRepository
{
    public function all(array $filters = []): Collection
    {
        return PaymentGateway::query()
            ->with('logoFile')
            ->when($filters['type'] ?? '', fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? '', fn ($query, $status) => $query->where('status', $status))
            ->orderBy('type')
            ->orderByDesc('status')
            ->orderBy('name')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => PaymentGateway::query()->count(),
            'active' => PaymentGateway::query()->where('status', ActivationStatus::Active->value)->count(),
        ];
    }
}
