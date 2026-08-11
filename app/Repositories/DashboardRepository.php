<?php

namespace App\Repositories;

use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Admin\TenantAdminAggregateService;

class DashboardRepository
{
    public function __construct(protected TenantAdminAggregateService $aggregateService)
    {
    }

    public function stats(): array
    {
        return [
            'revenue' => (float) PaymentLog::query()->where('status', 'paid')->sum('amount'),
            'tenants' => Tenant::query()->count(),
            'orders' => $this->aggregateService->orders()->count(),
            'products' => Product::query()->count(),
            'packages' => Package::query()->count(),
            'active_tenants' => Tenant::query()->where('status', 'active')->count(),
        ];
    }

    public function revenueSeries(): array
    {
        return collect(range(1, 12))->map(function (int $month) {
            $date = now()->startOfYear()->addMonths($month - 1);

            return [
                'label' => $date->format('M'),
                'revenue' => (float) PaymentLog::query()
                    ->where('status', 'paid')
                    ->whereYear('paid_at', $date->year)
                    ->whereMonth('paid_at', $date->month)
                    ->sum('amount'),
                'orders' => $this->aggregateService->orders()
                    ->filter(fn (object $order) => $order->placed_at?->year === $date->year && $order->placed_at?->month === $date->month)
                    ->count(),
            ];
        })->all();
    }

    public function latestPayments(int $limit = 5)
    {
        return PaymentLog::query()->with(['tenant', 'package.translations.language'])->latest('paid_at')->latest('id')->limit($limit)->get();
    }
}
