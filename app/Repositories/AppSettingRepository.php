<?php

namespace App\Repositories;

use App\Models\AppSetting;
use Illuminate\Support\Collection;

class AppSettingRepository
{
    public function getByKeys(array $keys): Collection
    {
        return AppSetting::query()
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');
    }

    public function stats(): array
    {
        return [
            'total' => AppSetting::query()->count(),
            'public' => AppSetting::query()->where('is_public', true)->count(),
        ];
    }
}
