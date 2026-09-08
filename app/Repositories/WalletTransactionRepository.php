<?php

namespace App\Repositories;

use App\Enums\WalletTransactionDirection;
use App\Services\Admin\TenantAdminAggregateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as PaginationState;
use Illuminate\Support\Collection;

class WalletTransactionRepository
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
        return $this->aggregateService->transactions()
            ->filter(function (object $transaction) use ($filters) {
                $search = trim((string) ($filters['search'] ?? ''));

                if ($search !== '') {
                    $haystack = strtolower(implode(' ', array_filter([
                        $transaction->reference,
                        $transaction->store_name,
                        $transaction->description,
                        $transaction->model_label,
                    ])));

                    if (!str_contains($haystack, strtolower($search))) {
                        return false;
                    }
                }

                return !filled($filters['direction'] ?? null)
                    || $transaction->direction->value === $filters['direction'];
            })
            ->sortByDesc(fn(object $transaction) => $transaction->created_at?->getTimestamp() ?? 0)
            ->values();
    }

    public function stats(): array
    {
        $transactions = $this->aggregateService->transactions();

        return [
            'total' => $transactions->count(),
            'credits' => $transactions->where('direction', WalletTransactionDirection::Credit)->count(),
            'debits' => $transactions->where('direction', WalletTransactionDirection::Debit)->count(),
            'admin_profit' => (float) $transactions->sum('admin_profit'),
        ];
    }

    public function find(string $tenantId, string $reference): ?object
    {
        return $this->aggregateService->transactions()
            ->first(fn(object $transaction) => (string) $transaction->tenant_id === $tenantId && (string) $transaction->reference === $reference);
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
