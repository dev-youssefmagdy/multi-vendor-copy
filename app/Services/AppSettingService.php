<?php

namespace App\Services;

use App\Enums\AppSettingType;
use App\Models\AppSetting;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AppSettingService
{
    public function __construct(protected CentralCatalogTenantSyncService $tenantSyncService)
    {
    }

    public function saveMany(array $settings): void
    {
        DB::transaction(function () use ($settings) {
            foreach ($settings as $key => $payload) {
                AppSetting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => (string) Arr::get($payload, 'value', ''),
                        'type' => Arr::get($payload, 'type', AppSettingType::String->value),
                        'is_public' => (bool) Arr::get($payload, 'is_public', false),
                    ]
                );
            }
        });

        $this->tenantSyncService->syncSettings();
    }
}
