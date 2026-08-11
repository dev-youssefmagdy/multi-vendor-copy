<?php

namespace App\Repositories;

use App\Enums\PaymentLogStatus;
use App\Services\Admin\TenantAdminAggregateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as PaginationState;
use Illuminate\Support\Collection;

class PaymentLogRepository
{
    public function __construct(protected TenantAdminAggregateService $aggregateService)
    {
    }

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->paginateCollection($this->applyFilters($filters), $perPage);
    }

    public function export(array $filters): Collection
    {
        return $this->applyFilters($filters);
    }

    protected function applyFilters(array $filters): Collection
    {
        return $this->aggregateService->paymentLogs()
            ->filter(function (object $log) use ($filters) {
                $search = trim((string) ($filters['search'] ?? ''));

                if ($search !== '') {
                    $haystack = strtolower(implode(' ', array_filter([
                        $log->reference,
                        $log->gateway,
                        $log->order_number,
                        $log->store_name,
                        $log->customer_name,
                        $log->customer_email,
                    ])));

                    if (!str_contains($haystack, strtolower($search))) {
                        return false;
                    }
                }

                if (filled($filters['status'] ?? null) && $log->status->value !== $filters['status']) {
                    return false;
                }

                return !filled($filters['gateway'] ?? null) || $log->gateway === $filters['gateway'];
            })
            ->sortByDesc(fn(object $log) => $log->created_at?->getTimestamp() ?? 0)
            ->values();
    }

    public function stats(): array
    {
        $logs = $this->aggregateService->paymentLogs();

        return [
            'total' => $logs->count(),
            'paid' => $logs->where('status', PaymentLogStatus::Paid)->count(),
            'failed' => $logs->where('status', PaymentLogStatus::Failed)->count(),
            'amount' => (float) $logs->sum('amount'),
        ];
    }

    public function gatewayOptions(): array
    {
        return ['' => 'All gateways'] + $this->aggregateService->paymentLogs()
            ->pluck('gateway')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(fn(string $gateway) => [$gateway => $gateway])
            ->all();
    }

    public function find(string $tenantId, string $orderNumber): ?object
    {
        return $this->aggregateService->paymentLogs()
            ->first(fn(object $log) => (string) $log->tenant_id === $tenantId && (string) $log->order_number === $orderNumber);
    }

    protected function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = PaginationState::resolveCurrentPage() ?: 1;
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator($results, $items->count(), $perPage, $page, [
            'path' => PaginationState::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }
}
