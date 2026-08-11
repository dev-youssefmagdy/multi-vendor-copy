<?php

namespace App\Repositories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class InvoiceRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function export(array $filters): EloquentCollection
    {
        return $this->buildQuery($filters)->get();
    }

    protected function buildQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::query()
            ->with('tenant')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhereHas('tenant', fn(Builder $tenantQuery) => $tenantQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('issued_at')
            ->latest('id');
    }

    public function stats(): array
    {
        return [
            'total' => Invoice::query()->count(),
            'paid' => Invoice::query()->where('status', InvoiceStatus::Paid->value)->count(),
            'draft' => Invoice::query()->where('status', InvoiceStatus::Draft->value)->count(),
            'amount' => (float) Invoice::query()->sum('amount'),
            'pdf_ready' => Invoice::query()->whereNotNull('pdf_path')->count(),
        ];
    }

    public function find(int $invoiceId): ?Invoice
    {
        return Invoice::query()->with('tenant')->find($invoiceId);
    }
}
