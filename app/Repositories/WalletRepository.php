<?php

namespace App\Repositories;

use App\Services\Admin\TenantAdminAggregateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as PaginationState;
use Illuminate\Support\Collection;

class WalletRepository
{
    public function __construct(protected TenantAdminAggregateService $aggregateService)
    {
    }

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $records = $this->aggregateService->wallets()
            ->filter(function (object $wallet) use ($filters) {
                $search = trim((string) ($filters['search'] ?? ''));

                if ($search !== '') {
                    $haystack = strtolower(implode(' ', array_filter([
                        $wallet->currency,
                        $wallet->tenant?->name,
                        $wallet->tenant?->email,
                    ])));

                    if (!str_contains($haystack, strtolower($search))) {
                        return false;
                    }
                }

                return !filled($filters['status'] ?? null) || $wallet->status === $filters['status'];
            })
            ->sortByDesc(fn(object $wallet) => $wallet->updated_at?->getTimestamp() ?? 0)
            ->values();

        return $this->paginateCollection($records, $perPage);
    }

    public function stats(): array
    {
        $wallets = $this->aggregateService->wallets();

        return [
            'total' => $wallets->count(),
            'balance' => (float) $wallets->sum('balance'),
            'pending' => (float) $wallets->sum('pending_balance'),
            'owner_profit' => (float) $wallets->sum('owner_profit'),
        ];
    }

    public function find(string $tenantId): ?object
    {
        return $this->aggregateService->wallets()->first(fn(object $wallet) => (string) $wallet->tenant_id === $tenantId);
    }

    protected function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = PaginationState::resolveCurrentPage() ?: 1;
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator(
            $results,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => PaginationState::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
