<?php

namespace App\Repositories;

use App\Models\AppSetting;
use App\Models\Currency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CurrencyRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Currency::query()
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('sign', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        $defaultCode = (string) (AppSetting::query()->where('key', 'default_currency')->value('value') ?? 'USD');

        return [
            'total' => Currency::query()->count(),
            'default' => $defaultCode,
            'configured_rates' => Currency::query()->where('conversion_rate', '>', 0)->count(),
        ];
    }
}
