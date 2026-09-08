<?php

namespace App\Repositories\Admin;

use App\Services\Admin\TenantAdminAggregateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as PaginationState;
use Illuminate\Support\Collection;

class CustomerRepository
{
    public function __construct(protected TenantAdminAggregateService $aggregateService)
    {
    }

    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $records = $this->applyFilters($filters);

        $page = PaginationState::resolveCurrentPage() ?: 1;

        return new Paginator(
            $records->forPage($page, $perPage)->values(),
            $records->count(),
            $perPage,
            $page,
            ['path' => PaginationState::resolveCurrentPath()]
        );
    }

    public function export(array $filters): Collection
    {
        return $this->applyFilters($filters);
    }

    protected function applyFilters(array $filters): Collection
    {
        $newSince = now()->subDays(7);

        return $this->aggregateService->customers()
            ->filter(function (object $customer) use ($filters, $newSince) {
                $search = trim((string) ($filters['search'] ?? ''));

                if ($search !== '') {
                    $haystack = strtolower(implode(' ', array_filter([
                        $customer->full_name,
                        $customer->email,
                        $customer->phone,
                        $customer->store_name,
                    ])));

                    if (!str_contains($haystack, strtolower($search))) {
                        return false;
                    }
                }

                if (filled($filters['status'] ?? null)) {
                    $wantActive = $filters['status'] === 'active';

                    if ($customer->active !== $wantActive) {
                        return false;
                    }
                }

                if (!empty($filters['new_only']) && !($customer->created_at && $customer->created_at->gte($newSince))) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn(object $customer) => $customer->created_at?->getTimestamp() ?? 0)
            ->values();
    }

    public function stats(): array
    {
        $customers = $this->aggregateService->customers();
        $newSince = now()->subDays(7);

        return [
            'total' => $customers->count(),
            'active' => $customers->where('active', true)->count(),
            'new' => $customers->filter(fn(object $customer) => $customer->created_at && $customer->created_at->gte($newSince))->count(),
        ];
    }

    public function isNew(object $customer): bool
    {
        return (bool) ($customer->created_at?->gte(now()->subDays(7)));
    }
}
