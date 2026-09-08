<?php

namespace App\Repositories;

use App\Enums\ActivationStatus;
use App\Enums\EmailTemplateType;
use App\Models\EmailTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmailTemplateRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return EmailTemplate::query()
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['type'] ?? null), fn($query) => $query->where('type', $filters['type']))
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => EmailTemplate::query()->count(),
            'active' => EmailTemplate::query()->where('status', ActivationStatus::Active->value)->count(),
            'admin' => EmailTemplate::query()->where('type', EmailTemplateType::Admin->value)->count(),
            'tenant' => EmailTemplate::query()->where('type', EmailTemplateType::Tenant->value)->count(),
        ];
    }
}
